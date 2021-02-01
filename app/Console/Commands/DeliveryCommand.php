<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\V1\Common\Admin\Resource\AdminController;
use App\Models\Common\RequestFilter;
use App\Services\SendPushNotification;
use App\Models\Common\AdminService;
use App\Models\Delivery\DeliveryRequest;
use App\Models\Common\UserRequest;
use App\Models\Common\Provider;
use App\Models\Common\Setting;
use Carbon\Carbon;
use DB;


class DeliveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronjob:delivery'; 

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updating the Scheduled Deliveries Timing';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $userRequest = UserRequest::where('status','SCHEDULED')
                        ->where('admin_service', 'DELIVERY')
                        ->whereNull('provider_id')
                        ->where('schedule_at','<=',Carbon::now()->addMinutes(20))
                        ->get();

        $hour = Carbon::now()->subHour();
        $futurehours = Carbon::now()->addMinutes(20);
        $date = Carbon::now();   

        \Log::info("Schedule delivery Request Started.".$date."==".$hour."==".$futurehours);

        if(!empty($userRequest)){

            foreach($userRequest as $newRequest){

                $deliveryRequest = DeliveryRequest::find($newRequest->request_id);
                \Log::info($newRequest->request_id);

                if($deliveryRequest != null) {

                    $setting = Setting::where('company_id', $deliveryRequest->company_id)->first();

                    $settings = json_decode(json_encode($setting->settings_data));

                    $siteConfig = $settings->site;

                    $transportConfig = $settings->delivery; 

                    $distance = isset($transportConfig->provider_search_radius) ? $transportConfig->provider_search_radius : 10;
                    $latitude = $deliveryRequest->s_latitude;
                    $longitude = $deliveryRequest->s_longitude;
                    $ride_delivery_id = $deliveryRequest->delivery_vehicle_id;

                    $Providers = Provider::with('service');
                    $Providers->select(DB::Raw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) AS distance"),'id');
                    $Providers->where('status', 'approved');
                    $Providers->where('is_online', 1);
                    $Providers->where('is_assigned', 0);
                    $Providers->whereRaw("(6371 * acos( cos( radians('$latitude') ) * cos( radians(latitude) ) * cos( radians(longitude) - radians('$longitude') ) + sin( radians('$latitude') ) * sin( radians(latitude) ) ) ) <= $distance");
                    $Providers->whereHas('service', function($query) use ($ride_delivery_id) {
                        $query->where('status','active');
                        $query->where('admin_service', 'DELIVERY');
                        $query->where('delivery_vehicle_id',$ride_delivery_id);
                    });
                    

                    $Providers->orderBy('distance','asc');
                    $Providers = $Providers->get();

                    if(!empty($Providers->toArray())) {

                        if($transportConfig->manual_request == 0) {
                            foreach ($Providers as $Provider) {

                                if($transportConfig->broadcast_request == 1){
                                   (new SendPushNotification)->IncomingRequest($Provider->id, 'DELIVERY'); 
                                }

                                $Filter = new RequestFilter;
                                // Send push notifications to the first provider
                                // incoming request push to provider
                                $Filter->admin_service = 'DELIVERY';
                                $Filter->request_id = $newRequest->id;
                                $Filter->provider_id = $Provider->id; 
                                $Filter->company_id = $newRequest->company_id;
                                $Filter->save();
                            }
                        }

                        $deliveryRequest->status = "SEARCHING";
                        $deliveryRequest->assigned_at = Carbon::now();
                        $deliveryRequest->schedule_at = null;
                        $deliveryRequest->save();

                        $rideData = DeliveryRequest::with('deliveries')->where('id', $deliveryRequest->id)->first();

                        $newRequest->status = $deliveryRequest->status;
                        $newRequest->request_data = json_encode($rideData);
                        $newRequest->save();

                        //Send message to socket
                        $requestData = ['type' => 'DELIVERY', 'room' => 'room_'.$deliveryRequest->company_id, 'id' => $deliveryRequest->id, 'city' => ($setting->demo_mode == 0) ? $deliveryRequest->country_id : 0, 'user' => $deliveryRequest->user_id ];
                        app('redis')->publish('newRequest', json_encode( $requestData ));

                        $requestData = ['type' => 'DELIVERY', 'room' => 'room_'.$deliveryRequest->company_id, 'id' => $deliveryRequest->id, 'city' => ($setting->demo_mode == 0) ? $deliveryRequest->country_id : 0, 'user' => $deliveryRequest->user_id ];
                        app('redis')->publish('checkDeliveryRequest', json_encode( $requestData ));
                    }
                    else{
                            
                        $deliveryRequest->status = "CANCELLED";
                        $deliveryRequest->assigned_at = Carbon::now();
                        $deliveryRequest->schedule_at = null;
                        $deliveryRequest->cancel_reason = 'Scheduled provider not found';
                        $deliveryRequest->save();

                        $user_request = UserRequest::where('admin_service', 'DELIVERY')->where('request_id',$deliveryRequest->id)->first();
                        $user_request->delete();

                    }

                }
                
                 //scehule start request push to user
                //(new SendPushNotification)->user_schedule($rideRequest->user_id, 'transport');
                 //scehule start request push to provider
                //(new SendPushNotification)->provider_schedule($rideRequest->provider_id, 'transport');
            }
        }

        $CustomPush = DB::connection('common')->table('custom_pushes')
                        ->where('schedule_at','<=',Carbon::now()->addMinutes(5))
                        ->get();

        if(!empty($CustomPush)){
            foreach($CustomPush as $Push){
                DB::table('custom_pushes')
                        ->where('id',$Push->id)
                        ->update(['schedule_at' => null ]);

                // sending push
                (new AdminController)->SendCustomPush($Push->id);
            }
        }
     }   
}

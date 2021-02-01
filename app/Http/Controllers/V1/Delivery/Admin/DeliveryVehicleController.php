<?php

namespace App\Http\Controllers\V1\Delivery\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Traits\Actions;
use App\Helpers\Helper;
use App\Models\Delivery\DeliveryVehicle;
use App\Models\Delivery\DeliveryCity;
use App\Models\Delivery\DeliveryCityPrice;
use App\Models\Delivery\DeliveryPeakPrice;
use App\Models\Common\AdminService;
use App\Models\Common\MenuCity;
use App\Models\Common\Menu;
use App\Models\Common\CompanyCity;
use App\Models\Common\GeoFence;

use App\Models\Common\CompanyCountry;
use App\Models\Common\PeakHour;
use App\Models\Delivery\DeliveryType;

use Illuminate\Support\Facades\Storage;
use Auth;

class DeliveryVehicleController extends Controller
{
    use Actions;

    private $model;
    private $request;

    public function __construct(DeliveryVehicle $model)
    {
        $this->model = $model;
    }

    public function index(Request $request)
    {
    	$datum = DeliveryVehicle::with('delivery_type')->where('company_id', Auth::user()->company_id);

        if($request->has('search_text') && $request->search_text != null) {
            $datum->Search($request->search_text);
        }

        if($request->has('order_by')) {
            $datum->orderby($request->order_by, $request->order_direction);
        }
        
        if($request->has('page') && $request->page == 'all') {
            $data = $datum->get();
        } else {
            $data = $datum->paginate(10);
        }


        return Helper::encryptResponse(['data' => $data]);
    } 

    public function vehicleList(Request $request)
    {
    	$datum = DeliveryVehicle::with('delivery_type')->where('company_id', Auth::user()->company_id);

        if($request->has('search_text') && $request->search_text != null) {
            $datum->Search($request->search_text);
        }

        if($request->has('order_by')) {
            $datum->orderby($request->order_by, $request->order_direction);
        }

        $data = $datum->paginate(10);

        return Helper::getResponse(['data' => $data]);
    } 

    public function store(Request $request)
    {
        $this->validate($request, [
            'vehicle_name' => 'required|max:255',            
            'weight' => 'required|numeric',          
            'length' => 'required|numeric',          
            'breadth' => 'required|numeric',          
            'height' => 'required|numeric',
            'delivery_type_id' => 'required',
            'vehicle_image' => 'sometimes|nullable|mimes:ico,png',
            'vehicle_marker' => 'sometimes|nullable|mimes:ico,png',
        ]);

        try {
                $rideDeliveryVehicle = new DeliveryVehicle;
                $rideDeliveryVehicle->company_id = Auth::user()->company_id; 
                $rideDeliveryVehicle->vehicle_name = $request->vehicle_name;            
                $rideDeliveryVehicle->weight = $request->weight;
                $rideDeliveryVehicle->length = $request->length;
                $rideDeliveryVehicle->breadth = $request->breadth;
                $rideDeliveryVehicle->height = $request->height;
                $rideDeliveryVehicle->vehicle_type = 'DELIVERY';
                $rideDeliveryVehicle->status = $request->status;
                $rideDeliveryVehicle->delivery_type_id = $request->delivery_type_id;
                if($request->hasFile('vehicle_image')) {
                    $rideDeliveryVehicle->vehicle_image = Helper::upload_file($request->file('vehicle_image'), 'vehicle/image');
                }
                if($request->hasFile('vehicle_marker')) {
                    $rideDeliveryVehicle->vehicle_marker = Helper::upload_file($request->file('vehicle_marker'), 'vehicle/marker');
                }
                $rideDeliveryVehicle->save();

                return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
           } 
           catch (\Throwable $e) 
           {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
           }
     }


    public function show($id)
    {
        try {
            $rideDeliveryVehicle = DeliveryVehicle::findOrFail($id);
            return Helper::getResponse(['data' => $rideDeliveryVehicle]);
        } catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'error' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'vehicle_name' => 'required|max:255',             
            'weight' => 'required|numeric',          
            'length' => 'required|numeric',          
            'breadth' => 'required|numeric',          
            'height' => 'required|numeric',
            'delivery_type_id' => 'required',
            'vehicle_image' => 'mimes:ico,png',
            'vehicle_marker' => 'mimes:ico,png',
        ]);

        try{
                $rideDeliveryVehicle = DeliveryVehicle::findOrFail($id);
                if($rideDeliveryVehicle)
                {
                    if($request->hasFile('vehicle_image')) {
                        if($rideDeliveryVehicle->vehicle_image) {
                            Helper::delete_picture($rideDeliveryVehicle->vehicle_image);
                        }
                        $rideDeliveryVehicle->vehicle_image = Helper::upload_file($request->file('vehicle_image'), 'vehicle/image');
                    }
                    if($request->hasFile('vehicle_marker')) {
                        if($rideDeliveryVehicle->vehicle_marker) {
                            Helper::delete_picture($rideDeliveryVehicle->vehicle_marker);
                        }
                        $rideDeliveryVehicle->vehicle_marker = Helper::upload_file($request->file('vehicle_marker'), 'vehicle/marker');
                    }
                    $rideDeliveryVehicle->vehicle_name = $request->vehicle_name;          
                    $rideDeliveryVehicle->weight = $request->weight;
                    $rideDeliveryVehicle->length = $request->length;
                    $rideDeliveryVehicle->breadth = $request->breadth;
                    $rideDeliveryVehicle->height = $request->height;
                    $rideDeliveryVehicle->vehicle_type = 'DELIVERY';
                    $rideDeliveryVehicle->status = $request->status;
                    $rideDeliveryVehicle->delivery_type_id = $request->delivery_type_id;
                    $rideDeliveryVehicle->save();
                    return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);
                }
                else
                {
                return Helper::getResponse(['status' => 404, 'message' => trans('admin.not_found')]); 
                }
            } 
            catch (\Throwable $e) {
                return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
            }
    }
    public function vehicletype()
    {
        $vehicle_type = DeliveryVehicle::where('company_id', Auth::user()->company_id)->where('status',1)->get();
        return Helper::getResponse(['data' => $vehicle_type]);
    }

    public function destroy($id)
    {
        return $this->removeModel($id);
    }

    public function multidestroy(Request $request)
    {
        $this->request = $request;
        return $this->removeMultiple();
    }

    public function statusChange(Request $request)
    {
        $this->request = $request;
        return $this->changeStatus();
    }

    public function statusChangeMultiple(Request $request)
    {
        $this->request = $request;
        return $this->changeStatusAll();
    }
    public function comission(Request $request)
    {
       
        $this->validate($request, [
             'country_id' => 'required',
             'city_id' => 'required',
             'admin_service' => 'required|in:TRANSPORT,ORDER,SERVICE,DELIVERY',
             'comission' => 'required',
             'fleet_comission' => 'required',
             'tax' => 'required',
             'night_charges' => 'required',

        ],['comission.required' => 'Please Enter Commission',
           'fleet_comission.required' => 'Please Enter Fleet Commission'
    ]);
       
        try{
            if($request->ride_city_id !=''){
                $rideCity = DeliveryCity::findOrFail($request->ride_city_id);
            }else{
                $rideCity = new DeliveryCity;
            }
           
            $rideCity->company_id = Auth::user()->company_id;  
            $rideCity->country_id = $request->country_id; 
            $rideCity->city_id = $request->city_id;  
            $rideCity->admin_service = $request->admin_service;  
            $rideCity->comission = $request->comission;  
            $rideCity->fleet_comission = $request->fleet_comission; 
            $rideCity->tax = $request->tax; 
            $rideCity->night_charges = $request->night_charges; 
            $rideCity->save();
       
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function getComission($country_id,$city_id,$admin_service)
    {
       
            $rideCity = DeliveryCity::where([['company_id',Auth::user()->company_id],
                                         ['country_id',$country_id],
                                         ['city_id',$city_id],
                                         ['admin_service',$admin_service]])->first();
             if($rideCity){
                return Helper::getResponse(['data' => $rideCity]);
             }
             return Helper::getResponse(['data' => '']);
  

    }
    
    public function gettaxiprice($id)
    {
       $admin_service = AdminService::where('admin_service','DELIVERY')->where('company_id',Auth::user()->company_id)->value('id');
       $countries = [];
       if($admin_service){
            $cityList = CompanyCountry::with('country','companyCountryCities')->where('company_id',Auth::user()->company_id)->where('status',1)->get();

            $countries = $cityList->map(function ($response) {
                $country = new \stdClass;
                $country->id = $response->country->id;
                $country->country_name = $response->country->country_name;
                $country->country_code = $response->country->country_code;
                $country->country_phonecode = $response->country->country_phonecode;
                $country->country_currency = $response->country->country_currency;
                $country->country_symbol = $response->country->country_symbol;
                $country->status = $response->country->status;
                $country->timezone = $response->country->timezone;
                $city = [];
                foreach ($response->companyCountryCities as $value) {
                    $city[] = $value->city;
                }
                usort($city, function($a, $b) {return strcmp($a->city_name, $b->city_name);});
                $country->city = $city;
                return $country;
            });
       }

       return Helper::getResponse(['data' => $countries]);
    }

    public function rideprice(Request $request)
    {
        // print_r($request->all());exit;
       /* $this->validate($request, [
             'city_id' => 'required',
             'fixed' => 'required',
             'price' => 'required',
             'minute' => 'required',
             'hour' => 'required',
             'distance' => 'required',
             'waiting_free_mins' => 'required',
             'waiting_min_charge' => 'required',
        ]);*/

         $this->validate($request, [
            'city_id' => 'required',
            'fixed' => 'required|numeric',
            'price' => 'sometimes|nullable|numeric',
            'weight_price' => 'sometimes|nullable|numeric',
            'weight' => 'sometimes|nullable|numeric',
            'distance' => 'sometimes|nullable|numeric',
            'calculator' => 'required|in:WEIGHT,DISTANCE,DISTANCEWEIGHT'            
        ]);
       
        try{

                $ridePrice = DeliveryCityPrice::where('delivery_vehicle_id',$request->ride_delivery_vehicle_id)->where('city_id',$request->city_id)->first();
                if($ridePrice == null){
                    $ridePrice = new DeliveryCityPrice;
                }
                
                

                $ridePrice->company_id = Auth::user()->company_id;  

                $ridePrice->fixed =  $request->fixed; 
                $ridePrice->city_id = $request->city_id;  
                $ridePrice->delivery_vehicle_id = $request->ride_delivery_vehicle_id;  
                $ridePrice->calculator =  $request->calculator; 
                if(!empty($request->price)) {
                    $ridePrice->pricing_differs=1;
                }else{
                    $ridePrice->pricing_differs=0;
                } 

                if(!empty($request->price))
                    $ridePrice->price = $request->price;
                else
                    $ridePrice->price=0;

                if(!empty($request->weight))
                    $ridePrice->weight = $request->weight;
                else
                    $ridePrice->weight=0;

                if(!empty($request->weight_price))
                    $ridePrice->weight_price = $request->weight_price;
                else
                    $ridePrice->weight_price=0;

                if(!empty($request->distance))
                    $ridePrice->distance = $request->distance;
                else
                    $ridePrice->distance=0;

                if(!empty($request->commission))
                    $ridePrice->commission = $request->commission;
                else
                    $ridePrice->commission=0;

                if(!empty($request->fleet_commission))
                    $ridePrice->fleet_commission = $request->fleet_commission;
                else
                    $ridePrice->fleet_commission=0;

                if(!empty($request->tax))
                    $ridePrice->tax = $request->tax;
                else
                    $ridePrice->tax=0;

                if(!empty($request->peak_commission))
                    $ridePrice->peak_commission = $request->peak_commission;
                else
                    $ridePrice->peak_commission=0;
                

                $ridePrice->save();

                $incoming_peak_request = $request->peak_price;
                if($incoming_peak_request){

                    foreach ($incoming_peak_request as $key => $peak_price) {

                        if(!empty($peak_price['value'])) {

                            if($peak_price['id'] !=''){
                                $RidePeakPrice = DeliveryPeakPrice::findOrFail($peak_price['id']);
                            }else{
                                $RidePeakPrice = new DeliveryPeakPrice;
                            }  
                            //$RidePeakPrice = new RidePeakPrice;
                            $RidePeakPrice->delivery_city_price_id =$ridePrice->id;
                            $RidePeakPrice->delivery_vehicle_id =$request->ride_delivery_vehicle_id;
                            $RidePeakPrice->peak_hour_id =$key;
                            $RidePeakPrice->peak_price =($peak_price['value'] !='')?$peak_price['value']:'0.00';
                            $RidePeakPrice->save();
                        } 

                    }

                }

            return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
        } 

        catch (\Throwable $e) {
            dd($e);
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }

    public function getRidePrice($city_id, $ride_delivery_vehicle_id)
    {
       
            $rideCityPrice = DeliveryCityPrice::where([['company_id',Auth::user()->company_id],
                                         ['delivery_vehicle_id',$ride_delivery_vehicle_id],
                                         ['city_id',$city_id]])->where('pricing_differs', 0)->first();

            $rideCityPriceList = DeliveryCityPrice::where([['company_id',Auth::user()->company_id],
                                         ['delivery_vehicle_id',$ride_delivery_vehicle_id],
                                         ['city_id',$city_id]])->get();
            $peakHour = PeakHour::where("city_id",$city_id)->where('company_id',Auth::user()->company_id)->get();

            $geofence = Geofence::where("city_id",$city_id)->where('company_id',Auth::user()->company_id)->get();

            if(count($rideCityPriceList) > 0){
                $ridePeakhour = [];
                foreach($rideCityPriceList as $key => $ride_city_price_list){
                    foreach($peakHour as $value){
                        $peakPrice = DeliveryPeakPrice::where([['delivery_city_price_id',$ride_city_price_list->id],['peak_hour_id',$value->id]])->first();
                        if($peakPrice){
                            $peakPrice['started_time'] = $value->started_time;
                            $peakPrice['ended_time'] = $value->ended_time;
                            $ridePeakhour[] = $peakPrice;
                        }
                    }
                    $rideCityPriceList[$key]['ridePeakhour'] = $ridePeakhour;
                }
            }

            if($rideCityPrice){
                foreach($peakHour as $key=>$value){
                    $RidePeakPrice = DeliveryPeakPrice::where([['delivery_city_price_id',$rideCityPrice->id],['peak_hour_id',$value->id]])->first();
                    if($RidePeakPrice){
                        $peakHour[$key]['ridePeakhour'] = $RidePeakPrice;
                    }
                }
            return Helper::getResponse(['data' => ['price'=>$rideCityPrice,'peakHour'=> $peakHour,'geofence'=> $geofence, 'priceList' => $rideCityPriceList]]);
            }
            
            return Helper::getResponse(['data' =>['price'=>'','peakHour'=> $peakHour,'geofence'=> $geofence, 'priceList' => $rideCityPriceList]]);

    }


    public function getcity(Request $request)
    {
         //dd($request->city_id);
        $menudetails=Menu::select('menu_type_id')->where('id',$request->menu_id)->first();

         $rideprice=DeliveryCityPrice::select('city_id')->whereHas('ridedelivery_type', function($query) use($menudetails){
                   $query->where('ride_type_id',$menudetails->menu_type_id);
             })->get()->toArray();
       
        $company_cities = CompanyCity::with(['country','city','menu_city' => function($query) use($request) {
            $query->where('menu_id','=',$request->menu_id);
        }])->where('company_id', Auth::user()->company_id);

        if($request->has('search_text') && $request->search_text != null) {
            $company_cities = $company_cities->Search($request->search_text);
        }
        $cities = $company_cities->paginate(500);

        foreach($cities as $key=>$value){

           $cities[$key]['city_price']=0;
           
           if(in_array($value->city_id,array_column($rideprice,'city_id'))){
            
             $cities[$key]['city_price']=1;
           } 
        }


        return Helper::getResponse(['data' => $cities]);
    }





    public function getvehicletype(){

     $vehicle_type = DeliveryType::all();
                                       
             return Helper::getResponse(['data' =>['vehicle_type'=>$vehicle_type]]);


    }

    public function updateStatus(Request $request, $id)
    {
        
        try {

            $datum = DeliveryVehicle::findOrFail($id);
            
            if($request->has('status')){
                if($request->status == 1){
                    $datum->status = 0;
                }else{
                    $datum->status = 1;
                }
            }
            $datum->save();
           
           
            return Helper::getResponse(['status' => 200, 'message' => trans('admin.activation_status')]);

        } 

        catch (\Throwable $e) {
            return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
        }
    }




    
}

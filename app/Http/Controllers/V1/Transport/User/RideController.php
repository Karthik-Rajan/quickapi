<?php

namespace App\Http\Controllers\V1\Transport\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SendPushNotification;
use App\Models\Transport\RideDeliveryVehicle;
use App\Models\Common\RequestFilter;
use App\Models\Transport\RideRequest;
use App\Models\Common\UserRequest;
use App\Models\Transport\RideType;
use App\Models\Common\Provider;
use App\Models\Common\Country;
use App\Models\Common\Rating;
use App\Services\V1\Transport\Ride;
use App\Models\Common\Setting;
use App\Models\Common\Reason;
use App\Models\Common\State;
use App\Models\Common\User;
use App\Models\Common\Menu;
use App\Models\Common\Card;
use App\Models\Transport\RideCityPrice;
use App\Models\Transport\RidePeakPrice;
use App\Models\Common\PeakHour;
use App\Models\Common\AdminService;
use App\Models\Transport\RideLostItem;
use App\Models\Transport\RideRequestDispute;
use App\Models\Transport\RideRequestPayment;
use App\Models\Common\ProviderService;
use App\Models\Common\CompanyCountry;
use App\Models\Common\Promocode;
use App\Services\PaymentGateway;
use App\Services\V1\Common\UserServices;
use App\Services\V1\Common\ProviderServices;
use App\Models\Common\PaymentLog;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\V1\Transport\Provider\TripController;
use App\Http\Controllers\V1\Common\Provider\HomeController;
use Carbon\Carbon;
use App\Traits\Actions;
use Auth;
use DB;

class RideController extends Controller
{
	use Actions;

	
	public function services(Request $request)
	{
		$this->validate($request, [
			'type' => 'required|numeric|exists:transport.ride_types,id',
			'latitude' => 'required|numeric',
			'longitude' => 'required|numeric'
		]);

		$transport= new \stdClass;

		$distance = isset($this->settings->transport->provider_search_radius) ? $this->settings->transport->provider_search_radius : 100;

		$ride_delivery_vehicles = [];

		$callback = function ($q) use($request) {
			$q->where('admin_service', 'TRANSPORT');
			$q->where('category_id',$request->type);
		};

		$withCallback = ['service' => $callback, 'service.ride_vehicle'];
		$whereHasCallback = ['service' => $callback];

		$data = (new UserServices())->availableProviders($request, $withCallback, $whereHasCallback);
        

		$service = null;
		$providers = []; 
		$nearestProvider = [];        

		//List providers in nearestProvider variable (result is ordered ascending based on distance)
		foreach($data as $datum) {
			if($datum->service != null) {
				$nearestProvider[] = [ 'service_id' => $datum->service->ride_delivery_id, 'latitude' => $datum->latitude, 'longitude' => $datum->longitude ];
				$service = $datum->service->ride_delivery_id;
				$ride_delivery_vehicles[] = $service;
			}

			$provider = new \stdClass();
			foreach (json_decode($datum) as $l => $val) {
				$provider->$l = $val;
			}
			$provider->service_id = $service;
			$providers[] = $provider;
		}

		$output=[];
		foreach ($nearestProvider as $near) {
			$sources = [];
		    $destinations = [];
			$sources[] = $near['latitude'].','.$near['longitude'];
			$destinations[] = $request->latitude.','.$request->longitude;
			$output[] = Helper::getDistanceMap($sources, $destinations);
		}

        
		$output=array_replace_recursive($output);
        $dis=[];

		if(count($output) > 0) {
			foreach ($output as $key => $data) {
				// dd($nearestProvider);
				if($data->status == "OK"){
				$estimations[$nearestProvider[$key]['service_id']][$data->rows[0]->elements[0]->duration->value] =$data->rows[0]->elements[0]->duration->text;
				$dis[$nearestProvider[$key]['service_id']][]=$data->rows[0]->elements[0]->duration->value;
			    ksort($estimations[$nearestProvider[$key]['service_id']]);
			    sort($dis[$nearestProvider[$key]['service_id']]);
				}
				

			}
		} 

		
		 

		$geofence =(new UserServices())->poly_check_request((round($request->latitude,6)),(round($request->longitude,6)), $this->user ? $this->user->city_id : $request->city_id);

		if($geofence == false) {
			return Helper::getResponse(['status' => 400, 'message' => trans('user.ride.service_not_available_location'), 'error' => trans('user.ride.service_not_available_location')]);
		}

		$service_list = RideDeliveryVehicle::with(['priceDetails' => function($q) use($geofence) {
			$q->where('geofence_id', $geofence['id']);
		}])->whereHas('priceDetails', function($q) use($geofence) {
			$q->where('geofence_id', $geofence['id']);
		})->whereIn('id', $ride_delivery_vehicles)->where('company_id', $this->company_id)->where('status', 1)->get();

		$service_types = [];
		$service_id_list = [];

		if(count($service_list) > 0) {
			foreach ($service_list as $k => $services) {
				$service = new \stdClass();
				foreach (json_decode($services)as $j => $s) {
					$service->$j = $s;
				}
				$service->estimated_time = isset($estimations[ $services->id ]) ?$estimations[ $services->id ][$dis[$services->id][0]] : '0 Min';
				$service_types[] = $service;
				$service_id_list[] = $service->id; 
			}
		}

		$ride_delivery_vehicles = RideDeliveryVehicle::with(['priceDetails' => function($q) use($request) {
			$q->where('city_id', $this->user ? $this->user->city_id : $request->city_id);
		}])->whereHas('priceDetails', function($q) use($request) {
			$q->where('city_id', $this->user ? $this->user->city_id : $request->city_id);
		})->where('ride_type_id', $request->type)->where('company_id', $this->company_id)->where('status', 1)->whereNotIn('id', $service_id_list)->select('*', \DB::raw('"..." AS "estimated_time"'))->get();

		if(count($ride_delivery_vehicles) > 0) {
			foreach ($ride_delivery_vehicles as $k => $ride_delivery_vehicle) {
				$service = new \stdClass();
				foreach (json_decode($ride_delivery_vehicle)as $j => $s) {
					if($j == 'price_details') {
						$service->estimated_time = isset($estimations[ $ride_delivery_vehicle->id ]) ?$estimations[ $ride_delivery_vehicle->id ][$dis[$ride_delivery_vehicle->id][0]] : '0 Min';
					}
					$service->$j = $s;
				}
				
				$service_types[] = $service;
			}
		}

		usort($service_types, function($a, $b) {return strcmp($a->vehicle_name, $b->vehicle_name);});

		$transport->services = $service_types;

		$transport->providers = $providers;

		if( Auth::guard(strtolower(Helper::getGuard()))->user() != null ) {
			$transport->promocodes = Promocode::where('company_id', $this->company_id)->where('service', 'TRANSPORT')
					->where('expiration','>=',date("Y-m-d H:i"))
					->whereDoesntHave('promousage', function($query) {
						$query->where('user_id', Auth::guard('user')->user()->id);
					})
					->get();
		} else {
			$transport->promocodes = [];
		}

		return Helper::getResponse(['data' => $transport]);
	}

	/*public function cards(Request $request)
	{
		$cards = (new Resource\CardResource)->index();

		return Helper::getResponse(['data' => $cards]);
	}*/

	
	public function estimate(Request $request)
	{
		$this->validate($request,[
			's_latitude' => 'required|numeric',
			's_longitude' => 'numeric',
			'd_latitude' => 'required|numeric',
			'd_longitude' => 'numeric',
			'service_type' => 'required|numeric|exists:transport.ride_delivery_vehicles,id',
		]);

		$geofence =(new UserServices())->poly_check_request((round($request->s_latitude,6)),(round($request->s_longitude,6)), $this->user ? $this->user->city_id : $request->city_id );
		if($geofence) {
			$request->request->add(['server_key' => $this->settings->site->server_key]);
			$request->request->add(['city_id' => $this->user ? $this->user->city_id : $request->city_id ]);
			$request->request->add(['geofence_id' => $geofence['id']]);

			$fare = (new UserServices())->estimated_fare($request)->getData();
			
			$service = RideDeliveryVehicle::find($request->service_type);

			if($request->has('current_longitude') && $request->has('current_latitude'))
			{
				User::where('id', $User->id)->update([
					'latitude' => $request->current_latitude,
					'longitude' => $request->current_longitude
				]);
			}
			$total_amount = get_object_vars($fare);
			if( Auth::guard(strtolower(Helper::getGuard()))->user() != null ) {
				$promocode = Promocode::where('company_id', $this->company_id)->where('service', 'TRANSPORT');
            	$promocode->Where('min_amount','<=',$total_amount['estimated_fare']);

            	$promocode->where('expiration','>=',date("Y-m-d H:i"));
            	$promocode->Where(function ($query) {
		            $query->whereNull('user_id');
		            $query->orWhere('user_id',$this->user->id);    
		        });
            	$promocode->Where(function ($query) {
		            $query->whereNull('startdate');
		            $query->orwhere('startdate','<=',$this->user->created_at);
        		});
        		$promocode->whereRaw("user_limit > (select ifnull((SELECT COUNT(*) from promocode_usages where promocode_id = promocodes.id and user_id = ".$this->user->id." group by promocode_id), 0) )");

				$promocodes = $promocode->get();

				$currency = Auth::guard('user')->user()->currency_symbol;
			} else {
				$promocodes = [];
				$currency = '';
			}

			return Helper::getResponse(['data' => ['fare' => $fare, 'service' => $service, 'promocodes' => $promocodes, 'unit' => $this->settings->site->unit_measurement, 'currency' => $currency ]]);
		} else {
            return ['status' => 400, 'message' => trans('user.ride.service_not_available_location'), 'error' => trans('user.ride.service_not_available_location')];
        }
	}

	public function create_ride(Request $request)
	{
		if(isset($this->settings->transport->destination)) {
			if($this->settings->transport->destination == 0) {
				$this->validate($request, [
					's_latitude' => 'required|numeric',
					's_longitude' => 'required|numeric',
					'ride_type_id' => 'required'
					
				]);
			} else {
				$this->validate($request, [
					's_latitude' => 'required|numeric',
					's_longitude' => 'required|numeric',
					'ride_type_id' => 'required',
				    'd_latitude' => 'required|numeric',
					'd_longitude' => 'required|numeric'
				]);
			}
		}

		try {
			$ride = (new Ride())->createRide($request);
			return Helper::getResponse(['status' => isset($ride['status']) ? $ride['status'] : 200, 'message' => isset($ride['message']) ? $ride['message'] : '', 'data' => isset($ride['data']) ? $ride['data'] : [] ]);
		} catch (Exception $e) {  
			return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
		}
	}

	public function status(Request $request)
	{

		try{

			$check_status = ['CANCELLED', 'SCHEDULED'];
			$admin_service = 'TRANSPORT';

			$rideRequest = RideRequest::RideRequestStatusCheck(Auth::guard('user')->user()->id, $check_status, 'TRANSPORT',0)
										->get()
										->toArray();

			$start_time = (Carbon::now())->toDateTimeString();
			$end_time = (Carbon::now())->toDateTimeString();

			$peak_percentage = 1+(0/100)."X";
			$peak = 0;

			$start_time_check = PeakHour::where('start_time', '<=', $start_time)->where('end_time', '>=', $start_time)->where('company_id', '>=', Auth::guard('user')->user()->company_id)->first();

			if( count($rideRequest) > 0 && $start_time_check){

				$Peakcharges = RidePeakPrice::where('ride_city_price_id', $rideRequest[0]['city_id'])->where('ride_delivery_id', $rideRequest[0]['ride_delivery_id'])->where('peak_hour_id',$start_time_check->id)->first();

				if($Peakcharges){
					$peak = 1;
				}

			}
			
			
			if(!empty($rideRequest[0]['payment'])) {
				$base_distance  = $rideRequest[0]['base_distance'];
				$total_distance = $rideRequest[0]['total_distance'];
				$total = $total_distance - $base_distance;
				$rideRequest[0]['payment']['base_fare_text'] = 'Base Fare for '.$base_distance .' kms';
				$rideRequest[0]['payment']['distance_fare_text'] = 'Distance Fare for '.$total.' Kms';
				$rideRequest[0]['payment']['time_fare_text'] = 'Ride Time for '.$rideRequest[0]['travel_time'].' mins';
				$rideRequest[0]['payment']['waiting_fare_text'] = 'Waiting Fare for '.$rideRequest[0]['payment']['total_waiting_time'] .' mins';
				$rideRequest[0]['payment']['discount_fare_text'] = 'Discount ('.$rideRequest[0]['payment']['discount_percent'] .'%)';
			}
									   

			$search_status = ['SEARCHING','SCHEDULED'];
			$rideRequestFilter = RideRequest::RideRequestAssignProvider(Auth::guard('user')->user()->id,$search_status)->get(); 

			if(!empty($rideRequest)){
				$rideRequest[0]['ride_otp'] = (int) $this->settings->transport->ride_otp ? $this->settings->transport->ride_otp : 0 ;
				$rideRequest[0]['peak'] = $peak ;

				$rideRequest[0]['reasons']=Reason::where('type','USER')->where('service','TRANSPORT')->where('status','Active')->get();
			}

			$Timeout = $this->settings->transport->provider_select_timeout ? $this->settings->transport->provider_select_timeout : 60 ;
			$response_time = $Timeout;

			if(!empty($rideRequestFilter)){
				for ($i=0; $i < sizeof($rideRequestFilter); $i++) {
					$ExpiredTime = $Timeout - (time() - strtotime($rideRequestFilter[$i]->assigned_at));
					if($rideRequestFilter[$i]->status == 'SEARCHING' && $ExpiredTime < 0) {
						(new ProviderServices())->assignNextProvider($rideRequestFilter[$i]->id, $admin_service );
						$response_time = $Timeout - (time() - strtotime($rideRequestFilter[$i]->assigned_at));
					}else if($rideRequestFilter[$i]->status == 'SEARCHING' && $ExpiredTime > 0){
						break;
					}
				}

			}

			if(empty($rideRequest)) {

				$cancelled_request = RideRequest::where('ride_requests.user_id', Auth::guard('user')->user()->id)
					->where('ride_requests.user_rated',0)
					->where('ride_requests.status', ['CANCELLED'])->orderby('updated_at', 'desc')
					->where('updated_at','>=',\Carbon\Carbon::now()->subSeconds(5))
					->first();
				
			}

			return Helper::getResponse(['data' => [
				'response_time' => $response_time, 
				'data' => $rideRequest, 
				'ride_otp' =>  isset($this->settings->transport->ride_otp) ? $this->settings->transport->ride_otp: 0 ,
				'sos' => isset($this->settings->site->sos_number) ? $this->settings->site->sos_number : '911' , 
				'emergency' => isset($this->settings->site->contact_number) ? $this->settings->site->contact_number : [['number' => '911']]  ]]);

		} catch (Exception $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.something_went_wrong'), 'error' => $e->getMessage() ]);
		}
	}

	public function checkRide(Request $request, $id)
	{

		try{

			
			$admin_service = 'TRANSPORT';
			$ride_type_id=RideRequest::select('ride_delivery_id')->where('id',$id)->first();
			$check_status = ['CANCELLED', 'SCHEDULED'];

			$rideRequest = RideRequest::RideRequestStatusCheck(Auth::guard('user')->user()->id, $check_status, 'TRANSPORT',$ride_type_id->ride_delivery_id)
										->where('id', $id)
										->get()
										->toArray();

			$start_time = (Carbon::now())->toDateTimeString();
			$end_time = (Carbon::now())->toDateTimeString();

			$peak_percentage = 1+(0/100)."X";
			$peak = 0;

			$start_time_check = PeakHour::where('start_time', '<=', $start_time)->where('end_time', '>=', $start_time)->where('company_id', '>=', Auth::guard('user')->user()->company_id)->first();

			if( count($rideRequest) > 0 && $start_time_check){

				$Peakcharges = RidePeakPrice::where('ride_city_price_id', $rideRequest[0]['city_id'])->where('ride_delivery_id', $rideRequest[0]['ride_delivery_id'])->where('peak_hour_id',$start_time_check->id)->first();

				if($Peakcharges){
					$peak = 1;
				}

			}
									   
			
			if(!empty($rideRequest[0]['payment'])) {
				$base_distance  = $rideRequest[0]['base_distance'];
				$total_distance = $rideRequest[0]['total_distance'];
				$total = $total_distance - $base_distance;
				$rideRequest[0]['payment']['base_fare_text'] = 'Base Fare for '.$base_distance .' kms';
				$rideRequest[0]['payment']['distance_fare_text'] = 'Distance Fare for '.$total.' Kms';
				$rideRequest[0]['payment']['time_fare_text'] = 'Ride Time for '.$rideRequest[0]['travel_time'].' mins';

				$rideRequest[0]['payment']['waiting_fare_text'] = 'Waiting Fare for '.$rideRequest[0]['payment']['total_waiting_time'] .' mins';
				$rideRequest[0]['payment']['discount_fare_text'] = 'Discount ('.$rideRequest[0]['payment']['discount_percent'] .'%)';
			}
									   

			$search_status = ['SEARCHING','SCHEDULED'];
			$rideRequestFilter = RideRequest::RideRequestAssignProvider(Auth::guard('user')->user()->id,$search_status)->get(); 

			if(!empty($rideRequest)){
				$rideRequest[0]['ride_otp'] = (int) $this->settings->transport->ride_otp ? $this->settings->transport->ride_otp : 0 ;
				$rideRequest[0]['peak'] = $peak ;

				$rideRequest[0]['reasons']=Reason::where('type','USER')->where('service','TRANSPORT')->where('status','Active')->get();

				$Timeout = $this->settings->transport->provider_select_timeout ? $this->settings->transport->provider_select_timeout : 60 ;
				$response_time = $Timeout;

				if(!empty($rideRequestFilter)){
					for ($i=0; $i < sizeof($rideRequestFilter); $i++) {
						$ExpiredTime = $Timeout - (time() - strtotime($rideRequestFilter[$i]->assigned_at));
						if($rideRequestFilter[$i]->status == 'SEARCHING' && $ExpiredTime < 0) {
							(new ProviderServices())->assignNextProvider($rideRequestFilter[$i]->id, $admin_service );
							$response_time = $Timeout - (time() - strtotime($rideRequestFilter[$i]->assigned_at));
						}else if($rideRequestFilter[$i]->status == 'SEARCHING' && $ExpiredTime > 0){
							break;
						}
					}

				}

				if(empty($rideRequest)) {

					$cancelled_request = RideRequest::where('ride_requests.user_id', Auth::guard('user')->user()->id)
						->where('ride_requests.user_rated',0)
						->where('ride_requests.status', ['CANCELLED'])->orderby('updated_at', 'desc')
						->where('updated_at','>=',\Carbon\Carbon::now()->subSeconds(5))
						->first();
					
				}

				return Helper::getResponse(['data' => [
					'response_time' => $response_time, 
					'data' => $rideRequest, 
					'sos' => isset($this->settings->site->sos_number) ? $this->settings->site->sos_number : '911' , 
					'emergency' => isset($this->settings->site->contact_number) ? $this->settings->site->contact_number : [['number' => '911']]  ]]);
		} else {
			return Helper::getResponse(['data' => [
				'data' => [] ]]);
		}

		} catch (Exception $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.something_went_wrong'), 'error' => $e->getMessage() ]);
		}
	}


	public function cancel_ride(Request $request)
	{
		$this->validate($request, [
			'id' => 'required|numeric|exists:transport.ride_requests,id,user_id,'.Auth::guard('user')->user()->id,
		]);

		$request->request->add(['cancelled_by' => 'USER']);

		try {
			$ride = (new Ride())->cancelRide($request);
			return Helper::getResponse(['status' => $ride['status'], 'message' => $ride['message'] ]);
		} catch (Exception $e) {  
			return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
		}
	}


	public function extend_trip(Request $request) 
	{
		$this->validate($request, [
			'id' => 'required|numeric|exists:transport.ride_requests,id,user_id,'.Auth::guard('user')->user()->id,
			'latitude' => 'required|numeric',
			'longitude' => 'required|numeric',
			'address' => 'required',
		]);

		try{

			$ride = (new Ride())->extendTrip($request);

			return Helper::getResponse(['message' => 'Destination location has been changed', 'data' => $ride]);

		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.something_went_wrong'), 'error' => $e->getMessage() ]);
		}
	}

	public function update_payment_method(Request $request)
	{
		$this->validate($request, [
			'id' => 'required|numeric|exists:transport.ride_requests,id,user_id,'.Auth::guard('user')->user()->id,
			'payment_mode' => 'required',
		]);

		try{

			$rideRequest = RideRequest::findOrFail($request->id);
			if($request->payment_mode != "CASH") {
				$rideRequest->status = 'COMPLETED';
				$rideRequest->save();
			}

			$payment = RideRequestPayment::where('ride_request_id', $rideRequest->id)->first();

			if($payment != null) {
				$payment->payment_mode = $request->payment_mode;
				$payment->save();
			}

			$ride = (new UserServices())->updatePaymentMode($request, $rideRequest, $payment);

			return Helper::getResponse(['message' => trans('api.ride.payment_updated')]);
		}

		catch (ModelNotFoundException $e) {
			return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
		}
	}

	

	public function search_user(Request $request)
	{

		$results=array();

		$term =  $request->input('stext');  

		$queries = User::where('first_name', 'LIKE', $term.'%')->where('company_id', Auth::user()->company_id)->take(5)->get();

		foreach ($queries as $query)
		{
			$results[]=$query;
		}    

		return response()->json(array('success' => true, 'data'=>$results));

	}
	
	public function search_provider(Request $request){

		$results=array();

		$term =  $request->input('stext');  

		$queries = Provider::where('first_name', 'LIKE', $term.'%')->take(5)->get();

		foreach ($queries as $query)
		{
			$results[]=$query;
		}    

		return response()->json(array('success' => true, 'data'=>$results));

	}
	
	public function searchRideLostitem(Request $request)
	{

		$results=array();

		$term =  $request->input('stext');

		if($request->input('sflag')==1){
			
			$queries = RideRequest::where('provider_id', $request->id)->orderby('id', 'desc')->take(10)->get();
		}
		else{

			$queries = RideRequest::where('user_id', $request->id)->orderby('id', 'desc')->take(10)->get();
		}

		foreach ($queries as $query)
		{
			$LostItem = RideLostItem::where('ride_request_id',$query->id)->first();
			if(!$LostItem)
			$results[]=$query;
		}

		return response()->json(array('success' => true, 'data'=>$results));

	}
	
	public function searchRideDispute(Request $request)
	{

		$results=array();

		$term =  $request->input('stext');

		if($request->input('sflag')==1){
			
			$queries = RideRequest::where('provider_id', $request->id)->orderby('id', 'desc')->take(10)->get();
		}
		else{

			$queries = RideRequest::where('user_id', $request->id)->orderby('id', 'desc')->take(10)->get();
		}

		foreach ($queries as $query)
		{
			$RideRequestDispute = RideRequestDispute::where('ride_request_id',$query->id)->first();
			if(!$RideRequestDispute)
			$results[]=$query;
		}

		return response()->json(array('success' => true, 'data'=>$results));

	}
	
	public function requestHistory(Request $request)
	{
		try {
			$history_status = array('CANCELLED','COMPLETED');
			$datum = RideRequest::where('company_id',  Auth::user()->company_id)
					 ->with('user', 'provider','payment');

			if(Auth::user()->hasRole('FLEET')) {
				$datum->where('admin_id', Auth::user()->id);  
			}
			if($request->has('search_text') && $request->search_text != null) {
				$datum->histroySearch($request->search_text);
			}
	
			if($request->has('order_by')) {
				$datum->orderby($request->order_by, $request->order_direction);
			}
			$data = $datum->whereIn('status',$history_status)->paginate(10);
			return Helper::getResponse(['data' => $data]);

		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}
	public function requestscheduleHistory(Request $request)
	{
		try {
			$scheduled_status = array('SCHEDULED');
			$datum = RideRequest::where('company_id',  Auth::user()->company_id)
					 ->whereIn('status',$scheduled_status)
					 ->with('user', 'provider');

			if(Auth::user()->hasRole('FLEET')) {
				$datum->where('admin_id', Auth::user()->id);  
			}
			if($request->has('search_text') && $request->search_text != null) {
				$datum->histroySearch($request->search_text);
			}
	
			if($request->has('order_by')) {
				$datum->orderby($request->order_by, $request->order_direction);
			}
	
			$data = $datum->paginate(10);
	
			return Helper::getResponse(['data' => $data]);

		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	public function requestStatementHistory(Request $request)
	{
		try {
			$history_status = array('CANCELLED','COMPLETED');
			$rides = RideRequest::where('company_id',  Auth::user()->company_id)
					 ->with('user', 'provider');
			if($request->has('country_id')) {
				$rides->where('country_id',$request->country_id);
			}
			if(Auth::user()->hasRole('FLEET')) {
				$rides->where('admin_id', Auth::user()->id);  
			}
			if($request->has('search_text') && $request->search_text != null) {
				$rides->histroySearch($request->search_text);
			}

			if($request->has('status') && $request->status != null) {
				$history_status = array($request->status);
			}

			if($request->has('user_id') && $request->user_id != null) {
				$rides->where('user_id',$request->user_id);
			}

			if($request->has('provider_id') && $request->provider_id != null) {
				$rides->where('provider_id',$request->provider_id);
			}

			if($request->has('ride_type') && $request->ride_type != null) {
				$rides->where('ride_type_id',$request->ride_type);
			}
	
			if($request->has('order_by')) {
				$rides->orderby($request->order_by, $request->order_direction);
			}
			$type = isset($_GET['type'])?$_GET['type']:'';
			if($type == 'today'){
				$rides->where('created_at', '>=', Carbon::today());
			}elseif($type == 'monthly'){
				$rides->where('created_at', '>=', Carbon::now()->month);
			}elseif($type == 'yearly'){
				$rides->where('created_at', '>=', Carbon::now()->year);
			}elseif ($type == 'range') {   
				if($request->has('from') &&$request->has('to')) {             
					if($request->from == $request->to) {
						$rides->whereDate('created_at', date('Y-m-d', strtotime($request->from)));
					} else {
						$rides->whereBetween('created_at',[Carbon::createFromFormat('Y-m-d', $request->from),Carbon::createFromFormat('Y-m-d', $request->to)]);
					}
				}
			}else{
				// dd(5);
			}
			$cancelrides = $rides;
			$orderCounts = $rides->count();
			if($request->has('page') && $request->page == 'all') {
	            $dataval = $rides->whereIn('status',$history_status)->get();
	        } else {
	            $dataval = $rides->whereIn('status',$history_status)->paginate(10);
	        }
			
			$cancelledQuery = $cancelrides->where('status','CANCELLED')->count();
			$total_earnings = 0;
			foreach($dataval as $ride){
				//$ride->status = $ride->status == 1?'Enabled' : 'Disable';
				$rideid  = $ride->id;
				$earnings = RideRequestPayment::select('total','commision')->where('ride_request_id',$rideid)->where('company_id',  Auth::user()->company_id)->first();
				if($earnings != null){
					$ride->earnings = $earnings->total;
					$ride->commision = $earnings->commision;
					$total_earnings = $total_earnings + $earnings->total;
				}else{
					$ride->earnings = 0;
				}
			}
			$data['rides'] = $dataval;
			$data['total_rides'] = $orderCounts;
			$data['revenue_value'] = $total_earnings;
			$data['cancelled_rides'] = $cancelledQuery;
			return Helper::getResponse(['data' => $data]);

		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	public function requestHistoryDetails($id)
	{
		try {
			$data = RideRequest::with('user', 'provider','rating','payment')->findOrFail($id);

			return Helper::getResponse(['data' => $data]);

		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}
	

	public function statement_provider(Request $request)
	{

		try{

		$datum = Provider::where('company_id', Auth::user()->company_id);

		if($request->has('search_text') && $request->search_text != null) {
			$datum->Search($request->search_text);
		}

		if($request->has('order_by')) {
			$datum->orderby($request->order_by, $request->order_direction);
		}

		if($request->has('page') && $request->page == 'all') {
            $Providers = $datum->get();
        } else {
            $Providers = $datum->paginate(10);
        }

		 

		foreach($Providers as $index => $Provider){

			$Rides = RideRequest::where('provider_id',$Provider->id)
						->where('status','<>','CANCELLED')
						->get()->pluck('id');

			$Providers[$index]->rides_count = $Rides->count();

			$Providers[$index]->payment = RideRequestPayment::whereIn('ride_request_id', $Rides)
							->select(\DB::raw(
							   'SUM(ROUND(provider_pay)) as overall'
							))->get();
		}

			return Helper::getResponse(['data' => $Providers]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

   public function statement_user(Request $request)
   {

	try{

		$datum = User::where('company_id', Auth::user()->company_id);

		if($request->has('search_text') && $request->search_text != null) {
			$datum->Search($request->search_text);
		}

		if($request->has('order_by')) {
			$datum->orderby($request->order_by, $request->order_direction);
		}

		if($request->has('page') && $request->page == 'all') {
            $Users = $datum->get();
        } else {
            $Users = $datum->paginate(10);
        }

			foreach($Users as $index => $User){

				$Rides = RideRequest::where('user_id',$User->id)
							->where('status','<>','CANCELLED')
							->get()->pluck('id');

				$Users[$index]->rides_count = $Rides->count();

				$Users[$index]->payment = RideRequestPayment::whereIn('ride_request_id', $Rides)
								->select(\DB::raw(
								'SUM(ROUND(total)) as overall' 
								))->get();
			}			

			return Helper::getResponse(['data' => $Users]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	public function rate(Request $request) {

		$this->validate($request, [
			  'id' => 'required|numeric|exists:transport.ride_requests,id,user_id,'.Auth::guard('user')->user()->id,
			  'rating' => 'required|integer|in:1,2,3,4,5',
			  'comment' => 'max:255',
			  'admin_service' => 'required|in:TRANSPORT,ORDER,SERVICE,DELIVERY',
		],['comment.max'=>'character limit should not exceed 255']);

		try {

			$rideRequest = RideRequest::where('id', $request->id)->where('status', 'COMPLETED')->firstOrFail();
			
			$data = (new UserServices())->rate($request, $rideRequest );

			return Helper::getResponse(['status' => isset($data['status']) ? $data['status'] : 200, 'message' => isset($data['message']) ? $data['message'] : '', 'error' => isset($data['error']) ? $data['error'] : '' ]);

		} catch (\Exception $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.ride.request_not_completed'), 'error' =>trans('api.ride.request_not_completed') ]);
		}
	}


	public function payment(Request $request) {

		$this->validate($request, [
			'id' => 'required|numeric|exists:transport.ride_requests,id',
		]);
		
		try {

			$tip_amount = 0;

			$UserRequest = RideRequest::with('provider_vehicle')->find($request->id);
			$payment = RideRequestPayment::with(['user','provider'])->where('ride_request_id', $request->id)->first();

			$ride = (new UserServices())->payment($request, $UserRequest, $payment);

			return Helper::getResponse(['message' => $ride]);

		} catch (\Throwable $e) {
			 return Helper::getResponse(['status' => 500, 'message' => trans('api.ride.request_not_completed'), 'error' => $e->getMessage() ]);
		}
	}
}
<?php

namespace App\Http\Controllers\V1\Delivery\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\SendPushNotification;
use App\Models\Delivery\DeliveryVehicle;
use App\Models\Common\RequestFilter;
use App\Models\Delivery\DeliveryRequest;
use App\Models\Delivery\Delivery;
use App\Models\Common\UserRequest;
use App\Models\Delivery\DeliveryType;
use App\Models\Common\Provider;
use App\Models\Common\Country;
use App\Models\Common\Rating;
use App\Services\V1\Delivery\DeliveryService;
use App\Models\Common\Setting;
use App\Models\Common\Reason;
use App\Models\Common\State;
use App\Models\Common\User;
use App\Models\Common\Menu;
use App\Models\Common\Card;
use App\Models\Delivery\DeliveryCityPrice;
use App\Models\Delivery\DeliveryPeakPrice;
use App\Models\Delivery\PackageType;
use App\Models\Common\PeakHour;
use App\Models\Common\AdminService;
//use App\Models\Delivery\RideLostItem;
use App\Models\Delivery\DeliveryRequestDispute;
use App\Models\Delivery\DeliveryPayment;
use App\Models\Common\ProviderService;
use App\Models\Common\CompanyCountry;
use App\Models\Common\Promocode;
use App\Services\PaymentGateway;
use App\Services\V1\Common\UserServices;
use App\Services\V1\Common\ProviderServices;
use App\Models\Common\PaymentLog;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\V1\Delivery\Provider\TripController;
use App\Http\Controllers\V1\Delivery\Provider\HomeController;
use Carbon\Carbon;
use App\Traits\Actions;
use Auth;
use DB;

class DeliveryController extends Controller
{
	use Actions;

	public function delivery_types($category_id)
	{
		$delivery_types = DeliveryType::select('id', 'delivery_name')->where('delivery_category_id', $category_id)->where('company_id', $this->company_id)->orderby('delivery_name')->get();

		return Helper::getResponse(['data' => $delivery_types]);
	}

	public function package_types()
	{
		$delivery_types = PackageType::select('id', 'package_name')->where('company_id', $this->company_id)->orderby('package_name')->get();

		return Helper::getResponse(['data' => $delivery_types]);
	}

	public function services(Request $request)
	{
		$this->validate($request, [
			'type' => 'required|numeric|exists:delivery.delivery_types,id',
			'latitude' => 'required|numeric',
			'longitude' => 'required|numeric'
		]);

		$delivery = new \stdClass;

		$distance = isset($this->settings->delivery->provider_search_radius) ? $this->settings->delivery->provider_search_radius : 100;

		$delivery_vehicles = [];

		$callback = function ($q) use ($request) {
			$q->where('admin_service', 'DELIVERY');
			$q->where('category_id', $request->type);
		};

		$withCallback = ['service' => $callback, 'service.delivery_vehicle'];
		$whereHasCallback = ['service' => $callback];

		$data = (new UserServices())->availableProviders($request, $withCallback, $whereHasCallback, 'delivery');


		$service = null;
		$providers = [];
		$nearestProvider = [];

		//List providers in nearestProvider variable (result is ordered ascending based on distance)
		foreach ($data as $datum) {
			if ($datum->service != null) {
				$nearestProvider[] = ['service_id' => $datum->service->delivery_vehicle_id, 'latitude' => $datum->latitude, 'longitude' => $datum->longitude];
				$service = $datum->service->delivery_vehicle_id;
				$delivery_vehicles[] = $service;
			}

			$provider = new \stdClass();
			foreach (json_decode($datum) as $l => $val) {
				$provider->$l = $val;
			}
			$provider->service_id = $service;
			$providers[] = $provider;
		}

		$output = [];
		foreach ($nearestProvider as $near) {
			$sources = [];
			$destinations = [];
			$sources[] = $near['latitude'] . ',' . $near['longitude'];
			$destinations[] = $request->latitude . ',' . $request->longitude;
			$output[] = Helper::getDistanceMap($sources, $destinations);
		}


		$output = array_replace_recursive($output);
		$dis = [];

		if (count($output) > 0) {
			foreach ($output as $key => $data) {
				// dd($nearestProvider);
				if ($data->status == "OK") {
					$estimations[$nearestProvider[$key]['service_id']][$data->rows[0]->elements[0]->duration->value] = $data->rows[0]->elements[0]->duration->text;
					$dis[$nearestProvider[$key]['service_id']][] = $data->rows[0]->elements[0]->duration->value;
					ksort($estimations[$nearestProvider[$key]['service_id']]);
					sort($dis[$nearestProvider[$key]['service_id']]);
				}
			}
		}

		$geofence = (new UserServices())->poly_check_request((round($request->latitude, 6)), (round($request->longitude, 6)));

		$service_list = DeliveryVehicle::with(['priceDetails' => function ($q) use ($geofence) {
			$q->where('geofence_id', $geofence);
		}])->whereHas('priceDetails', function ($q) use ($geofence) {
			$q->where('geofence_id', $geofence);
		})->whereIn('id', $delivery_vehicles)->where('company_id', $this->company_id)->where('status', 1)->get();

		$service_types = [];
		$service_id_list = [];

		if (count($service_list) > 0) {
			foreach ($service_list as $k => $services) {
				$service = new \stdClass();
				$service->estimated_time = isset($estimations[$services->id]) ? $estimations[$services->id][$dis[$services->id][0]] : '0 Min';
				foreach (json_decode($services) as $j => $s) {
					$service->$j = $s;
				}
				$service_types[] = $service;
				$service_id_list[] = $service->id;
			}
		}

		$delivery_vehicles = DeliveryVehicle::with(['priceDetails' => function ($q) use($request) {
			$q->where('city_id', $this->user ? $this->user->city_id : $request->city_id);
		}])->whereHas('priceDetails', function ($q) use($request) {
			$q->where('city_id', $this->user ? $this->user->city_id : $request->city_id);
		})->where('delivery_type_id', $request->type)->where('company_id', $this->company_id)->where('status', 1)->whereNotIn('id', $service_id_list)->select('*', \DB::raw('"..." AS "estimated_time"'))->get()->toArray();

		$delivery->services = array_merge($service_types, $delivery_vehicles);

		$delivery->providers = $providers;

		if( Auth::guard(strtolower(Helper::getGuard()))->user() != null ) {
		$delivery->promocodes = Promocode::where('company_id', $this->company_id)->where('service', 'DELIVERY')
					->where('expiration','>=',date("Y-m-d H:i"))
					->whereDoesntHave('promousage', function($query) {
						$query->where('user_id', Auth::guard('user')->user()->id);
					})
					->get();
				} else {
					$delivery->promocodes = [];
				}

		return Helper::getResponse(['data' => $delivery]);
	}

	public function cartesian($input) {
	    $result = array(array());

	    foreach ($input as $key => $values) {
	        $append = array();

	        foreach($result as $product) {
	            foreach($values as $item) {
	                $product[$key] = $item;
	                $append[] = $product;
	            }
	        }

	        $result = $append;
	    }

	    return $result;
	}

	public function reorder($original, $parsed = []) {

		$reorder = [];

		if(count($original['destination']) > 0) {
			unset($original['distance']);

			$cartesian =  $this->cartesian($original);

			foreach ($cartesian as $carte) {
				$source = explode(',', $carte['source']);
				$destination = explode(',', $carte['destination']);
				$reorder[] = ['source' => $carte['source'], 'destination' => $carte['destination'], 'distance' => Helper::getDistanceBetweenLocation(  $source[0], $source[1], $destination[0], $destination[1]  )];
			}

			usort($reorder, function ($item1, $item2) {
			    return $item1['distance'] <=> $item2['distance'];
			});

			$source_location = array_shift($reorder);

			$d_location = [];

			foreach ($reorder as $key => $d_latitude) {
				$d_location[] = $d_latitude['destination'];
			}

			$input = array(
			    'source' => array($source_location['source']),
			    'destination' => $d_location,
			    'distance' => 0
			);

			$parsed[] = $source_location;

			if(count($reorder) > 0) {
				return $this->reorder($input, $parsed);
			} else {
				return $parsed;
			}

		} else {
			return $parsed;
		}
		

		return count($original);
	}

	public function estimate(Request $request)
	{

		$this->validate($request, [
			's_latitude' => 'required|numeric',
			's_longitude' => 'required|numeric',
			'weight' => 'required',
			'delivery_type_id' => 'required|numeric',
			'service_type' => 'required|numeric|exists:delivery.delivery_vehicles,id',
			'payment_mode' => 'required',
		]);

		$d_location = [];

		foreach ($request->d_latitude as $key => $d_latitude) {
			$d_location[] = $request->d_latitude[$key]. ','. $request->d_longitude[$key];
		}

		$input = array(
		    'source' => array($request->s_latitude.','. $request->s_longitude),
		    'destination' => $d_location,
		    'distance' => 0
		);
		$location_requests = $this->reorder($input);

		$dLatitude = [];
		$dLongitude = [];

		foreach ($location_requests as $key => $location_request) {
			$destination = explode(',', $location_request["destination"]);
			$dLatitude[] = $destination[0];
			$dLongitude[] = $destination[1];
		}
		$request->request->add(['d_latitude' => $dLatitude, 'd_longitude' => $dLongitude ]);

		$request->request->add(['server_key' => $this->settings->site->server_key]);
		$request->request->add(['city_id' => $this->user ? $this->user->city_id : $request->city_id ]);

		$fare = $this->estimated_fare($request)->getData();
		$service = DeliveryVehicle::select('id', 'vehicle_name', 'vehicle_image', 'weight', 'length', 'breadth', 'height')->where('id', $request->service_type)->where('status', 1)->first();

		if($request->has('weight') && $request->weight != "") {
			foreach ($request->weight as $weight) {
				if($service->weight < $weight) return Helper::getResponse(['status' => 422, 'message' => 'Weight limit exceeded']);
			}
		}
		
		if($request->has('length') && $request->length != "") {
			foreach ($request->length as $length) {
				if($service->length < $length) return Helper::getResponse(['status' => 422, 'message' => 'Length limit exceeded']);
			}
		}

		if($request->has('breadth') && $request->breadth != "") {
			foreach ($request->breadth as $breadth) {
				if($service->breadth < $breadth) return Helper::getResponse(['status' => 422, 'message' => 'Breadth limit exceeded']);
			}
		}

		if($request->has('height') && $request->height != "") {
			foreach ($request->height as $height) {
				if($service->height < $height) return Helper::getResponse(['status' => 422, 'message' => 'Height limit exceeded']);
			}
		}

		if ($request->has('current_longitude') && $request->has('current_latitude')) {
			User::where('id', $User->id)->update([
				'latitude' => $request->current_latitude,
				'longitude' => $request->current_longitude
			]);
		}

		if( Auth::guard(strtolower(Helper::getGuard()))->user() != null ) {
			$promocodes = Promocode::select('id', 'promo_code', 'picture', 'percentage', 'max_amount', 'promo_description', 'expiration')->where('company_id', $this->company_id)->where('service', 'DELIVERY')
					->where('expiration','>=',date("Y-m-d H:i"))
					->whereDoesntHave('promousage', function($query) {
								$query->where('user_id',Auth::guard('user')->user()->id);
							})
					->get();

			$currency = Auth::guard('user')->user()->currency_symbol;
		} else {
			$promocodes = [];
			$currency = '';
		}

		return Helper::getResponse(['data' => ['fare' => $fare, 'service' => $service, 'promocodes' => $promocodes, 'currency' => $currency]]);
	}

	public function estimated_fare(Request $request){

		//try{       
            $response = new DeliveryService();

            $user = Auth::guard('user')->user();

	        $company_id = $user ? $user->company_id : 1;

			$request->request->add(['company_id' => $company_id]);
			$request->request->add(['total_deliveries' => count($request->d_latitude)]);
			$request->request->add(['type' => "estimate"]);
            $responsedata=$response->calculateFare($request->all(), 1);

            if(!empty($responsedata['errors'])){
                throw new \Exception($responsedata['errors']);
            }
            else{
                return response()->json( $responsedata['data'] );
            }

        /*} catch(Exception $e) {
            return response()->json( $e->getMessage() );
        }*/
	}

	public function create_request(Request $request)
	{
		$this->validate($request, [
			's_latitude' => 'required|numeric',
			's_longitude' => 'required|numeric',
			'delivery_type_id' => 'required',
			'd_latitude' => 'required',
			'd_longitude' => 'required',
			'd_address' => 'required',
			'weight' => 'required',
			'receiver_name' => 'required',
			'receiver_mobile' => 'required',
			'service_type' => 'required'
		]);

		try {

			$d_location = [];

			foreach ($request->d_latitude as $key => $d_latitude) {
			$d_location[] = $request->d_latitude[$key]. ','. $request->d_longitude[$key]. ','. $request->d_address[$key];
			}

			$input = array(
			   'source' => array($request->s_latitude.','. $request->s_longitude),
			   'destination' => $d_location,
			   'distance' => 0
			);
			$location_requests = $this->reorder($input);

			$dLatitude = [];
			$dLongitude = [];

			foreach ($location_requests as $key => $location_request) {
			$destination = explode(',', $location_request["destination"]);

			$dLatitude[] = $destination[0];
			$dLongitude[] = $destination[1];
			unset($destination[0]);
			unset($destination[1]);
            $dAddress[] = implode(" ",$destination);
			}
			
			$request->request->add(['d_latitude' => $dLatitude, 'd_longitude' => $dLongitude, 'd_address' => $dAddress]);

			$ride = (new DeliveryService())->createRide($request);
			return Helper::getResponse(['status' => isset($ride['status']) ? $ride['status'] : 200, 'message' => isset($ride['message']) ? $ride['message'] : '', 'data' => isset($ride['data']) ? $ride['data'] : [] ]);
		} catch (Exception $e) {  
			return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
		}

		
	}

	public function status(Request $request)
	{

		try{

			$check_status = ['CANCELLED', 'SCHEDULED'];
            $admin_service = 'DELIVERY';

            $deliveryRequest = DeliveryRequest::DeliveryRequestStatusCheck(Auth::guard('user')->user()->id, $check_status, 'DELIVERY',0)
                                        ->get()
                                        ->toArray();

            $start_time = (Carbon::now())->toDateTimeString();
            $end_time = (Carbon::now())->toDateTimeString();

            $peak_percentage = 1+(0/100)."X";
            $peak = 0;

            $start_time_check = PeakHour::where('start_time', '<=', $start_time)->where('end_time', '>=', $start_time)->where('company_id', '>=', Auth::guard('user')->user()->company_id)->first();

            if( count($deliveryRequest) > 0 && $start_time_check){

                $Peakcharges = DeliveryPeakPrice::where('delivery_city_price_id', $deliveryRequest[0]['city_id'])->where('delivery_vehicle_id', $deliveryRequest[0]['delivery_vehicle_id'])->where('peak_hour_id',$start_time_check->id)->first();

                if($Peakcharges){
                    $peak = 1;
                }

            }

            	$deliveries = Delivery::select('id')->where('delivery_request_id', $deliveryRequest[0]['id'])->pluck('id'); 

			if(!empty($deliveryRequest)){

				$delivery_payments = DeliveryPayment::whereIn('delivery_id', $deliveries)->get();
				if(count($delivery_payments)) {
					$delivery_id = $deliveryRequest[0]['id'];
					$user_id = 0;
					$provider_id = 0;
					$fleet_id = 0;
					$promocode_id = 0;
					$payment_id = 0;
					$company_id = 0;
					$payment_mode = 0;
					$fixed = 0;
					$distance = 0;
					$weight = 0;
					$commision = 0;
					$commision_percent = 0;
					$fleet = 0;
					$fleet_percent = 0;
					$discount = 0;
					$discount_percent = 0;
					$tax = 0;
					$tax_percent = 0;
					$wallet = 0;
					$is_partial = 0;
					$cash = 0;
					$card = 0;
					$peak_amount = 0;
					$peak_comm_amount = 0;
					$total_waiting_time = 0;
					$tips = 0;
					$round_of = 0;
					$total = 0;
					$payable = 0;
					$provider_pay = 0;

					foreach ($delivery_payments as $key => $delivery_payment) {
						$user_id = $delivery_payment->user_id;
						$provider_id = $delivery_payment->provider_id;
						$fleet_id = $delivery_payment->fleet_id;
						$$promocode_id = $delivery_payment->promocode_id;
						$payment_id = $delivery_payment->payment_id;
						$company_id = $delivery_payment->company_id;
						$payment_mode = $delivery_payment->payment_mode;
						$fixed += $delivery_payment->fixed;
						$distance += $delivery_payment->distance;
						$weight += $delivery_payment->weight;
						$commision += $delivery_payment->commision;
						$commision_percent += $delivery_payment->commision_percent;
						$fleet += $delivery_payment->fleet;
						$fleet_percent += $delivery_payment->fleet_percent;
						$discount += $delivery_payment->discount;
						$discount_percent += $delivery_payment->discount_percent;
						$tax += $delivery_payment->tax;
						$tax_percent += $delivery_payment->tax_percent;
						$wallet += $delivery_payment->wallet;
						$is_partial = $delivery_payment->is_partial;
						$cash += $delivery_payment->cash;
						$card += $delivery_payment->card;
						$peak_amount += $delivery_payment->peak_amount;
						$peak_comm_amount += $delivery_payment->peak_comm_amount;
						$total_waiting_time += $delivery_payment->total_waiting_time;
						$tips += $delivery_payment->tips;
						$round_of += $delivery_payment->round_of;
						$total += $delivery_payment->total;
						$payable += $delivery_payment->payable;
						$provider_pay += $delivery_payment->provider_pay;
					}

					$payment = new \stdClass;
					$payment->delivery_id = $delivery_id;
					$payment->user_id = $user_id;
					$payment->provider_id = $provider_id;
					$payment->fleet_id = $fleet_id;
					$payment->promocode_id = $promocode_id;
					$payment->payment_id = $payment_id;
					$payment->company_id = $company_id;
					$payment->payment_mode = $payment_mode;
					$payment->fixed = $fixed;
					$payment->distance = $distance;
					$payment->weight = $weight;
					$payment->commision = $commision;
					$payment->commision_percent = $commision_percent;
					$payment->fleet = $fleet;
					$payment->fleet_percent = $fleet_percent;
					$payment->discount = $discount;
					$payment->discount_percent = $discount_percent;
					$payment->tax = $tax;
					$payment->tax_percent = $tax_percent;
					$payment->wallet = $wallet;
					$payment->is_partial = $is_partial;
					$payment->cash = $cash;
					$payment->card = $card;
					$payment->peak_amount = $peak_amount;
					$payment->peak_comm_amount = $peak_comm_amount;
					$payment->total_waiting_time = $total_waiting_time;
					$payment->tips = $tips;
					$payment->round_of = $round_of;
					$payment->total = $total;
					$payment->payable = $payable;
					$payment->provider_pay = $provider_pay;

					$base_distance  = $deliveryRequest[0]['base_distance'];
					$total_distance = $deliveryRequest[0]['distance'];
					$base_weight  = $deliveryRequest[0]['base_weight'];
					$total_weight = $deliveryRequest[0]['weight'];
					$tot_dis = $total_distance - $base_distance;
					$tot_weig = $total_weight - $base_weight;
					$payment->base_fare_text = 'Base Fare for '.$base_distance .' kms';
					$payment->distance_fare_text = 'Distance Fare for '.$tot_dis.' Kms';
					$payment->weight_fare_text = 'Delivery Weight for '.$tot_weig.' Kgs';
					$payment->discount_fare_text = 'Discount ('.$deliveryRequest[0]['discount_percent'] .'%)';

					$deliveryRequest[0]['payment'] = $payment;


				}                

            $search_status = ['SEARCHING','SCHEDULED'];
            $deliveryRequestFilter = DeliveryRequest::DeliveryRequestAssignProvider(Auth::guard('user')->user()->id,$search_status)->get(); 

            if(!empty($deliveryRequest)){
                $deliveryRequest[0]['peak'] = $peak ;
                $deliveryRequest[0]['reasons']=Reason::where('type','USER')->where('service','DELIVERY')->where('status','Active')->get();
            }

            $Timeout = $this->settings->delivery->provider_select_timeout ? $this->settings->delivery->provider_select_timeout : 60 ;
            $response_time = $Timeout;

            if(!empty($deliveryRequestFilter)){
                for ($i=0; $i < sizeof($deliveryRequestFilter); $i++) {
                    $ExpiredTime = $Timeout - (time() - strtotime($deliveryRequestFilter[$i]->assigned_at));
                    if($deliveryRequestFilter[$i]->status == 'SEARCHING' && $ExpiredTime < 0) {
                        (new ProviderServices())->assignNextProvider($deliveryRequestFilter[$i]->id, $admin_service );
                        $response_time = $Timeout - (time() - strtotime($deliveryRequestFilter[$i]->assigned_at));
                    }else if($deliveryRequestFilter[$i]->status == 'SEARCHING' && $ExpiredTime > 0){
                        break;
                    }
                }

            }

            if(empty($deliveryRequest)) {

                $cancelled_request = DeliveryRequest::where('delivery_requests.user_id', Auth::guard('user')->user()->id)
                    ->where('delivery_requests.user_rated',0)
                    ->where('delivery_requests.status', ['CANCELLED'])->orderby('updated_at', 'desc')
                    ->where('updated_at','>=',\Carbon\Carbon::now()->subSeconds(5))
                    ->first();
                
            }

            return Helper::getResponse(['data' => [
                'response_time' => $response_time, 
                'data' => $deliveryRequest, 
                'sos' => isset($this->settings->site->sos_number) ? $this->settings->site->sos_number : '911' , 
                'emergency' => isset($this->settings->site->contact_number) ? $this->settings->site->contact_number : [['number' => '911']]  ]]);

		}
		} catch (Exception $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.something_went_wrong'), 'error' => $e->getMessage() ]);
		}
	}

	public function checkDelivery(Request $request, $id)
	{

		try{

			
			$admin_service = 'DELIVEERY';
			$ride_type_id=DeliveryRequest::where('id',$id)->first();
			$check_status = ['CANCELLED', 'SCHEDULED'];

			$rideRequest = DeliveryRequest::DeliveryRequestStatusCheck(Auth::guard('user')->user()->id, $check_status, 'DELIVERY',$ride_type_id->ride_delivery_id)
										->where('id', $id)
										->get()
										->toArray();

			$start_time = (Carbon::now())->toDateTimeString();
			$end_time = (Carbon::now())->toDateTimeString();

			$peak_percentage = 1+(0/100)."X";
			$peak = 0;
									   

			$search_status = ['SEARCHING','SCHEDULED'];
			$rideRequestFilter = Delivery::DeliveryRequestAssignProvider(Auth::guard('user')->user()->id,$search_status)->get(); 
			$deliveries = Delivery::select('id')->where('delivery_request_id', $rideRequest[0]['id'])->pluck('id'); 

			if(!empty($rideRequest)){

				$delivery_payments = DeliveryPayment::whereIn('delivery_id', $deliveries)->get();
				if(count($delivery_payments)) {
					$delivery_id = $rideRequest[0]['id'];
					$user_id = 0;
					$provider_id = 0;
					$fleet_id = 0;
					$promocode_id = 0;
					$payment_id = 0;
					$company_id = 0;
					$payment_mode = 0;
					$fixed = 0;
					$distance = 0;
					$weight = 0;
					$commision = 0;
					$commision_percent = 0;
					$fleet = 0;
					$fleet_percent = 0;
					$discount = 0;
					$discount_percent = 0;
					$tax = 0;
					$tax_percent = 0;
					$wallet = 0;
					$is_partial = 0;
					$cash = 0;
					$card = 0;
					$peak_amount = 0;
					$peak_comm_amount = 0;
					$total_waiting_time = 0;
					$tips = 0;
					$round_of = 0;
					$total = 0;
					$payable = 0;
					$provider_pay = 0;

					foreach ($delivery_payments as $key => $delivery_payment) {
						$user_id = $delivery_payment->user_id;
						$provider_id = $delivery_payment->provider_id;
						$fleet_id = $delivery_payment->fleet_id;
						$$promocode_id = $delivery_payment->promocode_id;
						$payment_id = $delivery_payment->payment_id;
						$company_id = $delivery_payment->company_id;
						$payment_mode = $delivery_payment->payment_mode;
						$fixed += $delivery_payment->fixed;
						$distance += $delivery_payment->distance;
						$weight += $delivery_payment->weight;
						$commision += $delivery_payment->commision;
						$commision_percent += $delivery_payment->commision_percent;
						$fleet += $delivery_payment->fleet;
						$fleet_percent += $delivery_payment->fleet_percent;
						$discount += $delivery_payment->discount;
						$discount_percent += $delivery_payment->discount_percent;
						$tax += $delivery_payment->tax;
						$tax_percent += $delivery_payment->tax_percent;
						$wallet += $delivery_payment->wallet;
						$is_partial = $delivery_payment->is_partial;
						$cash += $delivery_payment->cash;
						$card += $delivery_payment->card;
						$peak_amount += $delivery_payment->peak_amount;
						$peak_comm_amount += $delivery_payment->peak_comm_amount;
						$total_waiting_time += $delivery_payment->total_waiting_time;
						$tips += $delivery_payment->tips;
						$round_of += $delivery_payment->round_of;
						$total += $delivery_payment->total;
						$payable += $delivery_payment->payable;
						$provider_pay += $delivery_payment->provider_pay;
					}

					$payment = new \stdClass;
					$payment->delivery_id = $delivery_id;
					$payment->user_id = $user_id;
					$payment->provider_id = $provider_id;
					$payment->fleet_id = $fleet_id;
					$payment->promocode_id = $promocode_id;
					$payment->payment_id = $payment_id;
					$payment->company_id = $company_id;
					$payment->payment_mode = $payment_mode;
					$payment->fixed = $fixed;
					$payment->distance = $distance;
					$payment->weight = $weight;
					$payment->commision = $commision;
					$payment->commision_percent = $commision_percent;
					$payment->fleet = $fleet;
					$payment->fleet_percent = $fleet_percent;
					$payment->discount = $discount;
					$payment->discount_percent = $discount_percent;
					$payment->tax = $tax;
					$payment->tax_percent = $tax_percent;
					$payment->wallet = $wallet;
					$payment->is_partial = $is_partial;
					$payment->cash = $cash;
					$payment->card = $card;
					$payment->peak_amount = $peak_amount;
					$payment->peak_comm_amount = $peak_comm_amount;
					$payment->total_waiting_time = $total_waiting_time;
					$payment->tips = $tips;
					$payment->round_of = $round_of;
					$payment->total = $total;
					$payment->payable = $payable;
					$payment->provider_pay = $provider_pay;

					$base_distance  = $rideRequest[0]['base_distance'];
					$total_distance = $rideRequest[0]['distance'];
					$base_weight  = $rideRequest[0]['base_weight'];
					$total_weight = $rideRequest[0]['weight'];
					$tot_dis = $total_distance - $base_distance;
					$tot_weig = $total_weight - $base_weight;
					if($rideRequest[0]['calculator'] == "WEIGHT"){
						$payment->base_fare_text = 'Base Fare for '.$base_weight .' kgs';
					}else if($rideRequest[0]['calculator'] == "DISTANCE"){
						$payment->base_fare_text = 'Base Fare for '.$base_distance .' kgs';
					}else{
						$payment->base_fare_text = 'Base Fare for '.$base_distance .' kms&'.$base_weight.' kgs';
					}
					
					$payment->distance_fare_text = 'Distance Fare for '.$tot_dis.' Kms';
					$payment->weight_fare_text = 'Delivery Weight for '.$tot_weig.' Kgs';
					$payment->discount_fare_text = 'Discount ('.$rideRequest[0]['discount_percent'] .'%)';

					$rideRequest[0]['payment'] = $payment;


				}

				$rideRequest[0]['ride_otp'] = (int) $this->settings->transport->ride_otp ? $this->settings->transport->ride_otp : 0 ;
				$rideRequest[0]['peak'] = $peak ;

				$rideRequest[0]['reasons']=Reason::where('type','USER')->where('service','TRANSPORT')->where('status','Active')->get();

				$Timeout = $this->settings->transport->provider_select_timeout ? $this->settings->transport->provider_select_timeout : 60 ;
				$response_time = $Timeout;

				if(!empty($rideRequestFilter)){
					for ($i=0; $i < sizeof($rideRequestFilter); $i++) {
						$ExpiredTime = $Timeout - (time() - strtotime($rideRequestFilter[$i]->assigned_at));
						if($rideRequestFilter[$i]->status == 'SEARCHING' && $ExpiredTime < 0) {
							//(new ProviderServices())->assignNextProvider($rideRequestFilter[$i]->id, $admin_service );
							$response_time = $Timeout - (time() - strtotime($rideRequestFilter[$i]->assigned_at));
						}else if($rideRequestFilter[$i]->status == 'SEARCHING' && $ExpiredTime > 0){
							break;
						}
					}

				}

				if(empty($rideRequest)) {

					$cancelled_request = Delivery::where('user_id', Auth::guard('user')->user()->id)
						->where('user_rated',0)
						->where('status', ['CANCELLED'])->orderby('updated_at', 'desc')
						->where('updated_at','>=',\Carbon\Carbon::now()->subSeconds(5))
						->first();
					
				}

				return Helper::getResponse(['data' => [
					'response_time' => $response_time, 
					'data' => $rideRequest, 
					'sos' => isset($this->settings->site->sos_number) ? $this->settings->site->sos_number : '911' , 
					'emergency' => isset($this->settings->site->contact_number) ? $this->settings->site->contact_number : [['number' => '911']]  ]]);
		}

		} catch (Exception $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.something_went_wrong'), 'error' => $e->getMessage() ]);
		}
	}


	public function cancel_ride(Request $request)
	{
		//dd($request->all());
		$this->validate($request, [
			'id' => 'required|numeric|exists:delivery.delivery_requests,id,user_id,' . Auth::guard('user')->user()->id,
		]);

		$request->request->add(['cancelled_by' => 'USER']);

		try {
			$ride = (new DeliveryService())->cancelRide($request);
			return Helper::getResponse(['status' => $ride['status'], 'message' => $ride['message']]);
		} catch (Exception $e) {
			return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
		}
	}


	public function extend_trip(Request $request)
	{
		$this->validate($request, [
			'id' => 'required|numeric|exists:transport.ride_requests,id,user_id,' . Auth::guard('user')->user()->id,
			'latitude' => 'required|numeric',
			'longitude' => 'required|numeric',
			'address' => 'required',
		]);

		try {

			$ride = (new Ride())->extendTrip($request);

			return Helper::getResponse(['message' => 'Destination location has been changed', 'data' => $ride]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.something_went_wrong'), 'error' => $e->getMessage()]);
		}
	}

	public function update_payment_method(Request $request)
	{
		$this->validate($request, [
			'id' => 'required|numeric|exists:transport.ride_requests,id,user_id,' . Auth::guard('user')->user()->id,
			'payment_mode' => 'required',
		]);

		try {

			$rideRequest = DeliveryRequest::findOrFail($request->id);
			if ($request->payment_mode != "CASH") {
				$rideRequest->status = 'COMPLETED';
				$rideRequest->save();
			}

			$payment = DeliveryPayment::where('ride_request_id', $rideRequest->id)->first();

			if ($payment != null) {
				$payment->payment_mode = $request->payment_mode;
				$payment->save();
			}

			$ride = (new UserServices())->updatePaymentMode($request, $rideRequest, $payment);

			return Helper::getResponse(['message' => trans('api.ride.payment_updated')]);
		} catch (ModelNotFoundException $e) {
			return Helper::getResponse(['status' => 500, 'error' => $e->getMessage()]);
		}
	}



	public function search_user(Request $request)
	{

		$results = array();

		$term =  $request->input('stext');

		$queries = User::where('first_name', 'LIKE', $term . '%')->where('company_id', Auth::user()->company_id)->take(5)->get();

		foreach ($queries as $query) {
			$results[] = $query;
		}

		return response()->json(array('success' => true, 'data' => $results));
	}

	public function search_provider(Request $request)
	{

		$results = array();

		$term =  $request->input('stext');

		$queries = Provider::where('first_name', 'LIKE', $term . '%')->take(5)->get();

		foreach ($queries as $query) {
			$results[] = $query;
		}

		return response()->json(array('success' => true, 'data' => $results));
	}

	public function searchRideLostitem(Request $request)
	{

		$results = array();

		$term =  $request->input('stext');

		if ($request->input('sflag') == 1) {

			$queries = DeliveryRequest::where('provider_id', $request->id)->orderby('id', 'desc')->take(10)->get();
		} else {

			$queries = DeliveryRequest::where('user_id', $request->id)->orderby('id', 'desc')->take(10)->get();
		}

		foreach ($queries as $query) {
			$LostItem = RideLostItem::where('ride_request_id', $query->id)->first();
			if (!$LostItem)
				$results[] = $query;
		}

		return response()->json(array('success' => true, 'data' => $results));
	}

	public function searchRideDispute(Request $request)
	{

		$results = array();

		$term =  $request->input('stext');

		if ($request->input('sflag') == 1) {

			$queries = DeliveryRequest::where('provider_id', $request->id)->orderby('id', 'desc')->take(10)->get();
		} else {

			$queries = DeliveryRequest::where('user_id', $request->id)->orderby('id', 'desc')->take(10)->get();
		}

		foreach ($queries as $query) {
			$RideRequestDispute = DeliveryRequestDispute::where('ride_request_id', $query->id)->first();
			if (!$RideRequestDispute)
				$results[] = $query;
		}

		return response()->json(array('success' => true, 'data' => $results));
	}

	public function requestHistory(Request $request)
	{
		try {
			$history_status = array('CANCELLED', 'COMPLETED');
			$datum = DeliveryRequest::where('company_id',  Auth::user()->company_id)
				->with('user', 'provider', 'payment');

			if (Auth::user()->hasRole('FLEET')) {
				$datum->where('admin_id', Auth::user()->id);
			}
			if ($request->has('search_text') && $request->search_text != null) {
				$datum->Search($request->search_text);
			}

			if ($request->has('order_by')) {
				$datum->orderby($request->order_by, $request->order_direction);
			}
			$data = $datum->whereIn('status', $history_status)->paginate(10);
			return Helper::getResponse(['data' => $data]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}
	public function requestscheduleHistory(Request $request)
	{
		try {
			$scheduled_status = array('SCHEDULED');
			$datum = DeliveryRequest::where('company_id',  Auth::user()->company_id)
				->whereIn('status', $scheduled_status)
				->with('user', 'provider');

			if (Auth::user()->hasRole('FLEET')) {
				$datum->where('admin_id', Auth::user()->id);
			}
			if ($request->has('search_text') && $request->search_text != null) {
				$datum->Search($request->search_text);
			}

			if ($request->has('order_by')) {
				$datum->orderby($request->order_by, $request->order_direction);
			}

			$data = $datum->paginate(10);

			return Helper::getResponse(['data' => $data]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	public function requestStatementHistory(Request $request)
	{
		try {
			$history_status = array('CANCELLED', 'COMPLETED');
			$rides = DeliveryRequest::where('company_id',  Auth::user()->company_id)
				->with('user', 'provider');
			if ($request->has('country_id')) {
				$rides->where('country_id', $request->country_id);
			}
			if (Auth::user()->hasRole('FLEET')) {
				$rides->where('admin_id', Auth::user()->id);
			}
			if ($request->has('search_text') && $request->search_text != null) {
				$rides->Search($request->search_text);
			}

			if ($request->has('status') && $request->status != null) {
				$history_status = array($request->status);
			}

			if ($request->has('user_id') && $request->user_id != null) {
				$rides->where('user_id', $request->user_id);
			}

			if ($request->has('provider_id') && $request->provider_id != null) {
				$rides->where('provider_id', $request->provider_id);
			}

			if ($request->has('ride_type') && $request->ride_type != null) {
				$rides->where('ride_type_id', $request->ride_type);
			}

			if ($request->has('order_by')) {
				$rides->orderby($request->order_by, $request->order_direction);
			}
			$type = isset($_GET['type']) ? $_GET['type'] : '';
			if ($type == 'today') {
				$rides->where('created_at', '>=', Carbon::today());
			} elseif ($type == 'monthly') {
				$rides->where('created_at', '>=', Carbon::now()->month);
			} elseif ($type == 'yearly') {
				$rides->where('created_at', '>=', Carbon::now()->year);
			} elseif ($type == 'range') {
				if ($request->has('from') && $request->has('to')) {
					if ($request->from == $request->to) {
						$rides->whereDate('created_at', date('Y-m-d', strtotime($request->from)));
					} else {
						$rides->whereBetween('created_at', [Carbon::createFromFormat('Y-m-d', $request->from), Carbon::createFromFormat('Y-m-d', $request->to)]);
					}
				}
			} else {
				// dd(5);
			}
			$cancelrides = $rides;
			$orderCounts = $rides->count();
			if ($request->has('page') && $request->page == 'all') {
				$dataval = $rides->whereIn('status', $history_status)->get();
			} else {
				$dataval = $rides->whereIn('status', $history_status)->paginate(10);
			}

			$cancelledQuery = $cancelrides->where('status', 'CANCELLED')->count();
			$total_earnings = 0;
			foreach ($dataval as $ride) {
				//$ride->status = $ride->status == 1?'Enabled' : 'Disable';
				$rideid  = $ride->id;
				$earnings = DeliveryPayment::select('total')->where('ride_request_id', $rideid)->where('company_id',  Auth::user()->company_id)->first();
				if ($earnings != null) {
					$ride->earnings = $earnings->total;
					$total_earnings = $total_earnings + $earnings->total;
				} else {
					$ride->earnings = 0;
				}
			}
			$data['rides'] = $dataval;
			$data['total_rides'] = $orderCounts;
			$data['revenue_value'] = $total_earnings;
			$data['cancelled_rides'] = $cancelledQuery;
			return Helper::getResponse(['data' => $data]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	public function requestHistoryDetails($id)
	{
		try {
			$data = DeliveryRequest::with('user', 'provider', 'rating', 'payment')->findOrFail($id);

			return Helper::getResponse(['data' => $data]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}


	public function statement_provider(Request $request)
	{

		try {

			$datum = Provider::where('company_id', Auth::user()->company_id);

			if ($request->has('search_text') && $request->search_text != null) {
				$datum->Search($request->search_text);
			}

			if ($request->has('order_by')) {
				$datum->orderby($request->order_by, $request->order_direction);
			}

			if ($request->has('page') && $request->page == 'all') {
				$Providers = $datum->get();
			} else {
				$Providers = $datum->paginate(10);
			}



			foreach ($Providers as $index => $Provider) {

				$Rides = DeliveryRequest::where('provider_id', $Provider->id)
					->where('status', '<>', 'CANCELLED')
					->get()->pluck('id');

				$Providers[$index]->rides_count = $Rides->count();

				$Providers[$index]->payment = DeliveryPayment::whereIn('ride_request_id', $Rides)
					->select(\DB::raw(
						'SUM(ROUND(provider_pay)) as overall'
					))->get();
			}

			return Helper::getResponse(['data' => $Providers]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	public function statement_user(Request $request)
	{

		try {

			$datum = User::where('company_id', Auth::user()->company_id);

			if ($request->has('search_text') && $request->search_text != null) {
				$datum->Search($request->search_text);
			}

			if ($request->has('order_by')) {
				$datum->orderby($request->order_by, $request->order_direction);
			}

			if ($request->has('page') && $request->page == 'all') {
				$Users = $datum->get();
			} else {
				$Users = $datum->paginate(10);
			}

			foreach ($Users as $index => $User) {

				$Rides = DeliveryRequest::where('user_id', $User->id)
					->where('status', '<>', 'CANCELLED')
					->get()->pluck('id');

				$Users[$index]->rides_count = $Rides->count();

				$Users[$index]->payment = DeliveryPayment::whereIn('ride_request_id', $Rides)
					->select(\DB::raw(
						'SUM(ROUND(total)) as overall'
					))->get();
			}

			return Helper::getResponse(['data' => $Users]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	public function rate(Request $request)
	{

		$this->validate($request, [
			'id' => 'required|numeric|exists:delivery.delivery_requests,id,user_id,' . Auth::guard('user')->user()->id,
			'rating' => 'required|integer|in:1,2,3,4,5',
			'comment' => 'max:255',
			'admin_service' => 'required|in:TRANSPORT,ORDER,SERVICE,DELIVERY',
		], ['comment.max' => 'character limit should not exceed 255']);

		try {

			$rideRequest = DeliveryRequest::where('id', $request->id)->where('status', 'COMPLETED')->firstOrFail();

			$data = (new UserServices())->rate($request, $rideRequest);

			return Helper::getResponse(['status' => isset($data['status']) ? $data['status'] : 200, 'message' => isset($data['message']) ? $data['message'] : '', 'error' => isset($data['error']) ? $data['error'] : '']);
		} catch (\Exception $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.ride.request_not_completed'), 'error' => trans('api.ride.request_not_completed')]);
		}
	}


	public function payment(Request $request)
	{

		$this->validate($request, [
			'id' => 'required|numeric|exists:delivery.delivery_requests,id',
		]);

		try {

			$UserRequest = DeliveryRequest::find($request->id);

			$delivery = Delivery::findOrFail($UserRequest->id);
			

			//$payment = DeliveryRequest::find($request->id);

			//$ride = (new UserServices())->payment($request, $UserRequest, $payment);

			$tip_amount = $request->tips != "" ? $request->tips : 0;

			$deliveryConfig = $this->settings->delivery;
			$paymentConfig = json_decode( json_encode( $this->settings->payment ) , true);

			$cardObject = array_values(array_filter( $paymentConfig, function ($e) { return $e['name'] == 'card'; }));
			$card = 0;

			$stripe_secret_key = "";
			$stripe_publishable_key = "";
			$stripe_currency = "";

			$publishUrl = 'newRequest';
			if($UserRequest->admin_service == 'DELIVERY') $publishUrl = 'checkDeliveryRequest';

			if(count($cardObject) > 0) { 
				$card = $cardObject[0]['status'];

				$stripeSecretObject = array_values(array_filter( $cardObject[0]['credentials'], function ($e) { return $e['name'] == 'stripe_secret_key'; }));
				$stripePublishableObject = array_values(array_filter( $cardObject[0]['credentials'], function ($e) { return $e['name'] == 'stripe_publishable_key'; }));
				$stripeCurrencyObject = array_values(array_filter( $cardObject[0]['credentials'], function ($e) { return $e['name'] == 'stripe_currency'; }));

				if(count($stripeSecretObject) > 0) {
					$stripe_secret_key = $stripeSecretObject[0]['value'];
				}

				if(count($stripePublishableObject) > 0) {
					$stripe_publishable_key = $stripePublishableObject[0]['value'];
				}

				if(count($stripeCurrencyObject) > 0) {
					$stripe_currency = $stripeCurrencyObject[0]['value'];
				}
			}

			$random = $this->settings->delivery->booking_prefix.mt_rand(100000, 999999);

			$deliveries = Delivery::select('id')->where('delivery_request_id', $UserRequest->id)->pluck('id'); 

			$delivery_payments = DeliveryPayment::whereIn('delivery_id', $deliveries)->get();

			if(count($delivery_payments)) {
				$delivery_id = $UserRequest->id;
				$user_id = 0;
				$provider_id = 0;
				$fleet_id = 0;
				$promocode_id = 0;
				$payment_id = 0;
				$company_id = 0;
				$payment_mode = 0;
				$fixed = 0;
				$distance = 0;
				$weight = 0;
				$commision = 0;
				$commision_percent = 0;
				$fleet = 0;
				$fleet_percent = 0;
				$discount = 0;
				$discount_percent = 0;
				$tax = 0;
				$tax_percent = 0;
				$wallet = 0;
				$is_partial = 0;
				$cash = 0;
				$card = 0;
				$peak_amount = 0;
				$peak_comm_amount = 0;
				$total_waiting_time = 0;
				$tips = 0;
				$round_of = 0;
				$total = 0;
				$payable = 0;
				$provider_pay = 0;

				foreach ($delivery_payments as $delivery_payment) {
					$user_id = $delivery_payment->user_id;
					$provider_id = $delivery_payment->provider_id;
					$fleet_id = $delivery_payment->fleet_id;
					$$promocode_id = $delivery_payment->promocode_id;
					$payment_id = $delivery_payment->payment_id;
					$company_id = $delivery_payment->company_id;
					$payment_mode = $delivery_payment->payment_mode;
					$fixed += $delivery_payment->fixed;
					$distance += $delivery_payment->distance;
					$weight += $delivery_payment->weight;
					$commision += $delivery_payment->commision;
					$commision_percent += $delivery_payment->commision_percent;
					$fleet += $delivery_payment->fleet;
					$fleet_percent += $delivery_payment->fleet_percent;
					$discount += $delivery_payment->discount;
					$discount_percent += $delivery_payment->discount_percent;
					$tax += $delivery_payment->tax;
					$tax_percent += $delivery_payment->tax_percent;
					$wallet += $delivery_payment->wallet;
					$is_partial = $delivery_payment->is_partial;
					$cash += $delivery_payment->cash;
					$card += $delivery_payment->card;
					$peak_amount += $delivery_payment->peak_amount;
					$peak_comm_amount += $delivery_payment->peak_comm_amount;
					$total_waiting_time += $delivery_payment->total_waiting_time;
					$tips += $delivery_payment->tips;
					$round_of += $delivery_payment->round_of;
					$total += $delivery_payment->total;
					$payable += $delivery_payment->payable;
					$provider_pay += $delivery_payment->provider_pay;
				}

				$payment = new \stdClass;
				$payment->delivery_id = $delivery_id;
				$payment->user_id = $user_id;
				$payment->provider_id = $provider_id;
				$payment->fleet_id = $fleet_id;
				$payment->promocode_id = $promocode_id;
				$payment->payment_id = $payment_id;
				$payment->company_id = $company_id;
				$payment->payment_mode = $payment_mode;
				$payment->fixed = $fixed;
				$payment->distance = $distance;
				$payment->weight = $weight;
				$payment->commision = $commision;
				$payment->commision_percent = $commision_percent;
				$payment->fleet = $fleet;
				$payment->fleet_percent = $fleet_percent;
				$payment->discount = $discount;
				$payment->discount_percent = $discount_percent;
				$payment->tax = $tax;
				$payment->tax_percent = $tax_percent;
				$payment->wallet = $wallet;
				$payment->is_partial = $is_partial;
				$payment->cash = $cash;
				$payment->card = $card;
				$payment->peak_amount = $peak_amount;
				$payment->peak_comm_amount = $peak_comm_amount;
				$payment->total_waiting_time = $total_waiting_time;
				$payment->tips = $tips;
				$payment->round_of = $round_of;
				$payment->total = $total;
				$payment->payable = $payable;
				$payment->provider_pay = $provider_pay;

			}

			

			if (isset($request->tips) && !empty($request->tips)) {
				$tip_amount = round($request->tips, 2);
			}

			if($UserRequest->admin_service == 'DELIVERY'){
				$totalAmount = $payment->payable;
			}else{
				$totalAmount = $payment->payable + $tip_amount;
			}


			$paymentMode = $request->has('payment_mode') ? strtoupper($request->payment_mode) : $UserRequest->payment_mode;
			

			if($paymentMode != 'CASH') {

				if ($totalAmount == 0) {

					if($UserRequest->admin_service == 'DELIVERY'){
						$UserRequest->payment_mode = $paymentMode;
						$UserRequest->paid = 1;
						$UserRequest->status = 'PICKEDUP';
						$UserRequest->save();

					}else{

						$UserRequest->payment_mode = $paymentMode;
						$payment->card = $payment->payable;
						$payment->payable = 0;
						$payment->tips = $tip_amount;
						$payment->provider_pay = $payment->provider_pay + $tip_amount;
						$payment->save();

						$UserRequest->paid = 1;
						$UserRequest->status = 'PICKEDUP';
						$UserRequest->save();
					}

					//for create the transaction
					(new \App\Http\Controllers\V1\Delivery\Provider\TripController)->callTransaction($request->id);

					$requestData = ['type' => $UserRequest->admin_service, 'room' => 'room_'.$UserRequest->company_id, 'id' => $UserRequest->id, 'city' => ($this->settings->demo_mode == 0) ? $UserRequest->city_id : 0, 'user' => $UserRequest->user_id ];
					
					app('redis')->publish($publishUrl, json_encode( $requestData ));

					$delivery->status = 'STARTED';
					$delivery->started_at = Carbon::now();
					$delivery->save();

					Delivery::where('id', $UserRequest->id)->update(['paid' => 1]);

					return trans('api.paid');

				} else {

					$log = new PaymentLog();
					$log->company_id = $this->company_id;
					$log->admin_service = $UserRequest->admin_service;
					$log->user_type = 'user';
					$log->transaction_code = $random;
					$log->amount = $totalAmount;
					$log->transaction_id = $UserRequest->id;
					$log->payment_mode = $paymentMode;
					$log->user_id = $UserRequest->user_id;
					$log->save();
					switch ($paymentMode) {
						case 'CARD':

						if($request->has('card_id')) {
							Card::where('card_id', $request->card_id)->update(['is_default' => 1]);
						}

						$card = Card::where('user_id', $UserRequest->user_id)->where('is_default', 1)->first();

						if($card == null)  $card = Card::where('user_id', $UserRequest->user_id)->first();

						$gateway = new PaymentGateway('stripe');

						$response = $gateway->process([
							'order' => $random,
							"amount" => $totalAmount,
							"currency" => $stripe_currency,
							"customer" => $this->user->stripe_cust_id,
							"card" => $card->card_id,
							"description" => "Payment Charge for " . $this->user->email,
							"receipt_email" => $this->user->email,
						]);

						break;
					}
					if($response->status == "SUCCESS") {

						$deliveryPayment = DeliveryPayment::where('delivery_id', $UserRequest->id)->update(['payment_id' => $response->payment_id]);

						//$payment->payment_id = $response->payment_id;
						/*$payment->payment_mode = $paymentMode;
						$payment->card = $payment->payable;
						//$payment->payable = 0;
						$payment->tips = $tip_amount;
						//$payment->total = $totalAmount;
						$payment->provider_pay = $payment->provider_pay + $tip_amount;*/
						//$payment->save();

						$UserRequest->paid = 1;
						$UserRequest->status = 'PICKEDUP';
						$UserRequest->save();
						//for create the transaction
						(new \App\Http\Controllers\V1\Delivery\Provider\TripController)->callTransaction($request->id);

						$requestData = ['type' => $UserRequest->admin_service, 'room' => 'room_'.$UserRequest->company_id, 'id' => $UserRequest->id, 'city' => ($this->settings->demo_mode == 0) ? $UserRequest->city_id : 0, 'user' => $UserRequest->user_id ];
						app('redis')->publish($publishUrl, json_encode( $requestData ));

						$delivery->status = 'STARTED';
						$delivery->started_at = Carbon::now();
						$delivery->save();

						Delivery::where('id', $UserRequest->id)->update(['paid' => 1]);

						return trans('api.paid');

					} else {
						return trans('Transaction Failed');
					}
				}

			} else {
				$UserRequest->paid = 1;
				$UserRequest->status = 'PICKEDUP';
				$UserRequest->save();
				//for create the transaction
				if($UserRequest->admin_service == 'DELIVERY')
				(new \App\Http\Controllers\V1\Delivery\Provider\TripController)->callTransaction($request->id);

				$requestData = ['type' => $UserRequest->admin_service, 'room' => 'room_'.$UserRequest->company_id, 'id' => $UserRequest->id, 'city' => ($this->settings->demo_mode == 0) ? $UserRequest->city_id : 0, 'user' => $UserRequest->user_id ];
				app('redis')->publish($publishUrl, json_encode( $requestData ));

				$delivery->status = 'STARTED';
				$delivery->started_at = Carbon::now();
				$delivery->save();

				Delivery::where('id', $UserRequest->id)->update(['paid' => 1]);

				return trans('api.paid');
			}


			return Helper::getResponse(['message' => $ride]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 500, 'message' => trans('api.ride.request_not_completed'), 'error' => $e->getMessage()]);
		}
	}
}

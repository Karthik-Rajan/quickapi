<?php

namespace App\Http\Controllers\V1\Common\Admin\Resource;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Helpers\Helper;
use Illuminate\Support\Facades\Hash;
use App\Traits\Actions;
use App\Models\Common\GeoFence;
use App\Models\Common\CompanyCity;
use DB;
use Auth;
use Carbon\Carbon;

class GeofenceController extends Controller
{
	use Actions;

	private $model;
	private $request;
	 /**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct(GeoFence $model)
	{
		$this->model = $model;
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function index(Request $request)
	{
		
	  $datum = GeoFence::with('city')->where('company_id', Auth::user()->company_id);
		
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

		return Helper::getResponse(['data' => $data]);

	}

	/**
	 * Show the form for creating a new resource.
	 *
	 * @return \Illuminate\Http\Response
	 */
	public function create()
	{

	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @return \Illuminate\Http\Response
	 */
	public function store(Request $request)
	{

		$this->validate($request, [
			'city_id' => 'required|numeric',
			'location_name' => 'required',
			'ranges' => 'required',
		]);

		try{
			

			$datum = GeoFence::with('city')->where('company_id', Auth::user()->company_id)->get();
			foreach($datum as $avail)
			{
				$available_name = strtoupper(str_replace(' ', '', $avail->location_name));
				if(($available_name === strtoupper(str_replace(' ', '', $request->location_name))))
				{
					return Helper::getResponse(['status' => 422, 'message' => ("$request->location_name Already Exists") ]);
				}
			}


			$geofence = new GeoFence;
			$geofence->company_id = Auth::user()->company_id;  
			$geofence->city_id = $request->city_id;   
			$geofence->location_name = $request->location_name; 
			$geofence->ranges = $request->ranges;
			if($request->has('type')) $geofence->type = $request->type;              
			$geofence->save();

			try {
				$price = \App\Models\Transport\RideCityPrice::where('city_id', $geofence->city_id)->where('company_id', $geofence->company_id)->orderby('pricing_differs')->first();

				if($price) {
					$ridePrice = new \App\Models\Transport\RideCityPrice();
					$ridePrice->geofence_id = $geofence->id;
					$ridePrice->company_id = $geofence->company_id;  
					$ridePrice->fixed = $price->fixed;
					$ridePrice->city_id = $price->city_id;  
					$ridePrice->ride_delivery_vehicle_id = $price->ride_delivery_vehicle_id;  
					$ridePrice->calculator = $price->calculator;  
					$ridePrice->price = $price->price;
					$ridePrice->minute = $price->minute;
					$ridePrice->hour = $price->hour;
					$ridePrice->distance = $price->distance;
					$ridePrice->waiting_free_mins = $price->waiting_free_mins;
					$ridePrice->waiting_min_charge = $price->waiting_min_charge;
					$ridePrice->commission = $price->commission;
					$ridePrice->fleet_commission = $price->fleet_commission;
					$ridePrice->tax = $price->tax;
					$ridePrice->peak_commission = $price->peak_commission;
					$ridePrice->waiting_commission = $price->waiting_commission;
					$ridePrice->save();
				}
			} catch(\Throwable $e) { }

			return Helper::getResponse(['status' => 200, 'message' => trans('admin.create')]);
		} 
		catch (ModelNotFoundException $e) {
			return Helper::getResponse(['status' => 404, 'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	/**
	 * Display the specified resource.
	 *
	 * @param  \App\Promocode  $promocode
	 * @return \Illuminate\Http\Response
	 */
	public function show($id)
	{
		try {
			$geofence = GeoFence::findOrFail($id);
			$country = CompanyCity::where('city_id', $geofence->city_id)->first();
			$geofence->country_id = $country->country_id;
			return Helper::getResponse(['data' => $geofence]);
		} catch (\Throwable $e) {
			return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Promocode  $promocode
	 * @return \Illuminate\Http\Response
	 */
	public function update(Request $request, $id)
	{
		$this->validate($request, [
			'city_id' => 'required|numeric',
			'location_name' => 'required',
			'ranges' => 'required',
		]);

		try {

			$datum = GeoFence::with('city')->where('company_id', Auth::user()->company_id)->get();
			foreach($datum as $avail)
			{
				$available_name = strtoupper(str_replace(' ', '', $avail->location_name));
				if(($available_name === strtoupper(str_replace(' ', '', $request->location_name))) && ($avail->id != $request->id))
				{
					return Helper::getResponse(['status' => 422, 'message' => ("$request->location_name Already Exists") ]);
				}
			}


			$geofence = GeoFence::findOrFail($id);
			$geofence->company_id = Auth::user()->company_id;  
			$geofence->city_id = $request->city_id;   
			$geofence->location_name = $request->location_name; 
			$geofence->ranges = $request->ranges;             
			if($request->has('type')) {
                $geofence->type = $request->type;
            }  else {
                $geofence->type = NULL;
            }
			$geofence->save();

			return Helper::getResponse(['status' => 200, 'message' => trans('admin.update')]);    
		} 

		catch (ModelNotFoundException $e) {
			return Helper::getResponse(['status' => 404,'message' => trans('admin.something_wrong'), 'error' => $e->getMessage()]);
		}
	}

	public function updateStatus(Request $request, $id)
	{
		
		try {

			$datum = GeoFence::findOrFail($id);
			
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

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  \App\Promocode  $promocode
	 * @return \Illuminate\Http\Response
	 */
	public function destroy($id)
	{
		return $this->removeModel($id);
	}
}

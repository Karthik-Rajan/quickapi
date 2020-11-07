<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;
use App\Helpers\Helper;

class DeliveryRequest extends BaseModel
{
    protected $connection = 'delivery';


    protected $hidden = [
     	'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
    ];

    protected $appends = ['created_time','assigned_time', 'schedule_time', 'started_time', 'finished_time'];

    public function scopeuserHistorySearch($query, $searchText='') {
        if ($searchText != '') {
            $result =  $query
            ->where('booking_id', 'like', "%" . $searchText . "%")
            ->orWhere('status', 'like', "%" . $searchText . "%")
            ->orWhere('payment_mode', 'like', "%" . $searchText . "%");
        }
        return $result;
    }

    public function scopehistroySearch($query, $searchText='') {
        if ($searchText != '') {
            $result =  $query
            ->where('booking_id', 'like', "%" . $searchText . "%")
            ->orWhere('status', 'like', "%" . $searchText . "%")
            ->orwhereHas('ride', function ($q) use ($searchText){
            $q->where('vehicle_name', 'like', "%" . $searchText . "%");
                })
            ->orWhere('payment_mode', 'like', "%" . $searchText . "%");
        }
        return $result;
    }

    public function scopeProviderhistroySearch($query, $searchText='') {
        if ($searchText != '') {
            $result =  $query
            ->where('booking_id', 'like', "%" . $searchText . "%")
            ->orWhere('status', 'like', "%" . $searchText . "%")
            ->orWhere('s_address', 'like', "%" . $searchText . "%")
            ->orWhere('d_address', 'like', "%" . $searchText . "%")
            ->orwhereHas('payment', function ($q) use ($searchText){
            $q->where('total', 'like', "%" . $searchText . "%");
                });
            
        }
        return $result;
    }

     public function scopeHistoryUserTrips($query, $user_id,$showType='')
    {
        if($showType !=''){
          if($showType == 'past'){
                $history_status = array('CANCELLED','COMPLETED');
          }else if($showType=='upcoming'){
                $history_status = array('SCHEDULED');
          }else{
                $history_status = array('SEARCHING','ACCEPTED','STARTED','ARRIVED','PICKEDUP','DROPPED');
          }
        return $query->where('delivery_requests.user_id', $user_id)
                    ->whereIn('delivery_requests.status',$history_status)
                    ->orderBy('delivery_requests.created_at','desc');
        }else{
            
        }
    }

     public function scopeHistoryProvider($query, $provider_id,$historyStatus)
    {
        return $query->where('provider_id', $provider_id)
                    ->whereIn('status',$historyStatus)
                    ->orderBy('created_at','desc');
    }



    public function getCreatedTimeAttribute() {
        return (isset($this->attributes['created_at'])) ? (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->attributes['created_at'], 'UTC'))->setTimezone($this->attributes['timezone'])->format(Helper::dateFormat(1)) : '' ;
    }
    public function getAssignedTimeAttribute() {
        return (isset($this->attributes['assigned_at'])) ? (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->attributes['assigned_at'], 'UTC'))->setTimezone($this->attributes['timezone'])->format(Helper::dateFormat(1)) : '';
    }

    public function getScheduleTimeAttribute() {
        return (isset($this->attributes['schedule_at'])) ? (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->attributes['schedule_at'], 'UTC'))->setTimezone($this->attributes['timezone'])->format(Helper::dateFormat(1)) : '' ;
        
    }

    public function getStartedTimeAttribute() {
        return (isset($this->attributes['started_at'])) ? (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->attributes['started_at'], 'UTC'))->setTimezone($this->attributes['timezone'])->format(Helper::dateFormat(1)) : '' ;
        
    }

    public function getFinishedTimeAttribute() {
        return (isset($this->attributes['finished_at'])) ? (\Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $this->attributes['finished_at'], 'UTC'))->setTimezone($this->attributes['timezone'])->format(Helper::dateFormat(1)) : '' ;
        
    }
    
    /**
     * UserRequestPayment Model Linked
     */
    public function payment()
    {
        return $this->hasOne('App\Models\Delivery\DeliveryPayment', 'delivery_id');
    }

    /**
     * UserRequestRating Model Linked
     */
    public function rating()
    {
        return $this->hasOne('App\Models\Common\Rating','request_id')->where('admin_service', 'DELIVERY');
    }

    /**
     * UserRequestRating Model Linked
     */
    public function filter()
    {
        return $this->hasMany('App\Models\Transport\RideFilter', 'ride_request_id');
    }

    /**
     * The user who created the request.
     */
    public function user()
    {
        return $this->belongsTo('App\Models\Common\User');
    }

    /**
     * The provider assigned to the request.
     */
    public function provider()
    {
        return $this->belongsTo('App\Models\Common\Provider', 'provider_id');
    }

    public function provider_vehicle()
    {
        return $this->hasOne('App\Models\Common\ProviderVehicle', 'id', 'provider_vehicle_id');
    }

    public function service()
    {
       return $this->belongsTo('App\Models\Delivery\DeliveryVehicle', 'delivery_vehicle_id');
    }

    public function delivery()
    {
       return $this->hasOne('App\Models\Delivery\Delivery', 'delivery_request_id');
    }

    public function deliveries()
    {
       return $this->hasMany('App\Models\Delivery\Delivery', 'delivery_request_id');
    }

    public function delivery_type()
    {
       return $this->belongsTo('App\Models\Delivery\DeliveryType', 'delivery_type_id');
    }

    public function chat()
    {
       return $this->hasOne('App\Models\Common\Chat', 'request_id');
    }

    public function service_type()
    {
        return $this->belongsTo('App\Models\Common\ProviderService', 'provider_id', 'provider_id');
    }

    public function scopePendingRequest($query, $user_id)
    {
        return $query->where('user_id', $user_id)
                ->whereNotIn('status' , ['CANCELLED', 'SCHEDULED'])
                ->where(function($q){
                    $q->where('paid', '<>', 1)
                        ->orWhereNull('paid');
                    }
                );
    }

	public function scopeDeliveryRequestStatusCheck($query, $user_id, $check_status, $admin_service,$type)
    {
        return $query->where('delivery_requests.user_id', $user_id)
                    ->where('delivery_requests.user_rated',0)
                    ->whereNotIn('delivery_requests.status', $check_status)
                    ->select('delivery_requests.*')
                    ->with(['user','provider','service_type' => function($query) use($admin_service,$type) {
                         $query->where('admin_service', $admin_service);
                         if($type!=0)
                         $query->where('delivery_vehicle_id',$type);
                    },'service','deliveries.payment','service_type.vehicle','payment','rating','chat','deliveries.package_type']);
    }

    

	public function scopeDeliveryRequestAssignProvider($query, $user_id, $check_status)
    {
        return $query->where('delivery_requests.user_id', $user_id)
                    ->whereNull('delivery_requests.provider_id')
                    ->whereIn('delivery_requests.status', $check_status)
                    ->select('delivery_requests.*');
    }
    public function scopeUserTrips($query, $user_id)
    {
        return $query->where('delivery_requests.user_id', $user_id)
                    ->where('delivery_requests.status','!=','SCHEDULED')
                    ->orderBy('delivery_requests.created_at','desc')
                    ->select('delivery_requests.*');
    }

    public function scopeUserUpcomingTrips($query, $user_id)
    {
        return $query->where('delivery_requests.user_id', $user_id)
                    ->where('delivery_requests.status', 'SCHEDULED')
                    ->orderBy('delivery_requests.created_at','desc')
                    ->select('delivery_requests.*');
    }
    public function dispute() 
    {
        return $this->belongsTo('App\Models\Delivery\DeliveryRequestDispute','id','delivery_request_id');
    }
}

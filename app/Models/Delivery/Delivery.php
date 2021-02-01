<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;

class Delivery extends BaseModel
{
    protected $connection = 'delivery';

    protected $fillable = [
        'provider_id',
        'user_id',
        'service_type_id',
        'promocode_id',
        'status',
        'cancelled_by',
        'otp',
        'distance',
        'd_latitude',
        'd_longitude',
        'paid',
        'd_address',
        'assigned_at',
        'schedule_at',
        'is_scheduled',
        'started_at',
        'finished_at',
        'use_wallet',
        'user_rated',
        'provider_rated',
        'company_id'      
    ];

    protected $hidden = [
     	'created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
    ];

    public function delivery_vehicle()
    {
       return $this->belongsTo('App\Models\Delivery\DeliveryVehicle', 'delivery_vehicle_id');
    }

     public function delivery_request()
    {
       return $this->belongsTo('App\Models\Delivery\DeliveryRequest', 'delivery_request_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Common\User');
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Common\Provider', 'provider_id');
    }

    public function package_type()
    {
       return $this->belongsTo('App\Models\Delivery\PackageType', 'package_type_id');
    }

    public function delivery_type()
    {
       return $this->belongsTo('App\Models\Delivery\DeliveryType', 'package_type_id');
    }

    public function payment()
    {
        return $this->hasOne('App\Models\Delivery\DeliveryPayment', 'delivery_id');
    }

    public function chat()
    {
       return $this->hasOne('App\Models\Common\Chat', 'request_id');
    }

    /**
     * UserRequestRating Model Linked
     */
    public function rating()
    {
        return $this->hasOne('App\Models\Common\Rating', 'request_id');
    }

    public function scopeDeliveryRequestStatusCheck($query, $user_id, $check_status, $admin_service,$type)
    {
        return $query->where('deliveries.user_id', $user_id)
                    ->where('deliveries.user_rated',0)
                    ->whereNotIn('deliveries.status', $check_status)
                    ->select('deliveries.*')
                    ->with(['user','provider','service_type' => function($query) use($admin_service,$type) {
                         $query->where('admin_service', $admin_service);
                         if($type!=0)
                         $query->where('delivery_vehicle_id',$type);
                    },'delivery','service_type.vehicle','payment','rating','chat']);
    }

    public function scopeDeliveryRequestAssignProvider($query, $user_id, $check_status)
    {
        return $query->where('deliveries.user_id', $user_id)
                    ->whereNull('deliveries.provider_id')
                    ->whereIn('deliveries.status', $check_status)
                    ->select('deliveries.*');
    }
}

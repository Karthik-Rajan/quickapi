<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;
use Auth;
class DeliveryType extends BaseModel
{
    protected $connection = 'delivery';

    protected $fillable = [
        'company_id','delivery_name','status','delivery_category_id'
    ];

    protected $hidden = [
     	'company_id', 'created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
    ];

    public function servicelist() {
        return $this->hasMany('\App\Models\Delivery\DeliveryVehicle','delivery_type_id','id')->where('status',1);
    }

    public function providerservicelist() {
        return $this->hasOne('App\Models\Common\ProviderService','category_id','id');
    }

/*    public function providerservice() {
        return $this->hasOne('App\Models\Common\ProviderService','category_id','id')->where('admin_service','DELIVERY')->where('provider_id',Auth::guard('provider')->user()->id)->with('providervehicle');
    }*/

    public function providerservice() {
        return $this->hasOne('App\Models\Common\ProviderService','category_id','id')->where('admin_service','DELIVERY')->where('provider_id',Auth::guard('provider')->user()->id)->with(['providervehicle'=>function($q){
            $q->where('admin_service','DELIVERY');
        }]);
    }

}

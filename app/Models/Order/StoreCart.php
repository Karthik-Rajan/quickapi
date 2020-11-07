<?php

namespace App\Models\Order;

use App\Models\BaseModel;

use Illuminate\Database\Eloquent\Model;

class StoreCart extends Model
{
    protected $connection = 'order';

    protected $hidden = [
     	'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
     ];

    public function product()
    {
        return $this->hasOne('App\Models\Order\StoreItem','id','store_item_id')->select('id','item_name','item_price','item_description','is_veg','quantity','unit_id','picture','item_discount','item_discount_type','status');
    }

     public function store() {
        return $this->hasOne('App\Models\Order\Store','id','store_id')->select('store_name','currency_symbol','picture','rating','store_packing_charges','store_gst','commission','offer_min_amount','offer_percent','free_delivery','id','store_type_id','latitude','longitude','city_id','free_delivery_limit');
      }


      public function cartaddon() {
        return $this->hasMany('App\Models\Order\StoreCartItemAddon','store_cart_id','id');
      }

}

<?php

namespace App\Models\Order;

use App\Models\BaseModel;

class StoreItem extends BaseModel
{
    protected $connection = 'order';

    protected $casts = [
        'item_price'    => 'float',
        'item_discount' => 'float',
    ];

    protected $hidden = [
        'company_id', 'created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'updated_at', 'deleted_at',
    ];

    public function scopeSearch($query, $searchText = '')
    {
        return $query
            ->where('item_name', 'like', "%" . $searchText . "%")
            ->orwhere('item_description', 'like', "%" . $searchText . "%");

    }

    public function itemsaddon()
    {
        return $this->hasMany('App\Models\Order\StoreItemAddon', 'store_item_id', 'id');
    }

    public function unit()
    {
        return $this->belongsTo('App\Models\Common\Unit', 'unit_id', 'id')->select('id', 'name');
    }

    public function brand()
    {
        return $this->belongsTo('App\Models\Order\Brand', 'brand_id', 'id');
    }

    public function attribute()
    {
        return $this->belongsTo('App\Models\Order\Attribute', 'attribute_id', 'id');
    }

    public function attribute_value()
    {
        return $this->belongsTo('App\Models\Order\AttributeValue', 'attribute_value_id', 'id');
    }

    public function store()
    {
        return $this->hasOne('App\Models\Order\Store', 'id', 'store_id')->select('store_name', 'store_packing_charges', 'store_gst', 'commission', 'offer_min_amount', 'offer_percent', 'free_delivery', 'id', 'rating', 'estimated_delivery_time', 'currency_symbol');
    }

    public function itemcart()
    {
        return $this->hasMany('App\Models\Order\StoreCart', 'store_item_id', 'id');
    }

    public function itemcartaddon()
    {
        return $this->hasMany('App\Models\Order\StoreCartItemAddon', 'store_cart_item_id', 'id')->select('store_item_addons_id', 'store_cart_item_id');
    }

    public function categories()
    {
        return $this->belongsTo('App\Models\Order\StoreCategory', 'store_category_id', 'id');
    }

    public function attributeValue()
    {
        return $this->hasMany('App\Models\Order\AttributeValue', 'attribute_id', 'attribute_id');
    }

    public function scopeActive($query)
    {
        $expireAt = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        return $query
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', $expireAt)
            ->where('status', '!=', 2);
    }

}

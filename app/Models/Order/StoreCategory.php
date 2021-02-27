<?php

namespace App\Models\Order;

use App\Models\BaseModel;

class StoreCategory extends BaseModel
{
    protected $connection = 'order';

    protected $hidden = [
     	'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
     ];
    protected $appends = ['category_name'];

    public function scopeSearch($query, $searchText='') {
        return $query
          ->where('store_category_name', 'like', "%" . $searchText . "%")
          ->orWhere('store_category_description', 'like', "%" . $searchText . "%");
               
    }

    public function store() {
        return $this->hasOne('App\Models\Order\Store','id','store_id'); 
      }

    /**
     * Products belonging to the category
     */
    public function products()
    {
        return $this->hasMany('App\Models\Order\StoreItem','id','store_category_id');;
    } 
    public function parentCategory()
    {
        return $this->belongsTo('App\Models\Order\StoreCategory','parent_id','id');
    }
    public function childCategories()
    {
        return $this->hasMany('App\Models\Order\StoreCategory','parent_id');
    }
    public function getCategoryNameAttribute() {
        if(empty($this->parentCategory))
            return "";
        else
            return $this->parentCategory->store_category_name;

    } 
  }

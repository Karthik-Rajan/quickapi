<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use Auth;

class Document extends BaseModel
{
	protected $connection = 'common';
	
    protected $fillable = [
        'name',
        'type',
        'company_id'
    ];

    protected $hidden = [
     	'created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
    ];

    public function scopeSearch($query, $searchText='') {
        return $query
            ->where('name', 'like', "%" . $searchText . "%")
            ->orWhere('type', 'like', "%" . $searchText . "%");
    }
    public function provider_document()
    {
        return $this->belongsTo('App\Models\Common\ProviderDocument', 'id', 'document_id')->where('provider_id',Auth::guard('provider')->user()->id);
    }

    public function service()
    {
       return $this->belongsTo('App\Models\Common\AdminService', 'service', 'admin_service');
    }
    public function serviceCategory()
    {
        return $this->belongsTo('App\Models\Service\ServiceCategory','service_category_id',"id");
    }
    public function servicesubCategory()
    {
        return $this->belongsTo('App\Models\Service\ServiceSubcategory','service_subcategory_id','id');
    }
    public function subCategories()
    {
        return $this->hasMany('App\Models\Service\ServiceSubcategory', 'id','service_subcategory_id');
    }
}

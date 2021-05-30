<?php


namespace App\Models\Order;

use App\Models\BaseModel;
class StoreDrug extends BaseModel
{
     protected $connection = 'order';
    protected $hidden = [
        'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
     ];
     public function scopeSearch($query, $searchText='') {
        return $query
           ->whereHas('storetype', function ($q) use ($searchText){
                            $q->where('name', 'like', "%" . $searchText . "%");
                  })
            ->orwhere('name', 'like', "%" . $searchText . "%");
            
    }
}

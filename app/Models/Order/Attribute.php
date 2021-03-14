<?php

namespace App\Models\Order;

use App\Models\BaseModel;
class Attribute extends BaseModel
{
    protected $connection = 'order';
	
     protected $hidden = [
        'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
     ];
    public function attributeValues()
    {
        return $this->hasMany('App\Models\Order\AttributeValue');
    }
}

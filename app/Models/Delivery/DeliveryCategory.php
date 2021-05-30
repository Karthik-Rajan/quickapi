<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;


class DeliveryCategory extends BaseModel
{
    protected $connection = 'delivery';

    protected $fillable = [
        'status',
        'delivery_name',
        'company_id'      
    ];

    protected $hidden = [
     	'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
     ];
}

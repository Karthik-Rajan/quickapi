<?php

namespace App\Models\Order;

use App\Models\BaseModel;

class StoreCityPrice extends BaseModel
{
    protected $connection = 'order';

    protected $casts = [
        'delivery_charge' => 'float',
        'tax' => 'float'
    ];

    protected $hidden = [
     	'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
     ];
}

<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;

class DeliveryPayment extends BaseModel
{
    protected $connection = 'delivery';

    protected $casts = [
        'fixed' => 'float',
        'price' => 'float',
        'weight' => 'float',
        'distance' => 'float',
        'commission' => 'float',
        'fleet' => 'float',
        'tax' => 'float',
        'peak_commission' => 'float',
        'discount' => 'float'
    ];

    protected $hidden = [
     	'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
    ];
}

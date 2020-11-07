<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;

class DeliveryCity extends BaseModel
{
    protected $connection = 'delivery';

    protected $casts = [
        'comission' => 'float',
        'fleet_comission' => 'float',
        'tax' => 'float',
        'surge' => 'float',
        'driver_beta_amount' => 'float',
        'fleet_commission' => 'float',
        'peak_percentage' => 'float',
        'commission' => 'float',
        'fleet_commission' => 'float'
    ];

    protected $hidden = [
     	'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
     ];
}

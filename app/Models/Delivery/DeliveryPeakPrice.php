<?php

namespace App\Models\Delivery;

use App\Models\BaseModel;

class DeliveryPeakPrice extends BaseModel
{
    protected $connection = 'delivery';


    protected $hidden = [
     	'created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
    ];
}

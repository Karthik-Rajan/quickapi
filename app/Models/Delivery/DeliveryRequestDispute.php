<?php

namespace App\Models\Delivery;

use Illuminate\Database\Eloquent\Model;
use App\Models\BaseModel;

class DeliveryRequestDispute extends BaseModel
{
    protected $connection = 'delivery';


    protected $hidden = [
     	'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
    ];
}

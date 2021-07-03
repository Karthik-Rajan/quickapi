<?php

namespace App\Models\Order;

use App\Models\BaseModel;

class StoreOrderStatus extends BaseModel
{
    protected $connection = 'order';

    protected $hidden = [
        'company_id', 'created_type', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'updated_at', 'deleted_at',
    ];
}

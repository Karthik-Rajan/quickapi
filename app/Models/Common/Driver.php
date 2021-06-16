<?php

namespace App\Models\Common;

use App\Models\BaseModel;

class Driver extends BaseModel
{
    protected $connection = 'common';

    protected $fillable = [
        'first_name', 'last_name', 'status', 'phone',
    ];
}

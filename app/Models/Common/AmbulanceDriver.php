<?php

namespace App\Models\Common;

use App\Models\BaseModel;

class AmbulanceDriver extends BaseModel
{
    protected $connection = 'common';

    protected $table = 'ambulance_driver';

    protected $fillable = ['driver_id', 'ambulance_id', 'status', 'created_by', 'updated_by'];

    public function driver()
    {
        return $this->hasOne('App\Models\Common\Driver', 'id', 'driver_id');
    }
}

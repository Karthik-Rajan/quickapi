<?php

namespace App\Models\Common;

use App\Models\BaseModel;

class Ambulance extends BaseModel
{
    protected $connection = 'common';

    protected $table = 'ambulance';

    public function assigned()
    {
        return $this->hasOne('App\Models\Common\AmbulanceDriver', 'ambulance_id', 'id');
    }
}

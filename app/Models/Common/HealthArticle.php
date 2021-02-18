<?php

namespace App\Models\Common;

use App\Models\BaseModel;
use Auth;

class HealthArticle extends BaseModel
{
    protected $connection = 'common';

    protected $hidden = [
     	'company_id','created_at', 'updated_at', 'deleted_at'
     ];



}

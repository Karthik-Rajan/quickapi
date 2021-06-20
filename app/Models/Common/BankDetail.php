<?php

namespace App\Models\Common;

use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    protected $connection = 'common';
    protected $table      = 'bankdetails';
}

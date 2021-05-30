<?php

namespace App\Models\Transport;

use App\Models\BaseModel;

class RideRequestPayment extends BaseModel
{
	protected $connection = 'transport';

	protected $hidden = [
		'company_id','created_type', 'created_by', 'modified_type', 'modified_by', 'deleted_type', 'deleted_by', 'created_at', 'updated_at', 'deleted_at'
	];

	protected $casts = [
		'fixed' => 'float',
		'distance' => 'float',
		'minute' => 'float',
		'hour' => 'float',
		'commision' => 'float',
		'commision_percent' => 'float',
		'fleet' => 'float',
		'fleet_percent' => 'float',
		'discount' => 'float',
		'discount_percent' => 'float',
		'tax' => 'float',
		'tax_percent' => 'float',
		'wallet' => 'float',
		'cash' => 'float',
		'card' => 'float',
		'peak_amount' => 'float',
		'peak_comm_amount' => 'float',
		'waiting_amount' => 'float',
		'waiting_comm_amount' => 'float',
		'tips' => 'float',
		'toll_charge' => 'float',
		'round_of' => 'float',
		'geo_fencing_distance' => 'float',
		'geo_fencing_total' => 'float',
		'driver_beta_amount' => 'float',
		'night_fare_amount' => 'float',
		'total' => 'float',
		'payable' => 'float',
		'provider_pay' => 'float',
	];
	 protected $appends = ['sub_total','total_fare'];
	public function getSubTotalAttribute() {

       $sub_total = $this->fixed + $this->distance + $this->minute + $this->hour +$this->peak_amount + $this->waiting_amount +$this->tax - $this->discount;
        return $sub_total;
        
    }
    public function getTotalFareAttribute() {

        $total_fare = $this->fixed + $this->distance + $this->minute + $this->hour +$this->peak_amount + $this->waiting_amount ;

        return $total_fare;
        
    }
    public function promoCode()
    {
       return $this->belongsTo('App\Models\Common\Promocode', 'promocode_id');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\Common\User');
    }

    public function provider()
    {
        return $this->belongsTo('App\Models\Common\Provider', 'provider_id');
    }

}

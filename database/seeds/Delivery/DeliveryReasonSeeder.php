<?php

use Illuminate\Database\Seeder;

class DeliveryReasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($company = null)
    {
        Schema::disableForeignKeyConstraints();

        DB::table('reasons')->insert([
            ['company_id' => $company, 'service' => 'DELIVERY', 'type' =>'USER', 'reason' => 'Booked Wrongly', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'DELIVERY', 'type' =>'PROVIDER', 'reason' => 'User dint come to location for pickup', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'DELIVERY', 'type' =>'USER', 'reason' => 'Driver didn\'t start for pickup', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'DELIVERY', 'type' =>'USER', 'reason' => 'Driver not contactable', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'DELIVERY', 'type' =>'USER', 'reason' => 'Driver got stuck in traffic', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'DELIVERY', 'type' =>'PROVIDER', 'reason' => 'User was not contactable', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'DELIVERY', 'type' =>'USER', 'reason' => 'Provider delayed pickup', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'DELIVERY', 'type' =>'PROVIDER', 'reason' => 'User mentioned wrong location', 'status' => 'Active']
        ]);
        
        Schema::enableForeignKeyConstraints();
    }
}

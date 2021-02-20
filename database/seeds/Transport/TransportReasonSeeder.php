<?php

use Illuminate\Database\Seeder;

class TransportReasonSeeder extends Seeder
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
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'PATIENT', 'reason' => 'Booked Wrongly', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'CCM', 'reason' => 'User dint come to location for pickup', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'PATIENT', 'reason' => 'Driver didn\'t start for pickup', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'PATIENT', 'reason' => 'Driver not contactable', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'PATIENT', 'reason' => 'Driver got stuck in traffic', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'CCM', 'reason' => 'User was not contactable', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'PATIENT', 'reason' => 'Driver does not follow correct route', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'PATIENT', 'reason' => 'Provider delayed pickup', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'CCM', 'reason' => 'User mentioned wrong location', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'CCM', 'reason' => 'Driver does not follow correct route', 'status' => 'Active'],
            ['company_id' => $company, 'service' => 'TRANSPORT', 'type' =>'CCM', 'reason' => 'Driver does not follow correct route', 'status' => 'Active']
        ]);
        
        Schema::enableForeignKeyConstraints();
    }
}

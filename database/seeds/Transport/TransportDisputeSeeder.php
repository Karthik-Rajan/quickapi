<?php

use Illuminate\Database\Seeder;

class TransportDisputeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($company = null)
    {
        Schema::disableForeignKeyConstraints();

        DB::table('disputes')->insert([
            ['service' => 'TRANSPORT', 'dispute_type' => 'Patient', 'dispute_name' => 'CCM rude and arrogant', 'status' =>'active', 'admin_services' => 'TRANSPORT', 'company_id' =>$company],
            ['service' => 'TRANSPORT', 'dispute_type' => 'provider', 'dispute_name' => 'Customer arrogant and rude', 'status' =>'active', 'admin_services' => 'TRANSPORT', 'company_id' =>$company],
            ['service' => 'TRANSPORT', 'dispute_type' => 'Patient', 'dispute_name' => 'CCM Asked Extra Amount', 'status' =>'active', 'admin_services' => 'TRANSPORT', 'company_id' =>$company],
            ['service' => 'TRANSPORT', 'dispute_type' => 'provider', 'dispute_name' => 'Patient entered  wrong destination', 'status' =>'active', 'admin_services' => 'TRANSPORT', 'company_id' =>$company],
            ['service' => 'TRANSPORT', 'dispute_type' => 'Patient', 'dispute_name' => 'My Promocode does not get applied', 'status' =>'active', 'admin_services' => 'TRANSPORT', 'company_id' =>$company],
            ['service' => 'TRANSPORT', 'dispute_type' => 'Patient', 'dispute_name' => 'Driver followed wrong route', 'status' =>'active', 'admin_services' => 'TRANSPORT', 'company_id' =>$company],
            ['service' => 'TRANSPORT', 'dispute_type' => 'provider', 'dispute_name' => 'Patient changed multiple destination', 'status' =>'active', 'admin_services' => 'TRANSPORT', 'company_id' =>$company]      
        ]);
        
        Schema::enableForeignKeyConstraints();
    }
}

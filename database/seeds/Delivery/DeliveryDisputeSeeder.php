<?php

use Illuminate\Database\Seeder;

class DeliveryDisputeSeeder extends Seeder
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
            ['service' => 'DELIVERY', 'dispute_type' => 'user', 'dispute_name' => 'Provider rude and arrogant', 'status' =>'active', 'admin_services' => 'DELIVERY', 'company_id' =>$company],
            ['service' => 'DELIVERY', 'dispute_type' => 'provider', 'dispute_name' => 'Customer arrogant and rude', 'status' =>'active', 'admin_services' => 'DELIVERY', 'company_id' =>$company],
            ['service' => 'DELIVERY', 'dispute_type' => 'user', 'dispute_name' => 'Provider Asked Extra Amount', 'status' =>'active', 'admin_services' => 'DELIVERY', 'company_id' =>$company],
            ['service' => 'DELIVERY', 'dispute_type' => 'user', 'dispute_name' => 'My Promocode does not get applied', 'status' =>'active', 'admin_services' => 'DELIVERY', 'company_id' =>$company]    
        ]);
        
        Schema::enableForeignKeyConstraints();
    }
}

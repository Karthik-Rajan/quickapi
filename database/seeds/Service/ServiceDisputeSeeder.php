<?php

use Illuminate\Database\Seeder;

class ServiceDisputeSeeder extends Seeder
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
            ['service' => 'SERVICE', 'dispute_type' => 'PATIENT', 'dispute_name' => 'CCM asked extra amount', 'status' =>'active', 'admin_services' => 'SERVICE', 'company_id' =>$company],
            ['service' => 'SERVICE', 'dispute_type' => 'CCM', 'dispute_name' => 'Customer denied to pay amount', 'status' =>'active', 'admin_services' => 'SERVICE', 'company_id' =>$company],
            ['service' => 'SERVICE', 'dispute_type' => 'PATIENT', 'dispute_name' => 'My wallet amount does not deducted', 'status' =>'active', 'admin_services' => 'SERVICE', 'company_id' =>$company],
            ['service' => 'SERVICE', 'dispute_type' => 'PATIENT', 'dispute_name' => 'Promocode amount does not reduced', 'status' =>'active', 'admin_services' => 'SERVICE', 'company_id' =>$company],
            ['service' => 'SERVICE', 'dispute_type' => 'PATIENT', 'dispute_name' => 'CCM incompleted the service', 'status' =>'active', 'admin_services' => 'SERVICE', 'company_id' =>$company],
            ['service' => 'SERVICE', 'dispute_type' => 'CCM', 'dispute_name' => 'Patient provided wrong service information', 'status' =>'active', 'admin_services' => 'SERVICE', 'company_id' =>$company],
            ['service' => 'SERVICE', 'dispute_type' => 'CCM', 'dispute_name' => 'Patient neglected to pay additional charge', 'status' =>'active', 'admin_services' => 'SERVICE', 'company_id' =>$company] ,
            ['service' => 'SERVICE', 'dispute_type' => 'CCM', 'dispute_name' => 'Patient provided less amount', 'status' =>'active', 'admin_services' => 'SERVICE', 'company_id' =>$company]       
        ]);
        
        Schema::enableForeignKeyConstraints();
    }
}

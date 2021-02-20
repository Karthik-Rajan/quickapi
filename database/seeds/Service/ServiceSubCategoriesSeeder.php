<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Common\CompanyCity;
use App\Models\Common\Provider;
use App\Models\Common\Menu;
use Carbon\Carbon;

class ServiceSubCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($company = null)
    {
    	Schema::connection('service')->disableForeignKeyConstraints();

        $service = DB::table('admin_services')->where('admin_service', 'SERVICE')->first();


        $Doctors = DB::connection('service')->table('service_categories')->where('company_id', $company)->where('service_category_name', 'Doctors')->first()->id;
       

        $service_subcategories = [
           
['service_category_id' => $Doctors, 'company_id' => $company, 'service_subcategory_name' => 'General Physician', 'picture' => '', 'service_subcategory_order' => 0, 'service_subcategory_status' => 1],
['service_category_id' => $Doctors, 'company_id' => $company, 'service_subcategory_name' => 'Cardiologist', 'picture' => '', 'service_subcategory_order' => 0, 'service_subcategory_status' => 1],
['service_category_id' => $Doctors, 'company_id' => $company, 'service_subcategory_name' => 'Dermatologist', 'picture' => '', 'service_subcategory_order' => 0, 'service_subcategory_status' => 1]
        ];

        foreach (array_chunk($service_subcategories,1000) as $service_subcategory) {
            DB::connection('service')->table('service_subcategories')->insert($service_subcategory);
        }

        

	    Schema::connection('service')->enableForeignKeyConstraints();
    }
}

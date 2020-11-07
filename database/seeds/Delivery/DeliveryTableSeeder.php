<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Delivery\DeliveryVehicle;
use App\Models\Common\ProviderVehicle;
use App\Models\Delivery\DeliveryCategory;
use App\Models\Delivery\PackageType;
use App\Models\Delivery\DeliveryType;
use App\Models\Common\CompanyCity;
use App\Models\Common\Provider;
use App\Models\Common\Menu;
use Carbon\Carbon;

class DeliveryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($company = 1)
    {
        Schema::disableForeignKeyConstraints();

        PackageType::create([
            'company_id' => $company,
            'package_name' => 'Electronics',
            'status' => '1'
        ]);

        PackageType::create([
            'company_id' => $company,
            'package_name' => 'Medicine',
            'status' => '1'
        ]);

        $courier_category = DeliveryCategory::create([
            'company_id' => $company,
            'delivery_name' => 'Courier',
            'status' => '1'
        ]);

        $box = DeliveryType::create([
            'company_id' => $company,
            'delivery_category_id' => $courier_category->id,
            'delivery_name' => 'Box',
            'status' => '1'
        ]);

        $moto = DeliveryType::create([
            'company_id' => $company,
            'delivery_category_id' => $courier_category->id,
            'delivery_name' => 'Moto',
            'status' => '1'
        ]);
        

        $box_delivery_vehicles = [
            ['name' => 'Cargo Car', 'vehicle_image' => url('/').'/images/delivery/cargo-car.png', 'vehicle_marker' => url('/').'/images/delivery/sedan_marker.png', 'max_weight' => 400, 'max_length' => 10, 'max_width' => 10, 'max_height' => 10 ],
            ['name' => 'Mini Truck', 'vehicle_image' => url('/').'/images/delivery/mini-truck.png', 'vehicle_marker' => url('/').'/images/delivery/grey-car_marker.png', 'max_weight' => 10, 'max_length' => 10, 'max_width' => 10, 'max_height' => 10],
            ['name' => 'Truck', 'vehicle_image' => url('/').'/images/delivery/truck.png', 'vehicle_marker' => url('/').'/images/delivery/grey-car_marker.png', 'max_weight' => 10, 'max_length' => 10, 'max_width' => 10, 'max_height' => 10]
        ];
        

        $moto_delivery_vehicles = [
            ['name' => 'Scooter', 'vehicle_image' => url('/').'/images/delivery/scooter.png', 'vehicle_marker' => url('/').'/images/delivery/scooter_marker.png', 'max_weight' => 10, 'max_length' => 10, 'max_width' => 10, 'max_height' => 10],
            ['name' => 'Moto Bike', 'vehicle_image' => url('/').'/images/delivery/sports_bike.png', 'vehicle_marker' => url('/').'/images/delivery/grey-scooter_marker.png', 'max_weight' => 10, 'max_length' => 10, 'max_width' => 10, 'max_height' => 10]
        ];

        $delivery_vehicle_data = [];

        foreach($box_delivery_vehicles as $box_delivery_vehicle) {
            $delivery_vehicle_data[] = [
                'delivery_type_id' => $box->id,
                'vehicle_type' => 'DELIVERY',
                'vehicle_name' => $box_delivery_vehicle['name'],
                'vehicle_image' => $box_delivery_vehicle['vehicle_image'],
                'vehicle_marker' => $box_delivery_vehicle['vehicle_marker'],
                'vehicle_marker' => $box_delivery_vehicle['max_weight'],
                'vehicle_marker' => $box_delivery_vehicle['max_length'],
                'vehicle_marker' => $box_delivery_vehicle['max_width'],
                'vehicle_marker' => $box_delivery_vehicle['max_height'],
                'company_id' => $company
            ];
        }

        foreach($moto_delivery_vehicles as $moto_delivery_vehicle) {
            $delivery_vehicle_data[] = [
                'delivery_type_id' => $moto->id,
                'vehicle_type' => 'DELIVERY',
                'vehicle_name' => $moto_delivery_vehicle['name'],
                'vehicle_image' => $moto_delivery_vehicle['vehicle_image'],
                'vehicle_marker' => $moto_delivery_vehicle['vehicle_marker'],
                'vehicle_marker' => $moto_delivery_vehicle['max_weight'],
                'vehicle_marker' => $moto_delivery_vehicle['max_length'],
                'vehicle_marker' => $moto_delivery_vehicle['max_width'],
                'vehicle_marker' => $moto_delivery_vehicle['max_height'],
                'company_id' => $company
            ];
        }

        DB::connection('delivery')->table('delivery_vehicles')->insert($delivery_vehicle_data);

        $courier = Menu::create([
            'bg_color' => '#0097FF',
            'icon' => url('/').'/images/menus/delivery.png',
            'title' => 'Courier',
            'admin_service' => 'DELIVERY',
            'menu_type_id' => $courier_category->id,
            'company_id' => $company,
            'sort_order' => 1
        ]);
        

        $company_cities = CompanyCity::where('company_id', $company)->get();

        $menu_city_data = [];
        $delivery_city_prices = [];
        $delivery_cities = [];

        $delivery_vehicles_list = DB::connection('delivery')->table('delivery_vehicles')->where('company_id', $company)->get();

        foreach ($company_cities as $company_city) {

            $menu_city_data[] = [
                'menu_id' => $courier->id,
                'country_id' => $company_city->country_id,           
                'state_id' => $company_city->state_id,             
                'city_id' => $company_city->city_id,
                'status' => '1'
            ];

            $delivery_cities[] = [
                'company_id' => $company,
                'country_id' => $company_city->country_id,           
                'city_id' => $company_city->city_id,             
                'admin_service' => 'DELIVERY',
                'comission' => '1',
                'fleet_comission' => '1',
                'tax' => '1'
            ];


            foreach($delivery_vehicles_list as $delivery) {
                $delivery_city_prices[] = [
                    'company_id' => $company,
                    'fixed' => '50',           
                    'city_id' => $company_city->city_id,             
                    'delivery_vehicle_id' => $delivery->id,
                    'calculator' => 'DISTANCE'
                ];
            }
        }
        

        if(count($menu_city_data) > 0) {
            foreach (array_chunk($menu_city_data,1000) as $menu_city_datum) {
                DB::table('menu_cities')->insert($menu_city_datum);
            }
        }

        if(count($delivery_cities) > 0) {
            foreach (array_chunk($delivery_cities,1000) as $delivery_city) {
                DB::connection('delivery')->table('delivery_cities')->insert($delivery_city);
            }
        }

        if(count($delivery_city_prices) > 0) {
            foreach (array_chunk($delivery_city_prices,1000) as $delivery_city_price) {
                DB::connection('delivery')->table('delivery_city_prices')->insert($delivery_city_price);
            }
        }
        

        $providers = Provider::where('company_id', $company)->get();

        foreach ($providers as $provider) {

            $provider_vehicle = new ProviderVehicle();
            $delivery_vehicle = DeliveryVehicle::where('company_id', $company)->where('status', 1)->first();
            $provider_vehicle->provider_id = $provider->id;
            $provider_vehicle->company_id = $company;
            $provider_vehicle->vehicle_model = 'BMW X6';
            $provider_vehicle->vehicle_no = '3D0979';
            $provider_vehicle->vehicle_year = '2019';
            $provider_vehicle->vehicle_color = 'Black';
            $provider_vehicle->vehicle_make = 'BMW';
            $provider_vehicle->admin_service = 'DELIVERY';
            $provider_vehicle->vehicle_service_id = $delivery_vehicle->id;
            $provider_vehicle->save();

            DB::table('provider_services')->insert([
                [
                    'provider_id' => $provider->id,
                    'company_id' => $company,
                    'admin_service' => 'DELIVERY',
                    'provider_vehicle_id' => ($provider_vehicle != null) ? $provider_vehicle->id : null,
                    'delivery_vehicle_id' => ($delivery_vehicle != null) ? $delivery_vehicle->id : null,
                    'category_id' => $box->id,
                    'status' => 'ACTIVE',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]
            ]);

        }

        Schema::enableForeignKeyConstraints();
    }
}

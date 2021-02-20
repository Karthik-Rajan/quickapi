<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Common\Provider;
use App\Models\Common\ProviderVehicle;
use App\Models\Common\ProviderService;
use App\Models\Common\User;
use App\Models\Common\Country;
use App\Models\Common\State;
use App\Models\Common\City;
use App\Models\Common\Menu;
use App\Models\Common\MenuCity;
use App\Models\Transport\RideDeliveryVehicle;
use App\Models\Transport\RideType;
use App\Traits\Encryptable;
use Carbon\Carbon;

class DemoTableSeeder extends Seeder
{
	use Encryptable;
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run($company = null)
	{
		Schema::disableForeignKeyConstraints();

		$country = Country::where('country_code', 'US')->first();
		$washington_state = State::where('state_name', 'Washington')->where('country_id', $country->id)->first()->id;
		$city = City::where('city_name', 'Charleston')->where('state_id', $washington_state)->first();

		$users = [
			[
				'first_name' => 'User',
				'last_name' => 'Demo',
				'unique_id' => 'USR1',
				'email' => 'demo@demo.com',
				'password' => Hash::make('123456'),
				'country_code' => '91',
				'iso2' => '91',
				'mobile' => '9876543210',
				'gender' => 'MALE',             
				'company_id' => $company,
				'country_id' => $city->country_id,
				'state_id' => $city->state_id,
				'city_id' => $city->id,
				'currency_symbol' => '$',
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
				'picture' => 'http://lorempixel.com/512/512/business/Ampro',
				'referral_unique_id' =>'9AB001',
				'qrcode_url' =>'',
			]
		];

		foreach ($users as $user) {
			User::create($user);
		}

		
		$providers = [
			[
				'first_name' => 'Provider',
				'last_name' => 'Demo',
				'unique_id' => 'PRV1',
				'email' => 'demo@demo.com',
				'password' => Hash::make('123456'),
				'country_code' => '91',
				'iso2' => '91',
				'mobile' => '9876543210',
				'gender' => 'MALE',
				'status' => 'APPROVED',
				'is_online' => '0',
				'is_service' => '1',
				'is_document' => '1',
				'is_bankdetail' => '1',
				'latitude' => '13.00',
				'longitude' => '80.00',               
				'company_id' => $company,            
				'country_id' => $city->country_id,           
				'state_id' => $city->state_id,
				'city_id' => $city->id,
				'currency' => 'USD',
				'currency_symbol' => '$',
				'created_at' => Carbon::now(),
				'updated_at' => Carbon::now(),
				'picture' => 'http://lorempixel.com/512/512/business/Ampro',
				'referral_unique_id' =>'9AB001',
				'qrcode_url' =>'',
			]
		];

		foreach ($providers as $provider) {
			Provider::create($provider);
		}

		Schema::enableForeignKeyConstraints();
	}
}

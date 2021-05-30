<?php

use Illuminate\Database\Seeder;

class UnitTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run($company = null)
    {
        Schema::disableForeignKeyConstraints();

        DB::table('units')->insert([
            ['company_id' => $company, 'name' => 'gm'],
            ['company_id' => $company, 'name' => 'kg'],
            ['company_id' => $company, 'name' => 'pcs'],
            ['company_id' => $company, 'name' => 'lr']     
        ]);
        
        Schema::enableForeignKeyConstraints();
    }
}

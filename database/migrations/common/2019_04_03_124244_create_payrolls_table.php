<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePayrollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id');
            $table->string('transaction_id')->nullable();
            $table->integer('zone_id')->nullable();
            $table->integer('provider_id')->nullable();
            $table->integer('shop_id')->nullable();
            $table->integer('fleet_id')->nullable();
            $table->enum('admin_service', ['TRANSPORT','ORDER','SERVICE','DELIVERY'])->nullable(); 
            $table->string('wallet')->nullable();
            $table->enum('type', [
                    'PROVIDER',
                    'FLEET',
                    'SHOP',
                    'STORE'
                ]);
            $table->enum('payroll_type', [
                    'MANUAL',
                    'ZONE'
                ])->default('MANUAL');
            $table->enum('status', [
                    'PENDING',
                    'CANCEL',
                    'COMPLETED'
                ])->default('PENDING');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payrolls');
    }
}

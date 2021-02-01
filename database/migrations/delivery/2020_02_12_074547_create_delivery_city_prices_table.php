<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryCityPricesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('delivery')->create('delivery_city_prices', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('geofence_id')->nullable();
            $table->unsignedInteger('city_id');
            $table->unsignedInteger('vehicle_service_id')->nullable();
            $table->unsignedInteger('delivery_vehicle_id');
            $table->unsignedInteger('company_id');
            $table->enum('calculator', ['DISTANCE','WEIGHT','DISTANCEWEIGHT']);
            $table->decimal('fixed', 10, 2)->default(0);
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('weight', 10, 2)->default(0);
            $table->decimal('distance', 10, 2)->default(0);
            $table->decimal('weight_price', 10, 2)->default(0);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('fleet_commission', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('peak_commission', 10, 2)->default(0);
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('pricing_differs')->default(0);
            $table->enum('created_type', ['ADMIN','USER','PROVIDER','SHOP'])->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->enum('modified_type', ['ADMIN','USER','PROVIDER','SHOP'])->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->enum('deleted_type', ['ADMIN','USER','PROVIDER','SHOP'])->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('delivery_city_prices');
    }
}

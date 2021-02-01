<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('delivery')->create('delivery_vehicles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('delivery_type_id')->nullable();
            $table->enum('vehicle_type', ['DELIVERY'])->nullable();
            $table->string('vehicle_name');
            $table->string('vehicle_image')->nullable();
            $table->string('vehicle_marker')->nullable();
            $table->unsignedInteger('weight')->default(0);
            $table->unsignedInteger('length')->default(0);
            $table->unsignedInteger('breadth')->default(0);
            $table->unsignedInteger('height')->default(0);
            $table->tinyInteger('status')->default(1);
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
        Schema::dropIfExists('delivery_vehicles');
    }
}

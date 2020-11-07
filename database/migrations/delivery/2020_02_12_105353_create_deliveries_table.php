<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('delivery')->create('deliveries', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('delivery_request_id')->nullable(); 
            $table->unsignedInteger('user_id')->nullable();
            $table->unsignedInteger('provider_id')->nullable();
            $table->unsignedInteger('geofence_id')->nullable();
            $table->unsignedInteger('package_type_id')->nullable();
            $table->enum('status', ['PROCESSING','CANCELLED','STARTED','DROPPED','COMPLETED'])->nullable();
            $table->enum('admin_service', ['TRANSPORT','ORDER','SERVICE','DELIVERY'])->nullable(); 
            $table->tinyInteger('paid')->default(0);
            $table->tinyInteger('provider_rated')->default(0);
            $table->double('distance', 10, 2)->nullable();
            $table->double('weight', 10, 2)->nullable();
            $table->double('length', 10, 2)->nullable();
            $table->double('breadth', 10, 2)->nullable();
            $table->double('height', 10, 2)->nullable();
            $table->text('location_points');
            $table->string('timezone')->nullable();
            $table->string('travel_time')->nullable();
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
            $table->string('payment_mode')->nullable();
            $table->text('instruction');
            $table->string('s_address')->nullable();
            $table->double('s_latitude', 15, 8);
            $table->double('s_longitude', 15, 8);
            $table->string('d_address')->nullable();
            $table->double('d_latitude', 15, 8);
            $table->double('d_longitude', 15, 8);
            $table->double('track_distance', 10, 2);
            $table->text('destination_log');
            $table->enum('unit', ['KMS','MILES']);
            $table->tinyInteger('is_fragile')->default(0)->nullable();
            $table->string('currency')->nullable();
            $table->double('track_latitude', 15, 8);
            $table->double('track_longitude', 15, 8);
            $table->string('otp')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('schedule_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->tinyInteger('surge')->default(0)->nullable();
            $table->longText('route_key')->nullable();
            $table->unsignedInteger('admin_id')->nullable();
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
        Schema::dropIfExists('deliveries');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('delivery')->create('delivery_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('delivery_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('provider_id');
            $table->unsignedInteger('fleet_id')->nullable();
            $table->unsignedInteger('promocode_id')->nullable();
            $table->string('payment_id')->nullable();
            $table->unsignedInteger('company_id');
            $table->string('payment_mode')->nullable();
            $table->float('fixed', 10, 2)->default(0);
            $table->float('distance', 10, 2)->default(0);
            $table->float('weight', 10, 2)->default(0);
            $table->float('commision', 10, 2)->default(0);
            $table->float('commision_percent', 10, 2)->default(0);
            $table->float('fleet', 10, 2)->default(0);
            $table->float('fleet_percent', 10, 2)->default(0);
            $table->float('discount', 10, 2)->default(0);
            $table->float('discount_percent', 10, 2)->default(0);
            $table->float('tax', 10, 2)->default(0);
            $table->float('tax_percent', 10, 2)->default(0);
            $table->float('wallet', 10, 2)->default(0);
            $table->tinyInteger('is_partial')->nullable();
            $table->float('cash', 10, 2)->default(0);
            $table->float('card', 10, 2)->default(0);
            $table->float('peak_amount', 10, 2)->default(0);
            $table->float('peak_comm_amount', 10, 2)->default(0);
            $table->integer('total_waiting_time')->default(0);            
            $table->float('tips', 10, 2)->default(0);
            $table->float('round_of',  10, 2)->default(0);
            $table->float('total', 10, 2)->default(0);
            $table->float('payable', 10, 2)->default(0);
            $table->float('provider_pay', 10, 2)->default(0);
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
        Schema::dropIfExists('delivery_payments');
    }
}

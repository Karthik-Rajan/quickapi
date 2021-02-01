<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDeliveryRequestDisputesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('delivery')->create('delivery_request_disputes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->integer('delivery_request_id');
            $table->enum('dispute_type', ['user', 'provider']);
            $table->integer('user_id');
            $table->integer('provider_id');
            $table->string('dispute_name');
            $table->string('dispute_title')->nullable();
            $table->string('comments')->nullable();
            $table->float('refund_amount',10,2)->default(0);
            $table->enum('comments_by', ['user', 'admin']);
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->tinyInteger('is_admin')->default(0);
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
        Schema::dropIfExists('delivery_request_disputes');
    }
}

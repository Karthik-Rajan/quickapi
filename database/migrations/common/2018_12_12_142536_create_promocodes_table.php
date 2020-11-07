<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromocodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promocodes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('store_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('promo_code');
            $table->string('service')->nullable();
            $table->string('picture')->nullable();
            $table->float('percentage',5, 2)->default(0);
            $table->float('min_amount',10, 2)->default(0);
            $table->float('max_amount',10, 2)->default(0);
            $table->string('promo_description');
            $table->dateTime('startdate')->nullable();
            $table->dateTime('expiration');
            $table->bigInteger('user_limit')->default(1);
            $table->enum('status', ['ADDED','EXPIRED']);
            $table->tinyInteger('eligibility')->comment('1 = Everyone, 2 = Specific User,3 = New User')->default(1);
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
        Schema::dropIfExists('promocodes');
    }
}

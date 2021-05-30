<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoreDrugsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('order')->create('store_drugs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('name');
            $table->tinyInteger('status')->default(1);
            $table->string('picture')->nullable();
            $table->enum('created_type',['ADMIN','PATIENT','CCM','FIELD-EXECUTIVE','PHARMACY'])->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->enum('modified_type', ['ADMIN','PATIENT','CCM','FIELD-EXECUTIVE','PHARMACY'])->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->enum('deleted_type',['ADMIN','PATIENT','CCM','FIELD-EXECUTIVE','PHARMACY'])->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
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
        Schema::dropIfExists('store_drugs');
    }
}

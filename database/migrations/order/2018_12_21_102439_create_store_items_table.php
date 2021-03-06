<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateStoreItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('order')->create('store_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('store_id');
            $table->unsignedInteger('company_id');
            $table->string('item_name');
            $table->string('item_description')->nullable();
            $table->string('picture')->nullable();
            $table->unsignedInteger('store_category_id')->nullable();
            $table->enum('is_veg', ['Pure Veg','Non Veg'])->nullable();
            $table->decimal('item_price', 10, 2)->default(0);
            $table->decimal('item_discount', 10, 2)->default(0);
            $table->Integer('quantity')->nullable();
            $table->Integer('low_stock')->nullable();
            $table->Integer('unit_id')->nullable();
            $table->enum('item_discount_type', ['PERCENTAGE','AMOUNT'])->default('AMOUNT');
            $table->tinyInteger('is_addon')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1 = Active, 0 = Inactive, 2 = Disabled');
            $table->enum('created_type', ['ADMIN','PHARMACY'])->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->enum('modified_type', ['ADMIN','PHARMACY'])->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->enum('deleted_type', ['ADMIN','PHARMACY'])->nullable();
            $table->unsignedInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('store_id')->references('id')->on('stores')
                ->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('store_category_id')->references('id')->on('store_categories')
                ->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('store_items');
    }
}

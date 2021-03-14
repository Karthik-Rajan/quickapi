<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIteamToStoreItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('order')->table('store_items', function (Blueprint $table) {
            $table->string('batch_number')->nullable()->after('item_description');
            $table->string('brand_id')->unsigned()->nullable()->after('batch_number');
            $table->enum('gender',['Male','Female','All'])->after('brand_id')->default('All')->nullable();
            $table->string('tags')->nullable()->after('gender');
            $table->string('ingredients')->nullable()->after('tags');
            $table->string('uses')->nullable()->after('ingredients');
            $table->timestamp('expiry_date')->nullable()->after('pack_size');

             $table->foreign('brand_id')->references('id')->on('brands');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('store_items', function (Blueprint $table) {
            //
        });
    }
}

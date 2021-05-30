<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAttributeToStoreItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        Schema::connection('order')->table('store_items', function (Blueprint $table) {
            $table->string('item_number')->nullable()->after('batch_number');
            $table->unsignedInteger('attribute_id')->nullable()->unsigned()->after('item_number');
            $table->unsignedInteger('attribute_value_id')->nullable()->unsigned()->after('attribute_id');
            $table->string('dosage')->nullable()->after('attribute_value_id');
            $table->unsignedInteger('drug_id')->nullable()->unsigned()->after('dosage');
            $table->tinyInteger('is_drug')->default(0);
            $table->string('manufacturer')->nullable()->after('drug_id');
            $table->unsignedInteger('country_id')->nullable()->after('manufacturer');

            $table->foreign('attribute_id')->references('id')->on('attributes');
            $table->foreign('attribute_value_id')->references('id')->on('attribute_values');
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

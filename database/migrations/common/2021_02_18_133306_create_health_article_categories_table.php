<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHealthArticleCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('common')->create('health_article_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('company_id');
            $table->string('article_category_name');
            $table->string('alias_name')->nullable();
            $table->string('picture')->nullable();
            $table->mediumInteger('article_category_order');
            $table->tinyInteger('article_category_status');
            $table->enum('created_type',['ADMIN','PATIENT','CCM','FIELD-EXECUTIVE','PHARMACY'])->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->enum('modified_type',['ADMIN','PATIENT','CCM','FIELD-EXECUTIVE','PHARMACY'])->nullable();
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
        Schema::dropIfExists('health_article_categories');
    }
}

<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHealthArticleSubcategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('common')->create('health_article_subcategories', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('article_category_id');
            $table->unsignedInteger('company_id');
            $table->string('article_subcategory_name');
            $table->string('picture')->nullable();
            $table->mediumInteger('article_subcategory_order');
            $table->tinyInteger('article_subcategory_status');
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
        Schema::dropIfExists('health_article_subcategories');
    }
}

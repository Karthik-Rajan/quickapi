<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHealthArticlesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('common')->create('health_articles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('article_category_id');
            $table->unsignedInteger('article_subcategory_id');
            $table->unsignedInteger('company_id');
            $table->string('article_name');
            $table->string('picture')->nullable();
            $table->string('url')->nullable();
            $table->text('description')->nullable();
            $table->tinyInteger('article_status')->default(1);
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
        Schema::dropIfExists('health_articles');
    }
}

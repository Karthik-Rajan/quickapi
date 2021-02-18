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
        Schema::dropIfExists('health_article_subcategories');
    }
}

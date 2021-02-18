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
            $table->text('description')->nullable();
            $table->tinyInteger('article_status')->default(1);
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
        Schema::dropIfExists('health_articles');
    }
}

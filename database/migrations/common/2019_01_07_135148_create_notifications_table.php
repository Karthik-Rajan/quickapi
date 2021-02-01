<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->increments('id');
            $table->enum('notify_type', ['all', 'user', 'provider','shop','fleet'])->default('all');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('service')->nullable();
            $table->string('title')->nullable();
            $table->string('image')->nullable();
            $table->string('descriptions')->nullable();
            $table->timestamp('expiry_date')->nullable();
            $table->integer('is_viewed')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->unsignedInteger('company_id')->nullable();
            $table->enum('created_type', ['ADMIN'])->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->enum('modified_type', ['ADMIN'])->nullable();
            $table->unsignedInteger('modified_by')->nullable();
            $table->enum('deleted_type', ['ADMIN'])->nullable();
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
        Schema::dropIfExists('notifications');
    }
}

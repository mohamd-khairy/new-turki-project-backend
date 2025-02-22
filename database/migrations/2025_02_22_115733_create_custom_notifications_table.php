<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('custom_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->text('image')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->boolean('is_for_all')->default(0);
            $table->boolean('is_by_city')->default(0);
            $table->boolean('is_by_country')->default(0);
            $table->boolean('is_by_category')->default(0);
            $table->boolean('is_by_subcategory')->default(0);
            $table->boolean('is_by_product')->default(0);
            $table->boolean('is_by_size')->default(0);
            $table->boolean('for_clients_only')->default(0);

            $table->text('product_ids')->nullable();
            $table->text('size_ids')->nullable();
            $table->text('category_parent_ids')->nullable();
            $table->text('category_child_ids')->nullable();
            $table->text('city_ids')->nullable();
            $table->text('country_ids')->nullable();
            $table->text('client_ids')->nullable();
            $table->boolean('is_active')->default(1);

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
        Schema::dropIfExists('custom_notifications');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('description')->nullable();
            $table->string('weight')->nullable();
            $table->string('calories')->nullable();
            $table->unsignedFloat('no_rating')->default(0.0);
            $table->string('image')->nullable();
            $table->decimal('price', 18, 2)->unsigned()->default('0');
            $table->boolean('is_active')->default('0');
            $table->boolean('is_shalwata')->default('0');
            $table->unsignedBigInteger('shalwata_id')->nullable();
            $table->boolean('is_delivered')->default('0');
            $table->boolean('is_picked_up')->default('0');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('sub_category_id')->nullable();

            $table->foreign('shalwata_id')->references('id')->on('shalwatas')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('sub_category_id')->references('id')->on('sub_categories')->onDelete('cascade');
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
        Schema::dropIfExists('products');
    }
}

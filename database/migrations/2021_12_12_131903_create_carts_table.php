<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cart', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('quantity')->default(1);
            $table->foreignId('shalwata_id')->constrained('shalwatas')->cascadeOnDelete()->nullable();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('preparation_id')->constrained('preparations')->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->cascadeOnDelete();
            $table->foreignId('cut_id')->constrained('cuts')->cascadeOnDelete();
            $table->boolean('is_shalwata')->default(0);
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('cart_info_id')->constrained('cart_infos')->cascadeOnDelete();
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
        Schema::dropIfExists('carts');
    }
}

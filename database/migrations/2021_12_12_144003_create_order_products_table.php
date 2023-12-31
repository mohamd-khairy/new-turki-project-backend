<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quantity')->default(1);
            $table->unsignedDecimal('total_price', 14, 2);
            $table->string('order_ref_no');

            $table->foreign('order_ref_no')
                ->references('ref_no')
                ->on('orders');
            $table->foreignId('preparation_id')->constrained('preparations')->nullable()->cascadeOnDelete();
            $table->foreignId('size_id')->constrained('sizes')->nullable()->cascadeOnDelete();
            $table->foreignId('cut_id')->constrained('cuts')->nullable()->cascadeOnDelete();
            $table->foreignId('shalwata_id')->constrained('shalwatas')->nullable()->cascadeOnDelete();
            $table->foreignId('product_id')->references('id')->constrained('products')->cascadeOnDelete();

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
        Schema::dropIfExists('order_products');
    }
}

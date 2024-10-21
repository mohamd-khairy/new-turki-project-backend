<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->string('quantity')->nullable();
            $table->string('old_quantity')->nullable();
            $table->string('new_quantity')->nullable();
            $table->string('action')->nullable();
            $table->foreignId('stock_id')->nullable()->constrained();
            $table->foreignId('customer_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->unsignedBigInteger('order_ref_no')->nullable();
            $table->foreignId('order_product_id')->nullable()->constrained();
            $table->foreignId('size_id')->nullable()->constrained();
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
        Schema::dropIfExists('stock_logs');
    }
}

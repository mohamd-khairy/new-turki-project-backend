<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSizeStoresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('size_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('size_id')->nullable()->constrained();
            $table->foreignId('store_id')->nullable()->constrained();
            $table->foreignId('stock_id')->nullable()->constrained();
            $table->string('quantity')->nullable();
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
        Schema::dropIfExists('size_stores');
    }
}

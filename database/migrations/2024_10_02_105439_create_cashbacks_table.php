<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashbacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cashbacks', function (Blueprint $table) {
            $table->id();
            $table->string('cash_back_amount')->nullable();
            $table->date('cash_back_start_date')->nullable();
            $table->date('cash_back_end_date')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
            $table->text('city_ids')->nullable();
            $table->text('product_ids')->nullable();
            $table->text('customer_ids')->nullable();
            $table->text('sub_category_ids')->nullable();
            $table->text('category_ids')->nullable();
            $table->string('expired_days')->nullable();
            $table->date('expired_at')->nullable();
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
        Schema::dropIfExists('cashbacks');
    }
}

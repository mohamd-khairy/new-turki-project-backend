<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMoneySafesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('money_safes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('currency')->nullable();
            $table->string('balance')->nullable();
            $table->foreignId('city_id')->nullable()->constrained();
            $table->foreignId('user_id')->nullable()->constrained();
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
        Schema::dropIfExists('money_safes');
    }
}

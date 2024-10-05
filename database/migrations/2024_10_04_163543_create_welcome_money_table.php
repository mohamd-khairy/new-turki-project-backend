<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWelcomeMoneyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('welcome_money', function (Blueprint $table) {
            $table->id();
            $table->string('welcome_amount')->nullable();
            $table->date('welcome_start_date')->nullable();
            $table->date('welcome_end_date')->nullable();
            $table->unsignedBigInteger('country_id')->nullable();
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
        Schema::dropIfExists('welcome_money');
    }
}

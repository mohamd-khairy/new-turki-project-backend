<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddItemToCityTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->boolean('allow_cash')->default(1);
            $table->string('min_price')->nullable();
            $table->string('cash_back_amount')->nullable();
            $table->date('cash_back_start_date')->nullable();
            $table->date('cash_back_end_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('allow_cash');
            $table->dropColumn('min_price');
            $table->dropColumn('cash_back_amount');
            $table->dropColumn('cash_back_start_date');
            $table->dropColumn('cash_back_end_date');
        });
    }
}

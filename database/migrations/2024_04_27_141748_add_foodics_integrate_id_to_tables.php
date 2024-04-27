<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFoodicsIntegrateIdToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sizes', function (Blueprint $table) {
            $table->string('foodics_integrate_id')->nullable();
        });

        Schema::table('cuts', function (Blueprint $table) {
            $table->string('foodics_integrate_id')->nullable();
        });

        Schema::table('preparations', function (Blueprint $table) {
            $table->string('foodics_integrate_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sizes', function (Blueprint $table) {
            $table->dropColumn('foodics_integrate_id');
        });

        Schema::table('cuts', function (Blueprint $table) {
            $table->dropColumn('foodics_integrate_id');
        });

        Schema::table('preparations', function (Blueprint $table) {
            $table->dropColumn('foodics_integrate_id');
        });
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBoxesCountToOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('boxes_count')->default(0);
            $table->string('dishes_count')->default(0);
            $table->string('driver_name')->nullable();
            $table->unsignedBigInteger('sales_representative_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('boxes_count');
            $table->dropColumn('dishes_count');
            $table->dropColumn('driver_name');
            $table->dropColumn('sales_representative_id');
        });
    }
}

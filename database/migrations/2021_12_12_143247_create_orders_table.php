<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ref_no')->unique()->index();
            $table->unsignedDecimal('delivery_fee', 14, 2)->default(0.0);
            $table->unsignedDecimal('order_total', 14, 2)->default(0.0);
            $table->unsignedDecimal('discount_applied', 14, 2)->default(0.0);
            $table->unsignedDecimal('total_amount', 14, 2)->default(0.0);
            $table->unsignedDecimal('total_amount_after_discount', 14, 2)->default(0.0);
            $table->string("comment")->nullable();
            $table->boolean("using_wallet")->default(0);
            $table->unsignedDecimal('wallet_amount_used', 14, 2)->default(0.0);
            $table->string('applied_discount_code')->nullable();
            $table->string('address')->nullable();
            $table->foreign('applied_discount_code')
                ->references('code')
                ->on('discounts');
            $table->foreignId('address_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('order_state_id')->default(100)->constrained('order_states')->references('code')->cascadeOnDelete();
            $table->foreignId('payment_type_id')->constrained('payment_types')->cascadeOnDelete();
         //   $table->foreignId('applied_discount_code')->references('code')->constrained('discounts')->cascadeOnDelete();


            $table->softDeletes();
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
        Schema::dropIfExists('orders');
    }
}

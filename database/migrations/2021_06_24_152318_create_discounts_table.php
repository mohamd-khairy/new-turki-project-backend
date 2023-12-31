<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discounts', function (Blueprint $table) {

            $table->id();
            $table->string('name')->unique()->nullable();
            $table->string('code', 10)->unique()->index();
            $table->boolean('is_active')->default(1);
            $table->boolean('is_percent')->default(1);
            $table->boolean('is_for_all')->default(1);
            $table->unsignedInteger('discount_amount_percent');
            $table->unsignedDecimal('min_applied_amount', 14, 2)->nullable();
            $table->unsignedDecimal('max_discount', 14, 2)->nullable();
            $table->timestamp('expire_at');
            $table->unsignedInteger('use_times_per_user')->default(0);
            $table->text('product_ids')->nullable();
            $table->text('category_parent_ids')->nullable();
            $table->text('category_child_ids')->nullable();

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
        Schema::dropIfExists('discounts');
    }
}

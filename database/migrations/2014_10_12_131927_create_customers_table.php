<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\User\CountryCodeEnums;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('mobile_country_code', 5)->default(CountryCodeEnums::getKey('KSA'));
            $table->string('mobile', 35)->unique()->index();
            $table->string('name', 50)->default('');
            $table->string('email')->nullable();
            $table->string('timezone')->default('Asia/Riyadh');
            $table->string('avatar')->nullable();
            $table->string('avatar_thumb')->nullable();
            $table->string('age')->nullable();
            $table->decimal('wallet',18,2)->default(0.0);
            $table->unsignedBigInteger('loyalty_points')->default(0);
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
        Schema::dropIfExists('customers');
    }
}

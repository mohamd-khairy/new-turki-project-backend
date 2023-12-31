<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\User\CountryCodeEnums;
class CreateCustomerOtpLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_otp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('mobile_country_code', 5)->default(CountryCodeEnums::getKey('KSA'));
            $table->string('mobile', 35)->unique()->index();
            $table->integer('mobile_verification_code')->nullable();
            $table->integer('no_attempts')->default('0');
            $table->boolean('disabled')->default('0');
            $table->timestamp('disabled_at')->nullable();
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
        Schema::dropIfExists('customer_otp_logs');
    }
}

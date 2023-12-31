<?php
use App\Enums\User\CountryCodeEnums;
use App\Enums\User\GenderEnums;
use App\Enums\User\StatusEnums;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('mobile_country_code', 5)->default(CountryCodeEnums::getKey('KSA'));
            $table->string('mobile', 35)->unique()->index();
            $table->string('password');
            $table->boolean('gender')->default(GenderEnums::getKey('male'));
            $table->string('country_code', 3)->default('SA');
            $table->integer('age')->default(0);
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_active')->default(StatusEnums::getKey('in_active'));

            $table->string('timezone')->default('Asia/Riyadh');
            $table->string('avatar')->nullable();
            $table->string('avatar_thumb')->nullable();

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\PaymentType;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;



$factory->define(PaymentType::class, function (Faker $faker) {

    return [
        'name_ar' =>$faker->randomElement(['الدفع عند الاستلام','الدفع اون لاين']),
        'name_en' => $faker->randomElement(['pay online','pay cash']),
        'code' => $faker->randomElement(['COD','ARB','My Fatorah']),
    ];
});

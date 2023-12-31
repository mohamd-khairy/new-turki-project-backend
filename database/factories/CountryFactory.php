<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Country;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;



$factory->define(Country::class, function (Faker $faker) {

    return [
        'name_ar' =>$faker->randomElement(['السعودية', 'الامارات']),
        'name_en' => $faker->randomElement(['Saudi Arbia','United Arab Emirates']),
        'currency_ar' => $faker->randomElement(['ريال','درهم']),
        'currency_en' => $faker->randomElement(['SR','AED']),
        'phone_code' => $faker->randomElement(['+966','+971']),
        'latitude' => $faker->randomFloat(00,00,10000),
        'longitude' => $faker->randomFloat(00,00,10000),
        'is_active' => 1,
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Cut;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;



$factory->define(Cut::class, function (Faker $faker) {

    return [
        'name_ar' =>$faker->randomElement(['تقطيع مفاصل صغير','تقطيع انصاف','تقطيع ارباع']),
        'name_en' => $faker->randomElement(['half','quarter','cutting']),
        'price'=> $faker->numberBetween(0,0),
    ];
});


<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Shalwata;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;



$factory->define(Shalwata::class, function (Faker $faker) {

    return [
        'name_ar' =>$faker->randomElement(['بدون شلوطة','شلوطة']),
        'name_en' => $faker->randomElement(['shalwata','shalwata']),
        'price'=> $faker->numberBetween(0,0),
    ];
});

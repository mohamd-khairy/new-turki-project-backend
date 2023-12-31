<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Preparation;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;



$factory->define(Preparation::class, function (Faker $faker) {

    return [
        'name_ar' =>$faker->randomElement(['بدون تكييس','تكييس','اطباق مغلفة']),
        'name_en' => $faker->randomElement(['Preparation','Preparation','Preparation']),
        'price'=> $faker->numberBetween(0,0),
    ];
});

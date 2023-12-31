<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Size;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;



$factory->define(Size::class, function (Faker $faker) {

    return [
        'name_ar' =>$faker->randomElement(['صغير','وسط','كبير']),
        'name_en' => $faker->randomElement(['Small','Large','Mudiem']),
        'price'=> $faker->numberBetween(0,0),
    ];
});

<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Tag;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(Tag::class, function (Faker $faker) {

    return [
        'name_ar' =>$faker->randomElement(['خصم','جديد','الأكثر مبيعاً']),
        'name_en' =>$faker->randomElement(['Sale','New','Most Sale']),
        'color' => $faker->randomElement(['#C85C5C','#FBD148','#B2EA70']),
    ];
});

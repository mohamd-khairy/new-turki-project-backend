<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Category;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;



$factory->define(Category::class, function (Faker $faker) {

    return [
        'type_ar' =>$faker->randomElement(['فواكه وخضار','دواجن','لحوم']),
        'type_en' => $faker->randomElement(['Meat','chicken','Fruits and Vegetables']),
        'description' => $faker->randomElement(['test','test','test']),
        'image'=> 'demo/' . Arr::get($faker->randomElements(['1.jpg','2.jpg','3.jpg']),'0'),
        'thumbnail' => 'demo/'. Arr::get($faker->randomElements(['1.jpg','2.jpg','3.jpg']),'0'),
    ];
});


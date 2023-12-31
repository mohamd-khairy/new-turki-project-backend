<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\SubCategory;
use App\Models\Category;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(SubCategory::class, function (Faker $faker) {

    return [
        'category_id'=>Category::all()->random()->id,
        'type_ar' =>$faker->randomElement([ 'نعيمي','حري']),
        'type_en' => $faker->randomElement(['Naimi','hery']),
        'description' => $faker->randomElement(['test','test','test']),
        'image'=> 'demo/' . Arr::get($faker->randomElements(['1.jpg','1.jpg','1.jpg']),'0'),
        'thumbnail' => 'demo/'. Arr::get($faker->randomElements(['1.jpg','1.jpg','1.jpg']),'0'),
    ];
});


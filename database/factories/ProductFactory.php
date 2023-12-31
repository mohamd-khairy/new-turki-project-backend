<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Product;
use App\Models\Category;
use App\Models\SubCategory;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;



$factory->define(Product::class, function (Faker $faker) {

    return [
        'name_ar' =>$faker->randomElement(['نص حري ','ربع حري','حري','نص نعيمي','ربع نعيمي','نعيمي','حري']),
        'name_en' =>$faker->randomElement(['naimi','hery']),
        'description' => $faker->randomElement(['test','test','test']),
        'weight' => $faker->randomElement(['18','20']),
        'calories' => $faker->randomElement(['4000','2000']),
        'no_rating' => $faker->randomElement([5,3,2]),
        'price'=> $faker->numberBetween(0,0),
        'is_active' => 1,
        'is_shalwata' => 1,
        'is_delivered' => 1,
        'is_picked_up' => 1,
        'category_id'=>Category::all()->random()->id,
        'sub_category_id'=>SubCategory::all()->random()->id,
    ];
});


<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ProductCity;
use App\Models\Product;
use App\Models\City;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(ProductCity::class, function (Faker $faker) {

    return [
        'product_id'=>Product::all()->random()->id,
        'city_id'=>City::all()->random()->id,
    ];
});


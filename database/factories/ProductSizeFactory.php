<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ProductSize;
use App\Models\Product;
use App\Models\Size;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(ProductSize::class, function (Faker $faker) {

    return [
        'product_id'=>Product::all()->random()->id,
        'size_id'=>Size::all()->random()->id,
    ];
});

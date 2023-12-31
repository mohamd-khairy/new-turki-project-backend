<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ProductPreparation;
use App\Models\Product;
use App\Models\Preparation;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(ProductPreparation::class, function (Faker $faker) {

    return [
        'product_id'=>Product::all()->random()->id,
        'preparation_id'=>Preparation::all()->random()->id,
    ];
});

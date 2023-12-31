<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ProductCut;
use App\Models\Product;
use App\Models\Cut;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(ProductCut::class, function (Faker $faker) {

    return [
        'product_id'=>Product::all()->random()->id,
        'cut_id'=>Cut::all()->random()->id,
    ];
});


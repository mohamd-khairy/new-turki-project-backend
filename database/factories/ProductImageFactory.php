<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ProductImage;
use App\Models\Product;

use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(ProductImage::class, function (Faker $faker) {

    return [
        'product_id'=>Product::all()->random()->id,
        'image'=> 'demo/' . Arr::get($faker->randomElements(['1.jpg','2.jpg','3.jpg']),'0'),
        'thumbnail' => 'demo/'. Arr::get($faker->randomElements(['1.jpg','2.jpg','3.jpg']),'0'),
        'is_default' => 1,
    ];
});


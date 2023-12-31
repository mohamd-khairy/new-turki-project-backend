<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ProductTag;
use App\Models\Product;
use App\Models\Tag;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(ProductTag::class, function (Faker $faker) {

    return [
        'product_id'=>Product::all()->random()->id,
        'tag_id'=>Tag::all()->random()->id,
    ];
});

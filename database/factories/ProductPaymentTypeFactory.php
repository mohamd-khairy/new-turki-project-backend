<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\ProductPaymentType;
use App\Models\Product;
use App\Models\PaymentType;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(ProductPaymentType::class, function (Faker $faker) {

    return [
        'product_id'=>Product::all()->random()->id,
        'payment_type_id'=>PaymentType::all()->random()->id,
    ];
});


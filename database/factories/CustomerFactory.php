<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Customer;
use App\Models\Profile;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(Customer::class, function (Faker $faker) {

    return [
        'mobile_country_code' =>$faker->randomElement([ '+971','+966']),
        'mobile' => $faker->randomElement(['561051956']),
        'mobile_verification_code' => $faker->randomElement(['2244','1234','4422']),
        'no_attempts' => $faker->randomElement(['1','2','2']),
        'disabled' => $faker->randomElement(['0','0','0']),
        'disabledDate' => $faker->randomElement(['test','test','test']),
        'profile_id'=>Profile::all()->random()->id,
    ];
});

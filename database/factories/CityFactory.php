<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Country;
use App\Models\City;
use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(City::class, function (Faker $faker) {

    return [
        'country_id'=>Country::all()->random()->id,
        'name_ar' =>$faker->randomElement([ 'العين','الشارقة' ,'ابوظبي','دبي','الدمام','جدة' ,'مكة','الرياض']),
        'name_en' => $faker->randomElement(['Ryiadh','Jeddah','Makkah','Abha','Dubai','Abu Dhabi','Sharjah','Alain']),
        'is_active' => 1,
        'is_available_for_delivery' => 1,
    ];
});

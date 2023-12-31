<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Models\Profile;

use Faker\Generator as Faker;
use Illuminate\Support\Arr;
  /**
     * Define the model's default state.
     *
     * @return array
     */
  
$factory->define(Profile::class, function (Faker $faker) {

    return [
        'name' =>$faker->randomElement([ 'محمود','محمد']),
        'email' => $faker->randomElement(['Mahmoud','Ahmed']),
        'nationality' => $faker->randomElement(['SA','SA','SA']),
        'age' => $faker->randomElement(['test','test','test']),
        'avatar'=> 'demo/' . Arr::get($faker->randomElements(['1.jpg','1.jpg','1.jpg']),'0'),
        'avatar_thumb' => 'demo/'. Arr::get($faker->randomElements(['1.jpg','1.jpg','1.jpg']),'0'),
    ];
});

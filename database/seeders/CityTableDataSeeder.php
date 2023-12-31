<?php

namespace Database\Seeders;
use App\Models\City;
use Illuminate\Database\Seeder;

class CityTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(City::class, 8)->create();
    }
}

<?php

namespace Database\Seeders;
use App\Models\ProductCity;
use Illuminate\Database\Seeder;

class ProductCityTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(ProductCity::class, 10)->create();
    }
}

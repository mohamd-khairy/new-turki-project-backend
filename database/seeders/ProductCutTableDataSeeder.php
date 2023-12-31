<?php

namespace Database\Seeders;
use App\Models\ProductCut;
use Illuminate\Database\Seeder;

class ProductCutTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(ProductCut::class, 10)->create();
    }
}

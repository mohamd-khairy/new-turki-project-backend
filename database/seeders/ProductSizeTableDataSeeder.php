<?php

namespace Database\Seeders;
use App\Models\ProductSize;
use Illuminate\Database\Seeder;

class ProductSizeTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(ProductSize::class, 10)->create();
    }
}

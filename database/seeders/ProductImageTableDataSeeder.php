<?php

namespace Database\Seeders;
use App\Models\ProductImage;

use Illuminate\Database\Seeder;

class ProductImageTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(ProductImage::class, 10)->create();
    }
}

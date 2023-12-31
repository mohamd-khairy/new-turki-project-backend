<?php

namespace Database\Seeders;
use App\Models\ProductPreparation;
use Illuminate\Database\Seeder;

class ProductPreparationTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(ProductPreparation::class, 10)->create();
    }
}

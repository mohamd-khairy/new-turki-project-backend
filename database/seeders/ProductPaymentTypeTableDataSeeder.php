<?php

namespace Database\Seeders;
use App\Models\ProductPaymentType;
use Illuminate\Database\Seeder;

class ProductPaymentTypeTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(ProductPaymentType::class, 10)->create();
    }
}

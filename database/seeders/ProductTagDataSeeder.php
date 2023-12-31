<?php

namespace Database\Seeders;
use App\Models\ProductTag;
use Illuminate\Database\Seeder;

class ProductTagTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(ProductTag::class, 19)->create();
    }
}

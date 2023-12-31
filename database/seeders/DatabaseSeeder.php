<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(CategoryTableDataSeeder::class);
        // $this->call(SubCategoryTableDataSeeder::class);
        // $this->call(CountryTableDataSeeder::class);
        // $this->call(CityTableDataSeeder::class);
        // $this->call(SizeTableDataSeeder::class);
        // $this->call(CutTableDataSeeder::class);
        // $this->call(PaymentTypeTableDataSeeder::class);
        // $this->call(PreparationTableDataSeeder::class);
        // $this->call(ProductTableDataSeeder::class);
        // $this->call(ProductImageTableDataSeeder::class);
        // $this->call(ProductPaymentTypeTableDataSeeder::class);
        // $this->call(ProductPreparationTableDataSeeder::class);
        // $this->call(ProductSizeTableDataSeeder::class);
        // $this->call(ProductCutTableDataSeeder::class);
        // $this->call(ShalwataTableDataSeeder::class);
        // $this->call(ProductCityTableDataSeeder::class);
        $this->call(OrderStateTableDataSeeder::class);
        // $this->call(RoleDataSeeder::class);
        // \App\Models\User::factory(10)->create();
       //  \App\Models\Category::factory(10)->create();
    }
}

<?php

namespace Database\Seeders;
use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Customer::class, 10)->create();
    }
}

<?php

namespace Database\Seeders;
use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(PaymentType::class, 3)->create();
    }
}

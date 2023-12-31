<?php

namespace Database\Seeders;
use App\Models\Preparation;
use Illuminate\Database\Seeder;

class PreparationTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Preparation::class, 3)->create();
    }
}

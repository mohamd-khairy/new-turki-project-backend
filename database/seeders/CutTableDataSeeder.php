<?php

namespace Database\Seeders;
use App\Models\Cut;
use Illuminate\Database\Seeder;

class CutTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Cut::class, 3)->create();
    }
}

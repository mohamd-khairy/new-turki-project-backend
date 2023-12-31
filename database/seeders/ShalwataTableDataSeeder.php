<?php

namespace Database\Seeders;
use App\Models\Shalwata;
use Illuminate\Database\Seeder;

class ShalwataTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Shalwata::class, 2)->create();
    }
}

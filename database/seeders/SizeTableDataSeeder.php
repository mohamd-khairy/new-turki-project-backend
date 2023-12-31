<?php

namespace Database\Seeders;
use App\Models\Size;
use Illuminate\Database\Seeder;

class SizeTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Size::class, 3)->create();
    }
}

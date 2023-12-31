<?php

namespace Database\Seeders;
use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Tag::class, 10)->create();
    }
}

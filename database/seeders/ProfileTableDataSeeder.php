<?php

namespace Database\Seeders;
use App\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileTableDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Profile::class, 10)->create();
    }
}

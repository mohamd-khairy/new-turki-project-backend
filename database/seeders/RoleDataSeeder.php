<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\RolesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RoleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        RolesService::handle();
    }
}

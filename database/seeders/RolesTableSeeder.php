<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        Role::create(['name' => 'user-management']);
        Role::create(['name' => 'master-item']);
        Role::create(['name' => 'office']);
    }
}
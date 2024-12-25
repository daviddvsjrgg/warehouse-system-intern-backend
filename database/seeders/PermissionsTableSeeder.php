<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        Permission::create(['name' => 'create', 'description' => 'Create a resource']);
        Permission::create(['name' => 'read', 'description' => 'Read a resource']);
        Permission::create(['name' => 'update', 'description' => 'Update a resource']);
        Permission::create(['name' => 'delete', 'description' => 'Delete a resource']);
        Permission::create(['name' => 'export', 'description' => 'export a resource']);
        Permission::create(['name' => 'import', 'description' => 'import a resource']);
    }
}

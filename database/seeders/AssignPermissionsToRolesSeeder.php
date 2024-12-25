<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class AssignPermissionsToRolesSeeder extends Seeder
{
    public function run()
    {
        // Fetch all permissions
        $permissions = Permission::all();  // Get all permissions in the database

        // Fetch all roles
        $roles = Role::all();  // Get all roles in the database

        // Loop through each role and attach all permissions to it
        foreach ($roles as $role) {
            $role->permissions()->attach($permissions);
        }
    }
}

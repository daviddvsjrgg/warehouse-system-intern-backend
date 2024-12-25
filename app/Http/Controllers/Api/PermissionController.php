<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Models\Role;

class PermissionController extends Controller
{
    // Show All permissions
    public function index()
    {
        $permissions = Permission::all();

        return new GeneralResource(
            true,
            'Permissions retrieved successfully.',
            $permissions,
            200
        );
    }

    // Store a new permission
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $permission = Permission::create($validated);

        return new GeneralResource(
            true,
            'Permission created successfully.',
            $permission,
            201
        );
    }

    // Display a specific permission
    public function show($id)
    {
        $permission = Permission::findOrFail($id);

        return new GeneralResource(
            true,
            'Permission retrieved successfully.',
            $permission,
            200
        );
    }

    // Update a specific permission
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:permissions,name,' . $id . '|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        $permission = Permission::findOrFail($id);
        $permission->update($validated);

        return new GeneralResource(
            true,
            'Permission updated successfully.',
            $permission,
            200
        );
    }

    // Delete a specific permission
    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);

        $permission->delete();

        return new GeneralResource(
            true,
            'Permission deleted successfully.',
            null,
            200
        );
    }

    // Assign permissions to a role
    public function assignPermissions(Request $request, $roleId)
    {
        // Find the role
        $role = Role::findOrFail($roleId);

        // Get the list of permission IDs from the request
        $permissions = $request->permissions; // An array of permission IDs

        // Assign permissions to the role
        $role->permissions()->sync($permissions); // Sync permissions (attach or detach)

        return new GeneralResource(
            true, 
            'Permissions assigned to role successfully.', 
            null, 
            200
        );
    }


    // Show permissions assigned to a role
    public function showPermissions($roleId)
    {
        // Find the role
        $role = Role::findOrFail($roleId);

        // Get the permissions associated with the role
        $permissions = $role->permissions;

        // Include the role name in the response data
        $data = [
            'role_name' => $role->name,
            'permissions' => $permissions
        ];

        return new GeneralResource(
            true, 
            'Permissions retrieved successfully.', 
            $data, 
            200
        );
    }

    // Remove a permission from a role
    public function removePermission(Request $request, $roleId)
    {
        // Find the role
        $role = Role::findOrFail($roleId);

        // Get the permission ID from the request
        $permissionId = $request->permission_id;

        // Remove the permission from the role
        $role->permissions()->detach($permissionId);

        return new GeneralResource(
            true, 
            'Permission removed from role successfully.', 
            null, 
            200
        );
    }
}

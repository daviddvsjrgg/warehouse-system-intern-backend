<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Validator;

class PermissionService
{
    public function getAll()
    {
        return Permission::all();
    }

    public function store(array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|unique:permissions|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'message' => 'Validation Error',
                'data' => $validator->errors(),
                'status_code' => 422
            ];
        }

        $permission = Permission::create($validator->validated());

        return [
            'success' => true,
            'message' => 'Permission created successfully.',
            'data' => $permission,
            'status_code' => 201
        ];
    }

    public function show($id)
    {
        return Permission::findOrFail($id);
    }

    public function update($id, array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|unique:permissions,name,' . $id . '|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'message' => 'Validation Error',
                'data' => $validator->errors(),
                'status_code' => 422
            ];
        }

        $permission = Permission::findOrFail($id);
        $permission->update($validator->validated());

        return [
            'success' => true,
            'message' => 'Permission updated successfully.',
            'data' => $permission,
            'status_code' => 200
        ];
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return [
            'success' => true,
            'message' => 'Permission deleted successfully.',
            'data' => null,
            'status_code' => 200
        ];
    }

    public function assignPermissionsToRole($roleId, array $permissions)
    {
        $role = Role::findOrFail($roleId);
        $role->permissions()->sync($permissions);

        return [
            'success' => true,
            'message' => 'Permissions assigned to role successfully.',
            'data' => null,
            'status_code' => 200
        ];
    }

    public function showPermissionsOfRole($roleId)
    {
        $role = Role::findOrFail($roleId);
        return [
            'role_name' => $role->name,
            'permissions' => $role->permissions
        ];
    }

    public function removePermissionFromRole($roleId, $permissionId)
    {
        $role = Role::findOrFail($roleId);
        $role->permissions()->detach($permissionId);

        return [
            'success' => true,
            'message' => 'Permission removed from role successfully.',
            'data' => null,
            'status_code' => 200
        ];
    }
}

<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\Validator;

class RoleService
{
    public function getRoles(array $filters)
    {
        // Extract filters
        $query = $filters['query'] ?? null;
        $exact = $filters['exact'] ?? false;
        $perPage = $filters['per_page'] ?? 5;
        $permission = $filters['permission'] ?? null;

        // Define relationships to eager load
        $relationships = ['permissions'];

        // If 'exact' is true and a query is provided, find the exact match by name
        if ($exact && $query) {
            $roles = Role::with($relationships) // Eager load relationships
                ->where('name', $query)
                ->when($permission, function ($queryBuilder) use ($permission) {
                    return $queryBuilder->whereHas('permissions', function ($q) use ($permission) {
                        $q->where('name', $permission);
                    });
                })
                ->get();

            if ($roles->isEmpty()) {
                return [
                    'error' => true,
                    'message' => 'Role not found',
                    'data' => null,
                    'status_code' => 404
                ];
            }

            return [
                'success' => true,
                'message' => 'Exact role match found!',
                'data' => $roles,
                'status_code' => 200
            ];
        }

        // If 'exact' is false or no query, search with pagination and partial matching
        $roles = Role::with($relationships) // Eager load relationships
            ->when($query, function ($queryBuilder) use ($query) {
                return $queryBuilder->where('name', 'like', "%{$query}%");
            })
            ->when($permission, function ($queryBuilder) use ($permission) {
                return $queryBuilder->whereHas('permissions', function ($q) use ($permission) {
                    $q->where('name', $permission);
                });
            })
            ->paginate($perPage); // Paginate with dynamic per-page value

        return [
            'success' => true,
            'message' => 'Roles retrieved successfully!',
            'data' => $roles,
            'status_code' => 200
        ];
    }
    public function create(array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'message' => 'Validation Error',
                'data' => $validator->errors(),
                'status_code' => 422
            ];
        }

        try {
            $role = Role::create(['name' => $data['name']]);
            return [
                'success' => true,
                'message' => 'Role created successfully.',
                'data' => $role,
                'status_code' => 201
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Failed to create role: ' . $e->getMessage(),
                'data' => null,
                'status_code' => 500
            ];
        }
    }

    public function update(Role $role, array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'message' => 'Validation Error',
                'data' => $validator->errors(),
                'status_code' => 422
            ];
        }

        try {
            $role->update(['name' => $data['name']]);
            return [
                'success' => true,
                'message' => 'Role updated successfully.',
                'data' => $role,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Failed to update role: ' . $e->getMessage(),
                'data' => null,
                'status_code' => 500
            ];
        }
    }

    public function delete(Role $role)
    {
        try {
            $role->delete();
            return [
                'success' => true,
                'message' => 'Role deleted successfully.',
                'data' => null,
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Failed to delete role: ' . $e->getMessage(),
                'data' => null,
                'status_code' => 500
            ];
        }
    }
}

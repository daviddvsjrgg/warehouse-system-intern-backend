<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Http\Resources\GeneralResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        // Extract query, exact flag, items per page (with a default value of 5), and permission filter
        $query = $request->input('query');
        $exact = $request->input('exact', false); // Exact flag, defaults to false
        $perPage = $request->input('per_page', 5); // Defaults to 5 if not provided
        $permission = $request->input('permission'); // Optional permission filter
    
        // Define relationships to eager load (e.g., 'permissions')
        $relationships = ['permissions']; // Add any other relationships you want to eager load
    
        // If 'exact' is true and a query is provided, find the exact match by name
        if ($exact && $query) {
            $roles = Role::with($relationships)  // Eager load relationships
                ->where('name', $query)
                ->when($permission, function ($queryBuilder) use ($permission) {
                    return $queryBuilder->whereHas('permissions', function ($q) use ($permission) {
                        $q->where('name', $permission);
                    });
                })
                ->get();
    
            // If no roles are found, return a custom error response
            if ($roles->isEmpty()) {
                return new GeneralResource(false, 'Role not found', null, 404);
            }
    
            // Return the exact match without pagination
            return new GeneralResource(true, 'Exact role match found!', $roles, 200);
        }
    
        // If 'exact' is false or no query, search with pagination and partial matching
        $roles = Role::with($relationships)  // Eager load relationships
            ->when($query, function ($queryBuilder) use ($query) {
                return $queryBuilder->where('name', 'like', "%{$query}%");
            })
            ->when($permission, function ($queryBuilder) use ($permission) {
                return $queryBuilder->whereHas('permissions', function ($q) use ($permission) {
                    $q->where('name', $permission);
                });
            })
            ->paginate($perPage); // Paginate with dynamic per-page value
    
        // Return paginated results wrapped in a resource
        return new GeneralResource(true, 'Roles retrieved successfully!', $roles, 200);
    }
    

    public function store(Request $request)
    {   
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name',
        ]);

        // If validation fails, return detailed error messages
        if ($validator->fails()) {
            return response()->json(new GeneralResource(false, 'Validation Error', $validator->errors(), 422));
        }
    
        // Attempt to create the role with the validated data
        try {
            // Create the role with the validated name
            $role = Role::create($request->only(['name']));
    
            // Return success response
            return new GeneralResource(
                true, 
                'Role created successfully.', 
                $role, 
                201
            );
    
        } catch (\Exception $e) {
            // If an error occurs (e.g., database issues), catch it
            return new GeneralResource(
                false, 
                'Failed to create role: ' . $e->getMessage(), 
                null, 
                500
            );
        }
    }

    public function update(Request $request, Role $role)
    {
        // Define validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
        ]);

        // If validation fails, return detailed error messages
        if ($validator->fails()) {
            return response()->json(new GeneralResource(false, 'Validation Error', $validator->errors(), 422));
        }

        // Update the role's name
        try {
            $role->update($request->only(['name']));

            // Return success response
            return new GeneralResource(
                true, 
                'Role updated successfully.', 
                $role, 
                200
            );
        } catch (\Exception $e) {
            // If an error occurs (e.g., database issues), catch it
            return new GeneralResource(
                false, 
                'Failed to update role: ' . $e->getMessage(), 
                null, 
                500
            );
        }
    }

    public function destroy(Role $role)
    {
        // Attempt to delete the role
        try {
            $role->delete();

            // Return success response
            return new GeneralResource(
                true, 
                'Role deleted successfully.', 
                null, 
                200
            );
        } catch (\Exception $e) {
            // If an error occurs (e.g., database issues), catch it
            return new GeneralResource(
                false, 
                'Failed to delete role: ' . $e->getMessage(), 
                null, 
                500
            );
        }
    }
}

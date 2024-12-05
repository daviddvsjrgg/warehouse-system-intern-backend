<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\GeneralResource;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        // Extract query, exact flag, and items per page (with a default value of 5)
        $query = $request->input('query');
        $exact = $request->input('exact', false); // Exact flag, defaults to false
        $perPage = $request->input('per_page', 5); // Defaults to 5 if not provided

        // Start building the query
        $usersQuery = User::with(['roles' => function ($query) {
            $query->select('name');
        }]);

        // If 'exact' is true and a query is provided, find the exact match by name
        if ($exact && $query) {
            $usersQuery->where('name', $query)
                    ->orWhere('email', $query);
        } elseif ($query) {
            // If 'exact' is false but query is provided, search for partial matches
            $usersQuery->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
        }

        // Get the paginated results
        $users = $usersQuery->paginate($perPage);

        // Map roles to only return their names
        $users->getCollection()->each(function ($user) {
            $user->roles = $user->roles->pluck('name');
        });

        return new GeneralResource(true, 'Users retrieved successfully', $users, 200);
    }

    public function store(Request $request)
    {
        // Validate the request using Validator::make
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
        ]);

        // Check for validation errors
        if ($validator->fails()) {
            return response()->json(
                new GeneralResource(false, 'Validation Error', $validator->errors(), 422),
                422
            );
        }

        try {
            // Create user with email_verified_at set to the current timestamp using Carbon
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'email_verified_at' => Carbon::now(),  // Use Carbon::now() instead of now()
            ]);

            // Assign roles
            $roles = Role::whereIn('name', $request->roles)->get();
            $user->roles()->sync($roles);

            return response()->json(
                new GeneralResource(true, 'User created and verified successfully', $user->load('roles'), 201),
                201
            );
        } catch (\Exception $e) {
            // Handle any other errors
            return response()->json(
                new GeneralResource(false, 'An error occurred while creating the user', ['error' => $e->getMessage()], 500),
                500
            );
        }
    }


    public function updateUser(Request $request, User $user)
    {
        // Validate the request data using Validator::make
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name', // Ensure roles exist in the roles table
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(
                new GeneralResource(false, 'Validation Error', $validator->errors(), 422),
                422
            );
        }

        // Update the user's name
        $user->update([
            'name' => $request->name,
        ]);

        // Sync the roles with the provided ones
        $roles = Role::whereIn('name', $request->roles)->get();
        $user->roles()->sync($roles);

        // Return response with updated user data
        return new GeneralResource(true, 'User updated successfully', $user->load('roles'), 200);
    }

}

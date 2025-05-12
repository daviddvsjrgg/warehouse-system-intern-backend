<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserManagementService
{
    public function getUsers(array $filters)
    {
        $queryParam = $filters['query'] ?? null;
        $exact = filter_var($filters['exact'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $perPage = $filters['per_page'] ?? 5;

        $usersQuery = User::with(['roles' => function ($query) {
            $query->select('name');
        }]);

        if ($exact && $queryParam) {
            $usersQuery->where('name', $queryParam)
                ->orWhere('email', $queryParam);
        } elseif ($queryParam) {
            $usersQuery->where('name', 'like', "%{$queryParam}%")
                ->orWhere('email', 'like', "%{$queryParam}%");
        }

        $users = $usersQuery->paginate($perPage);

        $users->getCollection()->each(function ($user) {
            $user->roles = $user->roles->pluck('name');
        });

        return $users;
    }

     public function createUser(array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
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
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'email_verified_at' => Carbon::now(),
            ]);

            $roles = Role::whereIn('name', $data['roles'])->get();
            $user->roles()->sync($roles);

            return [
                'success' => true,
                'message' => 'User created and verified successfully',
                'data' => $user->load('roles'),
                'status_code' => 201
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'An error occurred while creating the user',
                'data' => ['error' => $e->getMessage()],
                'status_code' => 500
            ];
        }
    }

      public function updateUser(User $user, array $data)
    {
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'message' => 'Validation Error',
                'data' => $validator->errors(),
                'status_code' => 422
            ];
        }

        // Update name
        $user->name = $data['name'];

        // Optional password update
        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        // Sync roles
        $roles = Role::whereIn('name', $data['roles'])->get();
        $user->roles()->sync($roles);

        return [
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user->load('roles'),
            'status_code' => 200
        ];
    }
}


<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\User;

class AuthService
{
    public function login(array $request)
    {
        // Validate the input request to ensure email and password are present and correctly formatted
        $validator = Validator::make($request, [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // If validation fails, return error response with validation messages and 422 Unprocessable Entity status
        if ($validator->fails()) {
            return [
                'error' => 'error',
                'message' => $validator->errors(),
                'status_code' => 422,
            ];
        }

        // Attempt to retrieve user record by email
        $user = User::where('email', $request['email'])->first();

        // If user is not found or password does not match, return unauthorized error response
        if (!$user || !Hash::check($request['password'], $user->password)) {
            return [
                'error' => 'error',
                'message' => 'Email atau password salah!',
                'status_code' => 401,
            ];
        }

        // Create a new personal access token for the authenticated user
        $tokenResult = $user->createToken('token_name');
        $token = $tokenResult->plainTextToken;

        // Define token expiration time (1 hour from now), adjusted to the Asia/Jakarta timezone
        $expiresAt = Carbon::now()->addHour()->timezone('Asia/Jakarta');

        // Prepare and return the response with token details and user ID
        $data = [
            'token' => $token,
            'user_id' => $user->id,
            'expires_at' => $expiresAt,
            'expires_in' => 3600, // token validity in seconds
        ];

        return $data;
    }
}

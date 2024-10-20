<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return (new GeneralResource(false, 'Validation Error', $validator->errors(), 422))->response();
        }

        // Create a new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Return success response
        return (new GeneralResource(true, 'User registered successfully.', $user, 201))->response();
    }

    public function login(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return (new GeneralResource(false, 'Validation Error', $validator->errors(), 422))->response();
        }

        // Attempt to find the user
        $user = User::where('email', $request->email)->first();

        // Check if the user exists and the password is correct
        if (!$user || !Hash::check($request->password, $user->password)) {
            return (new GeneralResource(false, 'Unauthorized', null, 401))->response();
        }

        // Generate a new token for the user
        $token = $user->createToken('token_name')->plainTextToken;

        // Set token expiration time (e.g., 2 hours from now) in GMT+7
        $expiresAt = now()->timezone('GMT+7')->addHours(2);

        // Return success response with token and user ID
        return (new GeneralResource(true, 'Login successful.', [
            'token' => $token,
            'user_id' => $user->id,  // Include user ID in response
            'expires_at' => $expiresAt // Set token expiration time
        ], 200))->response();
    }



    public function logout(Request $request)
    {
        // Delete the user's current access token
        $request->user()->currentAccessToken()->delete();

        // Return success response
        return (new GeneralResource(true, 'Logged out successfully.', null, 200))->response();
    }
}

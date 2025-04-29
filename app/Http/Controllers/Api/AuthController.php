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
        // $validator = Validator::make($request->all(), [
        //     'name' => 'required|string|max:255',
        //     'email' => 'required|string|email|max:255|unique:users',
        //     'password' => 'required|string|min:8',
        // ]);

        // // Return validation errors if validation fails
        // if ($validator->fails()) {
        //     return (new GeneralResource(false, 'Validation Error', $validator->errors(), 422))->response();
        // }

        // // Create a new user
        // $user = User::create([
        //     'name' => $request->name,
        //     'email' => $request->email,
        //     'password' => Hash::make($request->password),
        // ]);

        // Return success response
        return (new GeneralResource(true, 'Register Not Available Yet!', null, 404))->response();
        // return (new GeneralResource(true, 'User registered successfully.', $user, 201))->response();
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return (new GeneralResource(false, 'Validation Error', $validator->errors(), 422))->response();
        }
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            return (new GeneralResource(false, 'Unauthorized', null, 401))->response();
        }
    
        // Generate token that expires in 1 hour
        $tokenResult = $user->createToken('token_name');
        $tokenResult->accessToken->expires_at = now()->addHours(1);
        $tokenResult->accessToken->save();
    
        $token = $tokenResult->plainTextToken;
        $expiresAt = now()->addHours(1)->timezone('Asia/Jakarta');
    
        return (new GeneralResource(true, 'Login successful.', [
            'token' => $token,
            'user_id' => $user->id,
            'expires_at' => $expiresAt,
            'expires_in' => 3600, // 1 jam dalam detik
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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Traits\ApiResponse;
use App\Services\AuthService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AuthController extends Controller
{
    use ApiResponse;
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function register()
    {
        return $this->sendSuccessResponse(true, 'Not Available', null, 500);
    }

    public function login(Request $request)
    {
        $data = $this->authService->login($request->all());

        if (isset($data['error'])){
            return $this->sendErrorResponse(false, $data['message'], null, $data['status_code']);
        }
        
        return $this->sendSuccessResponse(true, 'Succesfully Login', $data, 200);
    }

    public function logout(Request $request)
    {
        // Delete the user's current access token
        $request->user()->currentAccessToken()->delete();

        // Return success response
        return $this->sendSuccessResponse(true, 'Logged out successfully.', null, 200);
    }
}

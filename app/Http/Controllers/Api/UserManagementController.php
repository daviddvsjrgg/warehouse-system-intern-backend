<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{

    use ApiResponse;

    protected $userService;

    public function __construct(UserManagementService $userService)
    {
        $this->userService = $userService;
    }

    public function index(Request $request)
    {
        $users = $this->userService->getUsers($request->all());

        return $this->sendSuccessResponse(true, 'Users retrieved successfully.', $users, 200);
    }

    public function store(Request $request)
    {
        $result = $this->userService->createUser($request->all());

        if (isset($result['error']) && $result['error']) {
            return $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code']);
        }

        return $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }


    public function updateUser(Request $request, User $user)
    {
        $result = $this->userService->updateUser($user, $request->all());

        if (isset($result['error']) && $result['error']) {
            return $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code']);
        }

        return $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }

}

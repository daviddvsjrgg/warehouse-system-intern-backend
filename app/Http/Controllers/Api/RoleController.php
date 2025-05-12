<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    use ApiResponse;

    protected $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }
    public function index(Request $request)
    {
        // Get filtered parameters
        $filters = $request->all();

        // Get roles from the service layer
        $result = $this->roleService->getRoles($filters);

        // If there is an error, send error response
        if (isset($result['error']) && $result['error']) {
            return $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code']);
        }

        // Send success response
        return $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }
    
    public function store(Request $request)
    {
        $result = $this->roleService->create($request->all());

        return $result['error'] ?? false
            ? $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code'])
            : $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }

    public function update(Request $request, Role $role)
    {
        $result = $this->roleService->update($role, $request->all());

        return $result['error'] ?? false
            ? $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code'])
            : $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }

    public function destroy(Role $role)
    {
        $result = $this->roleService->delete($role);

        return $result['error'] ?? false
            ? $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code'])
            : $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }
}

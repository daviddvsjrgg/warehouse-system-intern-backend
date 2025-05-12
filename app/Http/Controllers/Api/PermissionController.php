<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ApiResponse;

    protected $service;

    public function __construct(PermissionService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return $this->sendSuccessResponse(true, 'Permissions retrieved successfully.', $this->service->getAll(), 200);
    }

    public function store(Request $request)
    {
        $result = $this->service->store($request->all());

        return $result['error'] ?? false
            ? $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code'])
            : $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }

    public function show($id)
    {
        $permission = $this->service->show($id);
        return $this->sendSuccessResponse(true, 'Permission retrieved successfully.', $permission, 200);
    }

    public function update(Request $request, $id)
    {
        $result = $this->service->update($id, $request->all());

        return $result['error'] ?? false
            ? $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code'])
            : $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }

    public function destroy($id)
    {
        $result = $this->service->destroy($id);
        return $this->sendSuccessResponse(true, $result['message'], null, $result['status_code']);
    }

    public function assignPermissions(Request $request, $roleId)
    {
        $result = $this->service->assignPermissionsToRole($roleId, $request->permissions);
        return $this->sendSuccessResponse(true, $result['message'], null, $result['status_code']);
    }

    public function showPermissions($roleId)
    {
        $data = $this->service->showPermissionsOfRole($roleId);
        return $this->sendSuccessResponse(true, 'Permissions retrieved successfully.', $data, 200);
    }

    public function removePermission(Request $request, $roleId)
    {
        $result = $this->service->removePermissionFromRole($roleId, $request->permission_id);
        return $this->sendSuccessResponse(true, $result['message'], null, $result['status_code']);
    }
}

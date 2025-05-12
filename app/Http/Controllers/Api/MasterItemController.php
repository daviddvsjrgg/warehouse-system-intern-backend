<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Resources\GeneralResource;
use App\Models\MasterItem;
use App\Services\MasterItemService;
use Illuminate\Http\Request;

class MasterItemController extends Controller
{

    use ApiResponse;

    protected $itemService;

    public function __construct(MasterItemService $itemService)
    {
        $this->itemService = $itemService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $result = $this->itemService->getItems($request->all());

        if (isset($result['error']) && $result['error']) {
            return $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code']);
        }

        return $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }

    /**
     * Store a newly created resource in storage.
     */

     // app/Http/Controllers/Api/MasterItemController.php
    public function store(Request $request)
    {
        $result = $this->itemService->storeItems($request->all());

        if (isset($result['error']) && $result['error']) {
            return $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code']);
        }

        return $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $item = MasterItem::findOrFail($id);
        return new GeneralResource(true, 'Data retrieved successfully!', $item, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $result = $this->itemService->updateItem($request->all(), $id);

        if (isset($result['error']) && $result['error']) {
            return $this->sendErrorResponse(false, $result['message'], $result['data'], $result['status_code']);
        }

        return $this->sendSuccessResponse(true, $result['message'], $result['data'], $result['status_code']);
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $item = MasterItem::findOrFail($id);
        $item->delete();
        return new GeneralResource(true, 'Data deleted successfully!', null, 204);
    }
}

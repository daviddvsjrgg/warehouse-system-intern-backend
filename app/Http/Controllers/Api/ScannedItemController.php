<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\ApiResponse;
use App\Http\Resources\GeneralResource;
use App\Models\ScannedItem;
use App\Services\ScannedItemService;
use Illuminate\Http\Request;

class ScannedItemController extends Controller
{
    use ApiResponse;

    protected $scannedItemService;

    public function __construct(ScannedItemService $scannedItemService)
    {
        $this->scannedItemService = $scannedItemService;
    }

    /**
     * Display a listing of the scanned items with their master items.
     */
    public function index(Request $request)
    {
        $response = $this->scannedItemService->getScannedItems($request->all());

        if ($response['success']) {
            return $this->sendSuccessResponse(true, $response['message'], $response['data'], $response['status_code']);
        }

        return $this->sendErrorResponse(false, $response['message'], null, $response['status_code']);
    }
    
    /**
     * Store a newly created scanned item.
     */
    public function store(Request $request)
    {
        $response = $this->scannedItemService->storeScannedItems($request->all());

        if (isset($response['error'])) {
            return $this->sendErrorResponse(false, $response['message'], null, $response['status_code']);
        }

        return $this->sendSuccessResponse(true, $response['message'], $response['data'], $response['status_code']);
    }

    /**
     * Display the specified scanned item with its master item.
     */
    public function show($id)
    {
        $scannedItem = ScannedItem::with(['master_item', 'user'])->findOrFail($id);
        return new GeneralResource(true, 'Data retrieved successfully!', $scannedItem, 200);
    }

    /**
     * Update the specified scanned item.
     */
    public function updateBarcodeSNOnly(Request $request, $id)
    {
        $response = $this->scannedItemService->updateBarcodeSNOnly($request->all(), $id);

        if (isset($response['error'])) {
            return $this->sendErrorResponse(false, $response['message'], null, $response['status_code']);
        }

        return $this->sendSuccessResponse(true, $response['message'], $response['data'], $response['status_code']);
    }

    public function updateInvoiceOnly(Request $request, $id)
    {
        $response = $this->scannedItemService->updateInvoiceOnly($request->all(), $id);

        if (isset($response['error'])) {
            return $this->sendErrorResponse(false, $response['message'], null, $response['status_code']);
        }

        return $this->sendSuccessResponse(true, $response['message'], $response['data'], $response['status_code']);
    }

    public function updateAllInvoice(Request $request)
    {
        $response = $this->scannedItemService->updateAllInvoice($request->all());

        if (isset($response['error'])) {
            return $this->sendErrorResponse(false, $response['message'], null, $response['status_code']);
        }

        return $this->sendSuccessResponse(true, $response['message'], $response['data'], $response['status_code']);
    }
    /**
     * Remove the specified scanned item.
     */
    public function destroy($id)
    {
        $scannedItem = ScannedItem::findOrFail($id);
        $scannedItem->delete();
        return new GeneralResource(true, 'Scanned item deleted successfully!', null, 204);
    }
    public function indexByInvoice(Request $request)
    {
        $response = $this->scannedItemService->indexByInvoice($request->all());

        if (isset($response['error']) && $response['error'] === true) {
            return $this->sendErrorResponse(false, $response['message'], null, $response['status_code']);
        }

        return $this->sendSuccessResponse($response['success'], $response['message'], $response['data'], $response['status_code']);
    }


    public function checkSNDuplicate(Request $request)
    {
        $response = $this->scannedItemService->checkSNDuplicate($request->all());

        if (isset($response['error']) && $response['error'] === true) {
            return $this->sendErrorResponse(false, $response['message'], null, $response['status_code']);
        }

        return $this->sendSuccessResponse(
            $response['success'], $response['message'], $response['data'], $response['status_code']);
    }

    // public function export(Request $request)
    // {
    //     // Fetch parameters
    //     $exactSearch = $request->input('exact');
    //     $isExactSearch = filter_var($request->input('is_exact_search', false), FILTER_VALIDATE_BOOLEAN); // Whether the search is exact
    //     $selectedFilter = $request->input('selected_filter', ''); // Fetch selected_filter parameter
    //     $startDate = $request->input('start_date');
    //     $endDate = $request->input('end_date');

    //     // Return the response using the GeneralResource format
    //     return Excel::download(new ScannedItemExport($exactSearch, $isExactSearch, $selectedFilter, $startDate, $endDate), 'scanned_items.xlsx');
    // }

}

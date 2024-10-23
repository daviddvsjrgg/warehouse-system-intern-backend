<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\ScannedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon; // Import Carbon for date handling

class ScannedItemController extends Controller
{
    /**
     * Display a listing of the scanned items with their master items.
     */
    public function index(Request $request)
    {
        // Fetch the 'per_page' parameter, or default to 5 if not provided
        $perPage = $request->input('per_page', 5);
        
        // Fetch the 'exact' search term (if provided)
        $exactSearch = $request->input('exact');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build the query
        $query = ScannedItem::with(['master_item', 'user']);

        // If the 'exact' search term is provided, filter by the exact match on 'sku' or 'invoice_number'
        if ($exactSearch) {
            $query->where(function ($q) use ($exactSearch) {
                $q->where('sku', $exactSearch)
                ->orWhere('invoice_number', $exactSearch);
            });
        }

        // If start date and end date are provided, filter by created_at date range
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        // Paginate the results with the requested 'per_page' value
        $scannedItems = $query->latest()->paginate($perPage);

        // Return the response using the GeneralResource format
        return new GeneralResource(true, 'Data retrieved successfully!', $scannedItems, 200);
    }

    


    /**
     * Store a newly created scanned item.
     */
    public function store(Request $request)
    {
        // Validate that 'items' is an array and validate each item's fields
        $validator = Validator::make($request->all(), [
            'items' => 'required|array', // Ensure 'items' is an array
            'items.*.item_id' => 'required|exists:master_items,id', // Ensure each item has a valid item_id
            'items.*.user_id' => 'required|exists:users,id', // Ensure each item has a valid user_id
            'items.*.sku' => 'required|string|max:255', // SKU should be a string with max length 255
            'items.*.invoice_number' => 'required|string|max:255', // Invoice number should be a string with max length 255
            'items.*.qty' => 'required|integer|min:1', // Quantity must be an integer with a minimum value of 1
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(new GeneralResource(false, 'Validation Error', $validator->errors(), 422));
        }

        // Get the validated data (array of items)
        $itemsData = $validator->validated()['items'];

        // Append created_at and updated_at timestamps for each item
        $currentTimestamp = Carbon::now();
        foreach ($itemsData as &$item) {
            $item['created_at'] = $currentTimestamp;
            $item['updated_at'] = $currentTimestamp;
        }

        // Insert items in batch using the ScannedItem model
        ScannedItem::insert($itemsData);

        // Return a success response
        return new GeneralResource(true, 'Scanned items added successfully!', null, 201);
    }


    /**
     * Display the specified scanned item with its master item.
     */
    public function show($id)
    {
        $scannedItem = ScannedItem::with('master_item')->findOrFail($id);
        return new GeneralResource(true, 'Data retrieved successfully!', $scannedItem, 200);
    }

    /**
     * Update the specified scanned item.
     */
    public function update(Request $request, $id)
    {
        // Inline validation
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:master_items,id',
            'user_id' => 'required|exists:users,id',
            'sku' => 'required|string|max:255',
            'invoice_number' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(new GeneralResource(false, 'Validation Error', $validator->errors(), 422));
        }

        // Use the validated data
        $validatedData = $validator->validated();
        $scannedItem = ScannedItem::findOrFail($id);
        $scannedItem->update($validatedData);

        return new GeneralResource(true, 'Scanned item updated successfully!', $scannedItem, 200);
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
}

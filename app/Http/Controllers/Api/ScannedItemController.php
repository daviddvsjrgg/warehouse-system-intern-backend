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
        $invoiceNumbers = $request->input('invoice_numbers', []); // Array of invoice numbers
        $barcodeSNs = $request->input('barcode_sns', []); // Array of barcode SNs
        
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        // Build the query
        $query = ScannedItem::with(['master_item', 'user']);

        // If the 'exact' search term is provided, filter by the exact match on 'sku' or 'invoice_number'
        if ($exactSearch) {
            $query->where(function ($q) use ($exactSearch) {
                $q->where('sku', $exactSearch)
                ->orWhere('barcode_sn', $exactSearch)
                ->orWhere('invoice_number', $exactSearch);
            });
        }

        // If invoice numbers are provided, filter by those numbers
        if (!empty($invoiceNumbers)) {
            $query->whereIn('invoice_number', $invoiceNumbers);
        }

        // If barcode SNs are provided, filter by those SNs
        if (!empty($barcodeSNs)) {
            $query->orWhereIn('barcode_sn', $barcodeSNs);
        }

        // Date filtering logic
        if ($startDate && $endDate && $startDate === $endDate) {
            $query->whereDate('created_at', $startDate);
        } else {
            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
            }
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
            'items.*.qty' => 'required|integer|min:0', // Quantity must be an integer with a minimum value of 0
            'items.*.barcode_sn' => 'required|string|max:255', // Barcode SN should be a string with max length 255
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
        $scannedItem = ScannedItem::with(['master_item', 'user'])->findOrFail($id);
        return new GeneralResource(true, 'Data retrieved successfully!', $scannedItem, 200);
    }

    /**
     * Update the specified scanned item.
     */
    public function update(Request $request, $id)
    {
        // Validate the request for barcode_sn and qty
        $request->validate([
            'barcode_sn' => 'required|string',
            'qty' => 'required|integer',
        ]);
    
        // Find the scanned item by ID
        $scannedItem = ScannedItem::findOrFail($id);
    
        // Update the barcode_sn and qty fields
        $scannedItem->barcode_sn = $request->barcode_sn;
        $scannedItem->qty = $request->qty;
        $scannedItem->save();
    
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

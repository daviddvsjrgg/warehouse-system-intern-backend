<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\ScannedItem;
use DB;
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
    
        // Check if the 'check-duplicate' parameter is set to true
        $checkDuplicate = filter_var($request->input('check-duplicate', false), FILTER_VALIDATE_BOOLEAN);
    
        // If check-duplicate is true, return duplicate barcode_sn records
        if ($checkDuplicate) {
            $duplicates = ScannedItem::select('barcode_sn')
                ->groupBy('barcode_sn')
                ->havingRaw('COUNT(barcode_sn) > 1')
                ->pluck('barcode_sn');
    
            // Fetch the full records of the duplicates
            $duplicateRecords = ScannedItem::whereIn('barcode_sn', $duplicates)
                ->with(['master_item', 'user'])
                ->paginate($perPage);
    
            return new GeneralResource(true, 'Duplicate barcode_sn retrieved successfully!', $duplicateRecords, 200);
        }
    
        // Fetch parameters
        $exactSearch = $request->input('exact');
        $isExactSearch = filter_var($request->input('is_exact_search', false), FILTER_VALIDATE_BOOLEAN); // Whether the search is exact
        $selectedFilter = $request->input('selected_filter', ''); // Fetch selected_filter parameter
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
    
        // Build the query
        $query = ScannedItem::with(['master_item', 'user']);
    
        // Apply the exact/partial search based on the isExactSearch flag
        if ($exactSearch) {
            if ($isExactSearch) {
                // Exact match (using '=' operator)
                $query->where(function ($q) use ($exactSearch) {
                    $q->where('sku', $exactSearch)
                        ->orWhere('barcode_sn', $exactSearch)
                        ->orWhere('invoice_number', $exactSearch);
                });
            } else {
                // Partial match (using 'LIKE' operator)
                $query->where(function ($q) use ($exactSearch) {
                    $q->where('sku', 'like', "%$exactSearch%")
                        ->orWhere('barcode_sn', 'like', "%$exactSearch%")
                        ->orWhere('invoice_number', 'like', "%$exactSearch%");
                });
            }
        }
    
        // Handle selectedFilter parameter for specific field filtering
        if ($selectedFilter) {
            if ($selectedFilter === 'sku') {
                if ($isExactSearch) {
                    $query->where('sku', $exactSearch); // Exact match for SKU
                } else {
                    $query->where('sku', 'like', "%$exactSearch%"); // Partial match for SKU
                }
            } elseif ($selectedFilter === 'invoice') {
                if ($isExactSearch) {
                    $query->where('invoice_number', $exactSearch); // Exact match for invoice number
                } else {
                    $query->where('invoice_number', 'like', "%$exactSearch%"); // Partial match for invoice number
                }
            } elseif ($selectedFilter === 'sn') {
                if ($isExactSearch) {
                    $query->where('barcode_sn', $exactSearch); // Exact match for barcode_sn
                } else {
                    $query->where('barcode_sn', 'like', "%$exactSearch%"); // Partial match for barcode_sn
                }
            }
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
    public function indexByInvoice(Request $request)
    {
        // Validate the search term if provided
        $invoiceNumber = $request->input('invoice_number'); // Get the invoice_number from request
    
        // Get all scanned items along with related master_item and user
        $groupedByInvoiceQuery = ScannedItem::with(['master_item', 'user']); // Eager load the relationships
    
        // Apply exact search for invoice_number if provided
        if ($invoiceNumber) {
            $groupedByInvoiceQuery->where('invoice_number', '=', $invoiceNumber);
        }
    
        // Get the data
        $groupedByInvoice = $groupedByInvoiceQuery
            ->get()
            ->groupBy('invoice_number') // First group by invoice_number
            ->map(function ($items, $invoiceNumber) {
                // Get the user email for the invoice (assuming we take the first user's email)
                $userEmail = $items->first()->user->email ?? 'Unknown User';
    
                // Get the created_at and updated_at timestamps (from the first item in the group)
                $createdAt = $items->first()->created_at;
                $updatedAt = $items->first()->updated_at;
    
                // For each invoice, group by sku and item name
                $groupedItemsBySkuAndName = $items->groupBy(function ($item) {
                    return $item->sku . '|' . $item->master_item->nama_barang;
                })->map(function ($skuAndNameItems) {
                    // Calculate total quantity by counting the number of serial numbers
                    $totalQty = $skuAndNameItems->count();
    
                    return [
                        'sku' => $skuAndNameItems->first()->sku,
                        'item_name' => $skuAndNameItems->first()->master_item->nama_barang ?? 'Unknown Item',
                        'total_qty' => $totalQty, // Add the total quantity here
                        'serial_numbers' => $skuAndNameItems->map(function ($item) {
                            return [
                                'barcode_sn' => $item->barcode_sn
                            ];
                        })
                    ];
                });
    
                return [
                    'invoice_number' => $invoiceNumber,
                    'total_qty' => $items->sum('qty'), // Calculate total quantity per invoice
                    'items' => $groupedItemsBySkuAndName->values(), // Reset keys and return the grouped items
                    'user_email' => $userEmail, // Include the user email
                    'created_at' => $createdAt, // Include the created_at timestamp
                    'updated_at' => $updatedAt  // Include the updated_at timestamp
                ];
            })
            ->values(); // Reset the keys to numeric indices
    
        // Paginate the grouped data
        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $groupedByInvoice->forPage($request->input('page', 1), 5), // Paginate with 5 items per page
            $groupedByInvoice->count(),
            5,
            $request->input('page', 1),
            ['path' => url()->current()]
        );
    
        return new GeneralResource(true, 'Grouped scanned items by invoice and SKU retrieved successfully!', $paginated, 200);
    }
    

}

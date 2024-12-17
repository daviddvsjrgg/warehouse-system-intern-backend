<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\MasterItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use DB;

class MasterItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Extract query, exact flag, and items per page (with a default value of 5)
        $query = $request->input('query');
        $exact = $request->input('exact', false); // Exact flag, defaults to false
        $perPage = $request->input('per_page', 5); // Defaults to 5 if not provided
    
        // If 'exact' is true and a query is provided, find the exact match by SKU
        if ($exact && $query) {
            $items = MasterItem::where('sku', $query)
                ->orWhere('nama_barang', $query)
                ->get();
    
            // If no item is found, return a custom error response
            if ($items->isEmpty()) {
                return response()->json(new GeneralResource(false, 'Item not found', null, 404));
            }
    
            // Return the exact match without pagination
            return new GeneralResource(true, 'Exact SKU match found!', $items, 200);
        }
    
        // If 'exact' is false or no query, search with pagination and partial matching
        $items = MasterItem::when($query, function ($queryBuilder) use ($query) {
                return $queryBuilder->where('nama_barang', 'like', "%{$query}%")
                    ->orWhere('barcode_sn', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->distinct() // Ensure the query is distinct (no duplicates)
            ->paginate($perPage); // Paginate with dynamic per-page value
    
        // Return paginated results wrapped in a resource
        return new GeneralResource(true, 'Data retrieved successfully!', $items, 200);
    }
    


    /**
     * Store a newly created resource in storage.
     */

     public function store(Request $request)
     {
         // Validate the request data
         $validator = Validator::make($request->all(), [
             'items' => 'required|array',
             'items.*.nama_barang' => 'required|string|max:255',
             'items.*.barcode_sn' => 'required|string|max:255',
             'items.*.sku' => 'required|string|max:255',
         ]);
     
         // If validation fails, return detailed error messages
         if ($validator->fails()) {
             return response()->json(new GeneralResource(false, 'Validation Error', $validator->errors(), 422));
         }
     
         // Extract validated data
         $itemsData = $validator->validated()['items'];
         $currentTimestamp = Carbon::now();
     
         // Add timestamps to each item
         foreach ($itemsData as &$item) {
             $item['created_at'] = $currentTimestamp;
             $item['updated_at'] = $currentTimestamp;
         }
     
         // Start a database transaction
         DB::beginTransaction();
     
         try {
             // Use chunking for large datasets
             foreach (array_chunk($itemsData, 1000) as $chunk) {
                 MasterItem::insert($chunk); // Batch insert each chunk
             }
     
             // Commit the transaction if everything is successful
             DB::commit();
     
             // Return success response
             return new GeneralResource(true, 'Data added successfully!', null, 201);
         } catch (\Exception $e) {
             // Rollback transaction if an error occurs
             DB::rollBack();
     
             // Return error response
             return response()->json(new GeneralResource(false, 'Error adding data', $e->getMessage(), 500));
         }
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
        // Inline validation
        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|max:255',
            'barcode_sn' => 'required|string|max:255|unique:master_items,barcode_sn,' . $id,
            'sku' => 'required|string|max:255|unique:master_items,sku,' . $id,
        ]);

        if ($validator->fails()) {
            return response()->json(new GeneralResource(false, 'Validation Error', $validator->errors(), 422));
        }

        $item = MasterItem::findOrFail($id);
        $item->update($validator->validated());
        return new GeneralResource(true, 'Data updated successfully!', $item, 200);
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

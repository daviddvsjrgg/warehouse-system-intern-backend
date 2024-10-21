<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\MasterItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MasterItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Extract query and items per page (with a default value of 5)
        $query = $request->input('query');
        $perPage = $request->input('per_page', 5); // Defaults to 5 if not provided

        // Retrieve items using search query if provided
        $items = MasterItem::when($query, function ($queryBuilder) use ($query) {
            return $queryBuilder->where('nama_barang', 'like', "%{$query}%")
                ->orWhere('barcode_sn', 'like', "%{$query}%")
                ->orWhere('sku', 'like', "%{$query}%");
        })
        ->paginate($perPage); // Paginate with dynamic per-page value

        // Return paginated results wrapped in a resource
        return new GeneralResource(true, 'Data retrieved successfully!', $items, 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Inline validation for batch data
        $validator = Validator::make($request->all(), [
            'items' => 'required|array', // Ensure 'items' is an array
            'items.*.nama_barang' => 'required|string|max:255',
            'items.*.barcode_sn' => 'required|string|max:255|unique:master_items,barcode_sn',
            'items.*.sku' => 'required|string|max:255|unique:master_items,sku',
        ]);

        if ($validator->fails()) {
            return response()->json(new GeneralResource(false, 'Validation Error', $validator->errors(), 422));
        }

        // Create items in batch
        $itemsData = $validator->validated()['items']; // Get validated items
        $items = MasterItem::insert($itemsData); // Use insert for batch creation

        return new GeneralResource(true, 'Data added successfully!', $items, 201);
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

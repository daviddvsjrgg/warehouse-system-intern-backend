<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\ScannedItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ScannedItemController extends Controller
{
    /**
     * Display a listing of the scanned items with their master items.
     */
    public function index(Request $request)
    {
        $scannedItems = ScannedItem::with('master_item')->paginate(5);
        return new GeneralResource(true, 'Data retrieved successfully!', $scannedItems, 200);
    }

    /**
     * Store a newly created scanned item.
     */
    public function store(Request $request)
    {
        // Inline validation
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:master_items,id',
            'sku' => 'required|string|max:255',
            'invoice_number' => 'required|string|max:255',
            'qty' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(new GeneralResource(false, 'Validation Error', $validator->errors(), 422));
        }

        // Use the validated data
        $validatedData = $validator->validated();
        $scannedItem = ScannedItem::create($validatedData);

        return new GeneralResource(true, 'Scanned item added successfully!', $scannedItem, 201);
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

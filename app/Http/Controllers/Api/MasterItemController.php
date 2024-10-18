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
    public function index()
    {
        $items = MasterItem::paginate(10);
        return new GeneralResource(true, 'Data retrieved successfully!', $items, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Inline validation
        $validator = Validator::make($request->all(), [
            'nama_barang' => 'required|string|max:255',
            'barcode_sn' => 'required|string|max:255|unique:master_items',
            'sku' => 'required|string|max:255|unique:master_items',
        ]);

        if ($validator->fails()) {
            return response()->json(new GeneralResource(false, 'Validation Error', $validator->errors(), 422));
        }

        $item = MasterItem::create($validator->validated());
        return new GeneralResource(true, 'Data added successfully!', $item, 201);
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

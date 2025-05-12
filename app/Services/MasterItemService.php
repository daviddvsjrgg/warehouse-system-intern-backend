<?php

namespace App\Services;

use App\Models\MasterItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MasterItemService
{
    public function getItems(array $filters)
    {
        $query = $filters['query'] ?? null;
        $exact = filter_var($filters['exact'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $perPage = $filters['per_page'] ?? 5;

        if ($exact && $query) {
            $items = MasterItem::where('sku', $query)
                ->orWhere('nama_barang', $query)
                ->get();

            if ($items->isEmpty()) {
                return [
                    'error' => true,
                    'message' => 'Item not found',
                    'data' => null,
                    'status_code' => 404
                ];
            }

            return [
                'success' => true,
                'message' => 'Exact SKU match found!',
                'data' => $items,
                'status_code' => 200
            ];
        }

        $items = MasterItem::when($query, function ($queryBuilder) use ($query) {
                return $queryBuilder->where('nama_barang', 'like', "%{$query}%")
                    ->orWhere('barcode_sn', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->paginate($perPage);

        return [
            'success' => true,
            'message' => 'Data retrieved successfully!',
            'data' => $items,
            'status_code' => 200
        ];
    }
    public function storeItems(array $data)
    {
        $validator = Validator::make($data, [
            'items' => 'required|array',
            'items.*.nama_barang' => 'required|string|max:255',
            'items.*.barcode_sn' => 'required|string|max:255',
            'items.*.sku' => 'required|unique:master_items,sku|string|max:255',
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors();
            $conflictingSkus = [];

            foreach ($data['items'] as $index => $item) {
                if ($errors->has("items.$index.sku")) {
                    $conflictingSkus[] = $item['sku'];
                }
            }

            $message = 'Validation Error';
            if (!empty($conflictingSkus)) {
                $message = "Validation Error: The following SKUs have already been taken: " . implode(', ', $conflictingSkus) . ".";
            }

            return [
                'error' => true,
                'message' => $message,
                'data' => $errors,
                'status_code' => 422,
            ];
        }

        $itemsData = $validator->validated()['items'];
        $timestamp = Carbon::now();

        foreach ($itemsData as &$item) {
            $item['created_at'] = $timestamp;
            $item['updated_at'] = $timestamp;
        }

        DB::beginTransaction();

        try {
            foreach (array_chunk($itemsData, 1000) as $chunk) {
                MasterItem::insert($chunk);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Data added successfully!',
                'data' => null,
                'status_code' => 201
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'error' => true,
                'message' => 'Error adding data',
                'data' => $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }

    public function updateItem(array $data, $id)
    {
        $validator = Validator::make($data, [
            'nama_barang' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return [
                'error' => true,
                'message' => 'Validation Error',
                'data' => $validator->errors(),
                'status_code' => 422,
            ];
        }

        try {
            $item = MasterItem::findOrFail($id);

            $item->update([
                'nama_barang' => $data['nama_barang'],
            ]);

            return [
                'success' => true,
                'message' => 'Data updated successfully!',
                'data' => $item,
                'status_code' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Item not found or update failed',
                'data' => $e->getMessage(),
                'status_code' => 500,
            ];
        }
    }
}

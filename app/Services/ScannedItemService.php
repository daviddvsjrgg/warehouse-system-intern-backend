<?php

namespace App\Services;

use App\Models\ScannedItem;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ScannedItemService
{
    public function getScannedItems(array $request)
    {
        $perPage = $request['per_page'] ?? 5;
        if ($perPage > 500) {
            return [
                'success' => true,
                'message' => 'per_page cannot more than 500',
                'data' => null,
                'status_code' => 400
            ];
        }
        
        $checkDuplicate = filter_var($request['check-duplicate'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($checkDuplicate) {
            $duplicates = ScannedItem::select('barcode_sn')
                ->groupBy('barcode_sn')
                ->havingRaw('COUNT(barcode_sn) > 1')
                ->pluck('barcode_sn');

            $items = ScannedItem::whereIn('barcode_sn', $duplicates)
                ->with(['master_item', 'user'])
                ->paginate($perPage);

            return [
                'success' => true,
                'message' => 'Duplicate barcode_sn retrieved successfully!',
                'data' => $items,
                'status_code' => 200
            ];
        }

        $exactSearch = $request['exact'] ?? null;
        $isExactSearch = filter_var($request['is_exact_search'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $selectedFilter = $request['selected_filter'] ?? '';
        $startDate = $request['start_date'] ?? null;
        $endDate = $request['end_date'] ?? null;

        $query = ScannedItem::with(['master_item', 'user']);

        if ($exactSearch) {
            $query->where(function ($q) use ($exactSearch, $isExactSearch) {
                $value = $isExactSearch ? $exactSearch : "%$exactSearch%";
                $operator = $isExactSearch ? '=' : 'like';

                $q->where('sku', $operator, $value)
                    ->orWhere('barcode_sn', $operator, $value)
                    ->orWhere('invoice_number', $operator, $value);
            });
        }

        if ($selectedFilter) {
            $fieldMap = [
                'sku' => 'sku',
                'invoice' => 'invoice_number',
                'sn' => 'barcode_sn',
            ];

            if (isset($fieldMap[$selectedFilter])) {
                $field = $fieldMap[$selectedFilter];
                $query->where($field, $isExactSearch ? '=' : 'like', $isExactSearch ? $exactSearch : "%$exactSearch%");
            }
        }

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

        $items = $query->latest()->paginate($perPage);

        return [
            'success' => true,
            'message' => 'Data retrieved successfully!',
            'data' => $items,
            'status_code' => 200
        ];
    }

    public function storeScannedItems(array $request)
    {
        $validator = Validator::make($request, [
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:master_items,id',
            'items.*.user_id' => 'required|exists:users,id',
            'items.*.sku' => 'required|string|max:255',
            'items.*.invoice_number' => 'required|string|max:255',
            'items.*.qty' => 'required|integer|min:0',
            'items.*.barcode_sn' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return [
                'error' => 'error',
                'message' => $validator->errors(),
                'status_code' => 422,
            ];
        }

        $itemsData = $validator->validated()['items'];
        $timestamp = now();

        foreach ($itemsData as &$item) {
            $item['created_at'] = $timestamp;
            $item['updated_at'] = $timestamp;
        }

        ScannedItem::insert($itemsData);

        return [
            'success' => true,
            'message' => 'Scanned items added successfully!',
            'data' => null,
            'status_code' => 201,
        ];
    }

    public function updateBarcodeSNOnly(array $request, $id)
    {
        $validator = Validator::make($request, [
            'barcode_sn' => 'required|string',
            'qty' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return [
                'error' => 'error',
                'message' => $validator->errors(),
                'status_code' => 422,
            ];
        }

        $scannedItem = ScannedItem::find($id);

        if (!$scannedItem) {
            return [
                'error' => 'error',
                'message' => 'Scanned item not found.',
                'status_code' => 404,
            ];
        }

        $scannedItem->barcode_sn = $request['barcode_sn'];
        $scannedItem->qty = $request['qty'];
        $scannedItem->save();

        return [
            'success' => true,
            'message' => 'SN item updated successfully!',
            'data' => $scannedItem,
            'status_code' => 200,
        ];
    }
    public function updateInvoiceOnly(array $request, $id)
    {
        $validator = Validator::make($request, [
            'invoice_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return [
                'error' => 'error',
                'message' => $validator->errors(),
                'status_code' => 422,
            ];
        }

        $scannedItem = ScannedItem::find($id);

        if (!$scannedItem) {
            return [
                'error' => 'error',
                'message' => 'Scanned item not found.',
                'status_code' => 404,
            ];
        }

        $scannedItem->invoice_number = $request['invoice_number'];
        $scannedItem->save();

        return [
            'success' => true,
            'message' => 'Invoice item updated successfully!',
            'data' => $scannedItem,
            'status_code' => 200,
        ];
    }

    public function updateAllInvoice(array $request)
    {
        $editInvoice = $request['editInvoice'] ?? null;
        $editTempInvoice = $request['editTempInvoice'] ?? null;

        if (!$editInvoice || !$editTempInvoice) {
            return [
                'error' => 'error',
                'message' => 'Both original and edited invoice numbers are required',
                'status_code' => 400,
            ];
        }

        DB::beginTransaction();

        try {
            $scannedItems = ScannedItem::where('invoice_number', $editInvoice)->get();

            if ($scannedItems->isEmpty()) {
                return [
                    'error' => 'error',
                    'message' => 'No scanned items found with the original invoice number',
                    'status_code' => 404,
                ];
            }

            foreach ($scannedItems as $scannedItem) {
                $scannedItem->invoice_number = $editTempInvoice;
                $scannedItem->save();
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Related Invoice items updated successfully!',
                'data' => $scannedItems,
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'error' => 'error',
                'message' => 'An error occurred while updating invoice numbers. Please try again.',
                'status_code' => 500,
            ];
        }
    }
    
    public function indexByInvoice(array $request)
    {
        $invoiceNumber = $request['invoice_number'] ?? null;
    
        if (!$invoiceNumber) {
            return [
                'error' => true,
                'message' => 'invoice_number harus dimasukkan',
                'data' => null,
                'status_code' => 422,
            ];
        }
    
        $query = ScannedItem::with(['master_item', 'user'])
            ->where('invoice_number', '=', $invoiceNumber);
    
        $grouped = $query->get()
            ->groupBy('invoice_number')
            ->map(function ($items, $invoiceNumber) {
                $firstItem = $items->first();
                $userEmail = $firstItem->user->email ?? 'Unknown Email';
                $userName = $firstItem->user->name ?? 'Unknown User';
                $createdAt = $firstItem->created_at;
                $updatedAt = $firstItem->updated_at;
    
                $groupedItems = $items->groupBy(function ($item) {
                    return $item->sku . '|' . $item->master_item->nama_barang;
                })->map(function ($group) {
                    return [
                        'sku' => $group->first()->sku,
                        'item_name' => $group->first()->master_item->nama_barang ?? 'Unknown Item',
                        'total_qty' => $group->count(),
                        'serial_numbers' => $group->map(function ($item) {
                            return [
                                'barcode_sn' => $item->barcode_sn,
                                'created_at' => $item->created_at,
                                'updated_at' => $item->updated_at,
                            ];
                        })->values()
                    ];
                });
    
                return [
                    'invoice_number' => $invoiceNumber,
                    'total_qty' => $items->sum('qty'),
                    'items' => $groupedItems->values(),
                    'user_name' => $userName,
                    'user_email' => $userEmail,
                    'created_at' => $createdAt,
                    'updated_at' => $updatedAt,
                ];
            })->values();
    
        $page = $request['page'] ?? 1;
        $perPage = 5;
        $paginated = new LengthAwarePaginator(
            $grouped->forPage($page, $perPage),
            $grouped->count(),
            $perPage,
            $page,
            ['path' => url()->current()]
        );
    
        return [
            'success' => true,
            'message' => 'Grouped scanned items by invoice and SKU retrieved successfully!',
            'data' => $paginated,
            'status_code' => 200,
        ];
    }

    public function checkSNDuplicate(array $request)
    {
        $invoices = array_unique($request['invoices'] ?? []);
        $barcodes = $request['barcodes'] ?? [];

        if (empty($invoices) && empty($barcodes)) {
            return [
                'error' => true,
                'message' => 'Invoices atau barcodes harus diisi.',
                'data' => null,
                'status_code' => 422,
            ];
        }

        $duplicates = [
            'invoices' => [],
            'barcodes' => [],
        ];

        foreach ($invoices as $invoice) {
            if (ScannedItem::where('invoice_number', $invoice)->exists()) {
                $duplicates['invoices'][] = $invoice;
            }
        }

        foreach ($barcodes as $barcode) {
            if (ScannedItem::where('barcode_sn', $barcode)->exists()) {
                $duplicates['barcodes'][] = $barcode;
            }
        }

        if (!empty($duplicates['invoices']) || !empty($duplicates['barcodes'])) {
            return [
                'success' => true,
                'message' => 'Terdeteksi Duplikat!',
                'data' => $duplicates,
                'status_code' => 200,
            ];
        }

        return [
            'success' => true,
            'message' => 'Tidak Ada Duplikat!',
            'data' => null,
            'status_code' => 200,
        ];
    }


}

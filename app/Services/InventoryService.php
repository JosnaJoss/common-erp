<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockBatch;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Exception;

class InventoryService
{
    /**
     * Process stock inward (e.g. Purchase, GRN, Production, Return In)
     */
    public function recordInward(
        int $companyId,
        ?int $branchId,
        int $godownId,
        int $itemId,
        float $quantity,
        float $unitCost,
        string $batchNo = 'DEFAULT',
        ?string $expiryDate = null,
        string $referenceType = 'PurchaseEntry',
        ?int $referenceId = null,
        ?string $referenceNo = null,
        ?string $remarks = null
    ): StockMovement {
        return DB::transaction(function () use (
            $companyId, $branchId, $godownId, $itemId, $quantity, $unitCost, $batchNo, $expiryDate,
            $referenceType, $referenceId, $referenceNo, $remarks
        ) {
            $batch = StockBatch::firstOrCreate(
                [
                    'godown_id' => $godownId,
                    'item_id' => $itemId,
                    'batch_no' => $batchNo ?: 'DEFAULT',
                ],
                [
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'batch_name' => 'Batch ' . ($batchNo ?: 'DEFAULT'),
                    'quantity' => 0,
                    'cost_price' => $unitCost,
                    'added_date' => now(),
                    'expiry_date' => $expiryDate,
                    'status' => 'active',
                ]
            );

            $batch->quantity = (float)$batch->quantity + $quantity;
            if ($unitCost > 0) {
                $batch->cost_price = $unitCost;
            }
            $batch->save();

            // Total godown balance after
            $balanceAfter = StockBatch::where('godown_id', $godownId)
                ->where('item_id', $itemId)
                ->sum('quantity');

            return StockMovement::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'godown_id' => $godownId,
                'item_id' => $itemId,
                'batch_id' => $batch->id,
                'movement_date' => now()->toDateString(),
                'movement_type' => 'inward',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'balance_after' => $balanceAfter,
                'remarks' => $remarks ?: "Stock Inward via {$referenceType}",
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Process stock outward (e.g. Sales, Dispatch, Issue, Return Out)
     */
    public function recordOutward(
        int $companyId,
        ?int $branchId,
        int $godownId,
        int $itemId,
        float $quantity,
        string $batchNo = 'DEFAULT',
        string $referenceType = 'SalesInvoice',
        ?int $referenceId = null,
        ?string $referenceNo = null,
        ?string $remarks = null,
        bool $allowNegative = false
    ): StockMovement {
        return DB::transaction(function () use (
            $companyId, $branchId, $godownId, $itemId, $quantity, $batchNo,
            $referenceType, $referenceId, $referenceNo, $remarks, $allowNegative
        ) {
            $batch = StockBatch::where('godown_id', $godownId)
                ->where('item_id', $itemId)
                ->where('batch_no', $batchNo ?: 'DEFAULT')
                ->first();

            $availableQty = $batch ? (float)$batch->quantity : 0;

            if (!$allowNegative && $availableQty < $quantity) {
                $item = Item::find($itemId);
                $itemName = $item ? $item->name : "Item #{$itemId}";
                throw new Exception("Insufficient stock for '{$itemName}' in selected godown. Available: {$availableQty}, Requested: {$quantity}");
            }

            if (!$batch) {
                $batch = StockBatch::create([
                    'company_id' => $companyId,
                    'branch_id' => $branchId,
                    'godown_id' => $godownId,
                    'item_id' => $itemId,
                    'batch_no' => $batchNo ?: 'DEFAULT',
                    'quantity' => 0,
                    'cost_price' => 0,
                    'added_date' => now(),
                    'status' => 'active',
                ]);
            }

            $batch->quantity = (float)$batch->quantity - $quantity;
            $batch->save();

            $balanceAfter = StockBatch::where('godown_id', $godownId)
                ->where('item_id', $itemId)
                ->sum('quantity');

            return StockMovement::create([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'godown_id' => $godownId,
                'item_id' => $itemId,
                'batch_id' => $batch->id,
                'movement_date' => now()->toDateString(),
                'movement_type' => 'outward',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_no' => $referenceNo,
                'quantity' => -$quantity,
                'unit_cost' => (float)$batch->cost_price,
                'balance_after' => $balanceAfter,
                'remarks' => $remarks ?: "Stock Outward via {$referenceType}",
                'created_by' => auth()->id(),
            ]);
        });
    }

    /**
     * Transfer stock between godowns
     */
    public function transferStock(
        int $companyId,
        int $sourceGodownId,
        int $destGodownId,
        int $itemId,
        float $quantity,
        string $batchNo = 'DEFAULT',
        ?string $referenceNo = null
    ): void {
        DB::transaction(function () use (
            $companyId, $sourceGodownId, $destGodownId, $itemId, $quantity, $batchNo, $referenceNo
        ) {
            // Outward from source
            $this->recordOutward(
                $companyId, null, $sourceGodownId, $itemId, $quantity, $batchNo,
                'StockTransfer', null, $referenceNo, "Transferred to Godown #{$destGodownId}"
            );

            // Inward to destination
            $sourceBatch = StockBatch::where('godown_id', $sourceGodownId)
                ->where('item_id', $itemId)
                ->where('batch_no', $batchNo)
                ->first();
            $unitCost = $sourceBatch ? (float)$sourceBatch->cost_price : 0;

            $this->recordInward(
                $companyId, null, $destGodownId, $itemId, $quantity, $unitCost, $batchNo,
                $sourceBatch?->expiry_date?->toDateString(), 'StockTransfer', null, $referenceNo, "Transferred from Godown #{$sourceGodownId}"
            );
        });
    }

    /**
     * Dispatch a stock transfer (deducts stock from source, marks in_transit)
     */
    public function dispatchTransfer(\App\Models\StockTransfer $transfer, int $userId): void
    {
        DB::transaction(function () use ($transfer, $userId) {
            if ($transfer->status !== 'pending') {
                throw new Exception("Only pending transfers can be dispatched.");
            }

            foreach ($transfer->items as $item) {
                $batchNo = $item->batch ? $item->batch->batch_no : 'DEFAULT';
                $this->recordOutward(
                    $transfer->company_id,
                    $transfer->source_branch_id,
                    $transfer->source_godown_id,
                    $item->item_id,
                    (float)$item->quantity,
                    $batchNo,
                    'StockTransfer',
                    $transfer->id,
                    $transfer->transfer_no,
                    "Dispatched to Godown #{$transfer->dest_godown_id}"
                );
            }

            $transfer->update([
                'status' => 'in_transit',
                'dispatched_by' => $userId,
                'dispatched_at' => now(),
            ]);
        });
    }

    /**
     * Receive / Confirm a stock transfer (inwards stock to destination, marks completed)
     */
    public function receiveTransfer(\App\Models\StockTransfer $transfer, int $userId): void
    {
        DB::transaction(function () use ($transfer, $userId) {
            if ($transfer->status === 'completed') {
                throw new Exception("Transfer is already completed.");
            }

            // If not yet dispatched, dispatch it first so source stock is cleanly deducted
            if ($transfer->status === 'pending') {
                $this->dispatchTransfer($transfer, $userId);
            }

            foreach ($transfer->items as $item) {
                $batchNo = $item->batch ? $item->batch->batch_no : 'DEFAULT';
                $sourceBatch = StockBatch::where('godown_id', $transfer->source_godown_id)
                    ->where('item_id', $item->item_id)
                    ->where('batch_no', $batchNo)
                    ->first();
                $unitCost = $sourceBatch ? (float)$sourceBatch->cost_price : 0;

                $this->recordInward(
                    $transfer->company_id,
                    $transfer->dest_branch_id,
                    $transfer->dest_godown_id,
                    $item->item_id,
                    (float)$item->quantity,
                    $unitCost,
                    $batchNo,
                    $sourceBatch?->expiry_date?->toDateString(),
                    'StockTransfer',
                    $transfer->id,
                    $transfer->transfer_no,
                    "Received from Godown #{$transfer->source_godown_id}"
                );
            }

            $transfer->update([
                'status' => 'completed',
                'received_by' => $userId,
                'received_at' => now(),
            ]);
        });
    }

    /**
     * Ensure a delivery van has an associated mobile stock godown
     */
    public function ensureMobileVanGodown(\App\Models\Vehicle $vehicle): \App\Models\Godown
    {
        $code = 'VAN-' . preg_replace('/[^A-Za-z0-9]/', '', strtoupper($vehicle->registration_number));
        return \App\Models\Godown::firstOrCreate(
            [
                'company_id' => $vehicle->company_id,
                'vehicle_id' => $vehicle->id,
            ],
            [
                'branch_id' => $vehicle->branch_id,
                'name' => "Mobile Van Stock ({$vehicle->vehicle_name} - {$vehicle->registration_number})",
                'code' => $code,
                'location_type' => 'van_mobile',
                'responsible_staff' => $vehicle->assigned_driver ?? 'Assigned Driver',
                'address' => 'Mobile Fleet Unit',
                'status' => 'active',
            ]
        );
    }
}


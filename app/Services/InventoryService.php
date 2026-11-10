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
}


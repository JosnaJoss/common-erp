<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBatch;
use App\Models\StockDiscrepancy;
use App\Models\StockDiscrepancyApproval;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\AccountingService;
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

                $item->update([
                    'dispatched_qty' => $item->quantity,
                    'in_transit_qty' => $item->quantity,
                ]);
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

                $item->update([
                    'received_qty' => $item->dispatched_qty > 0 ? $item->dispatched_qty : $item->quantity,
                    'in_transit_qty' => 0,
                ]);
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

    /**
     * Revert inward stock quantity (e.g. when editing a purchase bill)
     */
    public function revertInward(int $godownId, int $itemId, float $quantity, string $batchNo = 'DEFAULT'): void
    {
        $batch = StockBatch::where('godown_id', $godownId)
            ->where('item_id', $itemId)
            ->where('batch_no', $batchNo ?: 'DEFAULT')
            ->first();

        if ($batch) {
            $batch->quantity = max(0, (float)$batch->quantity - $quantity);
            $batch->save();
        }
    }

    /**
     * Revert outward stock quantity (e.g. when editing a sales invoice)
     */
    public function revertOutward(int $godownId, int $itemId, float $quantity, string $batchNo = 'DEFAULT'): void
    {
        $batch = StockBatch::where('godown_id', $godownId)
            ->where('item_id', $itemId)
            ->where('batch_no', $batchNo ?: 'DEFAULT')
            ->first();

        if ($batch) {
            $batch->quantity = (float)$batch->quantity + $quantity;
            $batch->save();
        }
    }

    // ==================== INTERNAL STOCK MOVEMENT METHODS ====================

    /**
     * Create an internal stock request from a department.
     */
    public function createStockRequest(array $data, array $items, int $userId): StockRequest
    {
        return DB::transaction(function () use ($data, $items, $userId) {
            $companyId = (int)$data['company_id'];
            $branchId = isset($data['branch_id']) ? (int)$data['branch_id'] : null;
            $fromDeptId = (int)$data['from_department_id'];
            $sourceGodownId = (int)$data['source_godown_id'];

            $fromDept = Department::where('id', $fromDeptId)->where('company_id', $companyId)->firstOrFail();
            $sourceGodown = Godown::where('id', $sourceGodownId)->where('company_id', $companyId)->firstOrFail();

            $fromGodownId = $data['from_godown_id'] ?? null;
            if ($fromDept->maintains_stock && empty($fromGodownId)) {
                $fromGodownId = $fromDept->default_godown_id;
            }

            if ($fromDept->maintains_stock && empty($fromGodownId)) {
                throw new Exception("Department '{$fromDept->name}' maintains stock but has no designated stock location configured.");
            }

            $dateCompact = date('Ymd');
            $reqNo = $data['request_no'] ?? ('REQ-' . $dateCompact . '-' . rand(1000, 9999));
            while (StockRequest::where('company_id', $companyId)->where('request_no', $reqNo)->exists()) {
                $reqNo = 'REQ-' . $dateCompact . '-' . rand(1000, 9999);
            }

            $request = StockRequest::create([
                'company_id' => $companyId,
                'branch_id' => $branchId ?: $fromDept->branch_id,
                'request_no' => $reqNo,
                'movement_type' => $data['movement_type'] ?? 'Item Request',
                'request_date' => $data['request_date'] ?? now()->toDateString(),
                'required_date' => $data['required_date'] ?? null,
                'priority' => $data['priority'] ?? 'medium',
                'from_department_id' => $fromDeptId,
                'to_department_id' => $data['to_department_id'] ?? $sourceGodown->department_id,
                'from_godown_id' => $fromGodownId,
                'source_godown_id' => $sourceGodownId,
                'reference_file' => $data['reference_file'] ?? null,
                'remarks' => $data['remarks'] ?? null,
                'status' => 'requested',
                'created_by' => $userId,
            ]);

            foreach ($items as $itemData) {
                $qty = (float)($itemData['requested_qty'] ?? $itemData['quantity'] ?? 0);
                if ($qty <= 0) {
                    throw new Exception("Requested quantity must be greater than zero.");
                }

                $item = Item::where('id', $itemData['item_id'])->where('company_id', $companyId)->firstOrFail();

                StockRequestItem::create([
                    'stock_request_id' => $request->id,
                    'item_id' => $item->id,
                    'unit_id' => $itemData['unit_id'] ?? $item->unit_id,
                    'requested_qty' => $qty,
                    'approved_qty' => 0,
                    'dispatched_qty' => 0,
                    'received_qty' => 0,
                    'preferred_batch_id' => $itemData['preferred_batch_id'] ?? null,
                    'remarks' => $itemData['remarks'] ?? null,
                ]);
            }

            return $request;
        });
    }

    /**
     * Approve or partially approve a stock request.
     */
    public function approveStockRequest(StockRequest $request, array $approvedItems, int $userId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($request, $approvedItems, $userId, $remarks) {
            if (!in_array($request->status, ['requested', 'pending', 'partially_approved'])) {
                throw new Exception("Only requested or pending stock requests can be approved.");
            }

            $totalRequested = 0;
            $totalApproved = 0;
            $anyApproved = false;

            foreach ($request->items as $reqItem) {
                $approvedQty = null;
                foreach ($approvedItems as $app) {
                    if ((int)$app['id'] === $reqItem->id || (int)($app['stock_request_item_id'] ?? 0) === $reqItem->id) {
                        $approvedQty = (float)$app['approved_qty'];
                        break;
                    }
                }

                if ($approvedQty === null) {
                    $approvedQty = (float)$reqItem->requested_qty;
                }

                if ($approvedQty > (float)$reqItem->requested_qty) {
                    throw new Exception("Approved quantity ({$approvedQty}) cannot exceed requested quantity ({$reqItem->requested_qty}) for Item #{$reqItem->item_id}.");
                }

                $reqItem->update(['approved_qty' => max(0, $approvedQty)]);

                $totalRequested += (float)$reqItem->requested_qty;
                $totalApproved += max(0, $approvedQty);
                if ($approvedQty > 0) {
                    $anyApproved = true;
                }
            }

            $newStatus = 'approved';
            if (!$anyApproved) {
                $newStatus = 'rejected';
            } elseif ($totalApproved < $totalRequested) {
                $newStatus = 'partially_approved';
            }

            $request->update([
                'status' => $newStatus,
                'approved_by' => $userId,
                'approved_at' => now(),
                'remarks' => $remarks ?: $request->remarks,
            ]);
        });
    }

    /**
     * Reject a stock request with mandatory reason.
     */
    public function rejectStockRequest(StockRequest $request, string $reason, int $userId): void
    {
        if (trim($reason) === '') {
            throw new Exception("Rejection reason is mandatory.");
        }

        $request->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    /**
     * Dispatch stock against an approved stock request (Batch-wise dispatch, sets In-Transit).
     */
    public function dispatchStockRequest(
        StockRequest $request,
        array $dispatchLines,
        int $userId,
        ?string $dispatchReference = null,
        ?string $remarks = null
    ): StockTransfer {
        return DB::transaction(function () use ($request, $dispatchLines, $userId, $dispatchReference, $remarks) {
            if (!in_array($request->status, ['approved', 'partially_approved'])) {
                throw new Exception("Stock can only be dispatched for approved or partially approved requests.");
            }

            $companyId = $request->company_id;
            $sourceGodownId = $request->source_godown_id;
            $destGodownId = $request->from_godown_id ?? $request->source_godown_id;

            $transferNo = 'TRF-' . date('Ymd') . '-' . rand(1000, 9999);
            while (StockTransfer::where('company_id', $companyId)->where('transfer_no', $transferNo)->exists()) {
                $transferNo = 'TRF-' . date('Ymd') . '-' . rand(1000, 9999);
            }

            $transfer = StockTransfer::create([
                'company_id' => $companyId,
                'stock_request_id' => $request->id,
                'transfer_no' => $transferNo,
                'dispatch_reference' => $dispatchReference,
                'transfer_date' => now()->toDateString(),
                'source_branch_id' => $request->branch_id,
                'source_godown_id' => $sourceGodownId,
                'dest_branch_id' => $request->branch_id,
                'dest_godown_id' => $destGodownId,
                'status' => 'in_transit',
                'dispatched_by' => $userId,
                'dispatched_at' => now(),
                'remarks' => $remarks ?: "Dispatch against Request #{$request->request_no}",
                'created_by' => $userId,
            ]);

            foreach ($dispatchLines as $line) {
                $reqItemId = (int)$line['stock_request_item_id'];
                $reqItem = StockRequestItem::where('id', $reqItemId)->where('stock_request_id', $request->id)->firstOrFail();

                $dispatchQty = (float)$line['dispatch_quantity'];
                if ($dispatchQty <= 0) {
                    continue;
                }

                $remainingApproved = (float)$reqItem->approved_qty - (float)$reqItem->dispatched_qty;
                if ($dispatchQty > $remainingApproved) {
                    throw new Exception("Dispatch quantity ({$dispatchQty}) exceeds remaining approved quantity ({$remainingApproved}) for Item #{$reqItem->item_id}.");
                }

                // Batch verification & stock deduction at source
                $batchNo = $line['batch_no'] ?? 'DEFAULT';
                $batch = StockBatch::where('godown_id', $sourceGodownId)
                    ->where('item_id', $reqItem->item_id)
                    ->where('batch_no', $batchNo)
                    ->lockForUpdate()
                    ->first();

                $availableQty = $batch ? (float)$batch->quantity : 0;
                if ($availableQty < $dispatchQty) {
                    $itemModel = Item::find($reqItem->item_id);
                    $itemName = $itemModel ? $itemModel->name : "Item #{$reqItem->item_id}";
                    throw new Exception("Insufficient stock for '{$itemName}' in Batch '{$batchNo}' at source warehouse. Available: {$availableQty}, Dispatched: {$dispatchQty}.");
                }

                // Deduct from source godown
                $this->recordOutward(
                    $companyId,
                    $request->branch_id,
                    $sourceGodownId,
                    $reqItem->item_id,
                    $dispatchQty,
                    $batchNo,
                    'StockTransfer',
                    $transfer->id,
                    $transferNo,
                    "Dispatched to {$request->fromDepartment->name} (Request #{$request->request_no})"
                );

                // Create transfer item line
                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'stock_request_item_id' => $reqItem->id,
                    'item_id' => $reqItem->item_id,
                    'batch_id' => $batch ? $batch->id : null,
                    'quantity' => $dispatchQty,
                    'dispatched_qty' => $dispatchQty,
                    'received_qty' => 0,
                    'damaged_qty' => 0,
                    'shortage_qty' => 0,
                    'in_transit_qty' => $dispatchQty,
                ]);

                // Update request item dispatched quantity
                $reqItem->increment('dispatched_qty', $dispatchQty);
            }

            return $transfer;
        });
    }

    /**
     * Confirm receipt of transfer with discrepancy reporting (Receiver side confirmation).
     */
    public function receiveTransferWithDiscrepancies(
        StockTransfer $transfer,
        array $receivedLines,
        int $userId,
        ?string $receiverRemarks = null
    ): void {
        DB::transaction(function () use ($transfer, $receivedLines, $userId, $receiverRemarks) {
            if (!in_array($transfer->status, ['in_transit', 'partially_received', 'pending'])) {
                throw new Exception("Transfer status '{$transfer->status}' is not eligible for receipt.");
            }

            // Separation of duty check: Dispatcher should not confirm receipt unless super-admin
            if ($transfer->dispatched_by && $transfer->dispatched_by === $userId) {
                $user = \App\Models\User::find($userId);
                if ($user && !$user->isSuperAdmin()) {
                    throw new Exception("Separation of duties violation: The dispatcher cannot confirm receipt on behalf of the receiving department.");
                }
            }

            $hasDiscrepancies = false;
            $allCompleted = true;

            foreach ($transfer->items as $transferItem) {
                // Find matching line in input
                $line = null;
                foreach ($receivedLines as $rl) {
                    if ((int)($rl['transfer_item_id'] ?? 0) === $transferItem->id || (int)($rl['id'] ?? 0) === $transferItem->id) {
                        $line = $rl;
                        break;
                    }
                }

                $dispatchedQty = (float)$transferItem->dispatched_qty;
                $alreadyReceived = (float)$transferItem->received_qty;
                $currentInTransit = (float)$transferItem->in_transit_qty;

                $receivedQty = $line !== null ? (float)($line['received_qty'] ?? 0) : $currentInTransit;
                $damagedQty = $line !== null ? (float)($line['damaged_qty'] ?? 0) : 0;
                $shortageQty = max(0, $dispatchedQty - ($alreadyReceived + $receivedQty + $damagedQty));

                if ($receivedQty + $damagedQty > $currentInTransit + 0.0001) {
                    throw new Exception("Accepted received plus damaged quantity cannot exceed outstanding in-transit quantity ({$currentInTransit}) for Item #{$transferItem->item_id}.");
                }

                // 1. Inward accepted stock into destination godown
                if ($receivedQty > 0) {
                    $sourceBatch = $transferItem->batch;
                    $batchNo = $sourceBatch ? $sourceBatch->batch_no : 'DEFAULT';
                    $unitCost = $sourceBatch ? (float)$sourceBatch->cost_price : 0;
                    $expiryDate = $sourceBatch?->expiry_date?->toDateString();

                    $this->recordInward(
                        $transfer->company_id,
                        $transfer->dest_branch_id,
                        $transfer->dest_godown_id,
                        $transferItem->item_id,
                        $receivedQty,
                        $unitCost,
                        $batchNo,
                        $expiryDate,
                        'StockTransfer',
                        $transfer->id,
                        $transfer->transfer_no,
                        "Received from Godown #{$transfer->source_godown_id}"
                    );
                }

                // 2. Update transfer item metrics
                $newReceivedTotal = $alreadyReceived + $receivedQty;
                $newInTransit = max(0, $currentInTransit - $receivedQty - $damagedQty);

                $transferItem->update([
                    'received_qty' => $newReceivedTotal,
                    'damaged_qty' => (float)$transferItem->damaged_qty + $damagedQty,
                    'shortage_qty' => $shortageQty,
                    'in_transit_qty' => $newInTransit,
                ]);

                // 3. Update stock request item received quantity
                if ($transferItem->requestItem) {
                    $transferItem->requestItem->increment('received_qty', $receivedQty);
                }

                // 4. Record discrepancies if damage or shortage reported
                if ($damagedQty > 0 || $shortageQty > 0 || !empty($line['discrepancy_type'])) {
                    $hasDiscrepancies = true;
                    $discType = $line['discrepancy_type'] ?? ($damagedQty > 0 ? 'damaged_goods' : 'shortage');
                    $discQty = $damagedQty > 0 ? $damagedQty : $shortageQty;
                    $reason = $line['discrepancy_reason'] ?? ($damagedQty > 0 ? 'Damaged goods in transit' : 'Shortage upon delivery');

                    StockDiscrepancy::create([
                        'company_id' => $transfer->company_id,
                        'branch_id' => $transfer->dest_branch_id,
                        'stock_transfer_id' => $transfer->id,
                        'stock_transfer_item_id' => $transferItem->id,
                        'stock_request_id' => $transfer->stock_request_id,
                        'item_id' => $transferItem->item_id,
                        'batch_id' => $transferItem->batch_id,
                        'source_godown_id' => $transfer->source_godown_id,
                        'dest_godown_id' => $transfer->dest_godown_id,
                        'dispatched_qty' => $dispatchedQty,
                        'received_qty' => $receivedQty,
                        'discrepancy_qty' => $discQty,
                        'discrepancy_type' => $discType,
                        'reason' => $reason,
                        'remarks' => $line['remarks'] ?? $receiverRemarks,
                        'status' => 'reported',
                        'created_by' => $userId,
                    ]);
                }

                if ($newInTransit > 0 || $damagedQty > 0 || $shortageQty > 0) {
                    $allCompleted = false;
                }
            }

            // Determine final transfer status
            $finalStatus = 'completed';
            if ($hasDiscrepancies || !$allCompleted) {
                $finalStatus = 'partially_received';
            }

            $transfer->update([
                'status' => $finalStatus,
                'received_by' => $userId,
                'received_at' => now(),
                'receiver_remarks' => $receiverRemarks,
            ]);
        });
    }

    /**
     * Mark discrepancy under investigation.
     */
    public function investigateDiscrepancy(StockDiscrepancy $discrepancy, string $investigationRemarks, int $userId): void
    {
        $discrepancy->update([
            'status' => 'under_investigation',
            'investigation_remarks' => $investigationRemarks,
            'investigated_by' => $userId,
            'investigated_at' => now(),
        ]);
    }

    /**
     * Submit discrepancy for write-off approval.
     */
    public function submitDiscrepancyWriteOff(StockDiscrepancy $discrepancy, string $reason, int $userId): void
    {
        DB::transaction(function () use ($discrepancy, $reason, $userId) {
            $discrepancy->update([
                'status' => 'pending_approval',
                'resolution_type' => 'loss_write_off',
                'resolution_notes' => $reason,
            ]);

            StockDiscrepancyApproval::create([
                'stock_discrepancy_id' => $discrepancy->id,
                'approver_id' => $userId,
                'action' => 'submitted',
                'decision_remarks' => $reason,
                'quantity' => $discrepancy->discrepancy_qty,
                'estimated_value' => round((float)$discrepancy->discrepancy_qty * ((float)($discrepancy->batch?->cost_price ?? $discrepancy->item->cost_price ?? 10)), 2),
            ]);
        });
    }

    /**
     * Approve discrepancy write-off with double-entry accounting journal posting.
     */
    public function approveDiscrepancyWriteOff(StockDiscrepancy $discrepancy, int $userId, ?string $remarks = null): void
    {
        DB::transaction(function () use ($discrepancy, $userId, $remarks) {
            // Separation of duties check: The user who reported or submitted the write-off cannot approve it
            $submittedBy = StockDiscrepancyApproval::where('stock_discrepancy_id', $discrepancy->id)
                ->where('action', 'submitted')
                ->latest()
                ->value('approver_id');

            if (($discrepancy->created_by && $discrepancy->created_by === $userId) || ($submittedBy && (int)$submittedBy === $userId)) {
                throw new Exception("Separation of duties violation: The user who reported or requested the discrepancy write-off cannot approve it.");
            }

            $companyId = $discrepancy->company_id;
            $qty = (float)$discrepancy->discrepancy_qty;
            $unitCost = (float)($discrepancy->batch?->cost_price ?? $discrepancy->item->cost_price ?? 10.0);
            if ($unitCost <= 0) $unitCost = 10.0;
            $totalLossValue = round($qty * $unitCost, 2);

            $adjNo = 'WO-DISC-' . date('Ymd') . '-' . rand(1000, 9999);

            // 1. Create Stock Adjustment record
            $adj = StockAdjustment::create([
                'company_id' => $companyId,
                'branch_id' => $discrepancy->branch_id,
                'godown_id' => $discrepancy->source_godown_id,
                'adjustment_no' => $adjNo,
                'adjustment_date' => now()->toDateString(),
                'reason' => "Approved transit write-off: {$discrepancy->discrepancy_type} ({$discrepancy->reason})",
                'status' => 'approved',
                'approved_by' => $userId,
                'approval_remarks' => $remarks ?: "Approved discrepancy write-off for Transfer #{$discrepancy->transfer->transfer_no}",
                'created_by' => $userId,
            ]);

            StockAdjustmentItem::create([
                'stock_adjustment_id' => $adj->id,
                'item_id' => $discrepancy->item_id,
                'batch_id' => $discrepancy->batch_id,
                'current_stock' => 0,
                'actual_stock' => 0,
                'adjustment_qty' => $qty,
                'type' => 'decrease',
            ]);

            // 2. Post financial double-entry journal
            if ($totalLossValue > 0) {
                $accountingService = new AccountingService();
                $stockAssetLedger = $accountingService->getOrCreateStandardLedger(
                    $companyId, 'Stock on Hand', 'Current Assets', 'Assets'
                );

                $lossLedgerName = in_array($discrepancy->discrepancy_type, ['damaged_goods', 'quality_rejection'])
                    ? 'Damaged & Expired Stock Expense'
                    : 'Inventory Shortage & Loss';

                $lossLedger = $accountingService->getOrCreateStandardLedger(
                    $companyId, $lossLedgerName, 'Direct Expenses & Purchases', 'Expenses'
                );

                $lines = [
                    [
                        'ledger_id' => $lossLedger->id,
                        'debit' => $totalLossValue,
                        'credit' => 0,
                        'narration' => "Transit loss write-off for Transfer #{$discrepancy->transfer->transfer_no}: {$discrepancy->reason}",
                    ],
                    [
                        'ledger_id' => $stockAssetLedger->id,
                        'debit' => 0,
                        'credit' => $totalLossValue,
                        'narration' => "Inventory asset reduction due to written-off transit discrepancy",
                    ],
                ];

                $jv = $accountingService->createJournalEntry(
                    $companyId,
                    $discrepancy->branch_id,
                    'Journal',
                    now()->toDateString(),
                    "Discrepancy Write-off {$adjNo} on {$discrepancy->item->name} ({$qty} units)",
                    $lines,
                    'StockDiscrepancy',
                    $discrepancy->id,
                    $adjNo
                );

                $adj->update(['journal_entry_id' => $jv->id]);
            }

            // 3. Update Discrepancy & Approval history
            $discrepancy->update([
                'status' => 'resolved',
                'resolution_type' => 'loss_write_off',
                'stock_adjustment_id' => $adj->id,
                'resolved_by' => $userId,
                'resolved_at' => now(),
            ]);

            StockDiscrepancyApproval::create([
                'stock_discrepancy_id' => $discrepancy->id,
                'approver_id' => $userId,
                'action' => 'approved',
                'decision_remarks' => $remarks ?: 'Write-off approved and posted to financial ledger.',
                'quantity' => $qty,
                'estimated_value' => $totalLossValue,
            ]);

            // 4. Check if parent transfer has all discrepancies resolved
            $parentTransfer = $discrepancy->transfer;
            $unresolvedCount = StockDiscrepancy::where('stock_transfer_id', $parentTransfer->id)
                ->where('status', '!=', 'resolved')
                ->where('status', '!=', 'cancelled')
                ->count();

            if ($unresolvedCount === 0) {
                $parentTransfer->update(['status' => 'completed']);
            }
        });
    }

    /**
     * Reject discrepancy write-off.
     */
    public function rejectDiscrepancyWriteOff(StockDiscrepancy $discrepancy, int $userId, string $reason): void
    {
        DB::transaction(function () use ($discrepancy, $userId, $reason) {
            $discrepancy->update([
                'status' => 'under_investigation',
                'investigation_remarks' => "Write-off rejected: {$reason}",
            ]);

            StockDiscrepancyApproval::create([
                'stock_discrepancy_id' => $discrepancy->id,
                'approver_id' => $userId,
                'action' => 'rejected',
                'decision_remarks' => $reason,
                'quantity' => $discrepancy->discrepancy_qty,
                'estimated_value' => 0,
            ]);
        });
    }

    /**
     * Resolve discrepancy by dispatching remaining replacement stock.
     */
    public function resolveDiscrepancyAdditionalDispatch(
        StockDiscrepancy $discrepancy,
        array $dispatchData,
        int $userId
    ): StockTransfer {
        return DB::transaction(function () use ($discrepancy, $dispatchData, $userId) {
            $companyId = $discrepancy->company_id;
            $qty = (float)($dispatchData['quantity'] ?? $discrepancy->discrepancy_qty);
            $batchNo = $dispatchData['batch_no'] ?? 'DEFAULT';

            $batch = StockBatch::where('godown_id', $discrepancy->source_godown_id)
                ->where('item_id', $discrepancy->item_id)
                ->where('batch_no', $batchNo)
                ->lockForUpdate()
                ->first();

            $avail = $batch ? (float)$batch->quantity : 0;
            if ($avail < $qty) {
                throw new Exception("Insufficient stock in source godown for replacement dispatch. Available: {$avail}, Required: {$qty}.");
            }

            $transferNo = 'TRF-REP-' . date('Ymd') . '-' . rand(1000, 9999);
            $newTransfer = StockTransfer::create([
                'company_id' => $companyId,
                'stock_request_id' => $discrepancy->stock_request_id,
                'transfer_no' => $transferNo,
                'dispatch_reference' => "Replacement for Discrepancy #{$discrepancy->id}",
                'transfer_date' => now()->toDateString(),
                'source_branch_id' => $discrepancy->branch_id,
                'source_godown_id' => $discrepancy->source_godown_id,
                'dest_branch_id' => $discrepancy->branch_id,
                'dest_godown_id' => $discrepancy->dest_godown_id,
                'status' => 'in_transit',
                'dispatched_by' => $userId,
                'dispatched_at' => now(),
                'remarks' => "Additional dispatch resolving Discrepancy #{$discrepancy->id}",
                'created_by' => $userId,
            ]);

            $this->recordOutward(
                $companyId,
                $discrepancy->branch_id,
                $discrepancy->source_godown_id,
                $discrepancy->item_id,
                $qty,
                $batchNo,
                'StockTransfer',
                $newTransfer->id,
                $transferNo,
                "Replacement dispatch for Discrepancy #{$discrepancy->id}"
            );

            StockTransferItem::create([
                'stock_transfer_id' => $newTransfer->id,
                'stock_request_item_id' => $discrepancy->transferItem?->stock_request_item_id,
                'item_id' => $discrepancy->item_id,
                'batch_id' => $batch ? $batch->id : null,
                'quantity' => $qty,
                'dispatched_qty' => $qty,
                'received_qty' => 0,
                'damaged_qty' => 0,
                'shortage_qty' => 0,
                'in_transit_qty' => $qty,
            ]);

            $discrepancy->update([
                'status' => 'pending_additional_dispatch',
                'resolution_type' => 'additional_dispatch',
                'resolution_notes' => "Replacement transfer {$transferNo} dispatched.",
                'resolved_by' => $userId,
                'resolved_at' => now(),
            ]);

            return $newTransfer;
        });
    }

    /**
     * Resolve discrepancy when confirmed received in separate delivery.
     */
    public function resolveDiscrepancySeparateDelivery(
        StockDiscrepancy $discrepancy,
        float $deliveredQty,
        int $userId,
        string $notes
    ): void {
        DB::transaction(function () use ($discrepancy, $deliveredQty, $userId, $notes) {
            if ($deliveredQty <= 0 || $deliveredQty > (float)$discrepancy->discrepancy_qty) {
                throw new Exception("Delivered quantity must be between 0.001 and {$discrepancy->discrepancy_qty}.");
            }

            // Inward delivered stock to destination godown
            $sourceBatch = $discrepancy->batch;
            $batchNo = $sourceBatch ? $sourceBatch->batch_no : 'DEFAULT';
            $unitCost = $sourceBatch ? (float)$sourceBatch->cost_price : 0;

            $this->recordInward(
                $discrepancy->company_id,
                $discrepancy->branch_id,
                $discrepancy->dest_godown_id,
                $discrepancy->item_id,
                $deliveredQty,
                $unitCost,
                $batchNo,
                $sourceBatch?->expiry_date?->toDateString(),
                'StockDiscrepancy',
                $discrepancy->id,
                "DELIV-DISC-{$discrepancy->id}",
                "Separate delivery confirmed: {$notes}"
            );

            // Update transfer item received quantity
            if ($discrepancy->transferItem) {
                $discrepancy->transferItem->increment('received_qty', $deliveredQty);
                $discrepancy->transferItem->decrement('shortage_qty', min($deliveredQty, (float)$discrepancy->transferItem->shortage_qty));
            }

            $discrepancy->update([
                'status' => 'resolved',
                'resolution_type' => 'separate_delivery',
                'resolution_notes' => $notes,
                'resolved_by' => $userId,
                'resolved_at' => now(),
            ]);

            // Re-evaluate parent transfer status
            $unresolvedCount = StockDiscrepancy::where('stock_transfer_id', $discrepancy->stock_transfer_id)
                ->where('status', '!=', 'resolved')
                ->where('status', '!=', 'cancelled')
                ->count();

            if ($unresolvedCount === 0) {
                $discrepancy->transfer->update(['status' => 'completed']);
            }
        });
    }

    /**
     * Cancel outstanding discrepancy requirement with proper reconciliation.
     */
    public function cancelOutstandingDiscrepancy(StockDiscrepancy $discrepancy, int $userId, string $reason): void
    {
        DB::transaction(function () use ($discrepancy, $userId, $reason) {
            $discrepancy->update([
                'status' => 'resolved',
                'resolution_type' => 'cancel_outstanding',
                'resolution_notes' => "Requirement cancelled: {$reason}",
                'resolved_by' => $userId,
                'resolved_at' => now(),
            ]);

            $parentTransfer = $discrepancy->transfer;
            $unresolvedCount = StockDiscrepancy::where('stock_transfer_id', $parentTransfer->id)
                ->where('status', '!=', 'resolved')
                ->where('status', '!=', 'cancelled')
                ->count();

            if ($unresolvedCount === 0) {
                $parentTransfer->update(['status' => 'completed']);
            }
        });
    }

    /**
     * Configure branch stock management mode.
     */
    public function configureBranchStock(Branch $branch, string $stockMode, ?int $centralGodownId, int $userId): void
    {
        if (!in_array($stockMode, ['centralized', 'multiple_locations'])) {
            throw new Exception("Invalid stock mode. Allowed: 'centralized', 'multiple_locations'.");
        }

        if ($centralGodownId) {
            Godown::where('id', $centralGodownId)->where('company_id', $branch->company_id)->firstOrFail();
        }

        $branch->update([
            'stock_mode' => $stockMode,
            'central_godown_id' => $centralGodownId,
        ]);
    }

    /**
     * Configure department stock maintenance settings safely.
     */
    public function configureDepartmentStock(
        Department $department,
        bool $maintainsStock,
        ?int $defaultGodownId,
        ?int $sourceGodownId,
        int $userId
    ): void {
        // Safety check: Before changing a department from stock-holding to non-stock-holding,
        // ensure existing stock balance is 0 or warn/require transfer
        if ($department->maintains_stock && !$maintainsStock && $department->default_godown_id) {
            $stockBalance = (float)StockBatch::where('godown_id', $department->default_godown_id)->sum('quantity');
            if ($stockBalance > 0.001) {
                throw new Exception("Cannot switch department '{$department->name}' to non-stock-holding while its designated location (Godown #{$department->default_godown_id}) has an active stock balance of {$stockBalance} units. Please reconcile or transfer stock first.");
            }
        }

        $department->update([
            'maintains_stock' => $maintainsStock,
            'default_godown_id' => $maintainsStock ? $defaultGodownId : null,
            'source_godown_id' => $sourceGodownId,
        ]);
    }
}


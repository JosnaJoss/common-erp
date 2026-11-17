<?php

namespace App\Services;

use App\Models\DailyOperation;
use App\Models\DailyOperationDistribution;
use App\Models\DailyOperationEmployee;
use App\Models\DailyOperationMaterial;
use App\Models\DailyOperationOutput;
use App\Models\DailyOperationWastage;
use App\Models\Item;
use App\Models\Staff;
use App\Models\StockBatch;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;

class DailyOperationService
{
    protected InventoryService $inventoryService;

    public function __construct(?InventoryService $inventoryService = null)
    {
        $this->inventoryService = $inventoryService ?? new InventoryService();
    }

    /**
     * Create a new Daily Operation with all child associations.
     */
    public function createOperation(
        array $data,
        array $employees = [],
        array $materials = [],
        array $outputs = [],
        array $wastages = [],
        array $distributions = [],
        int $userId = 1
    ): DailyOperation {
        return DB::transaction(function () use (
            $data, $employees, $materials, $outputs, $wastages, $distributions, $userId
        ) {
            $companyId = $data['company_id'];
            $date = $data['operation_date'] ?? now()->toDateString();
            $dateCompact = Carbon::parse($date)->format('Ymd');
            $opNumber = $data['operation_number'] ?? ('DOP-' . $dateCompact . '-' . rand(1000, 9999));

            // Ensure unique operation number
            $counter = 1;
            while (DailyOperation::where('company_id', $companyId)->where('operation_number', $opNumber)->exists()) {
                $opNumber = 'DOP-' . $dateCompact . '-' . rand(1000, 9999);
                $counter++;
                if ($counter > 10) break;
            }

            // Calculate total costs
            $totalLaborCost = 0;
            foreach ($employees as $emp) {
                $wage = $this->calculateEmployeeWage($emp);
                $totalLaborCost += $wage;
            }

            $totalMaterialCost = 0;
            foreach ($materials as $mat) {
                $unitCost = (float)($mat['unit_cost'] ?? 0);
                if ($unitCost <= 0) {
                    $sb = StockBatch::where('godown_id', $mat['source_godown_id'])
                        ->where('item_id', $mat['item_id'])
                        ->where('batch_no', $mat['batch_no'] ?? 'DEFAULT')
                        ->first();
                    $unitCost = $sb ? (float)$sb->cost_price : 0;
                }
                $totalMaterialCost += (float)$mat['consumed_quantity'] * $unitCost;
            }

            $totalWastageCost = 0;
            foreach ($wastages as $w) {
                $totalWastageCost += (float)($w['estimated_cost'] ?? 0);
            }

            $operation = DailyOperation::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'department_id' => $data['department_id'],
                'operation_number' => $opNumber,
                'operation_date' => $date,
                'department_head_id' => $data['department_head_id'] ?? null,
                'supervisor_id' => $data['supervisor_id'] ?? null,
                'work_order_ref' => $data['work_order_ref'] ?? null,
                'process_name' => $data['process_name'],
                'work_description' => $data['work_description'] ?? null,
                'start_time' => $data['start_time'] ?? null,
                'end_time' => $data['end_time'] ?? null,
                'work_status' => $data['work_status'] ?? 'completed',
                'approval_status' => $data['approval_status'] ?? 'draft',
                'approved_by' => in_array($data['approval_status'] ?? 'draft', ['department_head_approved', 'admin_approved']) ? $userId : null,
                'approval_date' => in_array($data['approval_status'] ?? 'draft', ['department_head_approved', 'admin_approved']) ? now() : null,
                'approval_remarks' => $data['approval_remarks'] ?? null,
                'stock_posted' => false,
                'total_labor_cost' => $totalLaborCost,
                'total_material_cost' => $totalMaterialCost,
                'total_wastage_cost' => $totalWastageCost,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => $userId,
            ]);

            // Save Employees
            foreach ($employees as $emp) {
                $staff = Staff::find($emp['staff_id']);
                $wage = $this->calculateEmployeeWage($emp);

                DailyOperationEmployee::create([
                    'daily_operation_id' => $operation->id,
                    'staff_id' => $emp['staff_id'],
                    'employment_type' => $emp['employment_type'] ?? ($staff ? $staff->employee_type : 'salaried'),
                    'hours_worked' => $emp['hours_worked'] ?? 8.00,
                    'units_completed' => $emp['units_completed'] ?? 0.00,
                    'rate_per_unit' => $emp['rate_per_unit'] ?? ($emp['employment_type'] === 'piece_rate' ? ($staff?->piece_rate ?? 0) : ($staff?->hourly_rate ?? 0)),
                    'wage_earned' => $wage,
                    'remarks' => $emp['remarks'] ?? null,
                ]);
            }

            // Save Materials
            foreach ($materials as $mat) {
                $unitCost = (float)($mat['unit_cost'] ?? 0);
                if ($unitCost <= 0) {
                    $sb = StockBatch::where('godown_id', $mat['source_godown_id'])
                        ->where('item_id', $mat['item_id'])
                        ->where('batch_no', $mat['batch_no'] ?? 'DEFAULT')
                        ->first();
                    $unitCost = $sb ? (float)$sb->cost_price : 0;
                }
                $consumedQty = (float)$mat['consumed_quantity'];
                $totalCost = $consumedQty * $unitCost;

                DailyOperationMaterial::create([
                    'daily_operation_id' => $operation->id,
                    'item_id' => $mat['item_id'],
                    'source_godown_id' => $mat['source_godown_id'],
                    'batch_no' => $mat['batch_no'] ?? 'DEFAULT',
                    'opening_quantity' => $mat['opening_quantity'] ?? 0,
                    'issued_quantity' => $mat['issued_quantity'] ?? $consumedQty,
                    'consumed_quantity' => $consumedQty,
                    'returned_quantity' => $mat['returned_quantity'] ?? 0,
                    'wastage_quantity' => $mat['wastage_quantity'] ?? 0,
                    'remaining_quantity' => $mat['remaining_quantity'] ?? 0,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                ]);
            }

            // Save Outputs
            foreach ($outputs as $out) {
                DailyOperationOutput::create([
                    'daily_operation_id' => $operation->id,
                    'item_id' => $out['item_id'],
                    'dest_godown_id' => $out['dest_godown_id'],
                    'batch_number' => $out['batch_number'] ?? ('BAT-' . $dateCompact . '-' . rand(100, 999)),
                    'produced_quantity' => (float)$out['produced_quantity'],
                    'rejected_quantity' => (float)($out['rejected_quantity'] ?? 0),
                    'wastage_quantity' => (float)($out['wastage_quantity'] ?? 0),
                    'quality_status' => $out['quality_status'] ?? 'passed',
                    'unit_cost' => (float)($out['unit_cost'] ?? 0),
                    'production_date' => $out['production_date'] ?? $date,
                    'expiry_date' => $out['expiry_date'] ?? null,
                    'remarks' => $out['remarks'] ?? null,
                ]);
            }

            // Save Wastages
            foreach ($wastages as $w) {
                DailyOperationWastage::create([
                    'daily_operation_id' => $operation->id,
                    'item_id' => $w['item_id'] ?? null,
                    'wastage_name' => $w['wastage_name'],
                    'wastage_type' => $w['wastage_type'] ?? 'process_loss',
                    'quantity' => (float)$w['quantity'],
                    'unit' => $w['unit'] ?? 'KG',
                    'estimated_cost' => (float)($w['estimated_cost'] ?? 0),
                    'recoverable_quantity' => (float)($w['recoverable_quantity'] ?? 0),
                    'reason' => $w['reason'] ?? null,
                ]);
            }

            // Save Distributions
            foreach ($distributions as $dst) {
                DailyOperationDistribution::create([
                    'daily_operation_id' => $operation->id,
                    'item_id' => $dst['item_id'],
                    'destination_godown_id' => $dst['destination_godown_id'],
                    'destination_type' => $dst['destination_type'] ?? 'branch_warehouse',
                    'batch_no' => $dst['batch_no'] ?? 'DEFAULT',
                    'quantity' => (float)$dst['quantity'],
                    'status' => $dst['status'] ?? 'received',
                    'notes' => $dst['notes'] ?? null,
                ]);
            }

            // If created directly with approved status, post inventory immediately
            if (in_array($operation->approval_status, ['department_head_approved', 'admin_approved'])) {
                $this->postOperationStock($operation, $userId);
            }

            return $operation;
        });
    }

    /**
     * Submit operation for approval.
     */
    public function submitOperation(DailyOperation $operation, int $userId): void
    {
        if ($operation->approval_status !== 'draft') {
            throw new Exception("Only draft operations can be submitted.");
        }

        $operation->update([
            'approval_status' => 'submitted',
        ]);
    }

    /**
     * Approve operation and trigger inventory posting when required.
     */
    public function approveOperation(
        DailyOperation $operation,
        string $targetStatus,
        int $userId,
        ?string $remarks = null
    ): void {
        DB::transaction(function () use ($operation, $targetStatus, $userId, $remarks) {
            $operation->update([
                'approval_status' => $targetStatus,
                'approved_by' => $userId,
                'approval_date' => now(),
                'approval_remarks' => $remarks,
            ]);

            // Post stock upon Department Head or Admin approval
            if (in_array($targetStatus, ['department_head_approved', 'admin_approved']) && !$operation->stock_posted) {
                $this->postOperationStock($operation, $userId);
            }
        });
    }

    /**
     * Reject an operation.
     */
    public function rejectOperation(DailyOperation $operation, int $userId, string $reason): void
    {
        if ($operation->stock_posted) {
            throw new Exception("Cannot reject an operation whose stock has already been posted.");
        }

        $operation->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $reason,
            'approved_by' => $userId,
            'approval_date' => now(),
        ]);
    }

    /**
     * Post stock movements for consumed materials, produced outputs, and distributions.
     */
    public function postOperationStock(DailyOperation $operation, int $userId): void
    {
        DB::transaction(function () use ($operation, $userId) {
            $op = DailyOperation::where('id', $operation->id)->lockForUpdate()->first();
            if ($op->stock_posted) {
                return; // Already posted, prevent duplicate posting
            }

            $companyId = $op->company_id;
            $branchId = $op->branch_id;
            $opNumber = $op->operation_number;

            // 1. Consume raw materials (Stock Outward)
            foreach ($op->materials as $mat) {
                $this->inventoryService->recordOutward(
                    $companyId,
                    $branchId,
                    $mat->source_godown_id,
                    $mat->item_id,
                    (float)$mat->consumed_quantity,
                    $mat->batch_no ?: 'DEFAULT',
                    'DailyOperation',
                    $op->id,
                    $opNumber,
                    "Consumed in {$op->process_name} (Op #{$opNumber})"
                );
            }

            // 2. Inward produced outputs (Stock Inward)
            foreach ($op->outputs as $out) {
                $this->inventoryService->recordInward(
                    $companyId,
                    $branchId,
                    $out->dest_godown_id,
                    $out->item_id,
                    (float)$out->produced_quantity,
                    (float)$out->unit_cost,
                    $out->batch_number ?: 'DEFAULT',
                    $out->expiry_date?->toDateString(),
                    'DailyOperation',
                    $op->id,
                    $opNumber,
                    "Produced via {$op->process_name} (Op #{$opNumber})"
                );
            }

            // 3. Handle distributions (Stock Transfers to showrooms, vans, remote warehouses)
            $defaultOutputGodown = $op->outputs->first()?->dest_godown_id;

            foreach ($op->distributions as $dist) {
                $sourceGodownId = $defaultOutputGodown ?: $dist->destination_godown_id;
                
                // If destination is different from the output godown, create a StockTransfer
                if ($sourceGodownId && $sourceGodownId !== $dist->destination_godown_id) {
                    $transferNo = 'TRF-DOP-' . rand(10000, 99999);
                    $destGodown = $dist->destinationGodown;

                    $transfer = StockTransfer::create([
                        'company_id' => $companyId,
                        'transfer_no' => $transferNo,
                        'transfer_date' => $op->operation_date,
                        'source_branch_id' => $branchId,
                        'source_godown_id' => $sourceGodownId,
                        'dest_branch_id' => $destGodown?->branch_id ?? $branchId,
                        'dest_godown_id' => $dist->destination_godown_id,
                        'status' => 'completed',
                        'dispatched_by' => $userId,
                        'dispatched_at' => now(),
                        'received_by' => $userId,
                        'received_at' => now(),
                        'remarks' => "Distribution from Daily Operation #{$opNumber} to {$dist->destination_type}",
                        'created_by' => $userId,
                    ]);

                    StockTransferItem::create([
                        'stock_transfer_id' => $transfer->id,
                        'item_id' => $dist->item_id,
                        'quantity' => (float)$dist->quantity,
                    ]);

                    // Execute physical stock transfer between locations
                    $this->inventoryService->transferStock(
                        $companyId,
                        $sourceGodownId,
                        $dist->destination_godown_id,
                        $dist->item_id,
                        (float)$dist->quantity,
                        $dist->batch_no ?: 'DEFAULT',
                        $transferNo
                    );

                    $dist->update([
                        'stock_transfer_id' => $transfer->id,
                        'status' => 'received',
                    ]);
                }
            }

            $op->update([
                'stock_posted' => true,
                'stock_posted_at' => now(),
            ]);
        });
    }

    /**
     * Helper to compute employee wage based on employment type.
     */
    protected function calculateEmployeeWage(array $emp): float
    {
        $type = $emp['employment_type'] ?? 'salaried';
        $staff = isset($emp['staff_id']) ? Staff::find($emp['staff_id']) : null;

        if ($type === 'piece_rate') {
            $units = (float)($emp['units_completed'] ?? 0);
            $rate = (float)($emp['rate_per_unit'] ?? ($staff?->piece_rate ?? 0));
            return $units * $rate;
        }

        if ($type === 'hourly') {
            $hours = (float)($emp['hours_worked'] ?? 8);
            $rate = (float)($emp['rate_per_unit'] ?? ($staff?->hourly_rate ?? 0));
            return $hours * $rate;
        }

        if ($type === 'daily_wage') {
            $dailySalary = $staff ? (float)$staff->salary_amount : 0;
            $hours = (float)($emp['hours_worked'] ?? 8);
            // Pro-rate daily wage if hours < 8, or full daily rate
            return $hours >= 8 ? $dailySalary : ($dailySalary / 8) * $hours;
        }

        // Salaried: wage is fixed monthly, daily activity wage is computed proportionally for cost tracking
        if ($staff && $staff->salary_amount > 0) {
            $dailyEquiv = (float)$staff->salary_amount / 26; // 26 working days standard
            $hours = (float)($emp['hours_worked'] ?? 8);
            return ($dailyEquiv / 8) * $hours;
        }

        return 0.00;
    }
}

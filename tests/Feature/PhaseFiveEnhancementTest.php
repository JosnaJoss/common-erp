<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DailyOperation;
use App\Models\DailyOperationEmployee;
use App\Models\DailyOperationMaterial;
use App\Models\DailyOperationOutput;
use App\Models\Department;
use App\Models\FinancialYear;
use App\Models\Godown;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Ledger;
use App\Models\ManufacturingOverheadAllocation;
use App\Models\Payroll;
use App\Models\Staff;
use App\Models\StockBatch;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\DailyOperationService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PhaseFiveEnhancementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Godown $godownRaw;
    protected Godown $godownFG;
    protected Item $rawItem;
    protected Item $finishedItem;
    protected Department $prodDept;
    protected Staff $craftsman;
    protected DailyOperationService $dailyOpService;
    protected InventoryService $inventoryService;
    protected AccountingService $accountingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dailyOpService = app(DailyOperationService::class);
        $this->inventoryService = app(InventoryService::class);
        $this->accountingService = app(AccountingService::class);

        $this->companyA = Company::where('code', 'APEX-IND')->first() ?? Company::first();

        $this->companyB = Company::where('id', '!=', $this->companyA->id)->first() ?? Company::create([
            'name' => 'Company B Security Test',
            'code' => 'COMP-B-SEC',
            'currency' => 'INR',
            'status' => 'active',
        ]);

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@commonerp.test'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'company_id' => $this->companyA->id]
        );

        $this->branchA = Branch::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'HQ-P5'],
            ['name' => 'Phase 5 Test HQ', 'status' => 'active']
        );

        $this->godownRaw = Godown::firstOrCreate(
            ['company_id' => $this->companyA->id, 'branch_id' => $this->branchA->id, 'name' => 'Raw Materials Godown P5'],
            ['code' => 'G-RAW-P5', 'status' => 'active']
        );

        $this->godownFG = Godown::firstOrCreate(
            ['company_id' => $this->companyA->id, 'branch_id' => $this->branchA->id, 'name' => 'Finished Goods Godown P5'],
            ['code' => 'G-FG-P5', 'status' => 'active']
        );

        $this->prodDept = Department::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'DEP-PROD-P5'],
            ['name' => 'Production Department P5', 'status' => 'active']
        );

        $unit = Unit::firstOrCreate(
            ['company_id' => $this->companyA->id, 'symbol' => 'KG'],
            ['name' => 'Kilogram']
        );

        $unitPcs = Unit::firstOrCreate(
            ['company_id' => $this->companyA->id, 'symbol' => 'PCS'],
            ['name' => 'Pieces']
        );

        $this->rawItem = Item::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'P5-RAW-STEEL'],
            ['name' => 'P5 Raw Steel Ingot', 'unit_id' => $unit->id, 'normal_price' => 50.00, 'item_type' => 'raw_material', 'status' => 'active']
        );

        $this->finishedItem = Item::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'P5-FG-VALVE'],
            ['name' => 'P5 Industrial Valve', 'unit_id' => $unitPcs->id, 'normal_price' => 250.00, 'item_type' => 'sales_item', 'status' => 'active']
        );

        $this->craftsman = Staff::firstOrCreate(
            ['company_id' => $this->companyA->id, 'employee_id' => 'EMP-CRAFT-P5'],
            [
                'first_name' => 'Ramesh',
                'last_name' => 'Kumar',
                'email' => 'ramesh.p5@apex.test',
                'phone' => '9876543299',
                'branch_id' => $this->branchA->id,
                'department_id' => $this->prodDept->id,
                'designation' => 'Machinist',
                'employee_type' => 'piece_rate',
                'salary_amount' => 15000.00,
                'basic_salary' => 15000.00,
                'date_of_joining' => '2023-01-01',
                'status' => 'active',
            ]
        );

        // Ensure standard ledgers exist and are classified
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Stock on Hand', 'Current Assets', 'Assets');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Raw Materials Inventory', 'Current Assets', 'Assets');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Finished Goods Inventory', 'Current Assets', 'Assets');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Manufacturing Labor Payable', 'Current Liabilities', 'Liabilities');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Manufacturing Overhead Absorbed', 'Direct Expenses', 'Expenses');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Salaries & Staff Wages', 'Indirect Expenses', 'Expenses');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Main Bank Account', 'Bank Accounts', 'Assets');
    }

    /**
     * Requirement 1: Manufacturing Reversal Workflow, Stock Rollback & Balanced Journal Reversal
     */
    public function test_manufacturing_reversal_workflow_and_atomic_rollback(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        // 1. Inward initial raw materials: 100 KG at ₹50/KG
        $this->inventoryService->recordInward(
            $this->companyA->id,
            $this->branchA->id,
            $this->godownRaw->id,
            $this->rawItem->id,
            100.0,
            50.00,
            'BAT-RAW-REV01',
            null,
            'PurchaseEntry',
            null,
            'TEST-SUP-INWARD'
        );

        $rawBatchBefore = StockBatch::where('godown_id', $this->godownRaw->id)
            ->where('item_id', $this->rawItem->id)
            ->where('batch_no', 'BAT-RAW-REV01')
            ->first();
        $this->assertEquals(100, (float)$rawBatchBefore->quantity);

        // 2. Create Daily Operation consuming 20 KG raw material, producing 10 Finished Goods
        $opData = [
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->prodDept->id,
            'operation_date' => '2026-11-05',
            'process_name' => 'Valve Machining Process',
            'work_status' => 'completed',
            'approval_status' => 'admin_approved',
        ];

        $employees = [
            [
                'staff_id' => $this->craftsman->id,
                'employment_type' => 'piece_rate',
                'hours_worked' => 8,
                'units_completed' => 10,
                'rate_per_unit' => 25.00, // ₹250 labor
                'calculated_wage' => 250.00,
            ]
        ];

        $materials = [
            [
                'item_id' => $this->rawItem->id,
                'source_godown_id' => $this->godownRaw->id,
                'batch_no' => 'BAT-RAW-REV01',
                'consumed_quantity' => 20, // 20 * 50 = ₹1000
                'unit_cost' => 50.00,
            ]
        ];

        $outputs = [
            [
                'item_id' => $this->finishedItem->id,
                'dest_godown_id' => $this->godownFG->id,
                'batch_number' => 'BAT-FG-REV01',
                'produced_quantity' => 10,
                'unit_cost' => 125.00, // Total ₹1250 (1000 mat + 250 labor)
                'production_date' => '2026-11-05',
            ]
        ];

        $operation = $this->dailyOpService->createOperation(
            $opData,
            $employees,
            $materials,
            $outputs,
            [],
            [],
            [],
            $this->adminUser->id
        );

        $this->assertNotNull($operation);
        $this->assertEquals(250.00, (float)$operation->total_labor_cost);
        $this->assertEquals(1000.00, (float)$operation->total_material_cost);

        // 3. Post Operation Stock & Journal Entries
        $this->dailyOpService->postOperationStock($operation, $this->adminUser->id);
        $operation->refresh();
        $this->assertTrue((bool)$operation->stock_posted);

        // Verify stock movements: Raw material reduced by 20, FG increased by 10
        $rawBatchAfterPost = StockBatch::where('godown_id', $this->godownRaw->id)
            ->where('item_id', $this->rawItem->id)
            ->where('batch_no', 'BAT-RAW-REV01')
            ->first();
        $this->assertEquals(80, (float)$rawBatchAfterPost->quantity);

        $fgBatchAfterPost = StockBatch::where('godown_id', $this->godownFG->id)
            ->where('item_id', $this->finishedItem->id)
            ->where('batch_no', 'BAT-FG-REV01')
            ->first();
        $this->assertEquals(10, (float)$fgBatchAfterPost->quantity);

        // Verify original journal entry
        $origJv = JournalEntry::find($operation->journal_entry_id);
        $this->assertNotNull($origJv);
        $this->assertEquals(round($origJv->lines()->sum('debit_amount'), 2), round($origJv->lines()->sum('credit_amount'), 2));

        // 4. Execute Reversal via POST route
        $reversalResponse = $this->post(route('daily_operations.reverse', $operation), [
            'reversal_reason' => 'Defective raw material batch detected, batch cancelled.',
        ]);

        $reversalResponse->assertRedirect();
        $reversalResponse->assertSessionHas('success');

        $operation->refresh();
        $this->assertFalse((bool)$operation->stock_posted);
        $this->assertEquals('rejected', $operation->approval_status);
        $this->assertNotNull($operation->reversed_at);
        $this->assertEquals($this->adminUser->id, $operation->reversed_by);

        // 5. Verify Stock Rollback: Raw materials restored to 100, FG reduced to 0
        $rawBatchRestored = StockBatch::where('godown_id', $this->godownRaw->id)
            ->where('item_id', $this->rawItem->id)
            ->where('batch_no', 'BAT-RAW-REV01')
            ->first();
        $this->assertEquals(100, (float)$rawBatchRestored->quantity);

        $fgBatchRestored = StockBatch::where('godown_id', $this->godownFG->id)
            ->where('item_id', $this->finishedItem->id)
            ->where('batch_no', 'BAT-FG-REV01')
            ->first();
        $this->assertEquals(0, (float)$fgBatchRestored->quantity);

        // 6. Verify Accounting Reversal
        $origJv->refresh();
        $this->assertTrue((bool)$origJv->is_reversed);

        $revJv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('reference_type', 'DailyOperationReversal')
            ->where('reference_id', $operation->id)
            ->first();
        $this->assertNotNull($revJv);
        $this->assertEquals(round($revJv->lines()->sum('debit_amount'), 2), round($revJv->lines()->sum('credit_amount'), 2));

        // 7. Verify Duplicate Reversal is Rejected
        $dupResponse = $this->post(route('daily_operations.reverse', $operation), [
            'reversal_reason' => 'Attempting duplicate reversal',
        ]);
        $dupResponse->assertSessionHasErrors();
    }

    /**
     * Requirement 1 Validation: Reversal Rejected when Downstream FG Stock is Consumed/Sold
     */
    public function test_manufacturing_reversal_rejected_when_downstream_stock_is_consumed(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        // 1. Inward raw materials
        $this->inventoryService->recordInward(
            $this->companyA->id,
            $this->branchA->id,
            $this->godownRaw->id,
            $this->rawItem->id,
            50.0,
            50.00,
            'BAT-RAW-DOWN01',
            null,
            'PurchaseEntry',
            null,
            'INWARD-TEST'
        );

        // 2. Create operation producing 10 FG units
        $operation = $this->dailyOpService->createOperation(
            [
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
                'department_id' => $this->prodDept->id,
                'operation_date' => now()->toDateString(),
                'process_name' => 'Assembly',
                'work_status' => 'completed',
                'approval_status' => 'admin_approved',
            ],
            [],
            [
                [
                    'item_id' => $this->rawItem->id,
                    'source_godown_id' => $this->godownRaw->id,
                    'batch_no' => 'BAT-RAW-DOWN01',
                    'consumed_quantity' => 10,
                    'unit_cost' => 50.00,
                ]
            ],
            [
                [
                    'item_id' => $this->finishedItem->id,
                    'dest_godown_id' => $this->godownFG->id,
                    'batch_number' => 'BAT-FG-DOWN01',
                    'produced_quantity' => 10,
                    'unit_cost' => 50.00,
                ]
            ],
            [],
            [],
            [],
            $this->adminUser->id
        );

        $this->dailyOpService->postOperationStock($operation, $this->adminUser->id);

        // 3. Simulate Downstream Consumption: Deduct 6 units (remaining = 4 < 10)
        $this->inventoryService->recordOutward(
            $this->companyA->id,
            $this->branchA->id,
            $this->godownFG->id,
            $this->finishedItem->id,
            6.0,
            'BAT-FG-DOWN01',
            'SalesInvoice',
            null,
            'DOWNSTREAM-SALES-DISPATCH'
        );

        $fgBatch = StockBatch::where('godown_id', $this->godownFG->id)
            ->where('item_id', $this->finishedItem->id)
            ->where('batch_no', 'BAT-FG-DOWN01')
            ->first();
        $this->assertEquals(4, (float)$fgBatch->quantity);

        // 4. Reversal attempt must fail due to downstream consumption
        $response = $this->post(route('daily_operations.reverse', $operation), [
            'reversal_reason' => 'Rollback consumed output',
        ]);

        $response->assertSessionHasErrors();
        $operation->refresh();
        $this->assertTrue((bool)$operation->stock_posted);
        $this->assertNull($operation->reversed_at);
    }

    /**
     * Requirement 1 Validation: Reversal Rejected when Accounting Period is Closed
     */
    public function test_manufacturing_reversal_rejected_when_accounting_period_is_closed(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $pastDate = '2022-06-15';

        // Create a closed financial year covering pastDate
        FinancialYear::firstOrCreate(
            ['company_id' => $this->companyA->id, 'title' => 'FY 2022-23 Closed'],
            [
                'start_date' => '2022-04-01',
                'end_date' => '2023-03-31',
                'is_closed' => true,
            ]
        );

        // Inward raw materials on pastDate
        $this->inventoryService->recordInward(
            $this->companyA->id,
            $this->branchA->id,
            $this->godownRaw->id,
            $this->rawItem->id,
            20.0,
            50.00,
            'BAT-RAW-CLOSED',
            null,
            'PurchaseEntry',
            null,
            'OLD-INWARD'
        );

        // Create operation in closed period
        $operation = $this->dailyOpService->createOperation(
            [
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
                'department_id' => $this->prodDept->id,
                'operation_date' => $pastDate,
                'process_name' => 'Closed Year Production',
                'work_status' => 'completed',
                'approval_status' => 'admin_approved',
            ],
            [],
            [
                [
                    'item_id' => $this->rawItem->id,
                    'source_godown_id' => $this->godownRaw->id,
                    'batch_no' => 'BAT-RAW-CLOSED',
                    'consumed_quantity' => 10,
                    'unit_cost' => 50.00,
                ]
            ],
            [
                [
                    'item_id' => $this->finishedItem->id,
                    'dest_godown_id' => $this->godownFG->id,
                    'batch_number' => 'BAT-FG-CLOSED',
                    'produced_quantity' => 10,
                    'unit_cost' => 50.00,
                ]
            ],
            [],
            [],
            [],
            $this->adminUser->id
        );

        $this->dailyOpService->postOperationStock($operation, $this->adminUser->id);

        // Reversal should be rejected
        $response = $this->post(route('daily_operations.reverse', $operation), [
            'reversal_reason' => 'Attempt reversal in closed period',
        ]);

        $response->assertSessionHasErrors();
        $operation->refresh();
        $this->assertTrue((bool)$operation->stock_posted);
    }

    /**
     * Requirement 4: Manufacturing Overhead Allocation, Auditing & Finished Goods Capitalization
     */
    public function test_manufacturing_overhead_allocation_and_capitalization(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        // Get or create expense ledger for Electricity
        $powerLedger = $this->accountingService->getOrCreateStandardLedger(
            $this->companyA->id,
            'Factory Electricity & Power',
            'Direct Expenses',
            'Expenses'
        );

        // Inward raw materials: 50 KG at ₹40 = ₹2000
        $this->inventoryService->recordInward(
            $this->companyA->id,
            $this->branchA->id,
            $this->godownRaw->id,
            $this->rawItem->id,
            50.0,
            40.00,
            'BAT-RAW-OVH01',
            null,
            'PurchaseEntry',
            null,
            'OVH-TEST-INWARD'
        );

        $overheads = [
            [
                'overhead_category' => 'electricity',
                'allocation_method' => 'machine_hours',
                'expense_ledger_id' => $powerLedger->id,
                'allocation_basis_value' => 20,
                'rate_per_unit' => 15.00,
                'allocated_amount' => 300.00,
                'notes' => '20 machine hours @ ₹15/hr',
            ]
        ];

        $employees = [
            [
                'staff_id' => $this->craftsman->id,
                'employment_type' => 'piece_rate',
                'hours_worked' => 8,
                'units_completed' => 20,
                'rate_per_unit' => 10.00, // ₹200 labor
                'calculated_wage' => 200.00,
            ]
        ];

        $materials = [
            [
                'item_id' => $this->rawItem->id,
                'source_godown_id' => $this->godownRaw->id,
                'batch_no' => 'BAT-RAW-OVH01',
                'consumed_quantity' => 20, // 20 * 40 = ₹800 material
                'unit_cost' => 40.00,
            ]
        ];

        $outputs = [
            [
                'item_id' => $this->finishedItem->id,
                'dest_godown_id' => $this->godownFG->id,
                'batch_number' => 'BAT-FG-OVH01',
                'produced_quantity' => 20,
                'unit_cost' => 65.00, // Total: (800 mat + 200 lab + 300 ovh) / 20 = 1300 / 20 = 65.00
                'production_date' => now()->toDateString(),
            ]
        ];

        $operation = $this->dailyOpService->createOperation(
            [
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
                'department_id' => $this->prodDept->id,
                'operation_date' => now()->toDateString(),
                'process_name' => 'Machining with Overhead',
                'work_status' => 'completed',
                'approval_status' => 'admin_approved',
            ],
            $employees,
            $materials,
            $outputs,
            [],
            [],
            $overheads,
            $this->adminUser->id
        );

        $this->assertEquals(300.00, (float)$operation->total_overhead_cost);
        $this->assertEquals(1, $operation->overheadAllocations()->count());

        $alloc = $operation->overheadAllocations()->first();
        $this->assertEquals('electricity', $alloc->overhead_category);
        $this->assertEquals(300.00, (float)$alloc->allocated_amount);
        $this->assertEquals($powerLedger->id, $alloc->expense_ledger_id);

        // Post stock and check accounting lines
        $this->dailyOpService->postOperationStock($operation, $this->adminUser->id);
        $operation->refresh();

        $jv = JournalEntry::find($operation->journal_entry_id);

        $this->assertNotNull($jv);
        $totalDebit = round($jv->lines()->sum('debit_amount'), 2);
        $totalCredit = round($jv->lines()->sum('credit_amount'), 2);
        $this->assertEquals($totalDebit, $totalCredit);

        // Verify Finished Goods Inventory was debited with full absorption: ₹1300
        $fgLedger = Ledger::where('company_id', $this->companyA->id)->where('name', 'Finished Goods Inventory')->first();
        $fgLine = $jv->lines()->where('ledger_id', $fgLedger->id)->first();
        $this->assertNotNull($fgLine);
        $this->assertEquals(1300.00, (float)$fgLine->debit_amount);

        // Verify Manufacturing Overhead Absorbed was credited: ₹300
        $ovhAbsorbedLedger = Ledger::where('company_id', $this->companyA->id)->where('name', 'Manufacturing Overhead Absorbed')->first();
        $this->assertNotNull($ovhAbsorbedLedger);
        $ovhLine = $jv->lines()->where('ledger_id', $ovhAbsorbedLedger->id)->first();
        $this->assertNotNull($ovhLine);
        $this->assertEquals(300.00, (float)$ovhLine->credit_amount);
    }

    /**
     * Requirement 2: Manufacturing Labor and Payroll Integration (Prevents Dual P&L Expense)
     */
    public function test_payroll_manufacturing_labor_settlement_prevents_double_expense(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $salaryMonth = Carbon::now()->format('Y-m');
        $bankLedger = Ledger::where('company_id', $this->companyA->id)->where('is_bank_or_cash', true)->first();
        $this->assertNotNull($bankLedger);

        // 1. Create a staff member with ₹25,000 basic salary
        $staff = Staff::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->prodDept->id,
            'employee_id' => 'EMP-P5-LAB-' . rand(100, 999),
            'first_name' => 'Anil',
            'last_name' => 'Verma',
            'email' => 'anil.verma' . rand(100, 999) . '@apex.test',
            'phone' => '98765' . rand(10000, 99999),
            'designation' => 'Factory Lead',
            'employee_type' => 'salaried',
            'salary_amount' => 30000.00,
            'basic_salary' => 25000.00,
            'hra' => 5000.00,
            'other_allowances' => 0.00,
            'date_of_joining' => '2023-01-01',
            'status' => 'active',
        ]);

        // 2. Record shop-floor manufacturing operations where this staff earned ₹8,000 direct wages
        $op = DailyOperation::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->prodDept->id,
            'operation_number' => 'OP-LAB-P5-' . rand(100, 999),
            'operation_date' => Carbon::now()->startOfMonth()->addDays(5)->toDateString(),
            'process_name' => 'Assembly Work',
            'work_status' => 'completed',
            'approval_status' => 'admin_approved',
            'stock_posted' => true,
            'total_labor_cost' => 8000.00,
        ]);

        DailyOperationEmployee::create([
            'daily_operation_id' => $op->id,
            'staff_id' => $staff->id,
            'employment_type' => 'salaried',
            'hours_worked' => 40,
            'units_completed' => 80,
            'rate_per_unit' => 100.00,
            'wage_earned' => 8000.00,
        ]);

        // 3. Generate Payroll for this staff
        $genResponse = $this->post(route('staff.payroll.generate'), [
            'salary_month' => $salaryMonth,
        ]);
        $genResponse->assertSessionHas('success');

        $payroll = Payroll::where('company_id', $this->companyA->id)
            ->where('staff_id', $staff->id)
            ->where('salary_month', $salaryMonth)
            ->first();
        $this->assertNotNull($payroll);

        // Gross = 25000 + 5000 = 30000
        $this->assertEquals(30000.00, (float)$payroll->gross_salary);

        // 4. Disburse Payroll
        $payResponse = $this->post(route('staff.payroll.bulk_pay'), [
            'payroll_ids' => [$payroll->id],
            'payment_mode' => 'Bank Transfer',
            'payment_ledger_id' => $bankLedger->id,
            'payment_date' => Carbon::now()->toDateString(),
            'transaction_reference' => 'SAL-DISB-P5-TEST',
        ]);
        $payResponse->assertSessionHas('success');

        $payroll->refresh();
        $this->assertEquals('paid', $payroll->payroll_status);

        // 5. Verify Labor Settlement Splits
        // Accrued manufacturing labor of ₹8,000 settled against liability
        // Remaining ₹22,000 (30,000 - 8,000) expensed as administrative salary
        $this->assertEquals(8000.00, (float)$payroll->manufacturing_labor_settled);
        $this->assertEquals(22000.00, (float)$payroll->administrative_salary_expense);

        // 6. Verify Accounting Journal Entry
        $jv = JournalEntry::find($payroll->journal_entry_id);
        $this->assertNotNull($jv);

        $laborPayableLedger = Ledger::where('company_id', $this->companyA->id)->where('name', 'Manufacturing Labor Payable')->first();
        $salaryExpenseLedger = Ledger::where('company_id', $this->companyA->id)->where('name', 'Salaries & Staff Wages')->first();

        $laborLine = $jv->lines()->where('ledger_id', $laborPayableLedger->id)->first();
        $this->assertNotNull($laborLine, 'Manufacturing Labor Payable line must be present in journal');
        $this->assertEquals(8000.00, (float)$laborLine->debit_amount);

        $adminSalLine = $jv->lines()->where('ledger_id', $salaryExpenseLedger->id)->first();
        $this->assertNotNull($adminSalLine, 'Salaries & Staff Wages line must be present in journal');
        $this->assertEquals(22000.00, (float)$adminSalLine->debit_amount);

        // Total debits must equal total credits
        $totalDebit = round($jv->lines()->sum('debit_amount'), 2);
        $totalCredit = round($jv->lines()->sum('credit_amount'), 2);
        $this->assertEquals($totalDebit, $totalCredit);
    }

    /**
     * Requirement 3: Cash Flow Statement Period Delta Working Capital Calculation
     */
    public function test_cash_flow_period_delta_working_capital_calculation(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();

        $response = $this->get(route('reports.financial.cash_flow', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        $response->assertStatus(200);
        $response->assertSee('STATEMENT OF CASH FLOWS');
        $response->assertSee('Operating Activities');

        // Verify view data contains period movements and reconciling cash balances
        $viewData = $response->original->getData();
        $this->assertArrayHasKey('openingCashBalance', $viewData);
        $this->assertArrayHasKey('closingCashBalance', $viewData);
        $this->assertArrayHasKey('netCashChange', $viewData);

        // Mathematical reconciliation invariant: openingCashBalance + netCashChange == closingCashBalance
        $expectedClosing = round($viewData['openingCashBalance'] + $viewData['netCashChange'], 2);
        $this->assertEquals($expectedClosing, round($viewData['closingCashBalance'], 2));
    }

    /**
     * Requirement 5: Inventory vs General Ledger Explicit Classification Reconciliation
     */
    public function test_inventory_explicit_classification_reconciliation(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        // Ensure inventory ledgers have is_inventory = true
        $invLedgers = Ledger::where('company_id', $this->companyA->id)
            ->where('is_inventory', true)
            ->get();
        $this->assertGreaterThan(0, $invLedgers->count(), 'There must be ledgers classified with is_inventory = true');

        $response = $this->get(route('reports.inventory.reconciliation'));
        $response->assertStatus(200);
        $response->assertSee('Inventory Valuation');
        $response->assertSee('Physical Stock Breakdown by Product');

        $viewData = $response->original->getData();
        $this->assertArrayHasKey('totalPhysicalValuation', $viewData);
        $this->assertArrayHasKey('totalLedgerValuation', $viewData);
        $this->assertArrayHasKey('variance', $viewData);

        // Variance invariant: variance == totalPhysicalValuation - totalLedgerValuation
        $expectedVariance = round($viewData['totalPhysicalValuation'] - $viewData['totalLedgerValuation'], 2);
        $this->assertEquals($expectedVariance, round($viewData['variance'], 2));
    }

    /**
     * Multi-Company Security: Cross-Company Daily Operation Reversal & Overhead Tenant Scoping
     */
    public function test_multi_company_security_on_phase_five_enhancements(): void
    {
        // 1. Create a daily operation belonging to Company B
        $deptB = Department::firstOrCreate(
            ['company_id' => $this->companyB->id, 'code' => 'DEP-B-SEC'],
            ['name' => 'Company B Production', 'status' => 'active']
        );

        $opB = DailyOperation::create([
            'company_id' => $this->companyB->id,
            'department_id' => $deptB->id,
            'operation_number' => 'OP-SEC-B-' . rand(1000, 9999),
            'operation_date' => now()->toDateString(),
            'process_name' => 'Company B Process',
            'work_status' => 'completed',
            'approval_status' => 'admin_approved',
            'stock_posted' => true,
        ]);

        // 2. Company A user attempts to reverse Company B's operation -> 403 Forbidden
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $crossReverseResponse = $this->post(route('daily_operations.reverse', $opB), [
            'reversal_reason' => 'Unauthorized breach attempt',
        ]);
        $crossReverseResponse->assertStatus(403);

        // 3. Daily Operation Overhead foreign-key tenant scoping
        // Company A user attempts to create operation referencing Company B expense ledger
        $expenseLedgerB = $this->accountingService->getOrCreateStandardLedger(
            $this->companyB->id,
            'Company B Power Expense',
            'Indirect Expenses',
            'Expenses'
        );

        $crossCreateResponse = $this->post(route('daily_operations.store'), [
            'department_id' => $this->prodDept->id,
            'operation_date' => now()->toDateString(),
            'process_name' => 'Illegal Cross Ledger Op',
            'overheads' => [
                [
                    'overhead_category' => 'electricity',
                    'expense_ledger_id' => $expenseLedgerB->id, // Belongs to Company B!
                    'allocation_method' => 'machine_hours',
                    'allocation_basis_value' => 10,
                    'rate_per_unit' => 20,
                    'allocated_amount' => 200,
                ]
            ],
            'outputs' => [
                [
                    'item_id' => $this->finishedItem->id,
                    'dest_godown_id' => $this->godownFG->id,
                    'produced_quantity' => 1,
                ]
            ]
        ]);

        // Validation rule should reject cross-company ledger
        $crossCreateResponse->assertSessionHasErrors(['overheads.0.expense_ledger_id']);
    }
}

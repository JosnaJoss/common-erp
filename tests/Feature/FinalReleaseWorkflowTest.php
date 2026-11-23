<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DailyOperation;
use App\Models\DailyOperationEmployee;
use App\Models\Department;
use App\Models\FinancialYear;
use App\Models\Godown;
use App\Models\GoodsReceiptNote;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Ledger;
use App\Models\Payroll;
use App\Models\PettyCashAccount;
use App\Models\PettyCashCategory;
use App\Models\PettyCashVoucher;
use App\Models\PurchaseEntry;
use App\Models\PurchaseOrder;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Staff;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\DailyOperationService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinalReleaseWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Godown $godownA;
    protected Unit $unitKg;
    protected Unit $unitPcs;
    protected Item $rawItem;
    protected Item $finishedItem;
    protected Supplier $supplier;
    protected Customer $customer;
    protected Department $prodDept;
    protected Staff $worker;
    protected Ledger $bankLedger;
    protected Ledger $cashLedger;

    protected AccountingService $accountingService;
    protected InventoryService $inventoryService;
    protected DailyOperationService $dailyOpService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountingService = app(AccountingService::class);
        $this->inventoryService = app(InventoryService::class);
        $this->dailyOpService = app(DailyOperationService::class);

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
            ['company_id' => $this->companyA->id, 'code' => 'HQ-FINAL'],
            ['name' => 'Final Release HQ', 'status' => 'active']
        );

        $this->godownA = Godown::firstOrCreate(
            ['company_id' => $this->companyA->id, 'branch_id' => $this->branchA->id, 'code' => 'G-FINAL-01'],
            ['name' => 'Final Central Godown', 'status' => 'active']
        );

        $this->unitKg = Unit::firstOrCreate(
            ['company_id' => $this->companyA->id, 'symbol' => 'KG'],
            ['name' => 'Kilogram']
        );

        $this->unitPcs = Unit::firstOrCreate(
            ['company_id' => $this->companyA->id, 'symbol' => 'PCS'],
            ['name' => 'Pieces']
        );

        $this->rawItem = Item::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'FINAL-RAW-COPPER'],
            [
                'name' => 'Copper Ingot Grade A',
                'unit_id' => $this->unitKg->id,
                'normal_price' => 120.00,
                'purchase_price' => 100.00,
                'item_type' => 'raw_material',
                'status' => 'active',
            ]
        );

        $this->finishedItem = Item::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'FINAL-FG-COIL'],
            [
                'name' => 'Copper Induction Coil',
                'unit_id' => $this->unitPcs->id,
                'normal_price' => 350.00,
                'purchase_price' => 200.00,
                'item_type' => 'sales_item',
                'status' => 'active',
            ]
        );

        $this->supplier = Supplier::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'SUP-FINAL-01'],
            [
                'name' => 'National Metals Corp',
                'contact_person' => 'Rajesh Sharma',
                'phone' => '9811122334',
                'email' => 'metals@national.test',
                'status' => 'active',
            ]
        );

        $supLedger = $this->accountingService->getOrCreateStandardLedger(
            $this->companyA->id,
            "Supplier - {$this->supplier->name}",
            'Sundry Creditors',
            'Liabilities'
        );
        $this->supplier->update(['ledger_id' => $supLedger->id]);

        $this->customer = Customer::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'CUST-FINAL-01'],
            [
                'name' => 'Bharat Electricals Ltd',
                'contact_person' => 'Sunil Gupta',
                'phone' => '9822233445',
                'email' => 'orders@bharatelec.test',
                'status' => 'active',
            ]
        );

        $custLedger = $this->accountingService->getOrCreateStandardLedger(
            $this->companyA->id,
            "Customer - {$this->customer->name}",
            'Sundry Debtors',
            'Assets'
        );
        $this->customer->update(['ledger_id' => $custLedger->id]);

        $this->prodDept = Department::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'DEP-PROD-FIN'],
            ['name' => 'Final Production Dept', 'status' => 'active']
        );

        $this->worker = Staff::firstOrCreate(
            ['company_id' => $this->companyA->id, 'employee_id' => 'EMP-FIN-W1'],
            [
                'first_name' => 'Dinesh',
                'last_name' => 'Yadav',
                'email' => 'dinesh.yadav@apex.test',
                'phone' => '9833344556',
                'branch_id' => $this->branchA->id,
                'department_id' => $this->prodDept->id,
                'designation' => 'Coil Technician',
                'employee_type' => 'piece_rate',
                'salary_amount' => 20000.00,
                'basic_salary' => 18000.00,
                'date_of_joining' => '2023-01-01',
                'status' => 'active',
            ]
        );

        // Standard Ledgers
        $this->bankLedger = $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Main Bank Account', 'Bank Accounts', 'Assets');
        $this->cashLedger = $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Cash in Hand', 'Cash & Bank Balances', 'Assets');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Stock on Hand', 'Current Assets', 'Assets');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Raw Materials Inventory', 'Current Assets', 'Assets');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Finished Goods Inventory', 'Current Assets', 'Assets');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Manufacturing Labor Payable', 'Current Liabilities', 'Liabilities');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Manufacturing Overhead Absorbed', 'Direct Expenses', 'Expenses');
        $this->accountingService->getOrCreateStandardLedger($this->companyA->id, 'Salaries & Staff Wages', 'Indirect Expenses', 'Expenses');
    }

    /**
     * Workflow A: Purchase -> Goods Receipt -> Stock Inward -> Supplier Invoice -> Payment
     */
    public function test_workflow_a_purchase_to_goods_receipt_to_payment(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $batchNo = 'BAT-PUR-FIN01';
        $qty = 50.0;
        $rate = 100.00; // 50 * 100 = ₹5,000

        // 1. Record Purchase Entry
        $response = $this->post(route('purchase.entries.store'), [
            'supplier_id' => $this->supplier->id,
            'godown_id' => $this->godownA->id,
            'invoice_number' => 'INV-SUP-FIN-001',
            'invoice_date' => now()->toDateString(),
            'payment_type' => 'Credit',
            'charge_allocation_method' => 'value',
            'freight_charges' => 200.00,
            'items' => [
                [
                    'item_id' => $this->rawItem->id,
                    'unit_id' => $this->unitKg->id,
                    'batch_number' => $batchNo,
                    'quantity' => $qty,
                    'rate' => $rate,
                    'discount_amount' => 0,
                    'tax_percentage' => 18,
                ]
            ]
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 2. Verify stock increased in godown
        $batch = StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->rawItem->id)
            ->where('batch_no', $batchNo)
            ->first();

        $this->assertNotNull($batch);
        $this->assertEquals($qty, (float)$batch->quantity);
        $this->assertGreaterThan(100.00, (float)$batch->cost_price); // Includes allocated freight

        // 3. Verify Purchase Entry & Accounting Voucher
        $pe = PurchaseEntry::where('company_id', $this->companyA->id)
            ->where('invoice_number', 'INV-SUP-FIN-001')
            ->first();
        $this->assertNotNull($pe);
        $this->assertEquals('unpaid', $pe->payment_status);

        $jv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('reference_type', 'PurchaseEntry')
            ->where('reference_id', $pe->id)
            ->first();
        $this->assertNotNull($jv);
        $this->assertEquals(round($jv->lines()->sum('debit_amount'), 2), round($jv->lines()->sum('credit_amount'), 2));

        // 4. Settle Payment to Supplier
        $payResponse = $this->post(route('purchase.payments.store'), [
            'supplier_id' => $this->supplier->id,
            'payment_date' => now()->toDateString(),
            'payment_mode' => 'Bank Transfer',
            'payment_ledger_id' => $this->bankLedger->id,
            'payment_amount' => $pe->grand_total,
            'transaction_reference' => 'TXN-BANK-PAY-01',
            'notes' => 'Settlement for INV-SUP-FIN-001',
        ]);

        $payResponse->assertSessionHasNoErrors();
        $payResponse->assertRedirect();

        // Verify Payment Journal
        $payJv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('voucher_type', 'Payment')
            ->latest('id')
            ->first();
        $this->assertNotNull($payJv);
        $this->assertEquals(round($payJv->lines()->sum('debit_amount'), 2), round($payJv->lines()->sum('credit_amount'), 2));
    }

    /**
     * Workflow B: Sales Order -> Stock Reduction -> Customer Invoice -> Receipt Collection
     */
    public function test_workflow_b_sales_to_customer_invoice_to_receipt(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $batchNo = 'BAT-SLS-FIN01';

        // 1. Inward stock for Finished Item
        $this->inventoryService->recordInward(
            $this->companyA->id,
            $this->branchA->id,
            $this->godownA->id,
            $this->finishedItem->id,
            20.0,
            200.00,
            $batchNo,
            null,
            'PurchaseEntry',
            null,
            'INITIAL-STOCK'
        );

        // 2. Create Sales Invoice (Credit Sale)
        $response = $this->post(route('sales.invoices.store'), [
            'customer_id' => $this->customer->id,
            'godown_id' => $this->godownA->id,
            'invoice_date' => now()->toDateString(),
            'payment_mode' => 'Credit',
            'items' => [
                [
                    'item_id' => $this->finishedItem->id,
                    'unit_id' => $this->unitPcs->id,
                    'batch_number' => $batchNo,
                    'quantity' => 5.0,
                    'rate' => 350.00, // 5 * 350 = 1750
                    'discount_amount' => 0,
                    'tax_percentage' => 18,
                ]
            ]
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // 3. Verify stock reduced by 5 (20 - 5 = 15 remaining)
        $batch = StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->finishedItem->id)
            ->where('batch_no', $batchNo)
            ->first();
        $this->assertEquals(15.0, (float)$batch->quantity);

        $si = SalesInvoice::where('company_id', $this->companyA->id)
            ->where('customer_id', $this->customer->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($si);
        $this->assertEquals('unpaid', $si->payment_status);

        // 4. Verify Double-Entry Accounting for Sales Invoice
        $siJv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('reference_type', 'SalesInvoice')
            ->where('reference_id', $si->id)
            ->first();
        $this->assertNotNull($siJv);
        $this->assertEquals(round($siJv->lines()->sum('debit_amount'), 2), round($siJv->lines()->sum('credit_amount'), 2));

        // 5. Collect Customer Payment
        $collResponse = $this->post(route('sales.collections.store'), [
            'customer_id' => $this->customer->id,
            'collection_date' => now()->toDateString(),
            'payment_mode' => 'Bank Transfer',
            'receipt_ledger_id' => $this->bankLedger->id,
            'collection_amount' => $si->grand_total,
            'transaction_reference' => 'TXN-BANK-COLL-01',
            'notes' => 'Collection for ' . $si->invoice_number,
        ]);

        $collResponse->assertSessionHasNoErrors();
        $collResponse->assertRedirect();

        // Verify Receipt Journal Voucher
        $recJv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('voucher_type', 'Receipt')
            ->latest('id')
            ->first();
        $this->assertNotNull($recJv);
        $this->assertEquals(round($recJv->lines()->sum('debit_amount'), 2), round($recJv->lines()->sum('credit_amount'), 2));
    }

    /**
     * Workflow C: Manufacturing -> Raw Consumption -> Production Cost -> Finished Stock & Rollback
     */
    public function test_workflow_c_manufacturing_complete_production_and_reversal(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $rawBatchNo = 'BAT-MFG-RAW01';
        $fgBatchNo = 'BAT-MFG-FG01';

        // 1. Inward Raw Material
        $this->inventoryService->recordInward(
            $this->companyA->id,
            $this->branchA->id,
            $this->godownA->id,
            $this->rawItem->id,
            40.0,
            100.00,
            $rawBatchNo,
            null,
            'PurchaseEntry',
            null,
            'RAW-SUP'
        );

        // 2. Create Daily Operation
        $op = $this->dailyOpService->createOperation(
            [
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
                'department_id' => $this->prodDept->id,
                'operation_date' => now()->toDateString(),
                'process_name' => 'Coil Winding & Insulation',
                'work_status' => 'completed',
                'approval_status' => 'admin_approved',
            ],
            [
                [
                    'staff_id' => $this->worker->id,
                    'employment_type' => 'piece_rate',
                    'hours_worked' => 8,
                    'units_completed' => 10,
                    'rate_per_unit' => 20.00, // ₹200 labor
                    'calculated_wage' => 200.00,
                ]
            ],
            [
                [
                    'item_id' => $this->rawItem->id,
                    'source_godown_id' => $this->godownA->id,
                    'batch_no' => $rawBatchNo,
                    'consumed_quantity' => 15.0, // 15 * 100 = ₹1,500
                    'unit_cost' => 100.00,
                ]
            ],
            [
                [
                    'item_id' => $this->finishedItem->id,
                    'dest_godown_id' => $this->godownA->id,
                    'batch_number' => $fgBatchNo,
                    'produced_quantity' => 10.0,
                    'unit_cost' => 170.00, // (1500 mat + 200 labor) / 10 = 170
                    'production_date' => now()->toDateString(),
                ]
            ],
            [],
            [],
            [],
            $this->adminUser->id
        );

        // 3. Post stock & Verify atomic movement
        $this->dailyOpService->postOperationStock($op, $this->adminUser->id);
        $op->refresh();
        $this->assertTrue((bool)$op->stock_posted);

        // Raw stock reduced by 15 (40 - 15 = 25)
        $rawBatch = StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->rawItem->id)
            ->where('batch_no', $rawBatchNo)
            ->first();
        $this->assertEquals(25.0, (float)$rawBatch->quantity);

        // FG stock increased by 10
        $fgBatch = StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->finishedItem->id)
            ->where('batch_no', $fgBatchNo)
            ->first();
        $this->assertEquals(10.0, (float)$fgBatch->quantity);

        // Verify Capitalization Journal Entry
        $mfgJv = JournalEntry::find($op->journal_entry_id);
        $this->assertNotNull($mfgJv);
        $this->assertEquals(round($mfgJv->lines()->sum('debit_amount'), 2), round($mfgJv->lines()->sum('credit_amount'), 2));

        // 4. Safe Reversal Workflow
        $revResponse = $this->post(route('daily_operations.reverse', $op), [
            'reversal_reason' => 'Quality tolerance check failure, batch scrapped.',
        ]);
        $revResponse->assertRedirect();
        $revResponse->assertSessionHas('success');

        $op->refresh();
        $this->assertFalse((bool)$op->stock_posted);
        $this->assertNotNull($op->reversed_at);

        // Stock restored cleanly
        $rawBatch->refresh();
        $this->assertEquals(40.0, (float)$rawBatch->quantity);

        $fgBatch->refresh();
        $this->assertEquals(0.0, (float)$fgBatch->quantity);
    }

    /**
     * Workflow D: Payroll -> Attendance & LOP -> Shop Labor Settlement -> Disbursement
     */
    public function test_workflow_d_payroll_generation_and_labor_settlement(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $salaryMonth = Carbon::now()->format('Y-m');

        // Record direct production wage for worker
        $op = DailyOperation::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->prodDept->id,
            'operation_number' => 'OP-PAY-FIN-' . rand(100, 999),
            'operation_date' => Carbon::now()->startOfMonth()->addDays(2)->toDateString(),
            'process_name' => 'Winding',
            'work_status' => 'completed',
            'approval_status' => 'admin_approved',
            'stock_posted' => true,
            'total_labor_cost' => 5000.00,
        ]);

        DailyOperationEmployee::create([
            'daily_operation_id' => $op->id,
            'staff_id' => $this->worker->id,
            'employment_type' => 'piece_rate',
            'hours_worked' => 20,
            'units_completed' => 50,
            'rate_per_unit' => 100.00,
            'wage_earned' => 5000.00,
        ]);

        // Generate Payroll
        $genResponse = $this->post(route('staff.payroll.generate'), [
            'salary_month' => $salaryMonth,
        ]);
        $genResponse->assertSessionHas('success');

        $payroll = Payroll::where('company_id', $this->companyA->id)
            ->where('staff_id', $this->worker->id)
            ->where('salary_month', $salaryMonth)
            ->first();
        $this->assertNotNull($payroll);

        // Disburse Payroll
        $payResponse = $this->post(route('staff.payroll.bulk_pay'), [
            'payroll_ids' => [$payroll->id],
            'payment_mode' => 'Bank Transfer',
            'payment_ledger_id' => $this->bankLedger->id,
            'payment_date' => Carbon::now()->toDateString(),
            'transaction_reference' => 'SAL-FIN-BATCH-01',
        ]);
        $payResponse->assertSessionHas('success');

        $payroll->refresh();
        $this->assertEquals('paid', $payroll->payroll_status);

        // Verify that 5000 was settled against Manufacturing Labor Payable
        $this->assertEquals(5000.00, (float)$payroll->manufacturing_labor_settled);

        // Verify Journal Entry is balanced
        $salJv = JournalEntry::find($payroll->journal_entry_id);
        $this->assertNotNull($salJv);
        $this->assertEquals(round($salJv->lines()->sum('debit_amount'), 2), round($salJv->lines()->sum('credit_amount'), 2));
    }

    /**
     * Workflow E: Expenses & Petty Cash -> Payment -> General Ledger
     */
    public function test_workflow_e_expenses_petty_cash_and_replenishment(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $pettyLedger = $this->accountingService->getOrCreateStandardLedger(
            $this->companyA->id,
            'Petty Cash Account - Main',
            'Cash & Bank Balances',
            'Assets'
        );

        $pettyAccount = PettyCashAccount::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'PCA-FIN-01'],
            [
                'name' => 'Main Office Petty Cash',
                'branch_id' => $this->branchA->id,
                'custodian_staff_id' => $this->worker->id,
                'float_limit' => 10000.00,
                'current_balance' => 5000.00,
                'ledger_id' => $pettyLedger->id,
                'status' => 'active',
            ]
        );

        $stationeryLedger = $this->accountingService->getOrCreateStandardLedger(
            $this->companyA->id,
            'Printing and Stationery Expenses',
            'Administrative & Indirect Expenses',
            'Expenses'
        );

        $category = PettyCashCategory::firstOrCreate(
            ['company_id' => $this->companyA->id, 'name' => 'Office Stationery & Printing'],
            [
                'ledger_id' => $stationeryLedger->id,
                'status' => 'active',
            ]
        );

        // 1. Record Petty Cash Expense Voucher
        $voucherResponse = $this->post(route('accounts.petty_cash.vouchers.store'), [
            'petty_cash_account_id' => $pettyAccount->id,
            'category_id' => $category->id,
            'voucher_date' => now()->toDateString(),
            'amount' => 450.00,
            'paid_to' => 'Metro Stationery Store',
            'remarks' => 'Printer cartridges and registers',
        ]);

        $voucherResponse->assertRedirect();
        $voucherResponse->assertSessionHas('success');

        $pettyAccount->refresh();
        $this->assertEquals(4550.00, (float)$pettyAccount->current_balance);

        $pcv = PettyCashVoucher::where('company_id', $this->companyA->id)
            ->where('petty_cash_account_id', $pettyAccount->id)
            ->latest('id')
            ->first();
        $this->assertNotNull($pcv);

        // Verify Journal Entry for Petty Cash Expense
        $pcvJv = JournalEntry::find($pcv->journal_entry_id);
        $this->assertNotNull($pcvJv);
        $this->assertEquals(round($pcvJv->lines()->sum('debit_amount'), 2), round($pcvJv->lines()->sum('credit_amount'), 2));

        // 2. Replenish Float from Bank
        $repResponse = $this->post(route('accounts.petty_cash.replenish.store'), [
            'petty_cash_account_id' => $pettyAccount->id,
            'amount' => 450.00,
            'bank_ledger_id' => $this->bankLedger->id,
            'replenishment_date' => now()->toDateString(),
            'transaction_reference' => 'CHQ-REPLENISH-01',
            'notes' => 'Weekly float top-up',
        ]);

        $repResponse->assertRedirect();
        $repResponse->assertSessionHas('success');

        $pettyAccount->refresh();
        $this->assertEquals(5000.00, (float)$pettyAccount->current_balance);
    }

    /**
     * Workflow F: Trial Balance, P&L, Balance Sheet, Cash Flow, GL Recon & Financial Year Lock
     */
    public function test_workflow_f_financial_statements_and_year_lock(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $startDate = Carbon::now()->startOfMonth()->toDateString();
        $endDate = Carbon::now()->endOfMonth()->toDateString();

        // 1. Trial Balance Check: Debits must equal Credits
        $tbResponse = $this->get(route('reports.financial.trial_balance'));
        $tbResponse->assertStatus(200);
        $tbData = $tbResponse->original->getData();
        $this->assertEquals(round($tbData['totalDebit'], 2), round($tbData['totalCredit'], 2));

        // 2. Profit & Loss Report
        $pnlResponse = $this->get(route('reports.financial.profit_and_loss', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));
        $pnlResponse->assertStatus(200);

        // 3. Balance Sheet Report (Verifies Equation: Assets = Liabilities + Equity)
        $bsResponse = $this->get(route('reports.financial.balance_sheet', ['as_of_date' => $endDate]));
        $bsResponse->assertStatus(200);
        $bsData = $bsResponse->original->getData();
        $this->assertEquals(round($bsData['totalAssets'], 2), round($bsData['totalLiabilitiesAndEquity'], 2));

        // 4. Cash Flow Statement (Indirect Method & Period Movement Reconciled)
        $cfResponse = $this->get(route('reports.financial.cash_flow', [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));
        $cfResponse->assertStatus(200);
        $cfData = $cfResponse->original->getData();
        $this->assertArrayHasKey('openingCashBalance', $cfData);
        $this->assertArrayHasKey('closingCashBalance', $cfData);
        $this->assertArrayHasKey('netCashChange', $cfData);

        // 5. Inventory-to-GL Reconciliation
        $reconResponse = $this->get(route('reports.inventory.reconciliation'));
        $reconResponse->assertStatus(200);
        $reconData = $reconResponse->original->getData();
        $expectedVariance = round($reconData['totalPhysicalValuation'] - $reconData['totalLedgerValuation'], 2);
        $this->assertEquals($expectedVariance, round($reconData['variance'], 2));

        // 6. Financial Year Lock Toggle & Protection
        $fy = FinancialYear::firstOrCreate(
            ['company_id' => $this->companyA->id, 'title' => 'FY 2026-27 Lockdown'],
            [
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_closed' => false,
            ]
        );

        // Lock the year
        $lockResponse = $this->post(route('accounts.financial_years.toggle_lock', $fy));
        $lockResponse->assertRedirect();
        $lockResponse->assertSessionHas('success');

        $fy->refresh();
        $this->assertTrue((bool)$fy->is_closed);

        // Attempting to post a purchase entry into the locked period must be rejected
        $blockedPurResponse = $this->post(route('purchase.entries.store'), [
            'supplier_id' => $this->supplier->id,
            'godown_id' => $this->godownA->id,
            'invoice_number' => 'INV-LOCKED-001',
            'invoice_date' => '2026-06-15', // In closed FY!
            'payment_type' => 'Credit',
            'items' => [
                [
                    'item_id' => $this->rawItem->id,
                    'quantity' => 1,
                    'rate' => 100,
                ]
            ]
        ]);
        $blockedPurResponse->assertSessionHasErrors(['invoice_date']);

        // Attempting to post a sales invoice into the locked period must be rejected
        $blockedSalesResponse = $this->post(route('sales.invoices.store'), [
            'customer_id' => $this->customer->id,
            'godown_id' => $this->godownA->id,
            'invoice_date' => '2026-07-20', // In closed FY!
            'payment_mode' => 'Credit',
            'items' => [
                [
                    'item_id' => $this->finishedItem->id,
                    'quantity' => 1,
                    'rate' => 350,
                ]
            ]
        ]);
        $blockedSalesResponse->assertSessionHasErrors(['invoice_date']);

        // Attempting manual journal voucher into locked period must be rejected
        $blockedJvResponse = $this->post(route('accounts.vouchers.store'), [
            'voucher_type' => 'Journal',
            'voucher_date' => '2026-08-10', // In closed FY!
            'lines' => [
                ['ledger_id' => $this->bankLedger->id, 'debit' => 100, 'credit' => 0],
                ['ledger_id' => $this->cashLedger->id, 'debit' => 0, 'credit' => 100],
            ]
        ]);
        $blockedJvResponse->assertSessionHasErrors(['voucher_date']);

        // Unlock year and verify status restored
        $unlockResponse = $this->post(route('accounts.financial_years.toggle_lock', $fy));
        $unlockResponse->assertRedirect();
        $fy->refresh();
        $this->assertFalse((bool)$fy->is_closed);
    }

    /**
     * Workflow G: Multi-Company Operations -> User Permissions -> Data Isolation
     */
    public function test_workflow_g_multi_company_security_and_tenant_isolation(): void
    {
        // 1. Setup Company B records
        $custB = Customer::firstOrCreate(
            ['company_id' => $this->companyB->id, 'code' => 'CUST-B-ISO'],
            ['name' => 'Company B Secret Customer', 'status' => 'active']
        );

        $fyB = FinancialYear::firstOrCreate(
            ['company_id' => $this->companyB->id, 'title' => 'FY 2026-27 Comp B'],
            ['start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_closed' => false]
        );

        // 2. Company A user attempts to view Company B customer -> 403 Forbidden
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $crossView = $this->get(route('customers.show', $custB));
        $crossView->assertStatus(403);

        // 3. Company A user attempts to lock/unlock Company B's financial year -> 403 Forbidden
        $crossLock = $this->post(route('accounts.financial_years.toggle_lock', $fyB));
        $crossLock->assertStatus(403);

        // 4. Company A user attempts to create sales invoice referencing Company B customer -> 422 Validation Error
        $crossSales = $this->post(route('sales.invoices.store'), [
            'customer_id' => $custB->id, // Belongs to Company B!
            'godown_id' => $this->godownA->id,
            'invoice_date' => now()->toDateString(),
            'payment_mode' => 'Credit',
            'items' => [
                [
                    'item_id' => $this->finishedItem->id,
                    'quantity' => 1,
                    'rate' => 350,
                ]
            ]
        ]);
        $crossSales->assertSessionHasErrors(['customer_id']);
    }
}

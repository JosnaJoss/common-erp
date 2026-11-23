<?php

namespace Tests\Feature;

use App\Models\AccountGroup;
use App\Models\Attendance;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\Branch;
use App\Models\CapitalInvestment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCollection;
use App\Models\DailyOperation;
use App\Models\DailyOperationEmployee;
use App\Models\Department;
use App\Models\FinancialYear;
use App\Models\Godown;
use App\Models\Investor;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Ledger;
use App\Models\Payroll;
use App\Models\PettyCashAccount;
use App\Models\PettyCashCategory;
use App\Models\PettyCashReplenishment;
use App\Models\PettyCashVoucher;
use App\Models\PurchaseEntry;
use App\Models\SalesInvoice;
use App\Models\Staff;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BusinessOperationsVerificationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Godown $godownA;
    protected Godown $godownB;
    protected Unit $unitKg;
    protected Unit $unitPcs;
    protected Item $rawItem;
    protected Item $salesItem;
    protected Supplier $supplier;
    protected Customer $customer;
    protected Department $prodDept;
    protected Staff $worker;
    protected Ledger $bankLedger;
    protected Ledger $cashLedger;
    protected AccountingService $accountingService;
    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountingService = app(AccountingService::class);
        $this->inventoryService = app(InventoryService::class);

        $this->companyA = Company::where('code', 'APEX-IND')->first() ?? Company::first();

        $this->companyB = Company::where('id', '!=', $this->companyA->id)->first() ?? Company::create([
            'name' => 'Company B Security Test',
            'code' => 'COMP-B-SEC',
            'currency' => 'INR',
            'status' => 'active',
        ]);

        $this->branchA = $this->companyA->branches()->first() ?? Branch::create([
            'company_id' => $this->companyA->id,
            'name' => 'Main Plant Branch',
            'code' => 'BR-MP-01',
            'status' => 'active',
        ]);

        $this->godownA = Godown::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'GDN-MP-RAW'],
            ['branch_id' => $this->branchA->id, 'name' => 'Central Raw Godown', 'status' => 'active']
        );

        $this->godownB = Godown::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'GDN-MP-FG'],
            ['branch_id' => $this->branchA->id, 'name' => 'Finished Goods Godown', 'status' => 'active']
        );

        $this->unitKg = Unit::firstOrCreate(
            ['company_id' => $this->companyA->id, 'name' => 'Kilogram'],
            ['symbol' => 'KG', 'unit_type' => 'weight', 'conversion_factor' => 1.0, 'status' => 'active']
        );

        $this->unitPcs = Unit::firstOrCreate(
            ['company_id' => $this->companyA->id, 'name' => 'Pieces'],
            ['symbol' => 'PCS', 'unit_type' => 'quantity', 'conversion_factor' => 1.0, 'status' => 'active']
        );

        $this->rawItem = Item::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'RAW-ST-001'],
            [
                'name' => 'Cold Rolled Steel Coil',
                'item_type' => 'raw_material',
                'unit_id' => $this->unitKg->id,
                'cost_price' => 50.00,
                'purchase_price' => 50.00,
                'selling_price' => 0.00,
                'reorder_quantity' => 100,
                'status' => 'active',
            ]
        );

        $this->salesItem = Item::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'FG-ST-001'],
            [
                'name' => 'Steel Precision Flange',
                'item_type' => 'both',
                'unit_id' => $this->unitPcs->id,
                'cost_price' => 80.00,
                'purchase_price' => 80.00,
                'selling_price' => 150.00,
                'reorder_quantity' => 20,
                'status' => 'active',
            ]
        );

        $sundryCreditors = $this->accountingService->getOrCreateStandardLedger(
            $this->companyA->id, 'Sundry Creditors Control', 'Current Liabilities', 'Liabilities'
        );

        $this->supplier = Supplier::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'SUP-METALS-01'],
            [
                'name' => 'Tata Steel Industries Ltd',
                'contact_person' => 'Ramesh Gupta',
                'phone' => '9847012345',
                'ledger_id' => $sundryCreditors->id,
                'status' => 'active',
            ]
        );

        $sundryDebtors = $this->accountingService->getOrCreateStandardLedger(
            $this->companyA->id, 'Sundry Debtors Control', 'Current Assets', 'Assets'
        );

        $this->customer = Customer::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'CUST-AUTO-01'],
            [
                'name' => 'Bharat Auto Components Ltd',
                'contact_person' => 'Suresh Nair',
                'phone' => '9847098765',
                'ledger_id' => $sundryDebtors->id,
                'status' => 'active',
            ]
        );

        $this->prodDept = Department::firstOrCreate(
            ['company_id' => $this->companyA->id, 'name' => 'Fabrication & Machining'],
            ['branch_id' => $this->branchA->id, 'code' => 'DEPT-FAB-01', 'status' => 'active']
        );

        $this->worker = Staff::firstOrCreate(
            ['company_id' => $this->companyA->id, 'employee_id' => 'EMP-TEST-001'],
            [
                'branch_id' => $this->branchA->id,
                'department_id' => $this->prodDept->id,
                'first_name' => 'Rajesh',
                'last_name' => 'Kumar',
                'employee_type' => 'piece_rate',
                'basic_salary' => 15000.00,
                'hra' => 3000.00,
                'other_allowances' => 2000.00,
                'salary_amount' => 20000.00,
                'pf_applicable' => true,
                'pf_rate_percent' => 12.0,
                'esi_applicable' => true,
                'esi_rate_percent' => 0.75,
                'status' => 'active',
            ]
        );

        $this->bankLedger = $this->accountingService->getOrCreateStandardLedger(
            $this->companyA->id, 'HDFC Corporate Current A/c', 'Bank Accounts', 'Assets'
        );
        $this->bankLedger->is_bank_or_cash = true;
        $this->bankLedger->opening_balance = 500000.00;
        $this->bankLedger->current_balance = 500000.00;
        $this->bankLedger->balance_type = 'debit';
        $this->bankLedger->save();

        $this->cashLedger = $this->accountingService->getOrCreateStandardLedger(
            $this->companyA->id, 'Main Cash in Hand', 'Cash-in-hand', 'Assets'
        );
        $this->cashLedger->is_bank_or_cash = true;
        $this->cashLedger->opening_balance = 50000.00;
        $this->cashLedger->current_balance = 50000.00;
        $this->cashLedger->balance_type = 'debit';
        $this->cashLedger->save();

        $this->adminUser = User::where('email', 'admin@erp.com')->first() ?? User::first();

        FinancialYear::firstOrCreate(
            ['company_id' => $this->companyA->id, 'title' => 'FY 2026-2027'],
            [
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_closed' => false,
            ]
        );

        session([
            'active_company_id' => $this->companyA->id,
            'active_branch_id' => $this->branchA->id,
            'current_company_id' => $this->companyA->id,
        ]);
    }

    // ==================== 1. INVENTORY MANAGEMENT ====================

    public function test_1_inventory_management_complete_operations(): void
    {
        $this->actingAs($this->adminUser);

        // A. Opening Stock Entry with balanced accounting
        $opResponse = $this->post(route('inventory.opening_stock.store'), [
            'godown_id' => $this->godownA->id,
            'items' => [
                [
                    'item_id' => $this->rawItem->id,
                    'quantity' => 100,
                    'unit_cost' => 50.00,
                ],
            ],
        ]);
        $opResponse->assertRedirect();

        $batch = StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->rawItem->id)
            ->first();
        $this->assertNotNull($batch);
        $this->assertGreaterThanOrEqual(100, (float)$batch->quantity);

        // Verify Opening Balance Equity journal entry exists
        $opJv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('reference_type', 'OpeningStock')
            ->latest()
            ->first();
        $this->assertNotNull($opJv);
        $this->assertEquals($opJv->total_debit, $opJv->total_credit);

        // B. Stock Transfer between warehouses (Godown A -> Godown B)
        $initialGodownAQty = (float)StockBatch::where('godown_id', $this->godownA->id)->where('item_id', $this->rawItem->id)->value('quantity');
        
        $trfResponse = $this->post(route('inventory.transfers.store'), [
            'source_godown_id' => $this->godownA->id,
            'dest_godown_id' => $this->godownB->id,
            'item_id' => $this->rawItem->id,
            'quantity' => 20,
            'transfer_date' => '2026-05-10',
            'mode' => 'completed',
            'remarks' => 'Internal transfer for production buffer',
        ]);
        $trfResponse->assertSessionHas('success');

        $afterGodownAQty = (float)StockBatch::where('godown_id', $this->godownA->id)->where('item_id', $this->rawItem->id)->value('quantity');
        $afterGodownBQty = (float)StockBatch::where('godown_id', $this->godownB->id)->where('item_id', $this->rawItem->id)->value('quantity');
        $this->assertEquals($initialGodownAQty - 20, $afterGodownAQty);
        $this->assertGreaterThanOrEqual(20, $afterGodownBQty);

        // C. Stock Physical Adjustment (Shortage / Decrease with Loss accounting)
        $curStock = (float)StockBatch::where('godown_id', $this->godownB->id)->where('item_id', $this->rawItem->id)->value('quantity');
        $adjResponse = $this->post(route('inventory.adjustments.store'), [
            'godown_id' => $this->godownB->id,
            'item_id' => $this->rawItem->id,
            'actual_stock' => $curStock - 5,
            'reason' => 'Physical count shortage',
        ]);
        $adjResponse->assertSessionHas('success');

        $adjBatchQty = (float)StockBatch::where('godown_id', $this->godownB->id)->where('item_id', $this->rawItem->id)->value('quantity');
        $this->assertEquals($curStock - 5, $adjBatchQty);

        $adjJv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('reference_type', 'StockAdjustment')
            ->latest()
            ->first();
        $this->assertNotNull($adjJv);
        $this->assertEquals($adjJv->total_debit, $adjJv->total_credit);

        // D. Current stock quantity, valuation and reports render cleanly
        $overviewResp = $this->get(route('inventory.overview'));
        $overviewResp->assertStatus(200);
        $overviewResp->assertSee('Stock Batches');

        $valuationResp = $this->get(route('reports.inventory.valuation'));
        $valuationResp->assertStatus(200);

        $reconcileResp = $this->get(route('reports.inventory.reconciliation'));
        $reconcileResp->assertStatus(200);
    }

    // ==================== 2. PURCHASE BILL ENTRY ====================

    public function test_2_purchase_bill_complete_workflow(): void
    {
        $this->actingAs($this->adminUser);

        $initialStock = (float)StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->rawItem->id)
            ->sum('quantity');

        // Create Purchase Bill Entry with landed freight charges
        $billResp = $this->post(route('purchase.entries.store'), [
            'supplier_id' => $this->supplier->id,
            'godown_id' => $this->godownA->id,
            'invoice_number' => 'PB-TATA-' . rand(1000, 9999),
            'invoice_date' => '2026-05-15',
            'payment_type' => 'Credit',
            'discount_amount' => 200.00,
            'freight_charges' => 500.00,
            'charge_allocation_method' => 'value',
            'items' => [
                [
                    'item_id' => $this->rawItem->id,
                    'quantity' => 100,
                    'rate' => 50.00,
                    'discount_amount' => 0,
                    'tax_percentage' => 18,
                    'batch_number' => 'LOT-STL-2026-01',
                    'expiry_date' => '2027-12-31',
                ],
            ],
        ]);
        $billResp->assertRedirect();

        $entry = PurchaseEntry::where('company_id', $this->companyA->id)
            ->where('supplier_id', $this->supplier->id)
            ->latest()
            ->first();
        $this->assertNotNull($entry);
        $this->assertEquals('unpaid', $entry->payment_status);

        // Inventory must increase
        $updatedStock = (float)StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->rawItem->id)
            ->sum('quantity');
        $this->assertEquals($initialStock + 100, $updatedStock);

        // Accounting entry must be posted and balanced
        $jv = $entry->journalEntry;
        $this->assertNotNull($jv);
        $this->assertEquals($jv->total_debit, $jv->total_credit);

        // Supplier outstanding balance verification
        $this->assertGreaterThan(0, $entry->balance_amount);

        // Record Supplier Payment Voucher
        $payResp = $this->post(route('purchase.payments.store'), [
            'supplier_id' => $this->supplier->id,
            'purchase_entry_id' => $entry->id,
            'payment_date' => '2026-05-20',
            'payment_amount' => $entry->grand_total,
            'payment_mode' => 'Bank Transfer',
            'payment_ledger_id' => $this->bankLedger->id,
            'transaction_reference' => 'UTR99887766',
            'remarks' => 'Full settlement of Tata Steel bill',
        ]);
        $payResp->assertSessionHas('success');

        $entry->refresh();
        $this->assertEquals(0, (float)$entry->balance_amount);
        $this->assertEquals('paid', $entry->payment_status);

        // Access and verify purchase history & bill details
        $showResp = $this->get(route('purchase.entries.show', $entry));
        $showResp->assertStatus(200);
        $showResp->assertSee($entry->invoice_number);
    }

    // ==================== 3. SALES BILL ENTRY ====================

    public function test_3_sales_bill_complete_workflow(): void
    {
        $this->actingAs($this->adminUser);

        // Ensure stock exists in Godown A for sales item
        $this->inventoryService->recordInward(
            $this->companyA->id,
            $this->branchA->id,
            $this->godownA->id,
            $this->salesItem->id,
            50,
            80.00,
            'BATCH-FLANGE-01',
            null,
            'OpeningStock',
            1,
            'OP-FLG'
        );

        $initialStock = (float)StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->salesItem->id)
            ->sum('quantity');

        // Post Sales Invoice Bill
        $invResp = $this->post(route('sales.invoices.store'), [
            'customer_id' => $this->customer->id,
            'godown_id' => $this->godownA->id,
            'invoice_date' => '2026-05-22',
            'payment_mode' => 'Credit',
            'discount_amount' => 100.00,
            'items' => [
                [
                    'item_id' => $this->salesItem->id,
                    'quantity' => 10,
                    'rate' => 150.00,
                    'discount_amount' => 0,
                    'tax_percentage' => 18,
                    'batch_number' => 'BATCH-FLANGE-01',
                ],
            ],
        ]);
        $invResp->assertRedirect();

        $invoice = SalesInvoice::where('company_id', $this->companyA->id)
            ->where('customer_id', $this->customer->id)
            ->latest()
            ->first();
        $this->assertNotNull($invoice);
        $this->assertEquals('unpaid', $invoice->payment_status);

        // Inventory must decrease
        $afterStock = (float)StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->salesItem->id)
            ->sum('quantity');
        $this->assertEquals($initialStock - 10, $afterStock);

        // Accounting entry must be posted and balanced
        $jv = $invoice->journalEntry;
        $this->assertNotNull($jv);
        $this->assertEquals($jv->total_debit, $jv->total_credit);

        // Customer outstanding balance verification
        $this->assertGreaterThan(0, $invoice->balance_amount);

        // Record Customer Collection Receipt
        $colResp = $this->post(route('sales.collections.store'), [
            'customer_id' => $this->customer->id,
            'sales_invoice_id' => $invoice->id,
            'collection_date' => '2026-05-25',
            'collection_amount' => $invoice->grand_total,
            'payment_mode' => 'Bank Transfer',
            'receipt_ledger_id' => $this->bankLedger->id,
            'transaction_reference' => 'IMPS11223344',
            'remarks' => 'Full clearance of invoice',
        ]);
        $colResp->assertSessionHas('success');

        $invoice->refresh();
        $this->assertEquals(0, (float)$invoice->balance_amount);
        $this->assertEquals('paid', $invoice->payment_status);

        // View and print printable sales bill
        $showResp = $this->get(route('sales.invoices.show', $invoice));
        $showResp->assertStatus(200);

        $printResp = $this->get(route('sales.invoices.print', $invoice));
        $printResp->assertStatus(200);
        $printResp->assertSee($invoice->invoice_number);
    }

    // ==================== 4. BANK RECONCILIATION ====================

    public function test_4_bank_reconciliation_complete_workflow(): void
    {
        $this->actingAs($this->adminUser);

        // Initialize Bank Reconciliation workspace
        $initResp = $this->post(route('accounts.bank_reconciliation.store'), [
            'ledger_id' => $this->bankLedger->id,
            'statement_date' => '2026-05-31',
            'statement_balance' => 450000.00,
            'notes' => 'May 2026 corporate bank statement reconciliation',
        ]);
        $initResp->assertRedirect();

        $brs = BankReconciliation::where('company_id', $this->companyA->id)
            ->where('ledger_id', $this->bankLedger->id)
            ->latest()
            ->first();
        $this->assertNotNull($brs);
        $this->assertEquals('in_progress', $brs->status);

        // In-place bank charges adjustment
        $adjResp = $this->post(route('accounts.bank_reconciliation.adjustment', $brs), [
            'adjustment_type' => 'bank_charges',
            'amount' => 350.00,
            'adjustment_date' => '2026-05-31',
            'narration' => 'Monthly current account maintenance fee',
        ]);
        $adjResp->assertSessionHas('success');

        // Verify that bank charges posted a double-entry payment voucher
        $chargeJv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('reference_type', 'BankReconciliation')
            ->where('reference_id', $brs->id)
            ->latest()
            ->first();
        $this->assertNotNull($chargeJv);
        $this->assertEquals(350.00, (float)$chargeJv->total_debit);
        $this->assertEquals(350.00, (float)$chargeJv->total_credit);

        // Toggle clearance on an item
        $item = $brs->items()->where('source', 'system')->where('is_cleared', false)->first();
        if ($item) {
            $clearResp = $this->post(route('accounts.bank_reconciliation.clear', [$brs, $item]));
            $clearResp->assertSessionHas('success');
            $item->refresh();
            $this->assertTrue((bool)$item->is_cleared);
        }

        // Complete and finalize reconciliation
        $compResp = $this->post(route('accounts.bank_reconciliation.complete', $brs));
        $compResp->assertRedirect();

        $brs->refresh();
        $this->assertEquals('reconciled', $brs->status);
        $this->assertNotNull($brs->reconciled_at);

        // Report view renders correctly
        $reportResp = $this->get(route('accounts.bank_reconciliation.report', $brs));
        $reportResp->assertStatus(200);
        $reportResp->assertSee('Bank Reconciliation Statement');
    }

    // ==================== 5. PETTY CASH ====================

    public function test_5_petty_cash_complete_workflow(): void
    {
        $this->actingAs($this->adminUser);

        // Create dedicated Petty Cash Account
        $accResp = $this->post(route('accounts.petty_cash.accounts.store'), [
            'name' => 'Plant Floor Petty Cash',
            'branch_id' => $this->branchA->id,
            'custodian_staff_id' => $this->worker->id,
            'float_limit' => 5000.00,
            'opening_balance' => 2500.00,
        ]);
        $accResp->assertSessionHas('success');

        $account = PettyCashAccount::where('company_id', $this->companyA->id)
            ->where('name', 'Plant Floor Petty Cash')
            ->first();
        $this->assertNotNull($account);
        $this->assertEquals(2500.00, (float)$account->current_balance);

        $category = PettyCashCategory::firstOrCreate(
            ['company_id' => $this->companyA->id, 'name' => 'Factory Refreshments & Tea'],
            ['status' => 'active']
        );

        // Record Petty Cash Expense Voucher
        $vchResp = $this->post(route('accounts.petty_cash.vouchers.store'), [
            'petty_cash_account_id' => $account->id,
            'category_id' => $category->id,
            'voucher_date' => '2026-05-18',
            'amount' => 450.00,
            'paid_to' => 'Green Tea Vendor',
            'remarks' => 'Staff tea and snacks for overtime shift',
        ]);
        $vchResp->assertRedirect();

        $account->refresh();
        $this->assertEquals(2050.00, (float)$account->current_balance);

        $voucher = PettyCashVoucher::where('company_id', $this->companyA->id)
            ->where('petty_cash_account_id', $account->id)
            ->latest()
            ->first();
        $this->assertNotNull($voucher);
        $this->assertNotNull($voucher->journalEntry);
        $this->assertEquals($voucher->journalEntry->total_debit, $voucher->journalEntry->total_credit);

        // Float Replenishment from Bank
        $repResp = $this->post(route('accounts.petty_cash.replenish.store'), [
            'petty_cash_account_id' => $account->id,
            'amount' => 450.00,
            'bank_ledger_id' => $this->bankLedger->id,
            'replenishment_date' => '2026-05-19',
            'notes' => 'Float replenishment to restore 2500 balance',
        ]);
        $repResp->assertSessionHas('success');

        $account->refresh();
        $this->assertEquals(2500.00, (float)$account->current_balance);

        // Daybook and Printable Voucher
        $daybookResp = $this->get(route('accounts.petty_cash.daybook'));
        $daybookResp->assertStatus(200);

        $printResp = $this->get(route('accounts.petty_cash.vouchers.print', $voucher));
        $printResp->assertStatus(200);
        $printResp->assertSee($voucher->voucher_no);
    }

    // ==================== 6. CAPITAL INVESTMENT ENTRY ====================

    public function test_6_capital_investment_complete_workflow(): void
    {
        $this->actingAs($this->adminUser);

        // Register new Investor / Shareholder
        $invResp = $this->post(route('accounts.capital.investors.store'), [
            'name' => 'Vikram Singhania',
            'investor_type' => 'Director',
            'email' => 'vikram@apexind.com',
            'phone' => '9845011223',
            'shareholding_percentage' => 25.0,
            'opening_balance' => 0,
        ]);
        $invResp->assertSessionHas('success');

        $investor = Investor::where('company_id', $this->companyA->id)
            ->where('name', 'Vikram Singhania')
            ->first();
        $this->assertNotNull($investor);
        $this->assertNotNull($investor->ledger);
        $this->assertEquals('Equity', $investor->ledger->accountGroup->nature);

        // Record Capital Contribution
        $initialBankBalance = (float)$this->bankLedger->current_balance;

        $contribResp = $this->post(route('accounts.capital.transactions.store'), [
            'investor_id' => $investor->id,
            'transaction_type' => 'contribution',
            'transaction_date' => '2026-05-12',
            'amount' => 250000.00,
            'payment_mode' => 'Bank Transfer',
            'deposit_ledger_id' => $this->bankLedger->id,
            'reference_no' => 'NEFT-CAP-9988',
            'remarks' => 'Equity capital infusion for plant expansion',
        ]);
        $contribResp->assertSessionHas('success');

        $investor->refresh();
        $this->assertEquals(250000.00, (float)$investor->current_balance);
        $this->assertEquals(250000.00, (float)$investor->total_capital_contributed);

        // Verify Journal Entry: Dr Bank, Cr Capital Equity
        $capJv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('reference_type', 'CapitalInvestment')
            ->latest()
            ->first();
        $this->assertNotNull($capJv);
        $this->assertEquals(250000.00, (float)$capJv->total_debit);
        $this->assertEquals(250000.00, (float)$capJv->total_credit);

        // Verify that capital investment is NOT recorded in Revenue / Sales
        $revenueLedgerIds = Ledger::where('company_id', $this->companyA->id)
            ->whereHas('accountGroup', fn($q) => $q->where('nature', 'Revenue'))
            ->pluck('id')
            ->toArray();
        $revenueImpact = $capJv->lines()->whereIn('ledger_id', $revenueLedgerIds)->count();
        $this->assertEquals(0, $revenueImpact, 'Capital investment must never be posted to revenue ledgers.');

        // Verify Trial Balance equality
        $tbResp = $this->get(route('reports.financial.trial_balance'));
        $tbResp->assertStatus(200);

        // Verify Balance Sheet includes Equity
        $bsResp = $this->get(route('reports.financial.balance_sheet'));
        $bsResp->assertStatus(200);
        $bsResp->assertSee('Vikram Singhania');
    }

    // ==================== 7. PAYROLL & MANUFACTURING LABOR SETTLEMENT ====================

    public function test_7_payroll_complete_workflow(): void
    {
        $this->actingAs($this->adminUser);

        $month = '2026-05';

        // 1. Mark some attendance
        Attendance::firstOrCreate(
            ['company_id' => $this->companyA->id, 'staff_id' => $this->worker->id, 'attendance_date' => '2026-05-02'],
            ['attendance_status' => 'present', 'in_time' => '09:00:00', 'out_time' => '17:00:00']
        );

        // 2. Simulate manufacturing labor accrued in daily operations
        $op = DailyOperation::create([
            'company_id' => $this->companyA->id,
            'branch_id' => $this->branchA->id,
            'department_id' => $this->prodDept->id,
            'operation_number' => 'DOP-VERIF-01',
            'operation_date' => '2026-05-15',
            'process_name' => 'Machining',
            'status' => 'approved',
            'stock_posted' => true,
            'created_by' => $this->adminUser->id,
        ]);

        DailyOperationEmployee::create([
            'daily_operation_id' => $op->id,
            'staff_id' => $this->worker->id,
            'wage_type' => 'piece_rate',
            'wage_rate' => 15.0,
            'units_completed' => 100,
            'wage_earned' => 1500.00,
        ]);

        // 3. Generate Payroll for this month
        $genResp = $this->post(route('staff.payroll.generate'), [
            'salary_month' => $month,
        ]);
        $genResp->assertSessionHas('success');

        $payroll = Payroll::where('company_id', $this->companyA->id)
            ->where('staff_id', $this->worker->id)
            ->where('salary_month', $month)
            ->first();
        $this->assertNotNull($payroll);
        $this->assertEquals('generated', $payroll->payroll_status);

        // 4. Disburse Salary via Bank
        $payResp = $this->post(route('staff.payroll.pay', $payroll), [
            'payment_mode' => 'Bank Transfer',
            'payment_ledger_id' => $this->bankLedger->id,
            'payment_date' => '2026-05-31',
            'transaction_reference' => 'SAL-MAY-9988',
        ]);
        $payResp->assertSessionHas('success');

        $payroll->refresh();
        $this->assertEquals('paid', $payroll->payroll_status);

        // Verify manufacturing labor settlement was debited to prevent double counting
        $this->assertEquals(1500.00, (float)$payroll->manufacturing_labor_settled);

        // Verify balanced journal voucher
        $jv = $payroll->journalEntry;
        $this->assertNotNull($jv);
        $this->assertEquals($jv->total_debit, $jv->total_credit);

        // Payslip view renders correctly
        $slipResp = $this->get(route('staff.payroll.payslip', $payroll));
        $slipResp->assertStatus(200);
        $slipResp->assertSee('Payslip');
    }

    // ==================== 8. SECURITY, YEAR LOCK & ROLLBACKS ====================

    public function test_8_security_tenant_isolation_and_lock_guards(): void
    {
        $this->actingAs($this->adminUser);

        // Cross-company access attempts return 403
        $companyBCustomer = Customer::create([
            'company_id' => $this->companyB->id,
            'code' => 'CUST-B-99',
            'name' => 'Cross Company Tenant B Customer',
            'status' => 'active',
        ]);

        $crossResp = $this->post(route('sales.invoices.store'), [
            'customer_id' => $companyBCustomer->id,
            'godown_id' => $this->godownA->id,
            'invoice_date' => '2026-05-22',
            'payment_mode' => 'Credit',
            'items' => [
                ['item_id' => $this->salesItem->id, 'quantity' => 1, 'rate' => 100],
            ],
        ]);
        $crossResp->assertSessionHasErrors(['customer_id']);

        // Closed Financial Year prevents postings
        $closedFy = FinancialYear::create([
            'company_id' => $this->companyA->id,
            'title' => 'FY 2024-2025 Closed',
            'start_date' => '2024-04-01',
            'end_date' => '2025-03-31',
            'is_closed' => true,
        ]);

        // Attempt purchase entry in closed period
        $closedPurResp = $this->post(route('purchase.entries.store'), [
            'supplier_id' => $this->supplier->id,
            'godown_id' => $this->godownA->id,
            'invoice_number' => 'INV-CLOSED-01',
            'invoice_date' => '2024-06-15',
            'payment_type' => 'Credit',
            'items' => [
                ['item_id' => $this->rawItem->id, 'quantity' => 10, 'rate' => 50],
            ],
        ]);
        $closedPurResp->assertSessionHasErrors(['invoice_date']);

        // Attempt sales invoice in closed period
        $closedSalesResp = $this->post(route('sales.invoices.store'), [
            'customer_id' => $this->customer->id,
            'godown_id' => $this->godownA->id,
            'invoice_date' => '2024-06-15',
            'payment_mode' => 'Credit',
            'items' => [
                ['item_id' => $this->salesItem->id, 'quantity' => 10, 'rate' => 150],
            ],
        ]);
        $closedSalesResp->assertSessionHasErrors(['invoice_date']);

        // Attempt capital contribution in closed period
        $investor = Investor::firstOrCreate(
            ['company_id' => $this->companyA->id, 'name' => 'Lock Test Investor'],
            ['investor_type' => 'Partner', 'status' => 'active']
        );
        $closedCapResp = $this->post(route('accounts.capital.transactions.store'), [
            'investor_id' => $investor->id,
            'transaction_type' => 'contribution',
            'transaction_date' => '2024-06-15',
            'amount' => 50000,
            'payment_mode' => 'Bank Transfer',
            'deposit_ledger_id' => $this->bankLedger->id,
            'remarks' => 'Test lock',
        ]);
        $closedCapResp->assertSessionHas('error');
    }
}

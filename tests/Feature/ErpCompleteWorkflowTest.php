<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Ledger;
use App\Models\Payroll;
use App\Models\PurchaseEntry;
use App\Models\SalesInvoice;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ErpCompleteWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('email', 'admin@erp.com')->first();
        $this->company = Company::where('code', 'APEX-IND')->first();
    }

    public function test_login_screen_renders_and_admin_can_authenticate()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sign In to ERP');

        $loginResponse = $this->post('/login', [
            'email' => 'admin@erp.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->adminUser);
    }

    public function test_dashboard_renders_with_metrics_and_charts()
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Executive Dashboard');
        $response->assertSee('Apex Industrial Solutions');
        $response->assertSee('Monthly Sales vs Purchases');
    }

    public function test_inventory_overview_and_batch_tracking()
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->get(route('inventory.overview'));

        $response->assertStatus(200);
        $response->assertSee('Inventory & Stock Batches');
        $response->assertSee('Total Stock Valuation');
    }

    public function test_purchase_inward_updates_stock_and_posts_balanced_accounting_journal()
    {
        $item = Item::where('company_id', $this->company->id)->first();
        $supplier = Supplier::where('company_id', $this->company->id)->first();
        $godown = $this->company->godowns()->first();

        $this->assertNotNull($item);
        $this->assertNotNull($supplier);
        $this->assertNotNull($godown);

        $initialStock = $item->totalStockQuantity();

        $invoiceData = [
            'supplier_id' => $supplier->id,
            'godown_id' => $godown->id,
            'invoice_number' => 'TEST-PUR-' . rand(1000, 9999),
            'invoice_date' => now()->toDateString(),
            'payment_type' => 'Credit',
            'remarks' => 'Automated test purchase entry',
            'items' => [
                [
                    'item_id' => $item->id,
                    'batch_number' => 'TEST-BATCH-01',
                    'quantity' => 20,
                    'rate' => 100.00,
                    'tax_percentage' => 18,
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->post(route('purchase.entries.store'), $invoiceData);

        $response->assertSessionHasNoErrors();

        // 1. Check stock increased
        $item->refresh();
        $this->assertEquals($initialStock + 20, $item->totalStockQuantity());

        // 2. Check Purchase Entry created
        $purchaseEntry = PurchaseEntry::where('invoice_number', $invoiceData['invoice_number'])->first();
        $this->assertNotNull($purchaseEntry);
        $this->assertEquals(2000.00, (float)$purchaseEntry->subtotal);
        $this->assertEquals(360.00, (float)$purchaseEntry->tax_amount);
        $this->assertEquals(2360.00, (float)$purchaseEntry->grand_total);

        // 3. Check balanced double-entry voucher posted
        $jv = $purchaseEntry->journalEntry;
        $this->assertNotNull($jv);
        $this->assertEquals(2360.00, (float)$jv->total_debit);
        $this->assertEquals(2360.00, (float)$jv->total_credit);
        $this->assertEquals($jv->total_debit, $jv->total_credit);
    }

    public function test_sales_invoice_deducts_stock_and_posts_balanced_accounting_journal()
    {
        $item = Item::where('company_id', $this->company->id)->first();
        $customer = Customer::where('company_id', $this->company->id)->first();
        $godown = $this->company->godowns()->first();

        // Ensure stock exists
        $inventoryService = new InventoryService();
        $inventoryService->recordInward(
            $this->company->id, null, $godown->id, $item->id,
            50, 100, 'TEST-SALE-BATCH', null, 'ManualInward', null, 'STOCK-PREP', 'Test pre-stock'
        );

        $initialStock = $item->fresh()->totalStockQuantity();

        $invoiceData = [
            'customer_id' => $customer->id,
            'godown_id' => $godown->id,
            'invoice_date' => now()->toDateString(),
            'payment_terms' => 'Net 30 Days',
            'due_date' => now()->addDays(30)->toDateString(),
            'remarks' => 'Automated test sales invoice',
            'items' => [
                [
                    'item_id' => $item->id,
                    'batch_number' => 'TEST-SALE-BATCH',
                    'quantity' => 10,
                    'rate' => 200.00,
                    'tax_percentage' => 18,
                ]
            ]
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->post(route('sales.invoices.store'), $invoiceData);

        $response->assertSessionHasNoErrors();

        // 1. Verify stock reduced by 10
        $this->assertEquals($initialStock - 10, $item->fresh()->totalStockQuantity());

        // 2. Verify Invoice created
        $invoice = SalesInvoice::where('company_id', $this->company->id)->latest()->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(2000.00, (float)$invoice->subtotal);
        $this->assertEquals(360.00, (float)$invoice->tax_amount);
        $this->assertEquals(2360.00, (float)$invoice->grand_total);

        // 3. Verify balanced double-entry voucher
        $jv = $invoice->journalEntry;
        $this->assertNotNull($jv);
        $this->assertEquals(2360.00, (float)$jv->total_debit);
        $this->assertEquals(2360.00, (float)$jv->total_credit);
        $this->assertEquals($jv->total_debit, $jv->total_credit);
    }

    public function test_customer_collection_receipt_and_journal_posting()
    {
        $customer = Customer::where('company_id', $this->company->id)->first();
        $bankLedger = Ledger::where('company_id', $this->company->id)->where('is_bank_or_cash', true)->first();

        $this->assertNotNull($customer);
        $this->assertNotNull($bankLedger);

        $receiptData = [
            'customer_id' => $customer->id,
            'collection_date' => now()->toDateString(),
            'collection_amount' => 5000.00,
            'payment_mode' => 'Bank Transfer',
            'receipt_ledger_id' => $bankLedger->id,
            'transaction_reference' => 'UTR-' . rand(100000, 999999),
            'collected_by' => 'Staff Agent',
            'remarks' => 'Advance customer payment',
        ];

        $response = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->post(route('sales.collections.store'), $receiptData);

        $response->assertSessionHasNoErrors();

        $lastJv = JournalEntry::where('company_id', $this->company->id)
            ->where('voucher_type', 'Receipt')
            ->latest()
            ->first();

        $this->assertNotNull($lastJv);
        $this->assertEquals(5000.00, (float)$lastJv->total_debit);
        $this->assertEquals(5000.00, (float)$lastJv->total_credit);
    }

    public function test_payroll_generation_with_statutory_pf_and_esi_deductions()
    {
        $staff = Staff::where('company_id', $this->company->id)->where('status', 'active')->first();
        $this->assertNotNull($staff);

        $salaryMonth = now()->format('Y-m');

        $response = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->post(route('staff.payroll.generate'), [
                'salary_month' => $salaryMonth,
            ]);

        $response->assertSessionHasNoErrors();

        $payroll = Payroll::where('company_id', $this->company->id)
            ->where('staff_id', $staff->id)
            ->where('salary_month', $salaryMonth)
            ->first();

        $this->assertNotNull($payroll);
        $this->assertGreaterThan(0, (float)$payroll->basic_salary);
        $this->assertEquals(
            (float)$payroll->gross_salary - (float)$payroll->total_deductions,
            (float)$payroll->net_salary
        );

        // Test Printable Payslip
        $payslipResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->get(route('staff.payroll.payslip', $payroll));

        $payslipResponse->assertStatus(200);
        $payslipResponse->assertSee($staff->full_name);
        $payslipResponse->assertSee('Net Salary Payable');
    }

    public function test_financial_reports_trial_balance_debits_equal_credits()
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->get(route('reports.financial.trial_balance'));

        $response->assertStatus(200);
        $response->assertSee('TRIAL BALANCE');
        $response->assertSee('The Trial Balance is perfectly balanced!');
    }

    public function test_overtime_tracking_and_approval_workflow()
    {
        $staff = Staff::where('company_id', $this->company->id)->where('status', 'active')->first();
        $this->assertNotNull($staff);

        // 1. Visit Overtime list
        $listResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->get(route('staff.overtime.index'));

        $listResponse->assertStatus(200);
        $listResponse->assertSee('Overtime Tracking');

        // 2. Record Overtime
        $storeResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->post(route('staff.overtime.store'), [
                'staff_id' => $staff->id,
                'date' => now()->toDateString(),
                'overtime_hours' => 3.5,
                'overtime_rate' => 200,
            ]);

        $storeResponse->assertSessionHasNoErrors();

        $ot = \App\Models\Overtime::where('staff_id', $staff->id)->latest()->first();
        $this->assertNotNull($ot);
        $this->assertEquals(3.5, (float)$ot->overtime_hours);
        $this->assertEquals(700.00, (float)$ot->total_amount);
        $this->assertEquals('approved', $ot->approval_status);

        // 3. Test Shift and Holiday pages
        $shiftResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->get(route('staff.shifts.index'));
        $shiftResponse->assertStatus(200);

        $holidayResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->get(route('staff.holidays.index'));
        $holidayResponse->assertStatus(200);

        // 4. Test Staff Show Page (which renders staff.documents.upload form)
        $showResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->get(route('staff.show', $staff));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Upload Staff Document');

        // 5. Test Staff Document Upload
        \Illuminate\Support\Facades\Storage::fake('local');
        $file = \Illuminate\Http\UploadedFile::fake()->create('id_card.pdf', 50, 'application/pdf');

        $docUploadResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->post(route('staff.documents.upload', $staff), [
                'document_type' => 'ID Proof',
                'title' => 'National ID Card',
                'document_file' => $file,
            ]);

        $docUploadResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('staff_documents', [
            'staff_id' => $staff->id,
            'title' => 'National ID Card',
            'document_type' => 'ID Proof',
        ]);

        // 6. Test Leave Application Submission & Approval Workflow
        $leaveApplyResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->post(route('staff.leaves.store'), [
                'staff_id' => $staff->id,
                'leave_type' => 'Casual',
                'start_date' => now()->toDateString(),
                'end_date' => now()->addDays(2)->toDateString(),
                'reason' => 'Family vacation',
            ]);

        $leaveApplyResponse->assertSessionHasNoErrors();

        $leave = \App\Models\LeaveApplication::where('staff_id', $staff->id)->latest()->first();
        $this->assertNotNull($leave);
        $this->assertEquals('pending', $leave->status);

        // Approve the leave application
        $approveResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->post(route('staff.leaves.status', $leave), [
                'status' => 'approved',
                'approval_remarks' => 'Approved by manager',
            ]);

        $approveResponse->assertSessionHasNoErrors();

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($this->adminUser->id, $leave->approved_by);
        $this->assertEquals('Approved by manager', $leave->approval_remarks);

        // Verify leaves list page reflects the Approved status
        $leavesListResponse = $this->actingAs($this->adminUser)
            ->withSession([
                'active_company_id' => $this->company->id,
                'active_branch_id' => $this->company->branches()->first()?->id,
            ])
            ->get(route('staff.leaves.index'));

        $leavesListResponse->assertStatus(200);
        $leavesListResponse->assertSee('Approved');
    }
}

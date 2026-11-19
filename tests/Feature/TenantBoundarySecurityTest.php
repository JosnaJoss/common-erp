<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DailyOperation;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Ledger;
use App\Models\Overtime;
use App\Models\PurchaseEntry;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\SalesReturn;
use App\Models\Staff;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TenantBoundarySecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected Company $companyA;
    protected Company $companyB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::where('email', 'admin@erp.com')->first();
        $this->companyA = Company::where('code', 'APEX-IND')->first();
        $this->companyB = Company::where('code', 'MALABAR-FOODS')->first()
            ?? Company::where('code', 'AQUAFRESH-BEV')->first()
            ?? Company::where('code', 'ELEGANT-STITCH')->first()
            ?? Company::where('id', '!=', $this->companyA->id)->first();

        $this->assertNotNull($this->user, 'Admin user must exist.');
        $this->assertNotNull($this->companyA, 'Company A must exist.');
        $this->assertNotNull($this->companyB, 'Company B must exist.');
        $this->assertNotEquals($this->companyA->id, $this->companyB->id, 'Company A and B must be distinct tenants.');
    }

    /**
     * Helper to make authenticated requests scoped to Company A context.
     */
    protected function actingAsCompanyA()
    {
        return $this->actingAs($this->user)->withSession([
            'active_company_id' => $this->companyA->id,
            'active_branch_id' => $this->companyA->branches()->first()?->id,
        ]);
    }

    // =========================================================================
    // 1. CUSTOMER ISOLATION
    // =========================================================================

    public function test_company_a_user_can_view_own_customer_but_cannot_view_company_b_customer(): void
    {
        $customerA = Customer::where('company_id', $this->companyA->id)->first();
        $customerB = Customer::where('company_id', $this->companyB->id)->first();

        $this->assertNotNull($customerA, 'Company A must have at least one customer.');
        $this->assertNotNull($customerB, 'Company B must have at least one customer.');

        // Company A accessing its own customer -> 200 OK
        $responseA = $this->actingAsCompanyA()->get(route('customers.show', $customerA));
        $responseA->assertStatus(200);

        // Company A accessing Company B's customer -> 403 Forbidden
        $responseB = $this->actingAsCompanyA()->get(route('customers.show', $customerB));
        $responseB->assertStatus(403);
    }

    public function test_company_a_user_cannot_edit_or_update_company_b_customer(): void
    {
        $customerB = Customer::where('company_id', $this->companyB->id)->first();
        $this->assertNotNull($customerB);

        $originalName = $customerB->name;

        // Edit screen -> 403
        $editResponse = $this->actingAsCompanyA()->get(route('customers.edit', $customerB));
        $editResponse->assertStatus(403);

        // Update action -> 403
        $updateResponse = $this->actingAsCompanyA()->put(route('customers.update', $customerB), [
            'name' => 'MALICIOUS_OVERWRITE_NAME',
            'code' => $customerB->code,
            'price_level' => 'normal',
            'status' => 'active',
        ]);
        $updateResponse->assertStatus(403);

        // Verify Company B customer was not altered
        $customerB->refresh();
        $this->assertEquals($originalName, $customerB->name);
    }

    public function test_company_a_user_cannot_delete_company_b_customer(): void
    {
        $customerB = Customer::where('company_id', $this->companyB->id)->first();
        $this->assertNotNull($customerB);

        // Direct controller call asserting 403 abort
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Unauthorized access to cross-company customer.');

        session(['active_company_id' => $this->companyA->id]);
        $controller = app(\App\Http\Controllers\CustomerController::class);
        $controller->destroy($customerB);
    }

    // =========================================================================
    // 2. SUPPLIER ISOLATION
    // =========================================================================

    public function test_company_a_user_cannot_view_edit_or_update_company_b_supplier(): void
    {
        $supplierA = Supplier::where('company_id', $this->companyA->id)->first();
        $supplierB = Supplier::where('company_id', $this->companyB->id)->first();

        $this->assertNotNull($supplierA);
        $this->assertNotNull($supplierB);

        // Show own -> 200
        $this->actingAsCompanyA()->get(route('suppliers.show', $supplierA))->assertStatus(200);

        // Show Company B -> 403
        $this->actingAsCompanyA()->get(route('suppliers.show', $supplierB))->assertStatus(403);

        // Edit Company B -> 403
        $this->actingAsCompanyA()->get(route('suppliers.edit', $supplierB))->assertStatus(403);

        // Update Company B -> 403
        $originalName = $supplierB->name;
        $this->actingAsCompanyA()->put(route('suppliers.update', $supplierB), [
            'name' => 'MALICIOUS_SUPPLIER_NAME',
            'code' => $supplierB->code,
            'status' => 'active',
        ])->assertStatus(403);

        $supplierB->refresh();
        $this->assertEquals($originalName, $supplierB->name);
    }

    // =========================================================================
    // 3. STAFF ISOLATION
    // =========================================================================

    public function test_company_a_user_cannot_view_modify_or_delete_company_b_staff(): void
    {
        $staffA = Staff::where('company_id', $this->companyA->id)->first();
        $staffB = Staff::where('company_id', $this->companyB->id)->first();

        $this->assertNotNull($staffA);
        $this->assertNotNull($staffB);

        // Show own -> 200
        $this->actingAsCompanyA()->get(route('staff.show', $staffA))->assertStatus(200);

        // Show Company B -> 403
        $this->actingAsCompanyA()->get(route('staff.show', $staffB))->assertStatus(403);

        // Edit Company B -> 403
        $this->actingAsCompanyA()->get(route('staff.edit', $staffB))->assertStatus(403);

        // Update Company B -> 403
        $this->actingAsCompanyA()->put(route('staff.update', $staffB), [
            'first_name' => 'Malicious',
            'last_name' => 'Staff',
            'employee_id' => $staffB->employee_id,
            'gender' => 'Male',
            'marital_status' => 'Single',
            'designation' => 'Worker',
            'employee_type' => 'salaried',
            'salary_amount' => 50000,
            'basic_salary' => 40000,
            'date_of_joining' => now()->toDateString(),
            'status' => 'active',
        ])->assertStatus(403);

        // Delete Company B -> 403
        $this->actingAsCompanyA()->delete(route('staff.destroy', $staffB))->assertStatus(403);
        $this->assertDatabaseHas('staff', ['id' => $staffB->id, 'company_id' => $this->companyB->id]);
    }

    // =========================================================================
    // 4. ACCOUNTS & LEDGER ISOLATION
    // =========================================================================

    public function test_company_a_user_cannot_view_company_b_ledger_statement_or_journal(): void
    {
        $ledgerA = Ledger::where('company_id', $this->companyA->id)->first();
        $ledgerB = Ledger::where('company_id', $this->companyB->id)->first();

        $this->assertNotNull($ledgerA);
        $this->assertNotNull($ledgerB);

        // Ledger Statement own -> 200
        $this->actingAsCompanyA()->get(route('accounts.ledgers.statement', $ledgerA))->assertStatus(200);

        // Ledger Statement Company B -> 403
        $this->actingAsCompanyA()->get(route('accounts.ledgers.statement', $ledgerB))->assertStatus(403);

        // Journal Voucher Company B
        $journalB = JournalEntry::where('company_id', $this->companyB->id)->first();
        if ($journalB) {
            $this->actingAsCompanyA()->get(route('accounts.vouchers.show', $journalB))->assertStatus(403);
        }
    }

    // =========================================================================
    // 5. FORGED FOREIGN KEY VALIDATION ATTACKS
    // =========================================================================

    public function test_forged_foreign_key_sales_order_rejected_when_referencing_company_b_customer(): void
    {
        $customerB = Customer::where('company_id', $this->companyB->id)->first();
        $itemA = Item::where('company_id', $this->companyA->id)->first();

        $this->assertNotNull($customerB);
        $this->assertNotNull($itemA);

        $soCountBefore = SalesOrder::where('company_id', $this->companyA->id)->count();

        // Attempt to create Sales Order in Company A referencing Company B's customer
        $response = $this->actingAsCompanyA()->post(route('sales.orders.store'), [
            'customer_id' => $customerB->id, // FORGED CROSS-COMPANY ID
            'order_date' => now()->toDateString(),
            'items' => [
                [
                    'item_id' => $itemA->id,
                    'quantity' => 10,
                    'selling_price' => 100,
                ]
            ]
        ]);

        // Expect validation error
        $response->assertSessionHasErrors(['customer_id']);
        $this->assertEquals($soCountBefore, SalesOrder::where('company_id', $this->companyA->id)->count());
    }

    public function test_forged_foreign_key_sales_invoice_rejected_when_referencing_company_b_records(): void
    {
        $customerB = Customer::where('company_id', $this->companyB->id)->first();
        $godownA = $this->companyA->godowns()->first();
        $godownB = $this->companyB->godowns()->first();
        $itemA = Item::where('company_id', $this->companyA->id)->first();

        $this->assertNotNull($customerB);
        $this->assertNotNull($godownA);
        $this->assertNotNull($godownB);
        $this->assertNotNull($itemA);

        $invCountBefore = SalesInvoice::where('company_id', $this->companyA->id)->count();

        // 1. Cross-company customer ID
        $response1 = $this->actingAsCompanyA()->post(route('sales.invoices.store'), [
            'customer_id' => $customerB->id, // FORGED
            'godown_id' => $godownA->id,
            'invoice_date' => now()->toDateString(),
            'payment_mode' => 'Credit',
            'items' => [
                [
                    'item_id' => $itemA->id,
                    'quantity' => 5,
                    'rate' => 100,
                ]
            ]
        ]);
        $response1->assertSessionHasErrors(['customer_id']);

        // 2. Cross-company godown ID
        $customerA = Customer::where('company_id', $this->companyA->id)->first();
        $response2 = $this->actingAsCompanyA()->post(route('sales.invoices.store'), [
            'customer_id' => $customerA->id,
            'godown_id' => $godownB->id, // FORGED
            'invoice_date' => now()->toDateString(),
            'payment_mode' => 'Credit',
            'items' => [
                [
                    'item_id' => $itemA->id,
                    'quantity' => 5,
                    'rate' => 100,
                ]
            ]
        ]);
        $response2->assertSessionHasErrors(['godown_id']);

        $this->assertEquals($invCountBefore, SalesInvoice::where('company_id', $this->companyA->id)->count());
    }

    public function test_forged_foreign_key_purchase_entry_rejected_when_referencing_company_b_records(): void
    {
        $supplierB = Supplier::where('company_id', $this->companyB->id)->first();
        $godownA = $this->companyA->godowns()->first();
        $itemA = Item::where('company_id', $this->companyA->id)->first();

        $this->assertNotNull($supplierB);
        $this->assertNotNull($godownA);
        $this->assertNotNull($itemA);

        $peCountBefore = PurchaseEntry::where('company_id', $this->companyA->id)->count();

        // Forged supplier ID
        $response = $this->actingAsCompanyA()->post(route('purchase.entries.store'), [
            'supplier_id' => $supplierB->id, // FORGED
            'godown_id' => $godownA->id,
            'invoice_number' => 'FORGED-PUR-001',
            'invoice_date' => now()->toDateString(),
            'payment_type' => 'Credit',
            'items' => [
                [
                    'item_id' => $itemA->id,
                    'quantity' => 10,
                    'rate' => 50,
                ]
            ]
        ]);

        $response->assertSessionHasErrors(['supplier_id']);
        $this->assertEquals($peCountBefore, PurchaseEntry::where('company_id', $this->companyA->id)->count());
    }

    public function test_forged_cross_company_journal_voucher_rejected(): void
    {
        $ledgerA = Ledger::where('company_id', $this->companyA->id)->first();
        $ledgerB = Ledger::where('company_id', $this->companyB->id)->first();

        $this->assertNotNull($ledgerA);
        $this->assertNotNull($ledgerB);

        $jvCountBefore = JournalEntry::where('company_id', $this->companyA->id)->count();

        // Post voucher injecting Company B's ledger ID
        $response = $this->actingAsCompanyA()->post(route('accounts.vouchers.store'), [
            'voucher_type' => 'Journal',
            'voucher_date' => now()->toDateString(),
            'narration' => 'Cross tenant journal injection attack',
            'lines' => [
                ['ledger_id' => $ledgerA->id, 'debit' => 1000, 'credit' => 0],
                ['ledger_id' => $ledgerB->id, 'debit' => 0, 'credit' => 1000], // FORGED
            ]
        ]);

        $response->assertSessionHasErrors(['lines.1.ledger_id']);
        $this->assertEquals($jvCountBefore, JournalEntry::where('company_id', $this->companyA->id)->count());
    }

    public function test_forged_staff_creation_rejected_when_referencing_company_b_branch_or_department(): void
    {
        $branchB = Branch::where('company_id', $this->companyB->id)->first();
        $deptB = Department::where('company_id', $this->companyB->id)->first();

        $this->assertNotNull($branchB);
        $this->assertNotNull($deptB);

        $staffCountBefore = Staff::where('company_id', $this->companyA->id)->count();

        $response = $this->actingAsCompanyA()->post(route('staff.store'), [
            'branch_id' => $branchB->id, // FORGED
            'department_id' => $deptB->id, // FORGED
            'employee_id' => 'EMP-MAL-001',
            'first_name' => 'Forged',
            'last_name' => 'Employee',
            'gender' => 'Male',
            'marital_status' => 'Single',
            'designation' => 'Worker',
            'employee_type' => 'salaried',
            'salary_amount' => 30000,
            'basic_salary' => 25000,
            'date_of_joining' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['branch_id', 'department_id']);
        $this->assertEquals($staffCountBefore, Staff::where('company_id', $this->companyA->id)->count());
    }

    // =========================================================================
    // 6. INVENTORY & OPERATIONS ISOLATION
    // =========================================================================

    public function test_cross_company_stock_transfer_dispatch_and_receive_rejected(): void
    {
        $godownB1 = $this->companyB->godowns()->first();
        $godownB2 = $this->companyB->godowns()->skip(1)->first() ?? $godownB1;
        $itemB = Item::where('company_id', $this->companyB->id)->first();

        if ($godownB1 && $godownB2 && $itemB) {
            $transferB = StockTransfer::create([
                'company_id' => $this->companyB->id,
                'source_godown_id' => $godownB1->id,
                'dest_godown_id' => $godownB2->id,
                'transfer_no' => 'ST-TEST-' . rand(1000, 9999),
                'transfer_date' => now()->toDateString(),
                'status' => 'pending',
            ]);

            // Attempt to dispatch from Company A -> 403
            $this->actingAsCompanyA()->post(route('inventory.transfers.dispatch', $transferB))->assertStatus(403);

            // Attempt to receive from Company A -> 403
            $this->actingAsCompanyA()->post(route('inventory.transfers.receive', $transferB))->assertStatus(403);
        }
    }

    public function test_cross_company_daily_operation_show_and_actions_rejected(): void
    {
        $deptB = Department::where('company_id', $this->companyB->id)->first();
        if ($deptB) {
            $opB = DailyOperation::create([
                'company_id' => $this->companyB->id,
                'department_id' => $deptB->id,
                'operation_number' => 'DOP-TEST-' . rand(1000, 9999),
                'operation_date' => now()->toDateString(),
                'process_name' => 'Assembly',
                'work_status' => 'completed',
                'approval_status' => 'submitted',
            ]);

            // Show -> 403
            $this->actingAsCompanyA()->get(route('daily_operations.show', $opB))->assertStatus(403);

            // Submit -> 403
            $this->actingAsCompanyA()->post(route('daily_operations.submit', $opB))->assertStatus(403);

            // Approve -> 403
            $this->actingAsCompanyA()->post(route('daily_operations.approve', $opB))->assertStatus(403);

            // Reject -> 403
            $this->actingAsCompanyA()->post(route('daily_operations.reject', $opB))->assertStatus(403);
        }
    }

    public function test_cross_company_purchase_order_and_request_rejected(): void
    {
        $supplierB = Supplier::where('company_id', $this->companyB->id)->first();
        $deptB = Department::where('company_id', $this->companyB->id)->first();

        if ($supplierB) {
            $poB = PurchaseOrder::create([
                'company_id' => $this->companyB->id,
                'supplier_id' => $supplierB->id,
                'po_number' => 'PO-TEST-' . rand(1000, 9999),
                'po_date' => now()->toDateString(),
                'subtotal' => 1000,
                'tax_amount' => 180,
                'total_amount' => 1180,
                'status' => 'approved',
            ]);

            // Show Company B's PO -> 403
            $this->actingAsCompanyA()->get(route('purchase.orders.show', $poB))->assertStatus(403);
        }

        if ($deptB) {
            $prB = PurchaseRequest::create([
                'company_id' => $this->companyB->id,
                'department_id' => $deptB->id,
                'request_no' => 'PR-TEST-' . rand(1000, 9999),
                'request_date' => now()->toDateString(),
                'requested_by' => 'User B',
                'priority' => 'High',
                'status' => 'pending',
            ]);

            // Approve Company B's PR -> 403
            $this->actingAsCompanyA()->post(route('purchase.requests.approve', $prB))->assertStatus(403);
        }
    }

    public function test_cross_company_overtime_approval_rejected(): void
    {
        $staffB = Staff::where('company_id', $this->companyB->id)->first();
        if ($staffB) {
            $otB = Overtime::create([
                'company_id' => $this->companyB->id,
                'staff_id' => $staffB->id,
                'date' => now()->toDateString(),
                'overtime_hours' => 2,
                'overtime_rate' => 150,
                'total_amount' => 300,
                'approval_status' => 'pending',
            ]);

            // Approve Company B's overtime -> 403
            $this->actingAsCompanyA()->post(route('staff.overtime.approve', $otB))->assertStatus(403);
        }
    }
}


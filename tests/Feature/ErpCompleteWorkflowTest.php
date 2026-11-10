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

    public function test_category_subcategory_product_edit_functionality()
    {
        $companyId = $this->company->id;
        $branchId = $this->company->branches()->first()?->id;

        // --- 1. CATEGORY EDIT & UPDATE ---
        $category = \App\Models\Category::where('company_id', $companyId)->first();
        $this->assertNotNull($category);
        $categoryId = $category->id;

        // Create a subcategory linked to this category to verify relationship preservation
        $sub = \App\Models\Subcategory::create([
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'name' => 'Original Subcategory ' . rand(100, 999),
            'status' => 'active',
        ]);
        $subId = $sub->id;

        // View categories index (listing page)
        $catIndexRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branchId])
            ->get(route('master.categories.index'));
        $catIndexRes->assertStatus(200);
        $catIndexRes->assertSee('Edit Category');
        $catIndexRes->assertSee('editCategoryModal' . $categoryId);
        $catIndexRes->assertSee('editSubcategoryModal' . $subId);

        // View Category edit page
        $catEditRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branchId])
            ->get(route('master.categories.edit', $category));
        $catEditRes->assertStatus(200);
        $catEditRes->assertSee($category->name);

        // Update Category
        $updatedCatName = 'Updated Category ' . rand(1000, 9999);
        $catUpdateRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branchId])
            ->put(route('master.categories.update', $category), [
                'name' => $updatedCatName,
                'description' => 'Updated Description',
                'status' => 'active',
            ]);
        $catUpdateRes->assertSessionHasNoErrors();
        $catUpdateRes->assertRedirect(route('master.categories.index'));

        // Verify Category updated in-place (same ID)
        $category->refresh();
        $this->assertEquals($categoryId, $category->id);
        $this->assertEquals($updatedCatName, $category->name);
        $this->assertEquals('Updated Description', $category->description);

        // Verify child Subcategory relationship remains intact
        $sub->refresh();
        $this->assertEquals($categoryId, $sub->category_id);
        $this->assertEquals($category->name, $sub->category->name);

        // --- 2. SUBCATEGORY EDIT & UPDATE ---
        // View Subcategory edit page
        $subEditRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branchId])
            ->get(route('master.subcategories.edit', $sub));
        $subEditRes->assertStatus(200);
        $subEditRes->assertSee($sub->name);

        // Update Subcategory
        $updatedSubName = 'Updated Subcategory ' . rand(1000, 9999);
        $subUpdateRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branchId])
            ->put(route('master.subcategories.update', $sub), [
                'category_id' => $categoryId,
                'name' => $updatedSubName,
                'description' => 'Updated Sub description',
                'status' => 'active',
            ]);
        $subUpdateRes->assertSessionHasNoErrors();
        $subUpdateRes->assertRedirect(route('master.categories.index'));

        // Verify Subcategory updated in-place (same ID)
        $sub->refresh();
        $this->assertEquals($subId, $sub->id);
        $this->assertEquals($updatedSubName, $sub->name);
        $this->assertEquals($categoryId, $sub->category_id);

        // --- 3. PRODUCT EDIT & UPDATE ---
        $product = \App\Models\Product::where('company_id', $companyId)->first();
        if (!$product) {
            $product = \App\Models\Product::create([
                'company_id' => $companyId,
                'category_id' => $categoryId,
                'subcategory_id' => $subId,
                'name' => 'Test Motor Product',
                'code' => 'PRD-TST-001',
                'status' => 'active',
            ]);
        }
        $productId = $product->id;

        // View Products index (listing page)
        $prodIndexRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branchId])
            ->get(route('master.products.index'));
        $prodIndexRes->assertStatus(200);
        $prodIndexRes->assertSee('Actions');
        $prodIndexRes->assertSee('editProductModal' . $productId);

        // View Product edit page
        $prodEditRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branchId])
            ->get(route('master.products.edit', $product));
        $prodEditRes->assertStatus(200);
        $prodEditRes->assertSee($product->name);

        // Update Product
        $updatedProdName = 'Updated Product ' . rand(1000, 9999);
        $updatedProdCode = 'PRD-UPD-' . rand(100, 999);
        $prodUpdateRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branchId])
            ->put(route('master.products.update', $product), [
                'category_id' => $categoryId,
                'subcategory_id' => $subId,
                'name' => $updatedProdName,
                'code' => $updatedProdCode,
                'description' => 'Updated Product Specifications',
                'status' => 'active',
            ]);
        $prodUpdateRes->assertSessionHasNoErrors();
        $prodUpdateRes->assertRedirect(route('master.products.index'));

        // Verify Product updated in-place (same ID)
        $product->refresh();
        $this->assertEquals($productId, $product->id);
        $this->assertEquals($updatedProdName, $product->name);
        $this->assertEquals($updatedProdCode, $product->code);
        $this->assertEquals('Updated Product Specifications', $product->description);
        $this->assertEquals($categoryId, $product->category_id);
        $this->assertEquals($subId, $product->subcategory_id);
    }

    public function test_territory_and_route_management_complete_hierarchy_workflow()
    {
        $companyId = $this->company->id;
        $branch1 = $this->company->branches()->first();
        $this->assertNotNull($branch1);

        // Create or get second branch for cross-branch testing
        $branch2 = \App\Models\Branch::firstOrCreate(
            ['company_id' => $companyId, 'code' => 'BR-TST-02'],
            ['name' => 'Secondary Branch', 'status' => 'active']
        );

        // 1. Visit Territory & Route Management page
        $routesIndexRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->get(route('master.routes.index'));
        $routesIndexRes->assertStatus(200);
        $routesIndexRes->assertSee('Territory & Delivery Routes', false);
        $routesIndexRes->assertSee('City Management');
        $routesIndexRes->assertSee('Location Management');

        // 2. City Management: Create City under Branch 1
        $cityName = 'Kochi Test ' . rand(100, 999);
        $createCityRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->post(route('master.cities.store'), [
                'branch_id' => $branch1->id,
                'name' => $cityName,
                'status' => 'active',
            ]);
        $createCityRes->assertSessionHasNoErrors();
        $createCityRes->assertRedirect(route('master.routes.index', ['tab' => 'cities']));

        $city = \App\Models\City::where('company_id', $companyId)->where('name', $cityName)->first();
        $this->assertNotNull($city);
        $this->assertEquals($branch1->id, $city->branch_id);

        // Create another city under Branch 2
        $cityBranch2 = \App\Models\City::create([
            'company_id' => $companyId,
            'branch_id' => $branch2->id,
            'name' => 'Foreign City ' . rand(100, 999),
            'status' => 'active',
        ]);

        // Edit City
        $updatedCityName = $cityName . ' Updated';
        $updateCityRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->put(route('master.cities.update', $city), [
                'branch_id' => $branch1->id,
                'name' => $updatedCityName,
                'status' => 'active',
            ]);
        $updateCityRes->assertSessionHasNoErrors();
        $city->refresh();
        $this->assertEquals($updatedCityName, $city->name);

        // 3. Location Management: Create Location under City
        $locationName = 'Kakkanad Metro ' . rand(100, 999);
        $createLocRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->post(route('master.locations.store'), [
                'city_id' => $city->id,
                'name' => $locationName,
                'pincode' => '682030',
                'status' => 'active',
            ]);
        $createLocRes->assertSessionHasNoErrors();
        $createLocRes->assertRedirect(route('master.routes.index', ['tab' => 'locations']));

        $location = \App\Models\Location::where('city_id', $city->id)->where('name', $locationName)->first();
        $this->assertNotNull($location);
        $this->assertEquals('682030', $location->pincode);
        $this->assertEquals($branch1->id, $location->city->branch_id);

        // Create a foreign location under Branch 2's city
        $foreignLoc = \App\Models\Location::create([
            'company_id' => $companyId,
            'city_id' => $cityBranch2->id,
            'name' => 'Foreign Area ' . rand(100, 999),
            'pincode' => '999999',
            'status' => 'active',
        ]);

        // Edit Location
        $updatedLocName = $locationName . ' Zone';
        $updateLocRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->put(route('master.locations.update', $location), [
                'city_id' => $city->id,
                'name' => $updatedLocName,
                'pincode' => '682031',
                'status' => 'active',
            ]);
        $updateLocRes->assertSessionHasNoErrors();
        $location->refresh();
        $this->assertEquals($updatedLocName, $location->name);
        $this->assertEquals('682031', $location->pincode);

        // 4. Route Management: Enforce Branch Hierarchy
        // Test cross-branch assignment prevention: trying to assign Branch 2's city to Branch 1's route must fail
        $invalidRouteRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->post(route('master.routes.store'), [
                'branch_id' => $branch1->id,
                'route_name' => 'Invalid Cross Branch Route ' . rand(100, 999),
                'cities' => [$cityBranch2->id], // Belonging to branch 2!
                'status' => 'active',
            ]);
        $invalidRouteRes->assertSessionHasErrors(['cities']);

        // Test cross-branch location assignment prevention
        $invalidLocRouteRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->post(route('master.routes.store'), [
                'branch_id' => $branch1->id,
                'route_name' => 'Invalid Loc Cross Route ' . rand(100, 999),
                'cities' => [$city->id],
                'locations' => [$foreignLoc->id], // Belongs to branch 2!
                'status' => 'active',
            ]);
        $invalidLocRouteRes->assertSessionHasErrors(['locations']);

        // Valid Route Creation under Branch 1
        $routeName = 'Kochi Central Logistics Route ' . rand(100, 999);
        $vehicle = \App\Models\Vehicle::where('company_id', $companyId)->first();

        $validRouteRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->post(route('master.routes.store'), [
                'branch_id' => $branch1->id,
                'route_name' => $routeName,
                'vehicle_id' => $vehicle?->id,
                'assigned_driver' => 'Anand Menon',
                'cities' => [$city->id],
                'locations' => [$location->id],
                'status' => 'active',
            ]);
        $validRouteRes->assertSessionHasNoErrors();
        $validRouteRes->assertRedirect(route('master.routes.index', ['tab' => 'routes']));

        $route = \App\Models\Route::where('company_id', $companyId)->where('route_name', $routeName)->first();
        $this->assertNotNull($route);
        $this->assertEquals($branch1->id, $route->branch_id);
        $this->assertEquals('Anand Menon', $route->assigned_driver);
        $this->assertTrue($route->cities->contains($city->id));
        $this->assertTrue($route->locations->contains($location->id));

        // 5. Route Update
        $updatedRouteName = $routeName . ' Express';
        $updateRouteRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->put(route('master.routes.update', $route), [
                'branch_id' => $branch1->id,
                'route_name' => $updatedRouteName,
                'vehicle_id' => $vehicle?->id,
                'assigned_driver' => 'Vijay Nair',
                'cities' => [$city->id],
                'locations' => [$location->id],
                'status' => 'active',
            ]);
        $updateRouteRes->assertSessionHasNoErrors();
        $route->refresh();
        $this->assertEquals($updatedRouteName, $route->route_name);
        $this->assertEquals('Vijay Nair', $route->assigned_driver);

        // 6. Delete protections and operations
        // Attempting to delete city with existing location should be blocked with error
        $blockedCityDelete = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->delete(route('master.cities.destroy', $city));
        $blockedCityDelete->assertSessionHas('error');

        // Delete route
        $deleteRouteRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->delete(route('master.routes.destroy', $route));
        $deleteRouteRes->assertSessionHasNoErrors();
        $this->assertNull(\App\Models\Route::find($route->id));

        // Delete location
        $deleteLocRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->delete(route('master.locations.destroy', $location));
        $deleteLocRes->assertSessionHasNoErrors();
        $this->assertNull(\App\Models\Location::find($location->id));

        // Now delete city (location is gone)
        $deleteCityRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch1->id])
            ->delete(route('master.cities.destroy', $city));
        $deleteCityRes->assertSessionHasNoErrors();
        $this->assertNull(\App\Models\City::find($city->id));
    }

    public function test_bulk_add_cities_and_locations_workflow()
    {
        $companyId = $this->company->id;
        $branch = \App\Models\Branch::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Bulk Test Hub'],
            ['code' => 'BTH', 'address' => 'Test Hub, Marine Drive', 'city' => 'Kochi', 'state' => 'Kerala', 'pincode' => '682031', 'status' => 'active']
        );

        // 1. Bulk Add Cities under $branch
        $cityA = 'Bulk City Alpha ' . rand(100, 999);
        $cityB = 'Bulk City Beta ' . rand(100, 999);
        $cityC = 'Bulk City Gamma ' . rand(100, 999);

        $bulkCityRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch->id])
            ->post(route('master.cities.store'), [
                'branch_id' => $branch->id,
                'cities' => [
                    ['name' => $cityA, 'status' => 'active'],
                    ['name' => $cityB, 'status' => 'active'],
                    ['name' => $cityC, 'status' => 'inactive'],
                ],
            ]);

        $bulkCityRes->assertSessionHasNoErrors();
        $bulkCityRes->assertRedirect(route('master.routes.index', ['tab' => 'cities']));
        $bulkCityRes->assertSessionHas('success');

        $this->assertDatabaseHas('cities', [
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'name' => $cityA,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('cities', [
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'name' => $cityB,
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('cities', [
            'company_id' => $companyId,
            'branch_id' => $branch->id,
            'name' => $cityC,
            'status' => 'inactive',
        ]);

        $createdCityA = \App\Models\City::where('company_id', $companyId)->where('name', $cityA)->first();

        // 2. City Duplicate Validation within Batch
        $duplicateBatchRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch->id])
            ->post(route('master.cities.store'), [
                'branch_id' => $branch->id,
                'cities' => [
                    ['name' => 'SameCityName', 'status' => 'active'],
                    ['name' => 'SameCityName', 'status' => 'active'],
                ],
            ]);
        $duplicateBatchRes->assertSessionHasErrors(['cities']);

        // 3. City Duplicate Validation with Existing City in Company
        $duplicateExistingRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch->id])
            ->post(route('master.cities.store'), [
                'branch_id' => $branch->id,
                'cities' => [
                    ['name' => $cityA, 'status' => 'active'],
                ],
            ]);
        $duplicateExistingRes->assertSessionHasErrors(['cities']);

        // 4. Bulk Add Locations under $createdCityA
        $locA = 'Loc Alpha ' . rand(100, 999);
        $locB = 'Loc Beta ' . rand(100, 999);
        $locC = 'Loc Gamma ' . rand(100, 999);

        $bulkLocRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch->id])
            ->post(route('master.locations.store'), [
                'city_id' => $createdCityA->id,
                'locations' => [
                    ['name' => $locA, 'pincode' => '682001', 'status' => 'active'],
                    ['name' => $locB, 'pincode' => '682002', 'status' => 'active'],
                    ['name' => $locC, 'pincode' => null, 'status' => 'inactive'],
                ],
            ]);

        $bulkLocRes->assertSessionHasNoErrors();
        $bulkLocRes->assertRedirect(route('master.routes.index', ['tab' => 'locations']));
        $bulkLocRes->assertSessionHas('success');

        $this->assertDatabaseHas('locations', [
            'company_id' => $companyId,
            'city_id' => $createdCityA->id,
            'name' => $locA,
            'pincode' => '682001',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('locations', [
            'company_id' => $companyId,
            'city_id' => $createdCityA->id,
            'name' => $locB,
            'pincode' => '682002',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('locations', [
            'company_id' => $companyId,
            'city_id' => $createdCityA->id,
            'name' => $locC,
            'status' => 'inactive',
        ]);

        // 5. Location Duplicate Validation within Batch
        $duplicateLocBatchRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch->id])
            ->post(route('master.locations.store'), [
                'city_id' => $createdCityA->id,
                'locations' => [
                    ['name' => 'SameLocationName', 'pincode' => '111111', 'status' => 'active'],
                    ['name' => 'SameLocationName', 'pincode' => '222222', 'status' => 'active'],
                ],
            ]);
        $duplicateLocBatchRes->assertSessionHasErrors(['locations']);

        // 6. Location Duplicate Validation with Existing Location under that City
        $duplicateLocExistingRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch->id])
            ->post(route('master.locations.store'), [
                'city_id' => $createdCityA->id,
                'locations' => [
                    ['name' => $locA, 'pincode' => '682001', 'status' => 'active'],
                ],
            ]);
        $duplicateLocExistingRes->assertSessionHasErrors(['locations']);

        // 7. Verify existing edit functionality still works intact
        $updatedCityName = $cityA . ' Modified';
        $editCityRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch->id])
            ->put(route('master.cities.update', $createdCityA), [
                'branch_id' => $branch->id,
                'name' => $updatedCityName,
                'status' => 'active',
            ]);
        $editCityRes->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cities', ['id' => $createdCityA->id, 'name' => $updatedCityName]);

        $createdLocA = \App\Models\Location::where('city_id', $createdCityA->id)->where('name', $locA)->first();
        $updatedLocName = $locA . ' Modified';
        $editLocRes = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $companyId, 'active_branch_id' => $branch->id])
            ->put(route('master.locations.update', $createdLocA), [
                'city_id' => $createdCityA->id,
                'name' => $updatedLocName,
                'pincode' => '682099',
                'status' => 'active',
            ]);
        $editLocRes->assertSessionHasNoErrors();
        $this->assertDatabaseHas('locations', ['id' => $createdLocA->id, 'name' => $updatedLocName, 'pincode' => '682099']);
    }
}

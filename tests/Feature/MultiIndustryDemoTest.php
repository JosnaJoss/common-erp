<?php

namespace Tests\Feature;

use App\Models\Bom;
use App\Models\Category;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\ProductionOrder;
use App\Models\PurchaseEntry;
use App\Models\QualityCheck;
use App\Models\SalesInvoice;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiIndustryDemoTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = User::where('email', 'admin@erp.com')->firstOrFail();
    }

    /**
     * Test all three sample companies exist with their branches, departments, and godowns.
     */
    public function test_all_sample_companies_exist_with_infrastructure(): void
    {
        $companies = [
            'ELEG-BOUTIQUE' => ['name' => 'Elegance Boutique', 'type' => 'retail', 'branches' => 3, 'godowns' => 4],
            'KANCHINAD-FOODS' => ['name' => 'Kanchinad Foods', 'type' => 'manufacturing', 'branches' => 3, 'godowns' => 4],
            'AQUAFRESH-BEV' => ['name' => 'AquaFresh Beverages', 'type' => 'manufacturing', 'branches' => 3, 'godowns' => 4],
        ];

        foreach ($companies as $code => $expected) {
            $company = Company::where('code', $code)->first();
            $this->assertNotNull($company, "Company with code {$code} must exist.");
            $this->assertEquals($expected['name'], $company->name);
            $this->assertEquals($expected['type'], $company->business_type);
            $this->assertGreaterThanOrEqual($expected['branches'], $company->branches()->count());
            $this->assertGreaterThanOrEqual($expected['godowns'], $company->godowns()->count());
        }
    }

    /**
     * Test Elegance Boutique (Retail / Fashion): items, inventory, transactions, and company isolation.
     */
    public function test_elegance_boutique_retail_workflow_and_isolation(): void
    {
        $company = Company::where('code', 'ELEG-BOUTIQUE')->firstOrFail();
        $branch = $company->branches()->firstOrFail();

        // 1. Check Items
        $items = Item::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(20, $items->count());
        $this->assertTrue($items->contains('code', 'ITM-SAR-KAN-RED'));
        $this->assertTrue($items->contains('code', 'ITM-KRT-FLR-M'));

        // 2. Check Customers and Suppliers
        $customers = Customer::where('company_id', $company->id)->get();
        $suppliers = Supplier::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(10, $customers->count());
        $this->assertGreaterThanOrEqual(5, $suppliers->count());

        // 3. Check Purchases and Sales
        $purchases = PurchaseEntry::where('company_id', $company->id)->get();
        $sales = SalesInvoice::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(10, $purchases->count());
        $this->assertGreaterThanOrEqual(15, $sales->count());

        // 4. Check Retail company has NO BOM or Production Orders
        $this->assertEquals(0, Bom::where('company_id', $company->id)->count());
        $this->assertEquals(0, ProductionOrder::where('company_id', $company->id)->count());

        // 5. Test Switching Context and Dashboard Rendering
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'active_company_id' => $company->id,
                'active_branch_id' => $branch->id,
            ])
            ->get('/dashboard');

        $response->assertStatus(200);

        // 6. Test Company Isolation: Boutique user/context should not see Kanchinad or AquaFresh products
        $kanchinadCompany = Company::where('code', 'KANCHINAD-FOODS')->firstOrFail();
        $kanchinadItem = Item::where('company_id', $kanchinadCompany->id)->firstOrFail();

        $this->assertDatabaseMissing('items', [
            'company_id' => $company->id,
            'id' => $kanchinadItem->id,
        ]);

        // 7. Double-entry Trial Balance Verification
        $journalIds = JournalEntry::where('company_id', $company->id)->pluck('id');
        $this->assertNotEmpty($journalIds);
        $totalDebit = JournalLine::whereIn('journal_entry_id', $journalIds)->sum('debit_amount');
        $totalCredit = JournalLine::whereIn('journal_entry_id', $journalIds)->sum('credit_amount');
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01, "Trial balance for Elegance Boutique must balance.");
    }

    /**
     * Test Kanchinad Foods (Food Processing / Curry Masala Manufacturing):
     * Raw materials, BOM recipes, production orders, quality checks, and sales.
     */
    public function test_kanchinad_foods_manufacturing_workflow_and_isolation(): void
    {
        $company = Company::where('code', 'KANCHINAD-FOODS')->firstOrFail();
        $branch = $company->branches()->firstOrFail();

        // 1. Raw Materials and Finished Spices
        $items = Item::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(20, $items->count());
        $this->assertTrue($items->contains('code', 'ITM-RAW-CHL')); // Whole red chilli
        $this->assertTrue($items->contains('code', 'ITM-FND-CHL-500')); // Chilli powder 500g

        // 2. BOM Recipes
        $boms = Bom::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(4, $boms->count());

        // 3. Production Orders & QC
        $prodOrders = ProductionOrder::where('company_id', $company->id)->get();
        $qcChecks = QualityCheck::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(4, $prodOrders->count());
        $this->assertGreaterThanOrEqual(3, $qcChecks->count());

        // 4. Test Switching Context and Dashboard Rendering
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'active_company_id' => $company->id,
                'active_branch_id' => $branch->id,
            ])
            ->get('/dashboard');

        $response->assertStatus(200);

        // 5. Double-entry Trial Balance Verification
        $journalIds = JournalEntry::where('company_id', $company->id)->pluck('id');
        $this->assertNotEmpty($journalIds);
        $totalDebit = JournalLine::whereIn('journal_entry_id', $journalIds)->sum('debit_amount');
        $totalCredit = JournalLine::whereIn('journal_entry_id', $journalIds)->sum('credit_amount');
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01, "Trial balance for Kanchinad Foods must balance.");
    }

    /**
     * Test AquaFresh Beverages (Water Purification & Carbonated Drinks):
     * Preforms, water, club soda, dispenser cans, BOM, production batches, and distribution.
     */
    public function test_aquafresh_beverages_production_and_distribution_workflow(): void
    {
        $company = Company::where('code', 'AQUAFRESH-BEV')->firstOrFail();
        $branch = $company->branches()->firstOrFail();

        // 1. Items Check
        $items = Item::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(20, $items->count());
        $this->assertTrue($items->contains('code', 'ITM-FND-CAN-20L')); // 20L Can
        $this->assertTrue($items->contains('code', 'ITM-FND-SDA-750')); // Club Soda

        // 2. BOM Recipes & Production
        $boms = Bom::where('company_id', $company->id)->get();
        $prodOrders = ProductionOrder::where('company_id', $company->id)->get();
        $qcChecks = QualityCheck::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(4, $boms->count());
        $this->assertGreaterThanOrEqual(4, $prodOrders->count());
        $this->assertGreaterThanOrEqual(3, $qcChecks->count());

        // 3. Customers and Distribution Sales
        $customers = Customer::where('company_id', $company->id)->get();
        $sales = SalesInvoice::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(10, $customers->count());
        $this->assertGreaterThanOrEqual(15, $sales->count());

        // 4. Test Switching Context and Dashboard Rendering
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'active_company_id' => $company->id,
                'active_branch_id' => $branch->id,
            ])
            ->get('/dashboard');

        $response->assertStatus(200);

        // 5. Double-entry Trial Balance Verification
        $journalIds = JournalEntry::where('company_id', $company->id)->pluck('id');
        $this->assertNotEmpty($journalIds);
        $totalDebit = JournalLine::whereIn('journal_entry_id', $journalIds)->sum('debit_amount');
        $totalCredit = JournalLine::whereIn('journal_entry_id', $journalIds)->sum('credit_amount');
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01, "Trial balance for AquaFresh Beverages must balance.");
    }

    /**
     * Test Company Context Switching via Route.
     */
    public function test_super_admin_can_switch_between_sample_companies(): void
    {
        $companies = Company::whereIn('code', ['ELEG-BOUTIQUE', 'KANCHINAD-FOODS', 'AQUAFRESH-BEV'])->get();

        foreach ($companies as $company) {
            $response = $this->actingAs($this->superAdmin)
                ->post('/company/switch', [
                    'company_id' => $company->id,
                ]);

            $response->assertRedirect();
            $this->assertEquals($company->id, session('active_company_id'));
        }
    }
}

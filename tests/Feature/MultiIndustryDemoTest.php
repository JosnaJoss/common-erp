<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Product;
use App\Models\PurchaseEntry;
use App\Models\SalesInvoice;
use App\Models\Staff;
use App\Models\Supplier;
use App\Models\Unit;
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
     * Test all 12 fresh industry demo companies exist with full infrastructure.
     */
    public function test_all_12_industry_companies_exist_with_complete_infrastructure(): void
    {
        $expectedIndustries = [
            'MALABAR-FOODS'    => ['name' => 'Malabar Taste Foods Pvt. Ltd.', 'type' => 'manufacturing'],
            'AQUAFRESH-BEV'    => ['name' => 'AquaFresh Beverages Pvt. Ltd.', 'type' => 'manufacturing'],
            'ELEGANT-STITCH'   => ['name' => 'Elegant Stitch Boutique', 'type' => 'retail'],
            'ROYAL-WOOD'       => ['name' => 'Royal Wood Furniture Works', 'type' => 'manufacturing'],
            'BUILDRIGHT-CONST' => ['name' => 'BuildRight Constructions', 'type' => 'service'],
            'DRIVECARE-AUTO'   => ['name' => 'DriveCare Automobile Services', 'type' => 'service'],
            'GREENFIELD-AGRO'  => ['name' => 'GreenField Agro Farms', 'type' => 'distribution'],
            'CAREPLUS-MED'     => ['name' => 'CarePlus Medical Centre', 'type' => 'service'],
            'PRINTPRO-PACK'    => ['name' => 'PrintPro Packaging Solutions', 'type' => 'manufacturing'],
            'PRIMETRADE-DIST'  => ['name' => 'PrimeTrade Wholesale Distributors', 'type' => 'wholesale'],
            'GLOWCARE-PERS'    => ['name' => 'GlowCare Personal Products', 'type' => 'manufacturing'],
            'HERITAGE-GOLD'    => ['name' => 'Heritage Gold & Diamonds', 'type' => 'retail'],
        ];

        foreach ($expectedIndustries as $code => $expected) {
            $company = Company::where('code', $code)->first();
            $this->assertNotNull($company, "Company with code {$code} must exist.");
            $this->assertEquals($expected['name'], $company->name);
            $this->assertEquals($expected['type'], $company->business_type);
            $this->assertGreaterThanOrEqual(3, $company->branches()->count(), "{$code} should have at least 3 branches.");
            $this->assertGreaterThanOrEqual(5, $company->departments()->count(), "{$code} should have at least 5 departments.");
            $this->assertGreaterThanOrEqual(3, $company->godowns()->count(), "{$code} should have at least 3 godowns.");
            $this->assertGreaterThanOrEqual(5, Unit::where('company_id', $company->id)->count(), "{$code} should have units of measurement.");
            $this->assertGreaterThanOrEqual(6, Item::where('company_id', $company->id)->count(), "{$code} should have items.");
            $this->assertGreaterThanOrEqual(3, Supplier::where('company_id', $company->id)->count(), "{$code} should have suppliers.");
            $this->assertGreaterThanOrEqual(4, Customer::where('company_id', $company->id)->count(), "{$code} should have customers.");
            $this->assertGreaterThanOrEqual(5, Staff::where('company_id', $company->id)->count(), "{$code} should have staff.");
            $this->assertGreaterThanOrEqual(4, User::where('company_id', $company->id)->count(), "{$code} should have demo users.");
        }
    }

    /**
     * Test Food Manufacturing company (Malabar Foods) master data and clean transaction state.
     */
    public function test_malabar_foods_manufacturing_master_data_and_clean_state(): void
    {
        $company = Company::where('code', 'MALABAR-FOODS')->firstOrFail();
        $branch = $company->branches()->firstOrFail();

        // 1. Raw Materials & Finished Products
        $items = Item::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(6, $items->count());
        $this->assertTrue($items->contains('code', 'RM-PEP-01'));
        $this->assertTrue($items->contains('code', 'FG-GM-250'));

        // 2. Customers and Suppliers with Ledgers
        $customers = Customer::where('company_id', $company->id)->get();
        $suppliers = Supplier::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(4, $customers->count());
        $this->assertGreaterThanOrEqual(3, $suppliers->count());
        foreach ($customers as $c) {
            $this->assertNotNull($c->ledger_id, "Customer {$c->name} must have a dedicated debtor ledger.");
        }
        foreach ($suppliers as $s) {
            $this->assertNotNull($s->ledger_id, "Supplier {$s->name} must have a dedicated creditor ledger.");
        }

        // 3. Transactions must be strictly zero for clean manual testing
        $this->assertEquals(0, PurchaseEntry::where('company_id', $company->id)->count());
        $this->assertEquals(0, SalesInvoice::where('company_id', $company->id)->count());

        // 4. Test Switching Context and Dashboard Rendering
        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'active_company_id' => $company->id,
                'active_branch_id' => $branch->id,
            ])
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test Tailoring & Garments Boutique (Elegant Stitch) master data and clean transaction state.
     */
    public function test_elegant_stitch_boutique_master_data_and_clean_state(): void
    {
        $company = Company::where('code', 'ELEGANT-STITCH')->firstOrFail();
        $branch = $company->branches()->firstOrFail();

        $items = Item::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(6, $items->count());
        $this->assertTrue($items->contains('code', 'RM-SILK-01'));
        $this->assertTrue($items->contains('code', 'FG-SHER-01'));

        $this->assertEquals(0, PurchaseEntry::where('company_id', $company->id)->count());
        $this->assertEquals(0, SalesInvoice::where('company_id', $company->id)->count());

        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'active_company_id' => $company->id,
                'active_branch_id' => $branch->id,
            ])
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test AquaFresh Beverages master data and clean transaction state.
     */
    public function test_aquafresh_beverages_master_data_and_clean_state(): void
    {
        $company = Company::where('code', 'AQUAFRESH-BEV')->firstOrFail();
        $branch = $company->branches()->firstOrFail();

        $items = Item::where('company_id', $company->id)->get();
        $this->assertGreaterThanOrEqual(6, $items->count());
        $this->assertTrue($items->contains('code', 'RM-PET-20G'));
        $this->assertTrue($items->contains('code', 'FG-AQ-1L-CS'));

        $this->assertEquals(0, PurchaseEntry::where('company_id', $company->id)->count());
        $this->assertEquals(0, SalesInvoice::where('company_id', $company->id)->count());

        $response = $this->actingAs($this->superAdmin)
            ->withSession([
                'active_company_id' => $company->id,
                'active_branch_id' => $branch->id,
            ])
            ->get('/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Test Company Context Switching across fresh demo companies.
     */
    public function test_super_admin_can_switch_between_sample_companies(): void
    {
        $companies = Company::whereIn('code', ['MALABAR-FOODS', 'AQUAFRESH-BEV', 'ELEGANT-STITCH', 'HERITAGE-GOLD'])->get();

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

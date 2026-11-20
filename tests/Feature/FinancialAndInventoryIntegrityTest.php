<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DailyOperation;
use App\Models\DailyOperationEmployee;
use App\Models\DailyOperationMaterial;
use App\Models\DailyOperationOutput;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Ledger;
use App\Models\Staff;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\Unit;
use App\Models\User;
use App\Services\AccountingService;
use App\Services\DailyOperationService;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FinancialAndInventoryIntegrityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Company $companyA;
    protected Company $companyB;
    protected Branch $branchA;
    protected Godown $godownA;
    protected Item $rawItem;
    protected Item $finishedItem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyA = Company::where('code', 'APEX-IND')->first() ?? Company::first();

        $this->companyB = Company::where('id', '!=', $this->companyA->id)->first() ?? Company::create([
            'name' => 'Company B Security Test',
            'code' => 'COMP-B',
            'currency' => 'INR',
        ]);

        $this->adminUser = User::firstOrCreate(
            ['email' => 'admin@commonerp.test'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'company_id' => $this->companyA->id]
        );

        $this->branchA = Branch::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'HQ-01'],
            ['name' => 'Headquarters']
        );

        $this->godownA = Godown::firstOrCreate(
            ['company_id' => $this->companyA->id, 'branch_id' => $this->branchA->id, 'name' => 'Main Warehouse'],
            ['code' => 'MW-01', 'status' => 'active']
        );

        $unit = Unit::firstOrCreate(['company_id' => $this->companyA->id, 'symbol' => 'KG'], ['name' => 'Kilogram']);

        $this->rawItem = Item::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'RAW-CHILLI-01'],
            ['name' => 'Raw Red Chilli', 'unit_id' => $unit->id, 'normal_price' => 50.00, 'status' => 'active']
        );

        $this->finishedItem = Item::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'MASALA-POWDER-01'],
            ['name' => 'Chilli Masala Powder', 'unit_id' => $unit->id, 'normal_price' => 150.00, 'status' => 'active']
        );
    }

    /**
     * Test P4-01: Opening Stock records physical inventory and posts balanced journal entry
     */
    public function test_opening_stock_posts_inventory_and_balanced_equity_journal(): void
    {
        $this->withoutExceptionHandling();
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $payload = [
            'godown_id' => $this->godownA->id,
            'items' => [
                [
                    'item_id' => $this->rawItem->id,
                    'quantity' => 100,
                    'unit_cost' => 50.00,
                ],
                [
                    'item_id' => $this->finishedItem->id,
                    'quantity' => 50,
                    'unit_cost' => 100.00,
                ],
            ],
        ];

        $expectedTotalValue = (100 * 50.00) + (50 * 100.00); // 5000 + 5000 = 10,000

        $response = $this->post(route('inventory.opening_stock.store'), $payload);
        $response->assertRedirect(route('inventory.overview', ['godown_id' => $this->godownA->id]));

        // 1. Verify physical batch balances
        $rawBatch = StockBatch::where('godown_id', $this->godownA->id)->where('item_id', $this->rawItem->id)->first();
        $this->assertNotNull($rawBatch);
        $this->assertGreaterThanOrEqual(100, (float)$rawBatch->quantity);

        $finishedBatch = StockBatch::where('godown_id', $this->godownA->id)->where('item_id', $this->finishedItem->id)->first();
        $this->assertNotNull($finishedBatch);
        $this->assertGreaterThanOrEqual(50, (float)$finishedBatch->quantity);

        // 2. Verify Journal Entry
        $jv = JournalEntry::where('company_id', $this->companyA->id)
            ->where('reference_type', 'OpeningStock')
            ->latest()
            ->first();

        $this->assertNotNull($jv, 'Opening Stock Journal Entry must be created');
        $this->assertEquals($expectedTotalValue, (float)$jv->total_debit);
        $this->assertEquals($expectedTotalValue, (float)$jv->total_credit);

        // Verify lines: Debit Stock on Hand, Credit Opening Balance Equity
        $stockLine = $jv->lines()->whereHas('ledger', fn($q) => $q->where('name', 'Stock on Hand'))->first();
        $equityLine = $jv->lines()->whereHas('ledger', fn($q) => $q->where('name', 'Opening Balance Equity'))->first();

        $this->assertNotNull($stockLine);
        $this->assertNotNull($equityLine);
        $this->assertEquals($expectedTotalValue, (float)$stockLine->debit_amount);
        $this->assertEquals($expectedTotalValue, (float)$equityLine->credit_amount);
    }

    /**
     * Test P4-02 & P4-03: Stock Adjustment Shortage, Surplus and Damage accounting
     */
    public function test_stock_adjustments_shortage_surplus_and_damage_accounting(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $batch = StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->rawItem->id)
            ->first();
        if (!$batch) {
            $batch = StockBatch::create([
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
                'godown_id' => $this->godownA->id,
                'item_id' => $this->rawItem->id,
                'batch_no' => 'DEFAULT',
                'quantity' => 100,
                'cost_price' => 50.00,
            ]);
        } else {
            $batch->update(['quantity' => 100, 'cost_price' => 50.00]);
        }

        // Case 1: Shortage (100 -> 90, diff: -10)
        $response = $this->post(route('inventory.adjustments.store'), [
            'godown_id' => $this->godownA->id,
            'item_id' => $this->rawItem->id,
            'actual_stock' => 90,
            'reason' => 'Annual physical count shortage deficit',
        ]);
        $response->assertRedirect();

        $adjShortage = StockAdjustment::where('company_id', $this->companyA->id)->latest('id')->first();
        $this->assertNotNull($adjShortage);
        $this->assertNotNull($adjShortage->journal_entry_id);

        $jvShortage = $adjShortage->journalEntry;
        $this->assertNotNull($jvShortage);
        $this->assertEquals(500.00, (float)$jvShortage->total_debit); // 10 * 50 = 500
        $this->assertEquals(500.00, (float)$jvShortage->total_credit);

        $shortageLine = $jvShortage->lines()->whereHas('ledger', fn($q) => $q->where('name', 'Inventory Shortage & Loss'))->first();
        $this->assertNotNull($shortageLine);
        $this->assertEquals(500.00, (float)$shortageLine->debit_amount);

        // Case 2: Surplus (90 -> 105, diff: +15)
        $response = $this->post(route('inventory.adjustments.store'), [
            'godown_id' => $this->godownA->id,
            'item_id' => $this->rawItem->id,
            'actual_stock' => 105,
            'reason' => 'Surplus stock discovered in godown shelf',
        ]);
        $response->assertRedirect();

        $adjSurplus = StockAdjustment::where('company_id', $this->companyA->id)->latest('id')->first();
        $jvSurplus = $adjSurplus->journalEntry;
        $this->assertNotNull($jvSurplus);
        $this->assertEquals(750.00, (float)$jvSurplus->total_debit); // 15 * 50 = 750
        $this->assertEquals(750.00, (float)$jvSurplus->total_credit);

        $surplusLine = $jvSurplus->lines()->whereHas('ledger', fn($q) => $q->where('name', 'Inventory Surplus & Gain'))->first();
        $this->assertNotNull($surplusLine);
        $this->assertEquals(750.00, (float)$surplusLine->credit_amount);

        // Case 3: Damaged Stock (105 -> 95, diff: -10, reason: damaged)
        $response = $this->post(route('inventory.adjustments.store'), [
            'godown_id' => $this->godownA->id,
            'item_id' => $this->rawItem->id,
            'actual_stock' => 95,
            'reason' => 'Water damage write-off',
        ]);
        $response->assertRedirect();

        $adjDamage = StockAdjustment::where('company_id', $this->companyA->id)->latest('id')->first();
        $jvDamage = $adjDamage->journalEntry;
        $this->assertNotNull($jvDamage);

        $damageLine = $jvDamage->lines()->whereHas('ledger', fn($q) => $q->where('name', 'Damaged & Expired Stock Expense'))->first();
        $this->assertNotNull($damageLine);
        $this->assertEquals(500.00, (float)$damageLine->debit_amount);
    }

    /**
     * Test P4-05, P4-06, P4-07: Manufacturing Operation atomicity and cost capitalization
     */
    public function test_manufacturing_daily_operation_capitalizes_costs_into_finished_goods_inventory(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $dept = Department::firstOrCreate(
            ['company_id' => $this->companyA->id, 'code' => 'PROD-01'],
            ['name' => 'Production Department', 'status' => 'active']
        );

        $staff = Staff::firstOrCreate(
            ['company_id' => $this->companyA->id, 'email' => 'worker1@commonerp.test'],
            ['first_name' => 'Ramesh', 'last_name' => 'Worker', 'employee_id' => 'EMP-TEST-01', 'employee_type' => 'piece_rate', 'piece_rate' => 20.00, 'status' => 'active']
        );

        // Establish raw stock
        $rawBatch = StockBatch::firstOrCreate(
            ['godown_id' => $this->godownA->id, 'item_id' => $this->rawItem->id, 'batch_no' => 'MFG-RAW-01'],
            ['company_id' => $this->companyA->id, 'quantity' => 100, 'cost_price' => 50.00]
        );
        $rawBatch->update(['quantity' => 100, 'cost_price' => 50.00]);

        $dos = new DailyOperationService();

        // 1. Create Operation
        $operation = $dos->createOperation(
            [
                'company_id' => $this->companyA->id,
                'branch_id' => $this->branchA->id,
                'department_id' => $dept->id,
                'operation_date' => now()->toDateString(),
                'process_name' => 'Spices Grinding & Packing',
                'work_status' => 'completed',
                'approval_status' => 'draft',
            ],
            // Employees: 10 units @ 20.00 = 200.00 labor
            [
                [
                    'staff_id' => $staff->id,
                    'employment_type' => 'piece_rate',
                    'units_completed' => 10,
                    'rate_per_unit' => 20.00,
                ],
            ],
            // Materials: Consume 20 Kg @ 50.00 = 1000.00 material
            [
                [
                    'item_id' => $this->rawItem->id,
                    'source_godown_id' => $this->godownA->id,
                    'batch_no' => 'MFG-RAW-01',
                    'consumed_quantity' => 20,
                    'unit_cost' => 50.00,
                ],
            ],
            // Outputs: Produce 18 Kg Finished Masala @ 66.67 = 1200.00 finished goods
            [
                [
                    'item_id' => $this->finishedItem->id,
                    'dest_godown_id' => $this->godownA->id,
                    'produced_quantity' => 18,
                    'unit_cost' => 66.67,
                    'batch_number' => 'FG-BATCH-01',
                    'expiry_date' => now()->addMonths(12)->toDateString(),
                ],
            ],
            // Wastages: 0
            [],
            // Distributions: 0
            [],
            $this->adminUser->id
        );

        $this->assertEquals(200.00, (float)$operation->total_labor_cost);
        $this->assertEquals(1000.00, (float)$operation->total_material_cost);

        // 2. Post Operation Stock & Accounting
        $dos->postOperationStock($operation, $this->adminUser->id);

        $operation->refresh();
        $this->assertTrue($operation->stock_posted);
        $this->assertNotNull($operation->journal_entry_id);

        // 3. Verify Stock Updates
        $rawBatch->refresh();
        $this->assertEquals(80.00, (float)$rawBatch->quantity); // 100 - 20 = 80

        $fgBatch = StockBatch::where('godown_id', $this->godownA->id)
            ->where('item_id', $this->finishedItem->id)
            ->where('batch_no', 'FG-BATCH-01')
            ->first();
        $this->assertNotNull($fgBatch);
        $this->assertEquals(18.00, (float)$fgBatch->quantity);

        // 4. Verify Accounting Journal Entry
        $jv = $operation->journalEntry;
        $this->assertNotNull($jv);
        $this->assertEquals(1200.00, (float)$jv->total_debit); // 1000 material + 200 labor = 1200
        $this->assertEquals(1200.00, (float)$jv->total_credit);

        $fgLine = $jv->lines()->whereHas('ledger', fn($q) => $q->where('name', 'Finished Goods Inventory'))->first();
        $rawLine = $jv->lines()->whereHas('ledger', fn($q) => $q->where('name', 'Raw Materials Inventory'))->first();
        $laborLine = $jv->lines()->whereHas('ledger', fn($q) => $q->where('name', 'Manufacturing Labor Payable'))->first();

        $this->assertNotNull($fgLine);
        $this->assertNotNull($rawLine);
        $this->assertNotNull($laborLine);

        $this->assertEquals(1200.00, (float)$fgLine->debit_amount);
        $this->assertEquals(1000.00, (float)$rawLine->credit_amount);
        $this->assertEquals(200.00, (float)$laborLine->credit_amount);

        // 5. Test Safe Reversal
        $dos->reverseOperation($operation, $this->adminUser->id, 'Order cancelled by customer');

        $operation->refresh();
        $this->assertFalse($operation->stock_posted);
        $this->assertEquals('rejected', $operation->approval_status);

        // Raw stock restored
        $rawBatch->refresh();
        $this->assertEquals(100.00, (float)$rawBatch->quantity);

        // FG stock deducted
        $fgBatch->refresh();
        $this->assertEquals(0.00, (float)$fgBatch->quantity);

        // Reversal JV posted
        $reversalJv = JournalEntry::where('reference_type', 'DailyOperationReversal')
            ->where('reference_id', $operation->id)
            ->latest()
            ->first();
        $this->assertNotNull($reversalJv);
        $this->assertEquals(1200.00, (float)$reversalJv->total_debit);
    }

    /**
     * Test P4-11, P4-12, P4-13: Balance Sheet Reconciliation, Cash Flow & Inventory Valuation
     */
    public function test_balance_sheet_cash_flow_and_inventory_reconciliation_reports(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        // 1. Balance Sheet
        $bsResponse = $this->get(route('reports.financial.balance_sheet'));
        $bsResponse->assertOk();
        $bsResponse->assertViewHas('totalAssets');
        $bsResponse->assertViewHas('totalLiabilitiesAndEquity');
        $bsResponse->assertViewHas('currentPeriodProfit');

        $totalAssets = $bsResponse->viewData('totalAssets');
        $totalLiabilitiesAndEquity = $bsResponse->viewData('totalLiabilitiesAndEquity');
        $this->assertEqualsWithDelta($totalAssets, $totalLiabilitiesAndEquity, 0.05, 'Balance Sheet Assets must equal Liabilities + Equity');

        // 2. Cash Flow Statement
        $cfResponse = $this->get(route('reports.financial.cash_flow'));
        $cfResponse->assertOk();
        $cfResponse->assertViewHas('operatingCashFlow');
        $cfResponse->assertViewHas('investingCashFlow');
        $cfResponse->assertViewHas('financingCashFlow');
        $cfResponse->assertViewHas('closingCashBalance');

        // 3. Inventory Reconciliation Report
        $reconResponse = $this->get(route('reports.inventory.reconciliation'));
        $reconResponse->assertOk();
        $reconResponse->assertViewHas('totalPhysicalValuation');
        $reconResponse->assertViewHas('totalLedgerValuation');
        $reconResponse->assertViewHas('variance');
    }

    /**
     * Test P4-16: Multi-company security boundaries on opening stock and adjustments
     */
    public function test_multi_company_security_rejects_cross_company_godown_and_items(): void
    {
        $this->actingAs($this->adminUser);
        session(['active_company_id' => $this->companyA->id]);

        $godownB = Godown::firstOrCreate(
            ['company_id' => $this->companyB->id, 'name' => 'Company B Godown'],
            ['code' => 'GD-B01', 'status' => 'active']
        );

        $itemB = Item::firstOrCreate(
            ['company_id' => $this->companyB->id, 'code' => 'COMP-B-ITEM'],
            ['name' => 'Foreign Item', 'normal_price' => 100.00, 'status' => 'active']
        );

        // Forged opening stock on Company B godown
        $response = $this->post(route('inventory.opening_stock.store'), [
            'godown_id' => $godownB->id,
            'items' => [
                ['item_id' => $this->rawItem->id, 'quantity' => 10, 'unit_cost' => 50],
            ],
        ]);
        $response->assertSessionHasErrors(['godown_id']);

        // Forged stock adjustment on Company B godown
        $response = $this->post(route('inventory.adjustments.store'), [
            'godown_id' => $godownB->id,
            'item_id' => $this->rawItem->id,
            'actual_stock' => 50,
            'reason' => 'Hacking test',
        ]);
        $response->assertSessionHasErrors(['godown_id']);

        // Forged stock adjustment on Company B item
        $response = $this->post(route('inventory.adjustments.store'), [
            'godown_id' => $this->godownA->id,
            'item_id' => $itemB->id,
            'actual_stock' => 50,
            'reason' => 'Hacking test',
        ]);
        $response->assertSessionHasErrors(['item_id']);
    }
}

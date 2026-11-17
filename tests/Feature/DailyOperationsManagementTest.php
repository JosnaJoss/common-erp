<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\DailyOperation;
use App\Models\Department;
use App\Models\Godown;
use App\Models\IndustryProcessTemplate;
use App\Models\Item;
use App\Models\Staff;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Services\DailyOperationService;
use App\Services\InventoryService;
use Tests\TestCase;

class DailyOperationsManagementTest extends TestCase
{
    protected Company $company;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::where('code', 'CRAFT-FURNITURE')->first() 
            ?? Company::where('code', 'KANCHINAD-FOODS')->first() 
            ?? Company::first();

        $this->adminUser = User::where('email', 'admin@erp.com')->first();
    }

    public function test_industry_process_templates_seeded_for_all_target_industries(): void
    {
        $expectedIndustries = [
            'food_processing', 'beverages', 'garments', 'furniture',
            'construction', 'automobile', 'agriculture', 'healthcare',
            'printing', 'wholesale', 'cosmetics', 'jewellery'
        ];

        foreach ($expectedIndustries as $industry) {
            $count = IndustryProcessTemplate::where('industry_type', $industry)->count();
            $this->assertGreaterThan(0, $count, "Process templates should be present for industry: {$industry}");
        }
    }

    public function test_daily_operations_listing_and_filter_accessible(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current_company_id' => $this->company->id])
            ->get('/operations');

        $response->assertStatus(200);
        $response->assertSee('Daily Operations');
        $response->assertSee('New Daily Operation');
    }

    public function test_create_daily_operation_screen_loads_with_dependencies(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current_company_id' => $this->company->id])
            ->get('/operations/create');

        $response->assertStatus(200);
        $response->assertSee('Record Daily Operation');
        $response->assertSee('Process Template');
        $response->assertSee('Employee Participation');
        $response->assertSee('Material / Stock Consumption');
        $response->assertSee('Production Output');
        $response->assertSee('Wastage');
        $response->assertSee('Output Distribution');
    }

    public function test_daily_operation_lifecycle_and_atomic_stock_posting(): void
    {
        $company = Company::where('code', 'CRAFT-FURNITURE')->firstOrFail();
        $branch = $company->branches()->firstOrFail();
        $dept = Department::where('company_id', $company->id)->where('code', 'DEPT-CRF-CRP')->firstOrFail();
        $godownSource = Godown::where('company_id', $company->id)->where('code', 'WH-CRF-TYD')->firstOrFail();
        $godownDest = Godown::where('company_id', $company->id)->where('code', 'WH-CRF-FND')->firstOrFail();
        $godownVan = Godown::where('company_id', $company->id)->where('location_type', 'van_mobile')->firstOrFail();

        $rawItem = Item::where('company_id', $company->id)->where('code', 'ITM-RM-TEAK')->firstOrFail();
        $finishedItem = Item::where('company_id', $company->id)->where('code', 'ITM-FND-DT6')->firstOrFail();

        $craftsman = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-CRF-004')->firstOrFail();

        $dailyOpService = app(DailyOperationService::class);
        $inventoryService = app(InventoryService::class);

        // Ensure source stock
        $inventoryService->recordInward(
            $company->id,
            $branch->id,
            $godownSource->id,
            $rawItem->id,
            20.0,
            4500.00,
            'TEST-BAT-RAW',
            null,
            'TestInward',
            null,
            'TEST-REF-01',
            'Test raw material inward'
        );

        $initialSourceStock = StockBatch::where('godown_id', $godownSource->id)->where('item_id', $rawItem->id)->sum('quantity');

        // Create Daily Operation as Draft
        $op = $dailyOpService->createOperation([
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'operation_number' => 'TEST-DOP-' . time(),
            'operation_date' => date('Y-m-d'),
            'department_head_id' => $craftsman->supervisor_id,
            'supervisor_id' => $craftsman->supervisor_id,
            'work_order_ref' => 'TEST-WO-001',
            'process_name' => 'Joinery Assembly Test',
            'work_description' => 'Automated test joinery operation',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'work_status' => 'completed',
            'approval_status' => 'draft',
            'remarks' => 'Test remarks',
        ], [
            [
                'staff_id' => $craftsman->id,
                'employment_type' => 'piece_rate',
                'hours_worked' => 8.0,
                'units_completed' => 2,
                'piece_rate' => 1200.00,
            ],
        ], [
            [
                'item_id' => $rawItem->id,
                'source_godown_id' => $godownSource->id,
                'batch_no' => 'TEST-BAT-RAW',
                'consumed_quantity' => 10.0,
                'unit_cost' => 4500.00,
            ],
        ], [
            [
                'item_id' => $finishedItem->id,
                'dest_godown_id' => $godownDest->id,
                'batch_number' => 'BAT-FND-TEST-' . time(),
                'produced_quantity' => 2.0,
                'unit_cost' => 42000.00,
                'quality_status' => 'passed',
            ],
        ], [
            [
                'wastage_name' => 'Edge Trimmings Test',
                'wastage_type' => 'scrap_trimming',
                'quantity' => 0.5,
                'unit' => 'CFT',
                'estimated_cost' => 250.00,
                'recoverable_quantity' => 0.2,
                'reason' => 'Test scrap trimmings',
            ],
        ], [
            [
                'item_id' => $finishedItem->id,
                'destination_godown_id' => $godownVan->id,
                'destination_type' => 'delivery_van',
                'batch_no' => 'BAT-FND-TEST-' . time(),
                'quantity' => 1.0,
            ],
        ]);

        $this->assertInstanceOf(DailyOperation::class, $op);
        $this->assertEquals('draft', $op->approval_status);
        $this->assertFalse($op->stock_posted);
        $this->assertEquals(2400.00, $op->total_labor_cost); // 2 units * ₹1200

        // Stock should NOT be deducted yet in draft mode
        $currentSourceStock = StockBatch::where('godown_id', $godownSource->id)->where('item_id', $rawItem->id)->sum('quantity');
        $this->assertEquals($initialSourceStock, $currentSourceStock);

        // Submit Operation
        $dailyOpService->submitOperation($op, $this->adminUser->id);
        $op->refresh();
        $this->assertEquals('submitted', $op->approval_status);

        // Approve Operation
        $dailyOpService->approveOperation($op, 'department_head_approved', $this->adminUser->id, 'Approved by test suite');
        $op->refresh();
        $this->assertEquals('department_head_approved', $op->approval_status);
        $this->assertTrue($op->stock_posted);

        // Verify stock deducted from source
        $afterSourceStock = StockBatch::where('godown_id', $godownSource->id)->where('item_id', $rawItem->id)->sum('quantity');
        $this->assertEquals($initialSourceStock - 10.0, $afterSourceStock);

        // Verify finished goods added
        $destStock = StockBatch::where('godown_id', $godownDest->id)->where('item_id', $finishedItem->id)->sum('quantity');
        $this->assertGreaterThanOrEqual(1.0, $destStock);

        // Verify automatic distribution transfer generated
        $transfer = StockTransfer::where('company_id', $company->id)
            ->where('remarks', 'like', "%{$op->operation_number}%")
            ->first();

        $this->assertNotNull($transfer, 'Distribution transfer should be created automatically');
        $this->assertEquals($godownVan->id, $transfer->dest_godown_id);

        // Verify StockMovements exist and audit trail links back to operation
        $movements = StockMovement::where('company_id', $company->id)
            ->where('reference_no', $op->operation_number)
            ->get();

        $this->assertGreaterThanOrEqual(2, $movements->count());
    }

    public function test_two_step_stock_transfer_dispatch_and_receive(): void
    {
        $company = Company::where('code', 'KANCHINAD-FOODS')->firstOrFail();
        $sourceGodown = Godown::where('company_id', $company->id)->where('code', 'WH-KF-FND')->firstOrFail();
        $vanGodown = Godown::where('company_id', $company->id)->where('location_type', 'van_mobile')->firstOrFail();
        $item = Item::where('company_id', $company->id)->where('code', 'ITM-FND-CHK-100')->firstOrFail();

        $inventoryService = app(InventoryService::class);

        // Ensure source stock
        $inventoryService->recordInward(
            $company->id,
            $sourceGodown->branch_id,
            $sourceGodown->id,
            $item->id,
            20.0,
            50.00,
            'DEFAULT',
            null,
            'TestTransferInward',
            null,
            'TRF-INW-01',
            'Inward for transfer test'
        );

        // 1. Create a pending transfer
        $transfer = StockTransfer::create([
            'company_id' => $company->id,
            'transfer_no' => 'TRF-TEST-' . time(),
            'transfer_date' => date('Y-m-d'),
            'source_branch_id' => $sourceGodown->branch_id,
            'source_godown_id' => $sourceGodown->id,
            'dest_branch_id' => $vanGodown->branch_id,
            'dest_godown_id' => $vanGodown->id,
            'status' => 'pending',
            'remarks' => 'Testing two-step van replenishment transfer',
        ]);

        StockTransferItem::create([
            'stock_transfer_id' => $transfer->id,
            'item_id' => $item->id,
            'quantity' => 10.0,
        ]);

        $this->assertEquals('pending', $transfer->status);
        $this->assertNull($transfer->dispatched_at);
        $this->assertNull($transfer->received_at);

        // 2. Dispatch transfer
        $inventoryService->dispatchTransfer($transfer, $this->adminUser->id);
        $transfer->refresh();
        $this->assertEquals('in_transit', $transfer->status);
        $this->assertNotNull($transfer->dispatched_at);
        $this->assertEquals($this->adminUser->id, $transfer->dispatched_by);

        // 3. Receive transfer at mobile van
        $inventoryService->receiveTransfer($transfer, $this->adminUser->id);
        $transfer->refresh();
        $this->assertEquals('completed', $transfer->status);
        $this->assertNotNull($transfer->received_at);
        $this->assertEquals($this->adminUser->id, $transfer->received_by);
    }

    public function test_daily_operations_report_analytics_and_csv_export(): void
    {
        // View Report Screen
        $response = $this->actingAs($this->adminUser)
            ->withSession(['current_company_id' => $this->company->id])
            ->get('/reports/daily-operations');

        $response->assertStatus(200);
        $response->assertSee('Daily Operations Intelligence Report');
        $response->assertSee('Total Activities');
        $response->assertSee('Labor Hours');
        $response->assertSee('Raw Mat. Consumed');
        $response->assertSee('Output Produced');
        $response->assertSee('Wastage');
        $response->assertSee('Export to CSV');

        // CSV Export endpoint
        $csvResponse = $this->actingAs($this->adminUser)
            ->withSession(['current_company_id' => $this->company->id])
            ->get('/reports/daily-operations?export=csv');

        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_all_four_companies_have_active_operations_and_mobile_vans(): void
    {
        $companies = ['ELEG-BOUTIQUE', 'KANCHINAD-FOODS', 'AQUAFRESH-BEV', 'CRAFT-FURNITURE'];

        foreach ($companies as $code) {
            $comp = Company::where('code', $code)->firstOrFail();

            // Mobile Van Godown exists
            $vanGodowns = Godown::where('company_id', $comp->id)->where('location_type', 'van_mobile')->count();
            $this->assertGreaterThan(0, $vanGodowns, "Company {$code} should possess at least one mobile van godown.");

            // Daily Operations exist
            $opsCount = DailyOperation::where('company_id', $comp->id)->count();
            $this->assertGreaterThan(0, $opsCount, "Company {$code} should possess seeded daily operations.");
        }
    }
}

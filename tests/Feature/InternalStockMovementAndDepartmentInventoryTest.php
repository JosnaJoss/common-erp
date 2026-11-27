<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Item;
use App\Models\StockBatch;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\StockDiscrepancy;
use App\Models\StockAdjustment;
use App\Models\JournalEntry;
use App\Models\Unit;
use App\Services\InventoryService;
use App\Services\DailyOperationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Carbon\Carbon;

class InternalStockMovementAndDepartmentInventoryTest extends TestCase
{
    use DatabaseTransactions;

    protected User $adminUser;
    protected Company $company;
    protected Branch $branch;
    protected Godown $mainWarehouse;
    protected Godown $productionStore;
    protected Department $productionDept;
    protected Department $packingDept;
    protected Item $testItem;
    protected StockBatch $testBatch;
    protected InventoryService $inventoryService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::where('email', 'admin@erp.com')->first() ?? User::first();
        $this->company = Company::find(1) ?? Company::first();
        $this->branch = Branch::where('company_id', $this->company->id)->first() ?? Branch::first();

        // Ensure main warehouse godown
        $this->mainWarehouse = Godown::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'TEST-MAIN-WH'],
            [
                'branch_id' => $this->branch->id,
                'name' => 'Main Test Central Warehouse',
                'location_type' => 'warehouse',
                'status' => 'active',
            ]
        );

        // Ensure production store godown
        $this->productionStore = Godown::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'TEST-PROD-STORE'],
            [
                'branch_id' => $this->branch->id,
                'name' => 'Production Department Store',
                'location_type' => 'production_unit',
                'status' => 'active',
            ]
        );

        // Configure branch
        $this->branch->update([
            'stock_mode' => 'multiple_locations',
            'central_godown_id' => $this->mainWarehouse->id,
        ]);

        // Ensure production department
        $this->productionDept = Department::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'TEST-PROD-DEPT'],
            [
                'branch_id' => $this->branch->id,
                'name' => 'Manufacturing Production Division',
                'department_type' => 'production',
                'maintains_stock' => true,
                'default_godown_id' => $this->productionStore->id,
                'source_godown_id' => $this->mainWarehouse->id,
                'status' => 'active',
            ]
        );

        // Ensure packing department
        $this->packingDept = Department::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'TEST-PACK-DEPT'],
            [
                'branch_id' => $this->branch->id,
                'name' => 'Packing Division',
                'department_type' => 'packing',
                'maintains_stock' => false,
                'default_godown_id' => null,
                'source_godown_id' => $this->mainWarehouse->id,
                'status' => 'active',
            ]
        );

        // Ensure item
        $unit = Unit::firstOrCreate(
            ['company_id' => $this->company->id, 'name' => 'Kilogram'],
            ['symbol' => 'KG', 'unit_type' => 'weight', 'conversion_factor' => 1.0, 'status' => 'active']
        );

        $this->testItem = Item::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'TEST-RAW-CHILLI'],
            [
                'name' => 'Raw Red Chilli Powder Grade A',
                'unit_id' => $unit->id,
                'status' => 'active',
                'cost_price' => 150.00,
            ]
        );

        // Seed stock batch in main warehouse (500 KG available)
        $this->testBatch = StockBatch::updateOrCreate(
            [
                'godown_id' => $this->mainWarehouse->id,
                'item_id' => $this->testItem->id,
                'batch_no' => 'BAT-CHILLI-001',
            ],
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'quantity' => 500.000,
                'cost_price' => 150.00,
                'expiry_date' => Carbon::now()->addYear()->toDateString(),
                'status' => 'active',
            ]
        );

        $this->inventoryService = new InventoryService();
    }

    public function test_branch_stock_configuration_and_department_setup(): void
    {
        // 1. Test Branch Stock Mode Update via Controller
        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('inventory.branches.stock_config', $this->branch->id), [
                'stock_mode' => 'multiple_locations',
                'central_godown_id' => $this->mainWarehouse->id,
            ]);

        $response->assertSessionHas('success');
        $this->branch->refresh();
        $this->assertEquals('multiple_locations', $this->branch->stock_mode);
        $this->assertEquals($this->mainWarehouse->id, $this->branch->central_godown_id);

        // 2. Test Department Stock Configuration Update
        $deptResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('inventory.departments.stock_config', $this->productionDept->id), [
                'maintains_stock' => 1,
                'default_godown_id' => $this->productionStore->id,
                'source_godown_id' => $this->mainWarehouse->id,
            ]);

        $deptResponse->assertSessionHas('success');
        $this->productionDept->refresh();
        $this->assertTrue((bool)$this->productionDept->maintains_stock);
        $this->assertEquals($this->productionStore->id, $this->productionDept->default_godown_id);
    }

    public function test_cannot_switch_department_with_active_stock_to_non_stock_holding(): void
    {
        // Put stock in production store
        StockBatch::updateOrCreate(
            [
                'godown_id' => $this->productionStore->id,
                'item_id' => $this->testItem->id,
                'batch_no' => 'PROD-STORE-BAT-01',
            ],
            [
                'company_id' => $this->company->id,
                'quantity' => 45.000,
                'cost_price' => 150.00,
                'status' => 'active',
            ]
        );

        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('inventory.departments.stock_config', $this->productionDept->id), [
                'maintains_stock' => 0,
                'default_godown_id' => null,
                'source_godown_id' => $this->mainWarehouse->id,
            ]);

        $response->assertSessionHasErrors('error');
        $this->productionDept->refresh();
        $this->assertTrue((bool)$this->productionDept->maintains_stock, 'Must protect against silent stock balance detachment');
    }

    public function test_internal_stock_request_creation_and_approval_workflow(): void
    {
        // 1. Create Stock Request
        $storeResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('inventory.requests.store'), [
                'from_department_id' => $this->productionDept->id,
                'source_godown_id' => $this->mainWarehouse->id,
                'from_godown_id' => $this->productionStore->id,
                'request_date' => Carbon::now()->toDateString(),
                'required_date' => Carbon::now()->addDays(2)->toDateString(),
                'priority' => 'high',
                'remarks' => 'Urgent requisition for production line 1',
                'items' => [
                    [
                        'item_id' => $this->testItem->id,
                        'quantity' => 100.000,
                        'remarks' => 'Need batch BAT-CHILLI-001',
                    ],
                ],
            ]);

        $storeResponse->assertRedirect();

        $stockRequest = StockRequest::where('from_department_id', $this->productionDept->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($stockRequest);
        $this->assertEquals('requested', $stockRequest->status);
        $this->assertEquals('high', $stockRequest->priority);
        $this->assertEquals(100.000, (float)$stockRequest->items->first()->requested_qty);
        $this->assertEquals(0.000, (float)$stockRequest->items->first()->approved_qty);

        // 2. Partial Approval by Warehouse Manager
        $reqItem = $stockRequest->items->first();
        $approveResponse = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('inventory.requests.approve', $stockRequest->id), [
                'items' => [
                    $reqItem->id => ['approved_qty' => 80.000],
                ],
                'remarks' => 'Partial approval of 80 KG due to quota limit',
            ]);

        $approveResponse->assertSessionHas('success');
        $stockRequest->refresh();
        $this->assertEquals('partially_approved', $stockRequest->status);
        $this->assertEquals(80.000, (float)$stockRequest->items->first()->approved_qty);
    }

    public function test_stock_request_rejection_requires_mandatory_reason(): void
    {
        $request = StockRequest::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'request_no' => 'REQ-TEST-REJECT-01',
            'request_date' => now()->toDateString(),
            'from_department_id' => $this->productionDept->id,
            'source_godown_id' => $this->mainWarehouse->id,
            'status' => 'requested',
            'created_by' => $this->adminUser->id,
        ]);

        $reqItem = StockRequestItem::create([
            'stock_request_id' => $request->id,
            'item_id' => $this->testItem->id,
            'requested_qty' => 50.000,
        ]);

        // Rejection without reason fails validation
        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('inventory.requests.reject', $request->id), [
                'rejection_reason' => '',
            ]);

        $response->assertSessionHasErrors('rejection_reason');
        $request->refresh();
        $this->assertEquals('requested', $request->status);

        // Rejection with reason succeeds
        $validReject = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->post(route('inventory.requests.reject', $request->id), [
                'rejection_reason' => 'Item currently quarantined for quality test',
            ]);

        $validReject->assertSessionHas('success');
        $request->refresh();
        $this->assertEquals('rejected', $request->status);
        $this->assertStringContainsString('quarantined', $request->rejection_reason);
    }

    public function test_batch_wise_dispatch_and_in_transit_stock_reduction(): void
    {
        // 1. Create and approve request
        $request = $this->inventoryService->createStockRequest(
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'from_department_id' => $this->productionDept->id,
                'source_godown_id' => $this->mainWarehouse->id,
                'from_godown_id' => $this->productionStore->id,
                'priority' => 'medium',
            ],
            [
                ['item_id' => $this->testItem->id, 'requested_qty' => 120.000],
            ],
            $this->adminUser->id
        );

        $this->inventoryService->approveStockRequest(
            $request,
            [['id' => $request->items->first()->id, 'approved_qty' => 100.000]],
            $this->adminUser->id
        );

        $initialWarehouseStock = (float)$this->testBatch->quantity; // 500

        // 2. First Dispatch of 60 KG
        $transfer1 = $this->inventoryService->dispatchStockRequest(
            $request,
            [
                [
                    'stock_request_item_id' => $request->items->first()->id,
                    'batch_no' => 'BAT-CHILLI-001',
                    'dispatch_quantity' => 60.000,
                ],
            ],
            $this->adminUser->id,
            'WAYBILL-001'
        );

        $this->assertNotNull($transfer1);
        $this->assertEquals('in_transit', $transfer1->status);

        // Check source stock reduced
        $this->testBatch->refresh();
        $this->assertEquals($initialWarehouseStock - 60.000, (float)$this->testBatch->quantity);

        // Check transfer item tracks in-transit
        $trfItem1 = $transfer1->items->first();
        $this->assertEquals(60.000, (float)$trfItem1->dispatched_qty);
        $this->assertEquals(60.000, (float)$trfItem1->in_transit_qty);
        $this->assertEquals(0.000, (float)$trfItem1->received_qty);

        // 3. Second Dispatch of 40 KG (completing approved 100 KG)
        $transfer2 = $this->inventoryService->dispatchStockRequest(
            $request,
            [
                [
                    'stock_request_item_id' => $request->items->first()->id,
                    'batch_no' => 'BAT-CHILLI-001',
                    'dispatch_quantity' => 40.000,
                ],
            ],
            $this->adminUser->id,
            'WAYBILL-002'
        );

        $this->testBatch->refresh();
        $this->assertEquals($initialWarehouseStock - 100.000, (float)$this->testBatch->quantity);

        // Check request tracked full dispatch
        $request->items->first()->refresh();
        $this->assertEquals(100.000, (float)$request->items->first()->dispatched_qty);
    }

    public function test_cannot_dispatch_beyond_available_batch_quantity(): void
    {
        $request = $this->inventoryService->createStockRequest(
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'from_department_id' => $this->productionDept->id,
                'source_godown_id' => $this->mainWarehouse->id,
                'from_godown_id' => $this->productionStore->id,
            ],
            [
                ['item_id' => $this->testItem->id, 'requested_qty' => 600.000],
            ],
            $this->adminUser->id
        );

        $this->inventoryService->approveStockRequest(
            $request,
            [['id' => $request->items->first()->id, 'approved_qty' => 600.000]],
            $this->adminUser->id
        );

        // Available is only 500, attempting to dispatch 550 must throw exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->inventoryService->dispatchStockRequest(
            $request,
            [
                [
                    'stock_request_item_id' => $request->items->first()->id,
                    'batch_no' => 'BAT-CHILLI-001',
                    'dispatch_quantity' => 550.000,
                ],
            ],
            $this->adminUser->id
        );
    }

    public function test_full_receipt_moves_in_transit_stock_to_destination_location(): void
    {
        // 1. Dispatch 50 KG
        $request = $this->inventoryService->createStockRequest(
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'from_department_id' => $this->productionDept->id,
                'source_godown_id' => $this->mainWarehouse->id,
                'from_godown_id' => $this->productionStore->id,
            ],
            [['item_id' => $this->testItem->id, 'requested_qty' => 50.000]],
            $this->adminUser->id
        );

        $this->inventoryService->approveStockRequest(
            $request,
            [['id' => $request->items->first()->id, 'approved_qty' => 50.000]],
            $this->adminUser->id
        );

        $transfer = $this->inventoryService->dispatchStockRequest(
            $request,
            [['stock_request_item_id' => $request->items->first()->id, 'batch_no' => 'BAT-CHILLI-001', 'dispatch_quantity' => 50.000]],
            $this->adminUser->id
        );

        // Initial destination stock should be 0
        $destBatchBefore = StockBatch::where('godown_id', $this->productionStore->id)
            ->where('item_id', $this->testItem->id)
            ->where('batch_no', 'BAT-CHILLI-001')
            ->first();
        $this->assertNull($destBatchBefore);

        // 2. Production Manager Confirms Full Receipt (50 KG)
        // Create dedicated production manager user to test separation of duties
        $prodManager = User::firstOrCreate(
            ['email' => 'prod.manager@test.com'],
            [
                'name' => 'Production Manager Test',
                'password' => bcrypt('password'),
                'company_id' => $this->company->id,
                'role_id' => $this->adminUser->role_id,
                'is_active' => true,
            ]
        );

        $this->inventoryService->receiveTransferWithDiscrepancies(
            $transfer,
            [
                [
                    'transfer_item_id' => $transfer->items->first()->id,
                    'received_qty' => 50.000,
                    'damaged_qty' => 0.000,
                ],
            ],
            $prodManager->id,
            'Received in good condition'
        );

        $transfer->refresh();
        $this->assertEquals('completed', $transfer->status);
        $this->assertEquals($prodManager->id, $transfer->received_by);

        // Verify stock added to destination godown with batch preserved
        $destBatchAfter = StockBatch::where('godown_id', $this->productionStore->id)
            ->where('item_id', $this->testItem->id)
            ->where('batch_no', 'BAT-CHILLI-001')
            ->first();

        $this->assertNotNull($destBatchAfter);
        $this->assertEquals(50.000, (float)$destBatchAfter->quantity);
        $this->assertEquals(150.00, (float)$destBatchAfter->cost_price);
    }

    public function test_partial_receipt_with_shortage_and_damaged_goods_reporting(): void
    {
        // Dispatch 100 KG
        $request = $this->inventoryService->createStockRequest(
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'from_department_id' => $this->productionDept->id,
                'source_godown_id' => $this->mainWarehouse->id,
                'from_godown_id' => $this->productionStore->id,
            ],
            [['item_id' => $this->testItem->id, 'requested_qty' => 100.000]],
            $this->adminUser->id
        );

        $this->inventoryService->approveStockRequest(
            $request,
            [['id' => $request->items->first()->id, 'approved_qty' => 100.000]],
            $this->adminUser->id
        );

        $transfer = $this->inventoryService->dispatchStockRequest(
            $request,
            [['stock_request_item_id' => $request->items->first()->id, 'batch_no' => 'BAT-CHILLI-001', 'dispatch_quantity' => 100.000]],
            $this->adminUser->id
        );

        $receiver = User::firstOrCreate(
            ['email' => 'receiver.test@test.com'],
            [
                'name' => 'Receiving Staff',
                'password' => bcrypt('password'),
                'company_id' => $this->company->id,
                'role_id' => $this->adminUser->role_id,
                'is_active' => true,
            ]
        );

        // Receiver counts: Accepted = 92 KG, Damaged = 5 KG, Shortage = 3 KG
        $this->inventoryService->receiveTransferWithDiscrepancies(
            $transfer,
            [
                [
                    'transfer_item_id' => $transfer->items->first()->id,
                    'received_qty' => 92.000,
                    'damaged_qty' => 5.000,
                    'discrepancy_type' => 'damaged_goods',
                    'discrepancy_reason' => 'Bags torn during transport with water damage',
                ],
            ],
            $receiver->id,
            'Partial delivery verification'
        );

        $transfer->refresh();
        $this->assertEquals('partially_received', $transfer->status, 'Transfer cannot be completed while unresolved variance remains');

        // Verify destination received only 92 KG
        $destBatch = StockBatch::where('godown_id', $this->productionStore->id)
            ->where('item_id', $this->testItem->id)
            ->where('batch_no', 'BAT-CHILLI-001')
            ->first();
        $this->assertEquals(92.000, (float)$destBatch->quantity);

        // Verify discrepancies logged
        $discrepancies = StockDiscrepancy::where('stock_transfer_id', $transfer->id)->get();
        $this->assertGreaterThan(0, $discrepancies->count());

        $disc = $discrepancies->first();
        $this->assertEquals('reported', $disc->status);
        $this->assertEquals(5.000, (float)$disc->discrepancy_qty);
        $this->assertEquals('damaged_goods', $disc->discrepancy_type);
    }

    public function test_discrepancy_investigation_and_write_off_approval_with_accounting_posting(): void
    {
        // 1. Setup a reported discrepancy
        $transfer = StockTransfer::create([
            'company_id' => $this->company->id,
            'transfer_no' => 'TRF-TEST-DISC-01',
            'transfer_date' => now()->toDateString(),
            'source_godown_id' => $this->mainWarehouse->id,
            'dest_godown_id' => $this->productionStore->id,
            'status' => 'partially_received',
            'created_by' => $this->adminUser->id,
        ]);

        $reporter = User::firstOrCreate(
            ['email' => 'reporter.staff@test.com'],
            [
                'name' => 'Warehouse Staff Reporter',
                'password' => bcrypt('password'),
                'company_id' => $this->company->id,
                'role_id' => $this->adminUser->role_id,
                'is_active' => true,
            ]
        );

        $discrepancy = StockDiscrepancy::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'stock_transfer_id' => $transfer->id,
            'item_id' => $this->testItem->id,
            'batch_id' => $this->testBatch->id,
            'source_godown_id' => $this->mainWarehouse->id,
            'dest_godown_id' => $this->productionStore->id,
            'dispatched_qty' => 50.000,
            'received_qty' => 45.000,
            'discrepancy_qty' => 5.000,
            'discrepancy_type' => 'damaged_goods',
            'reason' => 'Transit rain leakage soaked 5 KG',
            'status' => 'reported',
            'created_by' => $reporter->id,
        ]);

        // 2. Warehouse Manager Investigates
        $this->inventoryService->investigateDiscrepancy(
            $discrepancy,
            'Inspection confirmed 5 KG bag wet and molded. Cannot be salvaged.',
            $this->adminUser->id
        );

        $discrepancy->refresh();
        $this->assertEquals('under_investigation', $discrepancy->status);

        // 3. Submit for Write-Off Approval
        $this->inventoryService->submitDiscrepancyWriteOff(
            $discrepancy,
            'Damaged beyond salvage, request write-off',
            $reporter->id
        );

        $discrepancy->refresh();
        $this->assertEquals('pending_approval', $discrepancy->status);

        // 4. Separation of Duties: Reporter cannot approve their own write-off
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Separation of duties violation');
        $this->inventoryService->approveDiscrepancyWriteOff($discrepancy, $reporter->id);
    }

    public function test_authorized_manager_approves_write_off_and_creates_journal_entry(): void
    {
        $transfer = StockTransfer::create([
            'company_id' => $this->company->id,
            'transfer_no' => 'TRF-TEST-WO-02',
            'transfer_date' => now()->toDateString(),
            'source_godown_id' => $this->mainWarehouse->id,
            'dest_godown_id' => $this->productionStore->id,
            'status' => 'partially_received',
            'created_by' => $this->adminUser->id,
        ]);

        $reporter = User::firstOrCreate(
            ['email' => 'reporter2@test.com'],
            ['name' => 'Reporter Two', 'password' => bcrypt('pass'), 'company_id' => $this->company->id, 'role_id' => $this->adminUser->role_id, 'is_active' => true]
        );

        $discrepancy = StockDiscrepancy::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'stock_transfer_id' => $transfer->id,
            'item_id' => $this->testItem->id,
            'batch_id' => $this->testBatch->id,
            'source_godown_id' => $this->mainWarehouse->id,
            'dest_godown_id' => $this->productionStore->id,
            'dispatched_qty' => 50.000,
            'received_qty' => 45.000,
            'discrepancy_qty' => 5.000,
            'discrepancy_type' => 'damaged_goods',
            'reason' => 'Transit rain leakage',
            'status' => 'pending_approval',
            'created_by' => $reporter->id,
        ]);

        // Branch Manager (different user) approves write-off
        $branchManager = User::firstOrCreate(
            ['email' => 'branch.mgr@test.com'],
            ['name' => 'Branch Manager', 'password' => bcrypt('pass'), 'company_id' => $this->company->id, 'role_id' => $this->adminUser->role_id, 'is_active' => true]
        );

        $this->inventoryService->approveDiscrepancyWriteOff(
            $discrepancy,
            $branchManager->id,
            'Approved after inspection of photographs'
        );

        $discrepancy->refresh();
        $this->assertEquals('resolved', $discrepancy->status);
        $this->assertEquals('loss_write_off', $discrepancy->resolution_type);
        $this->assertNotNull($discrepancy->stock_adjustment_id);

        // Verify Stock Adjustment & Double-Entry Journal Entry
        $adj = StockAdjustment::find($discrepancy->stock_adjustment_id);
        $this->assertNotNull($adj);
        $this->assertEquals('approved', $adj->status);

        $jv = JournalEntry::find($adj->journal_entry_id);
        $this->assertNotNull($jv);
        $totalDebit = (float)$jv->lines()->sum('debit_amount');
        $totalCredit = (float)$jv->lines()->sum('credit_amount');
        $this->assertEquals($totalDebit, $totalCredit);
        $this->assertEquals(750.00, $totalDebit, '5 KG * 150 = 750 loss written off');

        // Check parent transfer completed since discrepancy resolved
        $transfer->refresh();
        $this->assertEquals('completed', $transfer->status);
    }

    public function test_discrepancy_resolution_via_replacement_dispatch(): void
    {
        $transfer = StockTransfer::create([
            'company_id' => $this->company->id,
            'transfer_no' => 'TRF-TEST-REP-03',
            'transfer_date' => now()->toDateString(),
            'source_godown_id' => $this->mainWarehouse->id,
            'dest_godown_id' => $this->productionStore->id,
            'status' => 'partially_received',
            'created_by' => $this->adminUser->id,
        ]);

        $discrepancy = StockDiscrepancy::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'stock_transfer_id' => $transfer->id,
            'item_id' => $this->testItem->id,
            'batch_id' => $this->testBatch->id,
            'source_godown_id' => $this->mainWarehouse->id,
            'dest_godown_id' => $this->productionStore->id,
            'dispatched_qty' => 50.000,
            'received_qty' => 46.000,
            'discrepancy_qty' => 4.000,
            'discrepancy_type' => 'shortage',
            'reason' => '4 KG missing in box',
            'status' => 'under_investigation',
            'created_by' => $this->adminUser->id,
        ]);

        $initialWarehouseStock = (float)$this->testBatch->quantity;

        // Warehouse manager dispatches 4 KG replacement
        $repTransfer = $this->inventoryService->resolveDiscrepancyAdditionalDispatch(
            $discrepancy,
            ['quantity' => 4.000, 'batch_no' => 'BAT-CHILLI-001'],
            $this->adminUser->id
        );

        $this->assertNotNull($repTransfer);
        $this->assertEquals('in_transit', $repTransfer->status);

        $this->testBatch->refresh();
        $this->assertEquals($initialWarehouseStock - 4.000, (float)$this->testBatch->quantity);

        $discrepancy->refresh();
        $this->assertEquals('pending_additional_dispatch', $discrepancy->status);
    }

    public function test_discrepancy_resolution_via_cancelling_outstanding_requirement(): void
    {
        $transfer = StockTransfer::create([
            'company_id' => $this->company->id,
            'transfer_no' => 'TRF-TEST-CAN-04',
            'transfer_date' => now()->toDateString(),
            'source_godown_id' => $this->mainWarehouse->id,
            'dest_godown_id' => $this->productionStore->id,
            'status' => 'partially_received',
            'created_by' => $this->adminUser->id,
        ]);

        $discrepancy = StockDiscrepancy::create([
            'company_id' => $this->company->id,
            'branch_id' => $this->branch->id,
            'stock_transfer_id' => $transfer->id,
            'item_id' => $this->testItem->id,
            'source_godown_id' => $this->mainWarehouse->id,
            'dest_godown_id' => $this->productionStore->id,
            'dispatched_qty' => 30.000,
            'received_qty' => 28.000,
            'discrepancy_qty' => 2.000,
            'discrepancy_type' => 'shortage',
            'reason' => 'Small shortage upon arrival',
            'status' => 'under_investigation',
            'created_by' => $this->adminUser->id,
        ]);

        $this->inventoryService->cancelOutstandingDiscrepancy(
            $discrepancy,
            $this->adminUser->id,
            'Production batch already finalized with 28 KG, remaining 2 KG cancelled'
        );

        $discrepancy->refresh();
        $this->assertEquals('resolved', $discrepancy->status);
        $this->assertEquals('cancel_outstanding', $discrepancy->resolution_type);

        $transfer->refresh();
        $this->assertEquals('completed', $transfer->status);
    }

    public function test_daily_operation_production_consumption_in_centralized_vs_multi_location(): void
    {
        // 1. Centralized Mode: Packing department consumes directly from main warehouse
        $dailyOpService = new DailyOperationService();

        $operation = $dailyOpService->createOperation(
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'department_id' => $this->packingDept->id,
                'process_name' => 'Retail Packing Line',
                'operation_date' => now()->toDateString(),
                'approval_status' => 'admin_approved',
            ],
            [],
            [
                [
                    'item_id' => $this->testItem->id,
                    'consumed_quantity' => 20.000,
                    'batch_no' => 'BAT-CHILLI-001',
                    'unit_cost' => 150.00,
                ],
            ],
            [],
            [],
            [],
            [],
            $this->adminUser->id
        );

        $this->assertNotNull($operation);
        $this->assertTrue((bool)$operation->stock_posted);

        // Check material was consumed directly from main warehouse
        $mat = $operation->materials->first();
        $this->assertEquals($this->mainWarehouse->id, $mat->source_godown_id);

        // 2. Multi-Location Mode: Production department consumes from its own production store
        // First put stock in production store
        StockBatch::updateOrCreate(
            [
                'godown_id' => $this->productionStore->id,
                'item_id' => $this->testItem->id,
                'batch_no' => 'PROD-STORE-BAT-02',
            ],
            [
                'company_id' => $this->company->id,
                'quantity' => 100.000,
                'cost_price' => 150.00,
                'status' => 'active',
            ]
        );

        $prodOp = $dailyOpService->createOperation(
            [
                'company_id' => $this->company->id,
                'branch_id' => $this->branch->id,
                'department_id' => $this->productionDept->id,
                'process_name' => 'Spice Blending Operation',
                'operation_date' => now()->toDateString(),
                'approval_status' => 'admin_approved',
            ],
            [],
            [
                [
                    'item_id' => $this->testItem->id,
                    'consumed_quantity' => 30.000,
                    'batch_no' => 'PROD-STORE-BAT-02',
                    'unit_cost' => 150.00,
                ],
            ],
            [],
            [],
            [],
            [],
            $this->adminUser->id
        );

        $this->assertNotNull($prodOp);
        $this->assertTrue((bool)$prodOp->stock_posted);

        // Check material was consumed from production store
        $prodMat = $prodOp->materials->first();
        $this->assertEquals($this->productionStore->id, $prodMat->source_godown_id);

        // Verify remaining stock in production store: 100 - 30 = 70
        $sb = StockBatch::where('godown_id', $this->productionStore->id)
            ->where('item_id', $this->testItem->id)
            ->where('batch_no', 'PROD-STORE-BAT-02')
            ->first();
        $this->assertEquals(70.000, (float)$sb->quantity);
    }

    public function test_cross_company_isolation_on_stock_requests_and_transfers(): void
    {
        $otherCompany = Company::where('id', '!=', $this->company->id)->first();
        if (!$otherCompany) {
            $this->markTestSkipped('Second company required for cross-company isolation test.');
        }

        // Create request in Company B
        $foreignDept = Department::where('company_id', $otherCompany->id)->first() ?? Department::create([
            'company_id' => $otherCompany->id,
            'name' => 'Foreign Dept',
            'code' => 'FOR-DEPT',
            'department_type' => 'production',
        ]);

        $foreignGodown = Godown::where('company_id', $otherCompany->id)->first() ?? Godown::create([
            'company_id' => $otherCompany->id,
            'name' => 'Foreign Warehouse',
            'code' => 'FOR-WH',
            'status' => 'active',
        ]);

        $foreignRequest = StockRequest::create([
            'company_id' => $otherCompany->id,
            'request_no' => 'REQ-FOREIGN-001',
            'request_date' => now()->toDateString(),
            'from_department_id' => $foreignDept->id,
            'source_godown_id' => $foreignGodown->id,
            'status' => 'requested',
            'created_by' => $this->adminUser->id,
        ]);

        // Attempting to access Company B request while active company is Company A must abort with 403
        $response = $this->actingAs($this->adminUser)
            ->withSession(['active_company_id' => $this->company->id])
            ->get(route('inventory.requests.show', $foreignRequest->id));

        $response->assertStatus(403);
    }

    public function test_inventory_movement_and_discrepancy_reports_are_accessible(): void
    {
        $reports = [
            'inventory.reports.requests',
            'inventory.reports.transfers',
            'inventory.reports.pending_receipts',
            'inventory.reports.discrepancies',
            'inventory.reports.in_transit',
            'inventory.reports.location_stock',
            'inventory.reports.material_consumption',
        ];

        foreach ($reports as $routeName) {
            $response = $this->actingAs($this->adminUser)
                ->withSession(['active_company_id' => $this->company->id])
                ->get(route($routeName));

            $response->assertStatus(200);
        }
    }
}

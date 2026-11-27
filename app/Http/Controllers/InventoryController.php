<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DailyOperationMaterial;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBatch;
use App\Models\StockDiscrepancy;
use App\Models\StockDiscrepancyApproval;
use App\Models\StockMovement;
use App\Models\StockRequest;
use App\Models\StockRequestItem;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Unit;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function overview(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $godownId = $request->get('godown_id');

        $query = StockBatch::where('company_id', $companyId)->with(['item.unit', 'godown']);

        if ($godownId) {
            $query->where('godown_id', $godownId);
        }

        $batches = $query->paginate(20);
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();

        $totalValuation = StockBatch::where('company_id', $companyId)
            ->selectRaw('SUM(quantity * cost_price) as total_val')
            ->value('total_val') ?? 0;

        return view('inventory.overview', compact('batches', 'godowns', 'totalValuation', 'godownId'));
    }

    public function movements(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = StockMovement::where('company_id', $companyId)->with(['item.unit', 'godown', 'batch']);

        if ($request->filled('item_id')) {
            $query->where('item_id', $request->get('item_id'));
        }
        if ($request->filled('godown_id')) {
            $query->where('godown_id', $request->get('godown_id'));
        }
        if ($request->filled('type')) {
            $query->where('movement_type', $request->get('type'));
        }

        $movements = $query->latest('movement_date')->latest('id')->paginate(25);
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();

        return view('inventory.movements', compact('movements', 'godowns', 'items'));
    }

    // Direct Inward
    public function inwardCreate()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();
        return view('inventory.inward', compact('godowns', 'items'));
    }

    public function inwardStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'quantity' => 'required|numeric|min:0.001',
            'unit_cost' => 'required|numeric|min:0',
            'batch_no' => 'nullable|string|max:50',
            'expiry_date' => 'nullable|date',
            'reference_type' => 'required|string',
            'reference_no' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $inventoryService = new InventoryService();
        $movement = $inventoryService->recordInward(
            $companyId, null, $data['godown_id'], $data['item_id'],
            $data['quantity'], $data['unit_cost'], $data['batch_no'] ?? 'DEFAULT',
            $data['expiry_date'] ?? null, $data['reference_type'], null,
            $data['reference_no'] ?? null, $data['remarks'] ?? null
        );

        AuditLog::log('inventory', 'inward', "Recorded inward of {$data['quantity']} units for Item #{$data['item_id']}");
        return redirect()->route('inventory.overview')->with('success', 'Stock inward recorded successfully.');
    }

    // Direct Outward
    public function outwardCreate()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();
        return view('inventory.outward', compact('godowns', 'items'));
    }

    public function outwardStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'quantity' => 'required|numeric|min:0.001',
            'batch_no' => 'nullable|string|max:50',
            'reference_type' => 'required|string',
            'reference_no' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        try {
            $inventoryService = new InventoryService();
            $inventoryService->recordOutward(
                $companyId, null, $data['godown_id'], $data['item_id'],
                $data['quantity'], $data['batch_no'] ?? 'DEFAULT',
                $data['reference_type'], null, $data['reference_no'] ?? null,
                $data['remarks'] ?? null
            );

            AuditLog::log('inventory', 'outward', "Recorded outward of {$data['quantity']} units for Item #{$data['item_id']}");
            return redirect()->route('inventory.overview')->with('success', 'Stock outward recorded successfully.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }
    }

    // Transfers
    public function transfers()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $transfers = StockTransfer::where('company_id', $companyId)
            ->with(['sourceGodown', 'destGodown', 'items.item'])
            ->latest()
            ->paginate(15);
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();

        return view('inventory.transfers', compact('transfers', 'godowns', 'items'));
    }

    public function transferStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'source_godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'dest_godown_id' => [
                'required',
                'different:source_godown_id',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'quantity' => 'required|numeric|min:0.001',
            'batch_no' => 'nullable|string|max:50',
            'transfer_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        $batchNo = $data['batch_no'] ?? 'DEFAULT';
        $transferNo = 'TRF-' . date('Ymd') . '-' . rand(1000, 9999);

        try {
            $transferMode = $request->input('mode', 'completed'); // 'completed' or 'pending'

            if ($transferMode === 'pending') {
                $transfer = StockTransfer::create([
                    'company_id' => $companyId,
                    'transfer_no' => $transferNo,
                    'transfer_date' => $data['transfer_date'],
                    'source_godown_id' => $data['source_godown_id'],
                    'dest_godown_id' => $data['dest_godown_id'],
                    'status' => 'pending',
                    'remarks' => $data['remarks'] ?? null,
                    'created_by' => auth()->id(),
                ]);

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'item_id' => $data['item_id'],
                    'quantity' => $data['quantity'],
                ]);

                AuditLog::log('inventory', 'transfer_create', "Initiated pending transfer {$transferNo}");
                return back()->with('success', "Stock transfer {$transferNo} initiated (Pending Dispatch).");
            }

            $inventoryService = new InventoryService();
            $inventoryService->transferStock(
                $companyId, $data['source_godown_id'], $data['dest_godown_id'],
                $data['item_id'], $data['quantity'], $batchNo, $transferNo
            );

            $transfer = StockTransfer::create([
                'company_id' => $companyId,
                'transfer_no' => $transferNo,
                'transfer_date' => $data['transfer_date'],
                'source_godown_id' => $data['source_godown_id'],
                'dest_godown_id' => $data['dest_godown_id'],
                'status' => 'completed',
                'dispatched_by' => auth()->id(),
                'dispatched_at' => now(),
                'received_by' => auth()->id(),
                'received_at' => now(),
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            StockTransferItem::create([
                'stock_transfer_id' => $transfer->id,
                'item_id' => $data['item_id'],
                'quantity' => $data['quantity'],
            ]);

            AuditLog::log('inventory', 'transfer', "Transferred {$data['quantity']} units from Godown #{$data['source_godown_id']} to Godown #{$data['dest_godown_id']}");
            return back()->with('success', "Stock transfer {$transferNo} completed successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['quantity' => $e->getMessage()]);
        }
    }

    public function transferDispatch(StockTransfer $transfer)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($transfer->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company stock transfer.');
        }

        try {
            $inventoryService = new InventoryService();
            $inventoryService->dispatchTransfer($transfer, auth()->id() ?? 1);
            AuditLog::log('inventory', 'transfer_dispatch', "Dispatched stock transfer {$transfer->transfer_no}");
            return back()->with('success', "Stock transfer {$transfer->transfer_no} marked In-Transit (Stock deducted from source).");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function transferReceive(StockTransfer $transfer)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($transfer->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company stock transfer.');
        }

        try {
            $inventoryService = new InventoryService();
            $inventoryService->receiveTransfer($transfer, auth()->id() ?? 1);
            AuditLog::log('inventory', 'transfer_receive', "Received and confirmed stock transfer {$transfer->transfer_no}");
            return back()->with('success', "Stock transfer {$transfer->transfer_no} completed (Stock added to destination).");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // Physical Stock Adjustments
    public function adjustments()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $adjustments = StockAdjustment::where('company_id', $companyId)
            ->with(['godown', 'items.item', 'approver'])
            ->latest()
            ->paginate(15);
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();

        return view('inventory.adjustments', compact('adjustments', 'godowns', 'items'));
    }

    public function adjustmentStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'actual_stock' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        return DB::transaction(function () use ($companyId, $data) {
            $godown = Godown::where('id', $data['godown_id'])->where('company_id', $companyId)->firstOrFail();
            $item = Item::where('id', $data['item_id'])->where('company_id', $companyId)->firstOrFail();

            $batch = StockBatch::where('godown_id', $data['godown_id'])
                ->where('item_id', $data['item_id'])
                ->lockForUpdate()
                ->first();

            $currentStock = $batch ? (float)$batch->quantity : 0;
            $actualStock = (float)$data['actual_stock'];
            $diff = $actualStock - $currentStock;

            if ($diff == 0) {
                return back()->with('info', 'No difference between current stock and actual stock.');
            }

            $type = $diff > 0 ? 'increase' : 'decrease';
            $adjQty = abs($diff);
            $adjNo = 'ADJ-' . date('Ymd') . '-' . rand(1000, 9999);

            $adj = StockAdjustment::create([
                'company_id' => $companyId,
                'branch_id' => $godown->branch_id,
                'godown_id' => $data['godown_id'],
                'adjustment_no' => $adjNo,
                'adjustment_date' => now()->toDateString(),
                'reason' => $data['reason'],
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'created_by' => auth()->id(),
            ]);

            StockAdjustmentItem::create([
                'stock_adjustment_id' => $adj->id,
                'item_id' => $data['item_id'],
                'batch_id' => $batch?->id,
                'current_stock' => $currentStock,
                'actual_stock' => $actualStock,
                'adjustment_qty' => $adjQty,
                'type' => $type,
            ]);

            $unitCost = $batch && (float)$batch->cost_price > 0
                ? (float)$batch->cost_price
                : ((float)($item->cost_price ?? 0) ?: (float)($item->purchase_price ?? 0) ?: 10.0);

            $inventoryService = new InventoryService();
            if ($type === 'increase') {
                $inventoryService->recordInward(
                    $companyId, $godown->branch_id, $data['godown_id'], $data['item_id'],
                    $adjQty, $unitCost, $batch ? $batch->batch_no : 'DEFAULT',
                    null, 'StockAdjustment', $adj->id, $adjNo, $data['reason']
                );
            } else {
                $inventoryService->recordOutward(
                    $companyId, $godown->branch_id, $data['godown_id'], $data['item_id'],
                    $adjQty, $batch ? $batch->batch_no : 'DEFAULT',
                    'StockAdjustment', $adj->id, $adjNo, $data['reason']
                );
            }

            // Post corresponding double-entry accounting journal
            $adjValue = round($adjQty * $unitCost, 2);
            if ($adjValue > 0) {
                $accountingService = new AccountingService();
                $stockAssetLedger = $accountingService->getOrCreateStandardLedger(
                    $companyId, 'Stock on Hand', 'Current Assets', 'Assets'
                );

                $reasonLower = strtolower($data['reason']);
                $lines = [];

                if (str_contains($reasonLower, 'damage') || str_contains($reasonLower, 'expire') || str_contains($reasonLower, 'spoil') || str_contains($reasonLower, 'waste')) {
                    $lossLedger = $accountingService->getOrCreateStandardLedger(
                        $companyId, 'Damaged & Expired Stock Expense', 'Direct Expenses & Purchases', 'Expenses'
                    );
                    $lines = [
                        ['ledger_id' => $lossLedger->id, 'debit' => $adjValue, 'credit' => 0, 'narration' => "Damaged/expired stock write-off: {$data['reason']}"],
                        ['ledger_id' => $stockAssetLedger->id, 'debit' => 0, 'credit' => $adjValue, 'narration' => "Inventory asset reduction due to damage/expiry"],
                    ];
                } elseif ($type === 'decrease') { // Shortage
                    $lossLedger = $accountingService->getOrCreateStandardLedger(
                        $companyId, 'Inventory Shortage & Loss', 'Direct Expenses & Purchases', 'Expenses'
                    );
                    $lines = [
                        ['ledger_id' => $lossLedger->id, 'debit' => $adjValue, 'credit' => 0, 'narration' => "Physical count shortage: {$data['reason']}"],
                        ['ledger_id' => $stockAssetLedger->id, 'debit' => 0, 'credit' => $adjValue, 'narration' => "Inventory asset reduction due to physical shortage"],
                    ];
                } else { // Surplus / gain
                    $gainLedger = $accountingService->getOrCreateStandardLedger(
                        $companyId, 'Inventory Surplus & Gain', 'Sales & Operating Revenue', 'Revenue'
                    );
                    $lines = [
                        ['ledger_id' => $stockAssetLedger->id, 'debit' => $adjValue, 'credit' => 0, 'narration' => "Inventory asset addition due to physical surplus"],
                        ['ledger_id' => $gainLedger->id, 'debit' => 0, 'credit' => $adjValue, 'narration' => "Physical count surplus gain: {$data['reason']}"],
                    ];
                }

                $jv = $accountingService->createJournalEntry(
                    $companyId,
                    $godown->branch_id,
                    'Journal',
                    now()->toDateString(),
                    "Stock Adjustment {$adjNo} on {$item->name} ({$type} {$adjQty})",
                    $lines,
                    'StockAdjustment',
                    $adj->id,
                    $adjNo
                );

                $adj->update(['journal_entry_id' => $jv->id]);
            }

            AuditLog::log('inventory', 'adjustment', "Applied adjustment {$adjNo} on Item #{$data['item_id']} ({$type} {$adjQty})");
            return back()->with('success', "Stock adjusted successfully. Current balance updated to {$actualStock}.");
        });
    }

    // Alerts
    public function alerts()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $items = Item::where('company_id', $companyId)->with(['unit', 'batches'])->where('status', 'active')->get();

        $lowStock = $items->filter(fn($i) => $i->totalStockQuantity() <= (float)$i->reorder_quantity);

        $today = Carbon::today();
        $expiringBatches = StockBatch::where('company_id', $companyId)
            ->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', $today->copy()->addDays(30))
            ->with(['item', 'godown'])
            ->get();

        return view('inventory.alerts', compact('lowStock', 'expiringBatches'));
    }

    // Bulk Opening Stock
    public function openingStock(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $selectedGodownId = $request->get('godown_id', $godowns->first()?->id);

        $items = Item::where('company_id', $companyId)
            ->where('status', 'active')
            ->with(['unit', 'batches' => fn($q) => $q->where('godown_id', $selectedGodownId)])
            ->get();

        return view('inventory.opening_stock', compact('godowns', 'selectedGodownId', 'items'));
    }

    public function openingStockStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'items' => 'required|array|min:1',
            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($companyId, $data) {
            $godown = Godown::where('id', $data['godown_id'])->where('company_id', $companyId)->firstOrFail();
            $inventoryService = new InventoryService();
            $count = 0;
            $totalOpeningValue = 0;
            $opRefNo = 'OPENING-' . date('Ymd') . '-' . $godown->id . '-' . rand(100, 999);

            foreach ($data['items'] as $row) {
                $qty = (float)($row['quantity'] ?? 0);
                if ($qty > 0) {
                    $item = Item::where('id', $row['item_id'])->where('company_id', $companyId)->firstOrFail();
                    $cost = (float)($row['unit_cost'] ?? 0);
                    if ($cost <= 0) {
                        $cost = (float)($item->cost_price ?? 0) ?: (float)($item->purchase_price ?? 0) ?: 10.0;
                    }

                    $inventoryService->recordInward(
                        $companyId,
                        $godown->branch_id,
                        $data['godown_id'],
                        $row['item_id'],
                        $qty,
                        $cost,
                        'DEFAULT',
                        null,
                        'OpeningStock',
                        $godown->id,
                        $opRefNo,
                        'Bulk Opening Stock Inward'
                    );

                    $totalOpeningValue += ($qty * $cost);
                    $count++;
                }
            }

            // Post opening balance accounting entry
            $totalOpeningValue = round($totalOpeningValue, 2);
            if ($totalOpeningValue > 0) {
                $accountingService = new AccountingService();
                $stockAssetLedger = $accountingService->getOrCreateStandardLedger(
                    $companyId, 'Stock on Hand', 'Current Assets', 'Assets'
                );
                $openingEquityLedger = $accountingService->getOrCreateStandardLedger(
                    $companyId, 'Opening Balance Equity', 'Capital & Reserves', 'Equity'
                );

                $lines = [
                    [
                        'ledger_id' => $stockAssetLedger->id,
                        'debit' => $totalOpeningValue,
                        'credit' => 0,
                        'narration' => "Opening stock valuation for Godown {$godown->name}",
                    ],
                    [
                        'ledger_id' => $openingEquityLedger->id,
                        'debit' => 0,
                        'credit' => $totalOpeningValue,
                        'narration' => "Opening balance equity for Godown {$godown->name}",
                    ],
                ];

                $accountingService->createJournalEntry(
                    $companyId,
                    $godown->branch_id,
                    'Journal',
                    now()->toDateString(),
                    "Opening Stock Initial Valuation for Godown: {$godown->name}",
                    $lines,
                    'OpeningStock',
                    $godown->id,
                    $opRefNo
                );
            }

            AuditLog::log('inventory', 'opening_stock', "Recorded opening stock for {$count} items in Godown #{$data['godown_id']}");
            return redirect()->route('inventory.overview', ['godown_id' => $data['godown_id']])
                ->with('success', "Opening stock successfully recorded for {$count} items with financial ledger balance.");
        });
    }

    // ==================== INTERNAL STOCK REQUESTS ====================

    public function requests(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = StockRequest::where('company_id', $companyId)
            ->with(['fromDepartment', 'sourceGodown', 'fromGodown', 'items.item', 'creator', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('from_department_id')) {
            $query->where('from_department_id', $request->get('from_department_id'));
        }
        if ($request->filled('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        $requests = $query->latest('request_date')->latest('id')->paginate(15);
        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();

        return view('inventory.requests.index', compact('requests', 'departments', 'godowns', 'items'));
    }

    public function requestCreate()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->with('unit')->get();

        return view('inventory.requests.create', compact('departments', 'godowns', 'items'));
    }

    public function requestStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'from_department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'source_godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'from_godown_id' => [
                'nullable',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'request_date' => 'required|date',
            'required_date' => 'nullable|date',
            'priority' => 'required|in:low,medium,high,urgent',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.preferred_batch_id' => 'nullable|exists:stock_batches,id',
            'items.*.remarks' => 'nullable|string',
        ]);

        $dept = Department::where('id', $data['from_department_id'])->first();
        $branchId = $dept?->branch_id ?? session('active_branch_id');

        $reqData = [
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'from_department_id' => $data['from_department_id'],
            'source_godown_id' => $data['source_godown_id'],
            'from_godown_id' => $data['from_godown_id'] ?? null,
            'request_date' => $data['request_date'],
            'required_date' => $data['required_date'] ?? null,
            'priority' => $data['priority'],
            'remarks' => $data['remarks'] ?? null,
        ];

        $reqItems = [];
        foreach ($data['items'] as $itemLine) {
            $reqItems[] = [
                'item_id' => $itemLine['item_id'],
                'requested_qty' => $itemLine['quantity'],
                'preferred_batch_id' => $itemLine['preferred_batch_id'] ?? null,
                'remarks' => $itemLine['remarks'] ?? null,
            ];
        }

        try {
            $inventoryService = new InventoryService();
            $stockRequest = $inventoryService->createStockRequest($reqData, $reqItems, auth()->id() ?? 1);
            AuditLog::log('inventory', 'request_create', "Created stock request {$stockRequest->request_no}");
            return redirect()->route('inventory.requests.show', $stockRequest)->with('success', "Stock request {$stockRequest->request_no} submitted successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function requestShow(StockRequest $stockRequest)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($stockRequest->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $stockRequest->load([
            'fromDepartment', 'sourceGodown', 'fromGodown', 'creator', 'approver',
            'items.item.unit', 'items.preferredBatch',
            'transfers.dispatcher', 'transfers.receiver', 'transfers.items.item'
        ]);

        return view('inventory.requests.show', compact('stockRequest'));
    }

    public function requestApprove(Request $request, StockRequest $stockRequest)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($stockRequest->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'items' => 'required|array',
            'items.*.approved_qty' => 'required|numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        try {
            $inventoryService = new InventoryService();
            $approvedLines = [];
            foreach ($data['items'] as $id => $row) {
                $approvedLines[] = [
                    'id' => (int)$id,
                    'approved_qty' => (float)$row['approved_qty'],
                ];
            }

            $inventoryService->approveStockRequest($stockRequest, $approvedLines, auth()->id() ?? 1, $data['remarks'] ?? null);
            AuditLog::log('inventory', 'request_approve', "Reviewed stock request {$stockRequest->request_no} -> {$stockRequest->status}");
            return back()->with('success', "Stock request {$stockRequest->request_no} review recorded (Status: {$stockRequest->status}).");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function requestReject(Request $request, StockRequest $stockRequest)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($stockRequest->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'rejection_reason' => 'required|string|min:3',
        ]);

        try {
            $inventoryService = new InventoryService();
            $inventoryService->rejectStockRequest($stockRequest, $data['rejection_reason'], auth()->id() ?? 1);
            AuditLog::log('inventory', 'request_reject', "Rejected stock request {$stockRequest->request_no}");
            return back()->with('success', "Stock request {$stockRequest->request_no} has been rejected.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ==================== DISPATCH & IN-TRANSIT ====================

    public function dispatchCreate(StockRequest $stockRequest)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($stockRequest->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        if (!in_array($stockRequest->status, ['approved', 'partially_approved'])) {
            return back()->withErrors(['error' => "Only approved requests can be dispatched."]);
        }

        $stockRequest->load(['items.item.unit', 'items.preferredBatch', 'sourceGodown', 'fromGodown']);

        // Load available batches in source godown for each item
        $itemBatches = [];
        foreach ($stockRequest->items as $item) {
            $batches = StockBatch::where('godown_id', $stockRequest->source_godown_id)
                ->where('item_id', $item->item_id)
                ->where('quantity', '>', 0)
                ->get();
            $itemBatches[$item->id] = $batches;
        }

        return view('inventory.requests.dispatch', compact('stockRequest', 'itemBatches'));
    }

    public function dispatchStore(Request $request, StockRequest $stockRequest)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($stockRequest->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'dispatch_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.stock_request_item_id' => 'required|exists:stock_request_items,id',
            'items.*.batch_no' => 'required|string',
            'items.*.dispatch_quantity' => 'required|numeric|min:0.001',
        ]);

        try {
            $inventoryService = new InventoryService();
            $transfer = $inventoryService->dispatchStockRequest(
                $stockRequest,
                $data['items'],
                auth()->id() ?? 1,
                $data['dispatch_reference'] ?? null,
                $data['remarks'] ?? null
            );

            AuditLog::log('inventory', 'dispatch', "Dispatched transfer {$transfer->transfer_no} for request {$stockRequest->request_no}");
            return redirect()->route('inventory.requests.show', $stockRequest)->with('success', "Transfer {$transfer->transfer_no} dispatched successfully (In-Transit).");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ==================== RECEIPT CONFIRMATION ====================

    public function receiptCreate(StockTransfer $transfer)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($transfer->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        if (!in_array($transfer->status, ['in_transit', 'partially_received', 'pending'])) {
            return back()->withErrors(['error' => "Transfer is not in transit or eligible for receipt."]);
        }

        $transfer->load(['sourceGodown', 'destGodown', 'items.item.unit', 'items.batch', 'request']);

        return view('inventory.transfers.receipt', compact('transfer'));
    }

    public function receiptStore(Request $request, StockTransfer $transfer)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($transfer->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'receiver_remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.transfer_item_id' => 'required|exists:stock_transfer_items,id',
            'items.*.received_qty' => 'required|numeric|min:0',
            'items.*.damaged_qty' => 'nullable|numeric|min:0',
            'items.*.discrepancy_type' => 'nullable|string|in:shortage,damaged_goods,wrong_item,wrong_batch,quality_rejection,excess_quantity,other',
            'items.*.discrepancy_reason' => 'nullable|string',
            'items.*.remarks' => 'nullable|string',
        ]);

        try {
            $inventoryService = new InventoryService();
            $inventoryService->receiveTransferWithDiscrepancies(
                $transfer,
                $data['items'],
                auth()->id() ?? 1,
                $data['receiver_remarks'] ?? null
            );

            AuditLog::log('inventory', 'receipt', "Confirmed receipt for transfer {$transfer->transfer_no} (Status: {$transfer->status})");
            return redirect()->route('inventory.transfers')->with('success', "Transfer {$transfer->transfer_no} receipt confirmation recorded (Status: {$transfer->status}).");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ==================== DISCREPANCY MANAGEMENT ====================

    public function discrepancies(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = StockDiscrepancy::where('company_id', $companyId)
            ->with(['transfer', 'request', 'item.unit', 'batch', 'sourceGodown', 'destGodown', 'creator', 'investigator', 'resolver', 'stockAdjustment']);

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->filled('discrepancy_type')) {
            $query->where('discrepancy_type', $request->get('discrepancy_type'));
        }

        $discrepancies = $query->latest('id')->paginate(15);

        return view('inventory.discrepancies.index', compact('discrepancies'));
    }

    public function discrepancyShow(StockDiscrepancy $discrepancy)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($discrepancy->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $discrepancy->load([
            'transfer.dispatcher', 'transfer.receiver', 'request.fromDepartment',
            'item.unit', 'batch', 'sourceGodown', 'destGodown',
            'creator', 'investigator', 'resolver', 'stockAdjustment.journalEntry',
            'approvals.approver'
        ]);

        $sourceBatches = StockBatch::where('godown_id', $discrepancy->source_godown_id)
            ->where('item_id', $discrepancy->item_id)
            ->where('quantity', '>', 0)
            ->get();

        return view('inventory.discrepancies.show', compact('discrepancy', 'sourceBatches'));
    }

    public function discrepancyInvestigate(Request $request, StockDiscrepancy $discrepancy)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($discrepancy->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'investigation_remarks' => 'required|string|min:3',
        ]);

        $inventoryService = new InventoryService();
        $inventoryService->investigateDiscrepancy($discrepancy, $data['investigation_remarks'], auth()->id() ?? 1);

        AuditLog::log('inventory', 'discrepancy_investigate', "Investigated discrepancy #{$discrepancy->id}");
        return back()->with('success', "Discrepancy #{$discrepancy->id} updated to Under Investigation.");
    }

    public function discrepancySubmitWriteOff(Request $request, StockDiscrepancy $discrepancy)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($discrepancy->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        $inventoryService = new InventoryService();
        $inventoryService->submitDiscrepancyWriteOff($discrepancy, $data['reason'], auth()->id() ?? 1);

        AuditLog::log('inventory', 'discrepancy_writeoff_submit', "Submitted write-off for discrepancy #{$discrepancy->id}");
        return back()->with('success', "Discrepancy #{$discrepancy->id} submitted for Write-Off Approval.");
    }

    public function discrepancyApproveWriteOff(Request $request, StockDiscrepancy $discrepancy)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($discrepancy->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'remarks' => 'nullable|string',
        ]);

        try {
            $inventoryService = new InventoryService();
            $inventoryService->approveDiscrepancyWriteOff($discrepancy, auth()->id() ?? 1, $data['remarks'] ?? null);

            AuditLog::log('inventory', 'discrepancy_writeoff_approve', "Approved write-off for discrepancy #{$discrepancy->id}");
            return back()->with('success', "Discrepancy #{$discrepancy->id} write-off approved and financial journal entry created.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function discrepancyRejectWriteOff(Request $request, StockDiscrepancy $discrepancy)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($discrepancy->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        $inventoryService = new InventoryService();
        $inventoryService->rejectDiscrepancyWriteOff($discrepancy, auth()->id() ?? 1, $data['reason']);

        AuditLog::log('inventory', 'discrepancy_writeoff_reject', "Rejected write-off for discrepancy #{$discrepancy->id}");
        return back()->with('info', "Discrepancy #{$discrepancy->id} write-off rejected.");
    }

    public function discrepancyResolveAdditionalDispatch(Request $request, StockDiscrepancy $discrepancy)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($discrepancy->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'quantity' => 'required|numeric|min:0.001',
            'batch_no' => 'required|string',
        ]);

        try {
            $inventoryService = new InventoryService();
            $newTransfer = $inventoryService->resolveDiscrepancyAdditionalDispatch($discrepancy, $data, auth()->id() ?? 1);

            AuditLog::log('inventory', 'discrepancy_replacement', "Replacement dispatch {$newTransfer->transfer_no} for discrepancy #{$discrepancy->id}");
            return back()->with('success', "Replacement transfer {$newTransfer->transfer_no} dispatched.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function discrepancyResolveSeparateDelivery(Request $request, StockDiscrepancy $discrepancy)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($discrepancy->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'delivered_quantity' => 'required|numeric|min:0.001',
            'notes' => 'required|string|min:3',
        ]);

        try {
            $inventoryService = new InventoryService();
            $inventoryService->resolveDiscrepancySeparateDelivery($discrepancy, (float)$data['delivered_quantity'], auth()->id() ?? 1, $data['notes']);

            AuditLog::log('inventory', 'discrepancy_separate_deliv', "Confirmed separate delivery for discrepancy #{$discrepancy->id}");
            return back()->with('success', "Separate delivery confirmed and stock received into destination godown.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function discrepancyCancelOutstanding(Request $request, StockDiscrepancy $discrepancy)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($discrepancy->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'reason' => 'required|string|min:3',
        ]);

        $inventoryService = new InventoryService();
        $inventoryService->cancelOutstandingDiscrepancy($discrepancy, auth()->id() ?? 1, $data['reason']);

        AuditLog::log('inventory', 'discrepancy_cancel', "Cancelled outstanding requirement for discrepancy #{$discrepancy->id}");
        return back()->with('info', "Discrepancy #{$discrepancy->id} outstanding requirement cancelled.");
    }

    // ==================== STOCK CONFIGURATION ====================

    public function stockConfiguration(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $branches = Branch::where('company_id', $companyId)->with(['centralGodown', 'godowns'])->get();
        $departments = Department::where('company_id', $companyId)->with(['branch', 'defaultGodown', 'sourceGodown'])->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();

        return view('inventory.configuration', compact('branches', 'departments', 'godowns'));
    }

    public function branchStockConfigUpdate(Request $request, Branch $branch)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($branch->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'stock_mode' => 'required|in:centralized,multiple_locations',
            'central_godown_id' => [
                'nullable',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
        ]);

        $inventoryService = new InventoryService();
        $inventoryService->configureBranchStock($branch, $data['stock_mode'], $data['central_godown_id'] ?? null, auth()->id() ?? 1);

        AuditLog::log('inventory', 'config_branch', "Updated stock mode to {$data['stock_mode']} for Branch {$branch->name}");
        return back()->with('success', "Stock configuration updated for Branch {$branch->name}.");
    }

    public function departmentStockConfigUpdate(Request $request, Department $department)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($department->company_id != $companyId) {
            abort(403, 'Unauthorized company boundary access.');
        }

        $data = $request->validate([
            'maintains_stock' => 'required|boolean',
            'default_godown_id' => [
                'nullable',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'source_godown_id' => [
                'nullable',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
        ]);

        try {
            $inventoryService = new InventoryService();
            $inventoryService->configureDepartmentStock(
                $department,
                (bool)$data['maintains_stock'],
                $data['default_godown_id'] ?? null,
                $data['source_godown_id'] ?? null,
                auth()->id() ?? 1
            );

            AuditLog::log('inventory', 'config_department', "Updated department stock configuration for {$department->name}");
            return back()->with('success', "Stock configuration updated for Department {$department->name}.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    // ==================== PHASE 10: REPORTS & DASHBOARDS ====================

    public function reportStockRequests(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = StockRequest::where('company_id', $companyId)
            ->with(['branch', 'fromDepartment', 'sourceGodown', 'fromGodown', 'items.item.unit', 'creator', 'approver']);

        if ($request->filled('status')) $query->where('status', $request->get('status'));
        if ($request->filled('from_department_id')) $query->where('from_department_id', $request->get('from_department_id'));
        if ($request->filled('date_from')) $query->whereDate('request_date', '>=', $request->get('date_from'));
        if ($request->filled('date_to')) $query->whereDate('request_date', '<=', $request->get('date_to'));

        $requests = $query->latest('request_date')->paginate(20);
        $departments = Department::where('company_id', $companyId)->get();

        return view('inventory.reports.requests', compact('requests', 'departments'));
    }

    public function reportStockTransfers(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = StockTransfer::where('company_id', $companyId)
            ->with(['sourceBranch', 'destBranch', 'sourceGodown', 'destGodown', 'items.item.unit', 'items.batch', 'request']);

        if ($request->filled('status')) $query->where('status', $request->get('status'));
        if ($request->filled('source_godown_id')) $query->where('source_godown_id', $request->get('source_godown_id'));
        if ($request->filled('dest_godown_id')) $query->where('dest_godown_id', $request->get('dest_godown_id'));

        $transfers = $query->latest('transfer_date')->paginate(20);
        $godowns = Godown::where('company_id', $companyId)->get();

        return view('inventory.reports.transfers', compact('transfers', 'godowns'));
    }

    public function reportPendingReceipts(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $pendingTransfers = StockTransfer::where('company_id', $companyId)
            ->whereIn('status', ['in_transit', 'partially_received'])
            ->with(['sourceGodown', 'destGodown', 'dispatcher', 'items.item.unit', 'items.batch', 'request'])
            ->latest('dispatched_at')
            ->paginate(20);

        return view('inventory.reports.pending_receipts', compact('pendingTransfers'));
    }

    public function reportDiscrepancies(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = StockDiscrepancy::where('company_id', $companyId)
            ->with(['transfer', 'request', 'item.unit', 'batch', 'sourceGodown', 'destGodown', 'creator', 'resolver']);

        if ($request->filled('status')) $query->where('status', $request->get('status'));
        if ($request->filled('discrepancy_type')) $query->where('discrepancy_type', $request->get('discrepancy_type'));

        $discrepancies = $query->latest('id')->paginate(20);

        return view('inventory.reports.discrepancies', compact('discrepancies'));
    }

    public function reportInTransit(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $inTransitItems = StockTransferItem::whereHas('transfer', function ($q) use ($companyId) {
                $q->where('company_id', $companyId)->whereIn('status', ['in_transit', 'partially_received']);
            })
            ->where('in_transit_qty', '>', 0)
            ->with(['transfer.sourceGodown', 'transfer.destGodown', 'transfer.dispatcher', 'item.unit', 'batch'])
            ->paginate(20);

        return view('inventory.reports.in_transit', compact('inTransitItems'));
    }

    public function reportLocationStock(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $godowns = Godown::where('company_id', $companyId)->with('department')->get();
        $selectedGodownId = $request->get('godown_id');

        $query = StockBatch::where('company_id', $companyId)->where('quantity', '>', 0)->with(['item.unit', 'godown.department']);
        if ($selectedGodownId) {
            $query->where('godown_id', $selectedGodownId);
        }

        $batches = $query->orderBy('godown_id')->orderBy('item_id')->paginate(25);

        return view('inventory.reports.location_stock', compact('batches', 'godowns', 'selectedGodownId'));
    }

    public function reportMaterialConsumption(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = DailyOperationMaterial::whereHas('dailyOperation', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
            ->with(['dailyOperation.department', 'item.unit', 'sourceGodown']);

        if ($request->filled('department_id')) {
            $query->whereHas('dailyOperation', fn($q) => $q->where('department_id', $request->get('department_id')));
        }
        if ($request->filled('item_id')) {
            $query->where('item_id', $request->get('item_id'));
        }

        $consumptions = $query->latest('id')->paginate(25);
        $departments = Department::where('company_id', $companyId)->get();
        $items = Item::where('company_id', $companyId)->get();

        return view('inventory.reports.consumption', compact('consumptions', 'departments', 'items'));
    }
}


<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Item;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\CompanyContextService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
            'godown_id' => 'required|exists:godowns,id',
            'item_id' => 'required|exists:items,id',
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
            'godown_id' => 'required|exists:godowns,id',
            'item_id' => 'required|exists:items,id',
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
            'source_godown_id' => 'required|exists:godowns,id',
            'dest_godown_id' => 'required|exists:godowns,id|different:source_godown_id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|numeric|min:0.001',
            'batch_no' => 'nullable|string|max:50',
            'transfer_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        $batchNo = $data['batch_no'] ?? 'DEFAULT';
        $transferNo = 'TRF-' . date('Ymd') . '-' . rand(1000, 9999);

        try {
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
            'godown_id' => 'required|exists:godowns,id',
            'item_id' => 'required|exists:items,id',
            'actual_stock' => 'required|numeric|min:0',
            'reason' => 'required|string',
        ]);

        $batch = StockBatch::where('godown_id', $data['godown_id'])
            ->where('item_id', $data['item_id'])
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

        $inventoryService = new InventoryService();
        if ($type === 'increase') {
            $inventoryService->recordInward(
                $companyId, null, $data['godown_id'], $data['item_id'],
                $adjQty, $batch ? (float)$batch->cost_price : 0, $batch ? $batch->batch_no : 'DEFAULT',
                null, 'StockAdjustment', $adj->id, $adjNo, $data['reason']
            );
        } else {
            $inventoryService->recordOutward(
                $companyId, null, $data['godown_id'], $data['item_id'],
                $adjQty, $batch ? $batch->batch_no : 'DEFAULT',
                'StockAdjustment', $adj->id, $adjNo, $data['reason']
            );
        }

        AuditLog::log('inventory', 'adjustment', "Applied adjustment {$adjNo} on Item #{$data['item_id']} ({$type} {$adjQty})");
        return back()->with('success', "Stock adjusted successfully. Current balance updated to {$actualStock}.");
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
}


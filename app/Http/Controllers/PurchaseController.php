<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Godown;
use App\Models\GoodsReceiptNote;
use App\Models\GrnItem;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\PurchaseEntry;
use App\Models\PurchaseEntryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\QualityCheck;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    // ==================== PURCHASE REQUESTS ====================
    public function requests()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $requests = PurchaseRequest::where('company_id', $companyId)
            ->with(['department', 'items.item'])
            ->latest()
            ->paginate(15);
        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();

        return view('purchase.requests.index', compact('requests', 'departments', 'items'));
    }

    public function requestStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'requested_by' => 'required|string|max:100',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'expected_delivery_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'items.*.quantity' => 'required|numeric|min:0.001',
        ]);

        $reqNo = 'PR-' . date('Y') . '-' . sprintf('%04d', PurchaseRequest::where('company_id', $companyId)->count() + 1);

        $pr = PurchaseRequest::create([
            'company_id' => $companyId,
            'department_id' => $data['department_id'],
            'request_no' => $reqNo,
            'request_date' => now()->toDateString(),
            'requested_by' => $data['requested_by'],
            'priority' => $data['priority'],
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'status' => 'pending',
        ]);

        foreach ($data['items'] as $item) {
            PurchaseRequestItem::create([
                'purchase_request_id' => $pr->id,
                'item_id' => $item['item_id'],
                'quantity' => $item['quantity'],
                'approved_quantity' => $item['quantity'],
            ]);
        }

        AuditLog::log('purchase', 'create_request', "Created purchase request {$reqNo}");
        return back()->with('success', "Purchase request {$reqNo} created successfully.");
    }

    public function requestApprove(Request $request, PurchaseRequest $purchaseRequest)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($purchaseRequest->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company purchase request.');
        }

        $purchaseRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approval_date' => now()->toDateString(),
            'approval_remarks' => $request->input('approval_remarks', 'Approved by authority'),
        ]);

        AuditLog::log('purchase', 'approve_request', "Approved purchase request {$purchaseRequest->request_no}");
        return back()->with('success', "Purchase request {$purchaseRequest->request_no} approved.");
    }

    // ==================== PURCHASE ORDERS (PO) ====================
    public function orders(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = PurchaseOrder::where('company_id', $companyId)->with(['supplier', 'items.item']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $orders = $query->latest()->paginate(15);
        $suppliers = Supplier::where('company_id', $companyId)->where('status', 'active')->get();

        return view('purchase.orders.index', compact('orders', 'suppliers'));
    }

    public function orderCreate()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $suppliers = Supplier::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();
        $requests = PurchaseRequest::where('company_id', $companyId)->where('status', 'approved')->get();

        return view('purchase.orders.create', compact('suppliers', 'items', 'requests'));
    }

    public function orderStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('company_id', $companyId),
            ],
            'purchase_request_id' => [
                'nullable',
                Rule::exists('purchase_requests', 'id')->where('company_id', $companyId),
            ],
            'po_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'terms_and_conditions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($data['supplier_id']);

        $poNo = 'PO-' . date('Y') . '-' . sprintf('%04d', PurchaseOrder::where('company_id', $companyId)->count() + 1);

        $subtotal = 0;
        $totalTax = 0;

        foreach ($data['items'] as $it) {
            $lineSub = $it['quantity'] * $it['rate'];
            $taxRate = $it['tax_percentage'] ?? 0;
            $lineTax = $lineSub * ($taxRate / 100);

            $subtotal += $lineSub;
            $totalTax += $lineTax;
        }

        $grandTotal = $subtotal + $totalTax;

        $po = PurchaseOrder::create([
            'company_id' => $companyId,
            'supplier_id' => $data['supplier_id'],
            'purchase_request_id' => $data['purchase_request_id'] ?? null,
            'po_number' => $poNo,
            'po_date' => $data['po_date'],
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'discount_amount' => 0,
            'total_amount' => $grandTotal,
            'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
            'status' => 'approved',
            'created_by' => auth()->id(),
        ]);

        foreach ($data['items'] as $it) {
            $lineSub = $it['quantity'] * $it['rate'];
            $taxRate = $it['tax_percentage'] ?? 0;
            $lineTax = $lineSub * ($taxRate / 100);

            PurchaseOrderItem::create([
                'purchase_order_id' => $po->id,
                'item_id' => $it['item_id'],
                'quantity' => $it['quantity'],
                'received_quantity' => 0,
                'rate' => $it['rate'],
                'tax_percentage' => $taxRate,
                'tax_amount' => $lineTax,
                'discount_amount' => 0,
                'total_amount' => $lineSub + $lineTax,
            ]);
        }

        if (!empty($data['purchase_request_id'])) {
            PurchaseRequest::where('id', $data['purchase_request_id'])->update(['status' => 'ordered']);
        }

        AuditLog::log('purchase', 'create_po', "Created Purchase Order {$poNo}");
        return redirect()->route('purchase.orders.show', $po)->with('success', "Purchase Order {$poNo} created.");
    }

    public function orderShow(PurchaseOrder $order)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($order->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company purchase order.');
        }

        $order->load(['supplier', 'items.item.unit', 'grns']);
        return view('purchase.orders.show', compact('order'));
    }

    // ==================== GOODS RECEIPT NOTE (GRN) ====================
    public function grns()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $grns = GoodsReceiptNote::where('company_id', $companyId)
            ->with(['supplier', 'godown', 'items.item'])
            ->latest()
            ->paginate(15);
        return view('purchase.grn.index', compact('grns'));
    }

    public function grnCreate(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $poId = $request->get('po_id');
        $po = $poId ? PurchaseOrder::where('company_id', $companyId)->with(['items.item', 'supplier'])->find($poId) : null;

        $suppliers = Supplier::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();

        return view('purchase.grn.create', compact('po', 'suppliers', 'godowns', 'items'));
    }

    public function grnStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('company_id', $companyId),
            ],
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'purchase_order_id' => [
                'nullable',
                Rule::exists('purchase_orders', 'id')->where('company_id', $companyId),
            ],
            'received_date' => 'required|date',
            'received_by' => 'required|string|max:100',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'items.*.ordered_quantity' => 'nullable|numeric|min:0',
            'items.*.received_quantity' => 'required|numeric|min:0.001',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($data['supplier_id']);
        $godown = Godown::where('company_id', $companyId)->findOrFail($data['godown_id']);

        $grnNo = 'GRN-' . date('Y') . '-' . sprintf('%04d', GoodsReceiptNote::where('company_id', $companyId)->count() + 1);

        $grn = GoodsReceiptNote::create([
            'company_id' => $companyId,
            'godown_id' => $data['godown_id'],
            'supplier_id' => $data['supplier_id'],
            'purchase_order_id' => $data['purchase_order_id'] ?? null,
            'grn_number' => $grnNo,
            'received_date' => $data['received_date'],
            'received_by' => $data['received_by'],
            'status' => 'qc_passed',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => auth()->id(),
        ]);

        foreach ($data['items'] as $it) {
            GrnItem::create([
                'goods_receipt_note_id' => $grn->id,
                'item_id' => $it['item_id'],
                'ordered_quantity' => $it['ordered_quantity'] ?? $it['received_quantity'],
                'received_quantity' => $it['received_quantity'],
                'accepted_quantity' => $it['received_quantity'],
                'rejected_quantity' => 0,
                'batch_number' => $it['batch_number'] ?? 'DEFAULT',
                'expiry_date' => $it['expiry_date'] ?? null,
            ]);

            // Update PO received quantity if linked
            if (!empty($data['purchase_order_id'])) {
                $poItem = PurchaseOrderItem::where('purchase_order_id', $data['purchase_order_id'])
                    ->where('item_id', $it['item_id'])
                    ->first();
                if ($poItem) {
                    $poItem->received_quantity += $it['received_quantity'];
                    $poItem->save();
                }
            }
        }

        AuditLog::log('purchase', 'create_grn', "Created Goods Receipt Note {$grnNo}");
        return redirect()->route('purchase.grns.show', $grn)->with('success', "GRN {$grnNo} recorded successfully.");
    }

    public function grnShow(GoodsReceiptNote $grn)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($grn->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company GRN.');
        }

        $grn->load(['supplier', 'godown', 'items.item.unit', 'purchaseOrder']);
        return view('purchase.grn.show', compact('grn'));
    }

    // ==================== PURCHASE ENTRIES (BILLS) ====================
    public function entries(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = PurchaseEntry::where('company_id', $companyId)->with(['supplier', 'godown', 'journalEntry']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        $entries = $query->latest('invoice_date')->paginate(15);
        $suppliers = Supplier::where('company_id', $companyId)->where('status', 'active')->get();

        return view('purchase.entries.index', compact('entries', 'suppliers'));
    }

    public function entryCreate()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $suppliers = Supplier::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->with('unit')->get();
        $units = \App\Models\Unit::where('company_id', $companyId)->where('status', 'active')->get();
        $bankLedgers = Ledger::where('company_id', $companyId)->where('is_bank_or_cash', true)->where('status', 'active')->get();

        return view('purchase.entries.create', compact('suppliers', 'godowns', 'items', 'units', 'bankLedgers'));
    }

    public function entryStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('company_id', $companyId),
            ],
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'payment_type' => 'required|in:Credit,Cash,Bank,UPI',
            'discount_amount' => 'nullable|numeric|min:0',
            'freight_charges' => 'nullable|numeric|min:0',
            'packaging_charges' => 'nullable|numeric|min:0',
            'loading_charges' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'round_off' => 'nullable|numeric',
            'charge_allocation_method' => 'nullable|in:value,quantity,equal,none',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'items.*.unit_id' => [
                'nullable',
                Rule::exists('units', 'id')->where('company_id', $companyId),
            ],
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_ledger_id' => [
                'nullable',
                'required_if:payment_type,Cash,Bank,UPI',
                Rule::exists('ledgers', 'id')->where('company_id', $companyId),
            ],
            'amount_paid' => 'nullable|numeric|min:0',
            'transaction_reference' => 'nullable|string|max:100',
        ]);

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($data['supplier_id']);
        $godown = Godown::where('company_id', $companyId)->findOrFail($data['godown_id']);

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['invoice_date'])
            ->where('end_date', '>=', $data['invoice_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->withErrors(['invoice_date' => 'Cannot record purchase entry in a closed financial year period.']);
        }

        return DB::transaction(function () use ($companyId, $data) {
            $billDiscount = (float)($data['discount_amount'] ?? 0);
            $freight = (float)($data['freight_charges'] ?? 0);
            $packaging = (float)($data['packaging_charges'] ?? 0);
            $loading = (float)($data['loading_charges'] ?? 0);
            $other = (float)($data['other_charges'] ?? 0);
            $roundOff = (float)($data['round_off'] ?? 0);
            $totalAdditionalCharges = $freight + $packaging + $loading + $other;
            $allocMethod = $data['charge_allocation_method'] ?? 'value';

            $subtotal = 0;
            $totalItemDiscount = 0;
            $taxTotal = 0;
            $processedItems = [];
            $totalBaseQty = 0;

            foreach ($data['items'] as $it) {
                $itemModel = Item::with('unit')->find($it['item_id']);
                $conversion = \App\Services\UnitConversionService::convertToBaseQuantity($itemModel, (float)$it['quantity'], $it['unit_id'] ?? null);

                $lineGross = (float)$it['quantity'] * (float)$it['rate'];
                $lineDisc = (float)($it['discount_amount'] ?? 0);
                $taxable = max(0, $lineGross - $lineDisc);
                $taxRate = (float)($it['tax_percentage'] ?? 0);
                $lineTax = $taxable * ($taxRate / 100);

                $subtotal += $lineGross;
                $totalItemDiscount += $lineDisc;
                $taxTotal += $lineTax;
                $totalBaseQty += $conversion['base_quantity'];

                $processedItems[] = [
                    'raw' => $it,
                    'item' => $itemModel,
                    'conversion' => $conversion,
                    'line_gross' => $lineGross,
                    'line_disc' => $lineDisc,
                    'taxable' => $taxable,
                    'tax_rate' => $taxRate,
                    'line_tax' => $lineTax,
                ];
            }

            $totalDiscount = $totalItemDiscount + $billDiscount;
            $netItemsPurchases = max(0, $subtotal - $totalDiscount);
            $grandTotal = max(0, $netItemsPurchases + $taxTotal + $totalAdditionalCharges + $roundOff);
            $purNo = 'PUR-' . date('Y') . '-' . sprintf('%04d', PurchaseEntry::where('company_id', $companyId)->count() + 1);

            $paidAmount = 0;
            $paymentType = $data['payment_type'] ?? 'Credit';
            if ($paymentType !== 'Credit' && !empty($data['payment_ledger_id'])) {
                $paidAmount = min($grandTotal, (float)($data['amount_paid'] ?? $grandTotal));
            }

            $balanceAmount = max(0, $grandTotal - $paidAmount);
            $paymentStatus = $balanceAmount == 0 && $paidAmount > 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $pe = PurchaseEntry::create([
                'company_id' => $companyId,
                'godown_id' => $data['godown_id'],
                'supplier_id' => $data['supplier_id'],
                'purchase_number' => $purNo,
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'payment_type' => $paymentType,
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'discount_amount' => $totalDiscount,
                'freight_charges' => $freight,
                'packaging_charges' => $packaging,
                'loading_charges' => $loading,
                'other_charges' => $other,
                'round_off' => $roundOff,
                'charge_allocation_method' => $allocMethod,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'payment_status' => $paymentStatus,
                'stock_updated' => true,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $inventoryService = new InventoryService();
            $itemCount = count($processedItems);

            foreach ($processedItems as $p) {
                $it = $p['raw'];
                $conversion = $p['conversion'];
                $batchNo = !empty($it['batch_number']) ? $it['batch_number'] : 'DEFAULT';

                // Calculate allocated landed charge for inventory unit cost
                $allocatedCharge = 0;
                if ($totalAdditionalCharges > 0 && $allocMethod !== 'none') {
                    if ($allocMethod === 'quantity' && $totalBaseQty > 0) {
                        $allocatedCharge = ($conversion['base_quantity'] / $totalBaseQty) * $totalAdditionalCharges;
                    } elseif ($allocMethod === 'equal' && $itemCount > 0) {
                        $allocatedCharge = $totalAdditionalCharges / $itemCount;
                    } else { // 'value' default
                        $allocatedCharge = $netItemsPurchases > 0 ? ($p['taxable'] / $netItemsPurchases) * $totalAdditionalCharges : 0;
                    }
                }

                // Landed unit cost per base unit for stock valuation
                $baseQty = max(0.001, $conversion['base_quantity']);
                $landedUnitCost = ($p['taxable'] + $allocatedCharge) / $baseQty;

                PurchaseEntryItem::create([
                    'purchase_entry_id' => $pe->id,
                    'item_id' => $it['item_id'],
                    'unit_id' => $conversion['unit_id'],
                    'entered_quantity' => $conversion['entered_quantity'],
                    'base_quantity' => $conversion['base_quantity'],
                    'batch_number' => $batchNo,
                    'expiry_date' => $it['expiry_date'] ?? null,
                    'quantity' => $conversion['base_quantity'],
                    'rate' => $it['rate'],
                    'tax_percentage' => $p['tax_rate'],
                    'tax_amount' => $p['line_tax'],
                    'discount_amount' => $p['line_disc'],
                    'line_total' => $p['taxable'] + $p['line_tax'],
                ]);

                // Update stock inward using base quantity and landed unit cost
                $inventoryService->recordInward(
                    $companyId, null, $data['godown_id'], $it['item_id'],
                    $conversion['base_quantity'], $landedUnitCost, $batchNo, $it['expiry_date'] ?? null,
                    'PurchaseEntry', $pe->id, $purNo, "Inward for Purchase {$data['invoice_number']}"
                );
            }

            // Post to Double-Entry Accounting
            $accountingService = new AccountingService();
            $purchaseLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Purchase Account', 'Direct Expenses & Purchases', 'Expenses');
            $gstInputLedger = $accountingService->getOrCreateStandardLedger($companyId, 'GST Input Tax Credit (Receivable)', 'Current Assets', 'Assets');
            $supplier = Supplier::find($data['supplier_id']);

            if ($supplier && $supplier->ledger) {
                $lines = [
                    ['ledger_id' => $purchaseLedger->id, 'debit' => $netItemsPurchases, 'credit' => 0, 'narration' => 'Purchase of materials net of discount'],
                    ['ledger_id' => $gstInputLedger->id, 'debit' => $taxTotal, 'credit' => 0, 'narration' => 'Input GST'],
                ];

                // Additional charge lines
                if ($freight > 0) {
                    $freightLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Freight & Cartage Inward', 'Direct Expenses & Purchases', 'Expenses');
                    $lines[] = ['ledger_id' => $freightLedger->id, 'debit' => $freight, 'credit' => 0, 'narration' => 'Freight charges on purchase'];
                }
                if ($packaging > 0) {
                    $packLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Packaging & Forwarding Expenses', 'Direct Expenses & Purchases', 'Expenses');
                    $lines[] = ['ledger_id' => $packLedger->id, 'debit' => $packaging, 'credit' => 0, 'narration' => 'Packaging charges on purchase'];
                }
                if ($loading > 0) {
                    $loadLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Loading & Unloading Charges', 'Direct Expenses & Purchases', 'Expenses');
                    $lines[] = ['ledger_id' => $loadLedger->id, 'debit' => $loading, 'credit' => 0, 'narration' => 'Loading/unloading charges'];
                }
                if ($other > 0) {
                    $otherLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Procurement Other Charges', 'Direct Expenses & Purchases', 'Expenses');
                    $lines[] = ['ledger_id' => $otherLedger->id, 'debit' => $other, 'credit' => 0, 'narration' => 'Other procurement charges'];
                }
                if ($roundOff != 0) {
                    $roundLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Round-Off Adjustment', 'Administrative & Indirect Expenses', 'Expenses');
                    if ($roundOff > 0) {
                        $lines[] = ['ledger_id' => $roundLedger->id, 'debit' => $roundOff, 'credit' => 0, 'narration' => 'Round-off adjustment'];
                    } else {
                        $lines[] = ['ledger_id' => $roundLedger->id, 'debit' => 0, 'credit' => abs($roundOff), 'narration' => 'Round-off adjustment'];
                    }
                }

                $lines[] = ['ledger_id' => $supplier->ledger->id, 'debit' => 0, 'credit' => $grandTotal, 'narration' => 'Supplier Bill Payable'];

                $jv = $accountingService->createJournalEntry(
                    $companyId, null, 'Purchase', $data['invoice_date'],
                    "Purchase Invoice {$data['invoice_number']} from {$supplier->name}",
                    $lines,
                    'PurchaseEntry', $pe->id, $purNo
                );

                $pe->update(['journal_entry_id' => $jv->id]);

                // Process immediate payment if selected
                if ($paidAmount > 0 && !empty($data['payment_ledger_id'])) {
                    $payNo = 'PAY-' . date('Y') . '-' . sprintf('%04d', SupplierPayment::where('company_id', $companyId)->count() + 1);
                    $bankLedger = Ledger::find($data['payment_ledger_id']);

                    $sp = SupplierPayment::create([
                        'company_id' => $companyId,
                        'supplier_id' => $data['supplier_id'],
                        'purchase_entry_id' => $pe->id,
                        'payment_number' => $payNo,
                        'payment_date' => $data['invoice_date'],
                        'payment_amount' => $paidAmount,
                        'payment_mode' => $paymentType === 'Cash' ? 'Cash' : ($paymentType === 'UPI' ? 'UPI' : 'Bank Transfer'),
                        'payment_ledger_id' => $data['payment_ledger_id'],
                        'transaction_reference' => $data['transaction_reference'] ?? null,
                        'remarks' => "Immediate payment at bill entry for Invoice {$data['invoice_number']}",
                        'created_by' => auth()->id(),
                    ]);

                    if ($bankLedger) {
                        $payJv = $accountingService->createJournalEntry(
                            $companyId, null, 'Payment', $data['invoice_date'],
                            "Disbursement to {$supplier->name} for Bill {$data['invoice_number']}",
                            [
                                ['ledger_id' => $supplier->ledger->id, 'debit' => $paidAmount, 'credit' => 0, 'narration' => "Payment for Bill {$data['invoice_number']}"],
                                ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => $paidAmount, 'narration' => "Disbursement via {$paymentType}"],
                            ],
                            'SupplierPayment', $sp->id, $payNo
                        );
                        $sp->update(['journal_entry_id' => $payJv->id]);
                    }
                }
            }

            AuditLog::log('purchase', 'create_entry', "Recorded Purchase Entry {$purNo} from invoice {$data['invoice_number']}");
            return redirect()->route('purchase.entries.show', $pe)->with('success', "Purchase invoice {$purNo} recorded and stock/accounts updated.");
        });
    }

    public function entryEdit(PurchaseEntry $entry)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($entry->company_id != $companyId) {
            abort(403);
        }

        $entry->load(['supplier', 'godown', 'items.item.unit', 'items.unit']);
        $suppliers = Supplier::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->with('unit')->get();
        $units = \App\Models\Unit::where('company_id', $companyId)->where('status', 'active')->get();

        return view('purchase.entries.edit', compact('entry', 'suppliers', 'godowns', 'items', 'units'));
    }

    public function entryUpdate(Request $request, PurchaseEntry $entry)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($entry->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('company_id', $companyId),
            ],
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'payment_type' => 'required|in:Credit,Cash,Bank,UPI',
            'discount_amount' => 'nullable|numeric|min:0',
            'freight_charges' => 'nullable|numeric|min:0',
            'packaging_charges' => 'nullable|numeric|min:0',
            'loading_charges' => 'nullable|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'round_off' => 'nullable|numeric',
            'charge_allocation_method' => 'nullable|in:value,quantity,equal,none',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'items.*.unit_id' => [
                'nullable',
                Rule::exists('units', 'id')->where('company_id', $companyId),
            ],
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($data['supplier_id']);
        $godown = Godown::where('company_id', $companyId)->findOrFail($data['godown_id']);

        return DB::transaction(function () use ($companyId, $entry, $data) {
            $inventoryService = new InventoryService();

            // 1. Revert previous stock inward for this entry's items (using base_quantity if available)
            foreach ($entry->items as $oldItem) {
                $revertQty = (float)($oldItem->base_quantity ?: $oldItem->quantity);
                $inventoryService->revertInward(
                    $entry->godown_id,
                    $oldItem->item_id,
                    $revertQty,
                    $oldItem->batch_number ?? 'DEFAULT'
                );
            }

            // 2. Compute new totals
            $billDiscount = (float)($data['discount_amount'] ?? 0);
            $freight = (float)($data['freight_charges'] ?? 0);
            $packaging = (float)($data['packaging_charges'] ?? 0);
            $loading = (float)($data['loading_charges'] ?? 0);
            $other = (float)($data['other_charges'] ?? 0);
            $roundOff = (float)($data['round_off'] ?? 0);
            $totalAdditionalCharges = $freight + $packaging + $loading + $other;
            $allocMethod = $data['charge_allocation_method'] ?? 'value';

            $subtotal = 0;
            $totalItemDiscount = 0;
            $taxTotal = 0;
            $processedItems = [];
            $totalBaseQty = 0;

            foreach ($data['items'] as $it) {
                $itemModel = Item::with('unit')->find($it['item_id']);
                $conversion = \App\Services\UnitConversionService::convertToBaseQuantity($itemModel, (float)$it['quantity'], $it['unit_id'] ?? null);

                $lineGross = (float)$it['quantity'] * (float)$it['rate'];
                $lineDisc = (float)($it['discount_amount'] ?? 0);
                $taxable = max(0, $lineGross - $lineDisc);
                $taxRate = (float)($it['tax_percentage'] ?? 0);
                $lineTax = $taxable * ($taxRate / 100);

                $subtotal += $lineGross;
                $totalItemDiscount += $lineDisc;
                $taxTotal += $lineTax;
                $totalBaseQty += $conversion['base_quantity'];

                $processedItems[] = [
                    'raw' => $it,
                    'item' => $itemModel,
                    'conversion' => $conversion,
                    'line_gross' => $lineGross,
                    'line_disc' => $lineDisc,
                    'taxable' => $taxable,
                    'tax_rate' => $taxRate,
                    'line_tax' => $lineTax,
                ];
            }

            $totalDiscount = $totalItemDiscount + $billDiscount;
            $netItemsPurchases = max(0, $subtotal - $totalDiscount);
            $grandTotal = max(0, $netItemsPurchases + $taxTotal + $totalAdditionalCharges + $roundOff);
            $paidAmount = (float)$entry->paid_amount;
            $balanceAmount = max(0, $grandTotal - $paidAmount);
            $paymentStatus = $balanceAmount == 0 && $paidAmount > 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            // 3. Update existing PurchaseEntry record (preserves ID, purchase_number, created_by)
            $entry->update([
                'godown_id' => $data['godown_id'],
                'supplier_id' => $data['supplier_id'],
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'payment_type' => $data['payment_type'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'discount_amount' => $totalDiscount,
                'freight_charges' => $freight,
                'packaging_charges' => $packaging,
                'loading_charges' => $loading,
                'other_charges' => $other,
                'round_off' => $roundOff,
                'charge_allocation_method' => $allocMethod,
                'grand_total' => $grandTotal,
                'balance_amount' => $balanceAmount,
                'payment_status' => $paymentStatus,
                'remarks' => $data['remarks'] ?? null,
            ]);

            // 4. Delete old line items and insert new line items
            $entry->items()->delete();
            $itemCount = count($processedItems);

            foreach ($processedItems as $p) {
                $it = $p['raw'];
                $conversion = $p['conversion'];
                $batchNo = !empty($it['batch_number']) ? $it['batch_number'] : 'DEFAULT';

                // Calculate allocated landed charge for inventory unit cost
                $allocatedCharge = 0;
                if ($totalAdditionalCharges > 0 && $allocMethod !== 'none') {
                    if ($allocMethod === 'quantity' && $totalBaseQty > 0) {
                        $allocatedCharge = ($conversion['base_quantity'] / $totalBaseQty) * $totalAdditionalCharges;
                    } elseif ($allocMethod === 'equal' && $itemCount > 0) {
                        $allocatedCharge = $totalAdditionalCharges / $itemCount;
                    } else { // 'value' default
                        $allocatedCharge = $netItemsPurchases > 0 ? ($p['taxable'] / $netItemsPurchases) * $totalAdditionalCharges : 0;
                    }
                }

                $baseQty = max(0.001, $conversion['base_quantity']);
                $landedUnitCost = ($p['taxable'] + $allocatedCharge) / $baseQty;

                PurchaseEntryItem::create([
                    'purchase_entry_id' => $entry->id,
                    'item_id' => $it['item_id'],
                    'unit_id' => $conversion['unit_id'],
                    'entered_quantity' => $conversion['entered_quantity'],
                    'base_quantity' => $conversion['base_quantity'],
                    'batch_number' => $batchNo,
                    'expiry_date' => $it['expiry_date'] ?? null,
                    'quantity' => $conversion['base_quantity'],
                    'rate' => $it['rate'],
                    'tax_percentage' => $p['tax_rate'],
                    'tax_amount' => $p['line_tax'],
                    'discount_amount' => $p['line_disc'],
                    'line_total' => $p['taxable'] + $p['line_tax'],
                ]);

                // Record inward to destination batch
                $inventoryService->recordInward(
                    $companyId, null, $data['godown_id'], $it['item_id'],
                    $conversion['base_quantity'], $landedUnitCost, $batchNo, $it['expiry_date'] ?? null,
                    'PurchaseEntry', $entry->id, $entry->purchase_number, "Inward for Purchase {$data['invoice_number']} (Updated)"
                );
            }

            // 5. Update Double-Entry Accounting
            $accountingService = new AccountingService();
            $purchaseLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Purchase Account', 'Direct Expenses & Purchases', 'Expenses');
            $gstInputLedger = $accountingService->getOrCreateStandardLedger($companyId, 'GST Input Tax Credit (Receivable)', 'Current Assets', 'Assets');
            $supplier = Supplier::find($data['supplier_id']);

            if ($supplier && $supplier->ledger && $entry->journalEntry) {
                $lines = [
                    ['ledger_id' => $purchaseLedger->id, 'debit' => $netItemsPurchases, 'credit' => 0, 'narration' => 'Purchase of materials net of discount'],
                    ['ledger_id' => $gstInputLedger->id, 'debit' => $taxTotal, 'credit' => 0, 'narration' => 'Input GST'],
                ];

                if ($freight > 0) {
                    $freightLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Freight & Cartage Inward', 'Direct Expenses & Purchases', 'Expenses');
                    $lines[] = ['ledger_id' => $freightLedger->id, 'debit' => $freight, 'credit' => 0, 'narration' => 'Freight charges on purchase'];
                }
                if ($packaging > 0) {
                    $packLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Packaging & Forwarding Expenses', 'Direct Expenses & Purchases', 'Expenses');
                    $lines[] = ['ledger_id' => $packLedger->id, 'debit' => $packaging, 'credit' => 0, 'narration' => 'Packaging charges on purchase'];
                }
                if ($loading > 0) {
                    $loadLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Loading & Unloading Charges', 'Direct Expenses & Purchases', 'Expenses');
                    $lines[] = ['ledger_id' => $loadLedger->id, 'debit' => $loading, 'credit' => 0, 'narration' => 'Loading/unloading charges'];
                }
                if ($other > 0) {
                    $otherLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Procurement Other Charges', 'Direct Expenses & Purchases', 'Expenses');
                    $lines[] = ['ledger_id' => $otherLedger->id, 'debit' => $other, 'credit' => 0, 'narration' => 'Other procurement charges'];
                }
                if ($roundOff != 0) {
                    $roundLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Round-Off Adjustment', 'Administrative & Indirect Expenses', 'Expenses');
                    if ($roundOff > 0) {
                        $lines[] = ['ledger_id' => $roundLedger->id, 'debit' => $roundOff, 'credit' => 0, 'narration' => 'Round-off adjustment'];
                    } else {
                        $lines[] = ['ledger_id' => $roundLedger->id, 'debit' => 0, 'credit' => abs($roundOff), 'narration' => 'Round-off adjustment'];
                    }
                }

                $lines[] = ['ledger_id' => $supplier->ledger->id, 'debit' => 0, 'credit' => $grandTotal, 'narration' => 'Supplier Bill Payable'];

                $accountingService->updateJournalEntry(
                    $entry->journalEntry,
                    $data['invoice_date'],
                    "Purchase Invoice {$data['invoice_number']} from {$supplier->name} (Updated)",
                    $lines
                );
            }

            AuditLog::log('purchase', 'update_entry', "Updated Purchase Entry {$entry->purchase_number} from invoice {$data['invoice_number']}");
            return redirect()->route('purchase.entries.show', $entry)->with('success', "Purchase bill {$entry->purchase_number} updated successfully.");
        });
    }

    public function entryShow(PurchaseEntry $entry)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($entry->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company purchase bill.');
        }

        $entry->load(['supplier', 'godown', 'items.item.unit', 'journalEntry.lines.ledger', 'payments', 'returns.items.item']);
        return view('purchase.entries.show', compact('entry'));
    }

    // ==================== PURCHASE RETURNS / DEBIT NOTES ====================
    public function returns(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = PurchaseReturn::where('company_id', $companyId)->with(['supplier', 'purchaseEntry', 'items.item']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->get('supplier_id'));
        }

        $returns = $query->latest('return_date')->paginate(15);
        $suppliers = Supplier::where('company_id', $companyId)->where('status', 'active')->get();

        return view('purchase.returns.index', compact('returns', 'suppliers'));
    }

    public function returnCreate(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $suppliers = Supplier::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->with('unit')->get();

        $peId = $request->get('purchase_entry_id');
        $purchaseEntry = $peId ? PurchaseEntry::where('company_id', $companyId)->with(['items.item.unit', 'supplier'])->find($peId) : null;

        return view('purchase.returns.create', compact('suppliers', 'godowns', 'items', 'purchaseEntry'));
    }

    public function returnStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('company_id', $companyId),
            ],
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'purchase_entry_id' => [
                'nullable',
                Rule::exists('purchase_entries', 'id')->where('company_id', $companyId),
            ],
            'return_date' => 'required|date',
            'return_reason' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'items.*.unit_id' => [
                'nullable',
                Rule::exists('units', 'id')->where('company_id', $companyId),
            ],
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.return_quantity' => 'required|numeric|min:0.001',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($data['supplier_id']);
        $godown = Godown::where('company_id', $companyId)->findOrFail($data['godown_id']);

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['return_date'])
            ->where('end_date', '>=', $data['return_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->withErrors(['return_date' => 'Cannot record purchase return in a closed financial year period.']);
        }

        return DB::transaction(function () use ($companyId, $data) {
            $subtotal = 0;
            $taxTotal = 0;
            $processedItems = [];

            foreach ($data['items'] as $it) {
                $itemModel = Item::with('unit')->find($it['item_id']);
                $conversion = \App\Services\UnitConversionService::convertToBaseQuantity($itemModel, (float)$it['return_quantity'], $it['unit_id'] ?? null);

                $lineSub = (float)$it['return_quantity'] * (float)$it['rate'];
                $taxRate = (float)($it['tax_percentage'] ?? 0);
                $lineTax = $lineSub * ($taxRate / 100);

                $subtotal += $lineSub;
                $taxTotal += $lineTax;

                $processedItems[] = [
                    'raw' => $it,
                    'conversion' => $conversion,
                    'line_sub' => $lineSub,
                    'line_tax' => $lineTax,
                ];
            }

            $grandTotal = $subtotal + $taxTotal;
            $retNo = 'PRT-' . date('Y') . '-' . sprintf('%04d', PurchaseReturn::where('company_id', $companyId)->count() + 1);

            $pr = PurchaseReturn::create([
                'company_id' => $companyId,
                'supplier_id' => $data['supplier_id'],
                'godown_id' => $data['godown_id'],
                'purchase_entry_id' => $data['purchase_entry_id'] ?? null,
                'return_number' => $retNo,
                'return_date' => $data['return_date'],
                'total_amount' => $grandTotal,
                'return_reason' => $data['return_reason'],
                'status' => 'completed',
                'created_by' => auth()->id(),
            ]);

            $inventoryService = new InventoryService();
            foreach ($processedItems as $p) {
                $it = $p['raw'];
                $conversion = $p['conversion'];
                $batchNo = !empty($it['batch_number']) ? $it['batch_number'] : 'DEFAULT';

                PurchaseReturnItem::create([
                    'purchase_return_id' => $pr->id,
                    'item_id' => $it['item_id'],
                    'unit_id' => $conversion['unit_id'],
                    'entered_quantity' => $conversion['entered_quantity'],
                    'base_quantity' => $conversion['base_quantity'],
                    'batch_number' => $batchNo,
                    'return_quantity' => $conversion['base_quantity'],
                    'rate' => $it['rate'],
                    'tax_amount' => $p['line_tax'],
                    'total_amount' => $p['line_sub'] + $p['line_tax'],
                ]);

                // Deduct stock outward for purchase return using base quantity
                $inventoryService->recordOutward(
                    $companyId, null, $data['godown_id'], $it['item_id'],
                    (float)$conversion['base_quantity'], $batchNo,
                    'PurchaseReturn', $pr->id, $retNo,
                    "Purchase Return {$retNo}: {$data['return_reason']}"
                );
            }

            // Post Debit Note Journal Entry
            $accountingService = new AccountingService();
            $purchaseReturnLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Purchase Returns & Allowances', 'Direct Expenses & Purchases', 'Expenses');
            $gstInputLedger = $accountingService->getOrCreateStandardLedger($companyId, 'GST Input Tax Credit (Receivable)', 'Current Assets', 'Assets');
            $supplier = Supplier::find($data['supplier_id']);

            if ($supplier && $supplier->ledger) {
                $jv = $accountingService->createJournalEntry(
                    $companyId, null, 'Purchase Return', $data['return_date'],
                    "Debit Note {$retNo} to {$supplier->name}: {$data['return_reason']}",
                    [
                        ['ledger_id' => $supplier->ledger->id, 'debit' => $grandTotal, 'credit' => 0, 'narration' => 'Debit Note - Return to Supplier'],
                        ['ledger_id' => $purchaseReturnLedger->id, 'debit' => 0, 'credit' => $subtotal, 'narration' => 'Purchase Return Credit'],
                        ['ledger_id' => $gstInputLedger->id, 'debit' => 0, 'credit' => $taxTotal, 'narration' => 'Reverse Input GST Credit'],
                    ],
                    'PurchaseReturn', $pr->id, $retNo
                );

                $pr->update(['journal_entry_id' => $jv->id]);

                // If linked to a purchase bill, optionally reduce balance
                if (!empty($data['purchase_entry_id'])) {
                    $pe = PurchaseEntry::find($data['purchase_entry_id']);
                    if ($pe && $pe->balance_amount > 0) {
                        $pe->balance_amount = max(0, $pe->balance_amount - $grandTotal);
                        if ($pe->balance_amount == 0 && $pe->paid_amount > 0) {
                            $pe->payment_status = 'paid';
                        }
                        $pe->save();
                    }
                }
            }

            AuditLog::log('purchase', 'create_return', "Created Purchase Return {$retNo} to Supplier #{$data['supplier_id']}");
            return redirect()->route('purchase.returns.show', $pr)->with('success', "Debit Note {$retNo} issued, stock outward deducted, and supplier ledger debited.");
        });
    }

    public function returnShow(PurchaseReturn $return)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($return->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company purchase return.');
        }

        $return->load(['supplier', 'godown', 'purchaseEntry', 'items.item.unit', 'journalEntry.lines.ledger']);
        return view('purchase.returns.show', compact('return'));
    }

    // ==================== SUPPLIER PAYMENTS ====================
    public function payments()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $payments = SupplierPayment::where('company_id', $companyId)
            ->with(['supplier', 'purchaseEntry', 'paymentLedger'])
            ->latest('payment_date')
            ->paginate(15);
        $suppliers = Supplier::where('company_id', $companyId)->where('status', 'active')->get();
        $bankLedgers = Ledger::where('company_id', $companyId)->where('is_bank_or_cash', true)->get();

        return view('purchase.payments.index', compact('payments', 'suppliers', 'bankLedgers'));
    }

    public function paymentStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')->where('company_id', $companyId),
            ],
            'purchase_entry_id' => [
                'nullable',
                Rule::exists('purchase_entries', 'id')->where('company_id', $companyId),
            ],
            'payment_date' => 'required|date',
            'payment_amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:Cash,Bank Transfer,Cheque,UPI,Other',
            'payment_ledger_id' => [
                'required',
                Rule::exists('ledgers', 'id')->where('company_id', $companyId),
            ],
            'transaction_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $supplier = Supplier::where('company_id', $companyId)->findOrFail($data['supplier_id']);

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['payment_date'])
            ->where('end_date', '>=', $data['payment_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->withErrors(['payment_date' => 'Cannot record supplier payment in a closed financial year period.']);
        }

        $payNo = 'PAY-' . date('Y') . '-' . sprintf('%04d', SupplierPayment::where('company_id', $companyId)->count() + 1);

        $sp = SupplierPayment::create([
            'company_id' => $companyId,
            'supplier_id' => $data['supplier_id'],
            'purchase_entry_id' => $data['purchase_entry_id'] ?? null,
            'payment_number' => $payNo,
            'payment_date' => $data['payment_date'],
            'payment_amount' => $data['payment_amount'],
            'payment_mode' => $data['payment_mode'],
            'payment_ledger_id' => $data['payment_ledger_id'],
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Post to Accounting
        $accountingService = new AccountingService();
        $supplier = Supplier::find($data['supplier_id']);
        $bankLedger = Ledger::find($data['payment_ledger_id']);

        if ($supplier && $supplier->ledger && $bankLedger) {
            $jv = $accountingService->createJournalEntry(
                $companyId, null, 'Payment', $data['payment_date'],
                "Disbursement to {$supplier->name} via {$data['payment_mode']}",
                [
                    ['ledger_id' => $supplier->ledger->id, 'debit' => $data['payment_amount'], 'credit' => 0, 'narration' => 'Payment to Supplier'],
                    ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => $data['payment_amount'], 'narration' => 'Credit Bank/Cash Account'],
                ],
                'SupplierPayment', $sp->id, $payNo
            );

            $sp->update(['journal_entry_id' => $jv->id]);

            // Update purchase entry paid amount if selected
            if (!empty($data['purchase_entry_id'])) {
                $pe = PurchaseEntry::find($data['purchase_entry_id']);
                if ($pe) {
                    $pe->paid_amount += $data['payment_amount'];
                    $pe->balance_amount = max(0, $pe->grand_total - $pe->paid_amount);
                    $pe->payment_status = $pe->balance_amount == 0 ? 'paid' : 'partial';
                    $pe->save();
                }
            }
        }

        AuditLog::log('purchase', 'make_payment', "Made supplier payment {$payNo} of amount {$data['payment_amount']}");
        return back()->with('success', "Payment {$payNo} recorded and journal entry posted.");
    }
}


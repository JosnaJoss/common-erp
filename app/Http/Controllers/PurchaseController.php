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
            'department_id' => 'required|exists:departments,id',
            'requested_by' => 'required|string|max:100',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'expected_delivery_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
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
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_request_id' => 'nullable|exists:purchase_requests,id',
            'po_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'terms_and_conditions' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

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
            'supplier_id' => 'required|exists:suppliers,id',
            'godown_id' => 'required|exists:godowns,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'received_date' => 'required|date',
            'received_by' => 'required|string|max:100',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.ordered_quantity' => 'nullable|numeric|min:0',
            'items.*.received_quantity' => 'required|numeric|min:0.001',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
        ]);

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
        $items = Item::where('company_id', $companyId)->where('status', 'active')->get();

        return view('purchase.entries.create', compact('suppliers', 'godowns', 'items'));
    }

    public function entryStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'godown_id' => 'required|exists:godowns,id',
            'invoice_number' => 'required|string|max:100',
            'invoice_date' => 'required|date',
            'payment_type' => 'required|in:Cash,Credit,Bank',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.batch_number' => 'nullable|string|max:50',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        return DB::transaction(function () use ($companyId, $data) {
            $subtotal = 0;
            $taxTotal = 0;

            foreach ($data['items'] as $it) {
                $lineSub = $it['quantity'] * $it['rate'];
                $taxRate = $it['tax_percentage'] ?? 0;
                $lineTax = $lineSub * ($taxRate / 100);

                $subtotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $grandTotal = $subtotal + $taxTotal;
            $purNo = 'PUR-' . date('Y') . '-' . sprintf('%04d', PurchaseEntry::where('company_id', $companyId)->count() + 1);

            $pe = PurchaseEntry::create([
                'company_id' => $companyId,
                'godown_id' => $data['godown_id'],
                'supplier_id' => $data['supplier_id'],
                'purchase_number' => $purNo,
                'invoice_number' => $data['invoice_number'],
                'invoice_date' => $data['invoice_date'],
                'payment_type' => $data['payment_type'],
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'discount_amount' => 0,
                'grand_total' => $grandTotal,
                'paid_amount' => 0,
                'balance_amount' => $grandTotal,
                'payment_status' => 'unpaid',
                'stock_updated' => true,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $inventoryService = new InventoryService();
            foreach ($data['items'] as $it) {
                $lineSub = $it['quantity'] * $it['rate'];
                $taxRate = $it['tax_percentage'] ?? 0;
                $lineTax = $lineSub * ($taxRate / 100);
                $batchNo = !empty($it['batch_number']) ? $it['batch_number'] : 'DEFAULT';

                PurchaseEntryItem::create([
                    'purchase_entry_id' => $pe->id,
                    'item_id' => $it['item_id'],
                    'batch_number' => $batchNo,
                    'expiry_date' => $it['expiry_date'] ?? null,
                    'quantity' => $it['quantity'],
                    'rate' => $it['rate'],
                    'tax_percentage' => $taxRate,
                    'tax_amount' => $lineTax,
                    'discount_amount' => 0,
                    'line_total' => $lineSub + $lineTax,
                ]);

                // Update stock inward
                $inventoryService->recordInward(
                    $companyId, null, $data['godown_id'], $it['item_id'],
                    $it['quantity'], $it['rate'], $batchNo, $it['expiry_date'] ?? null,
                    'PurchaseEntry', $pe->id, $purNo, "Inward for Purchase {$data['invoice_number']}"
                );
            }

            // Post to Double-Entry Accounting
            $accountingService = new AccountingService();
            $purchaseLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Purchase Account', 'Direct Expenses & Purchases', 'Expenses');
            $gstInputLedger = $accountingService->getOrCreateStandardLedger($companyId, 'GST Input Tax Credit (Receivable)', 'Current Assets', 'Assets');
            $supplier = Supplier::find($data['supplier_id']);

            if ($supplier && $supplier->ledger) {
                $jv = $accountingService->createJournalEntry(
                    $companyId, null, 'Purchase', $data['invoice_date'],
                    "Purchase Invoice {$data['invoice_number']} from {$supplier->name}",
                    [
                        ['ledger_id' => $purchaseLedger->id, 'debit' => $subtotal, 'credit' => 0, 'narration' => 'Purchase of materials'],
                        ['ledger_id' => $gstInputLedger->id, 'debit' => $taxTotal, 'credit' => 0, 'narration' => 'Input GST'],
                        ['ledger_id' => $supplier->ledger->id, 'debit' => 0, 'credit' => $grandTotal, 'narration' => 'Supplier Bill Payable'],
                    ],
                    'PurchaseEntry', $pe->id, $purNo
                );

                $pe->update(['journal_entry_id' => $jv->id]);
            }

            AuditLog::log('purchase', 'create_entry', "Recorded Purchase Entry {$purNo} from invoice {$data['invoice_number']}");
            return redirect()->route('purchase.entries.show', $pe)->with('success', "Purchase invoice {$purNo} recorded and stock/accounts updated.");
        });
    }

    public function entryShow(PurchaseEntry $entry)
    {
        $entry->load(['supplier', 'godown', 'items.item.unit', 'journalEntry.lines.ledger', 'payments']);
        return view('purchase.entries.show', compact('entry'));
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
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_entry_id' => 'nullable|exists:purchase_entries,id',
            'payment_date' => 'required|date',
            'payment_amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:Cash,Bank Transfer,Cheque,UPI,Other',
            'payment_ledger_id' => 'required|exists:ledgers,id',
            'transaction_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

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


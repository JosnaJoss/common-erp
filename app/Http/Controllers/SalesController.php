<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerCollection;
use App\Models\Godown;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\Route;
use App\Models\SalesDelivery;
use App\Models\SalesDeliveryItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Vehicle;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    // ==================== SALES ORDERS ====================
    public function orders(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = SalesOrder::where('company_id', $companyId)->with(['customer', 'items.item']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $orders = $query->latest()->paginate(15);
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->get();

        return view('sales.orders.index', compact('orders', 'customers'));
    }

    public function orderCreate()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->whereIn('item_type', ['sales_item', 'both'])->where('status', 'active')->get();

        return view('sales.orders.create', compact('customers', 'items'));
    }

    public function orderStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.selling_price' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $orderNo = 'SO-' . date('Y') . '-' . sprintf('%04d', SalesOrder::where('company_id', $companyId)->count() + 1);

        $subtotal = 0;
        $totalTax = 0;

        foreach ($data['items'] as $it) {
            $lineSub = $it['quantity'] * $it['selling_price'];
            $taxRate = $it['tax_percentage'] ?? 0;
            $lineTax = $lineSub * ($taxRate / 100);

            $subtotal += $lineSub;
            $totalTax += $lineTax;
        }

        $grandTotal = $subtotal + $totalTax;

        $so = SalesOrder::create([
            'company_id' => $companyId,
            'customer_id' => $data['customer_id'],
            'order_number' => $orderNo,
            'order_date' => $data['order_date'],
            'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
            'subtotal' => $subtotal,
            'tax_amount' => $totalTax,
            'discount_amount' => 0,
            'grand_total' => $grandTotal,
            'status' => 'confirmed',
            'notes' => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        foreach ($data['items'] as $it) {
            $lineSub = $it['quantity'] * $it['selling_price'];
            $taxRate = $it['tax_percentage'] ?? 0;
            $lineTax = $lineSub * ($taxRate / 100);

            SalesOrderItem::create([
                'sales_order_id' => $so->id,
                'item_id' => $it['item_id'],
                'quantity' => $it['quantity'],
                'selling_price' => $it['selling_price'],
                'tax_percentage' => $taxRate,
                'tax_amount' => $lineTax,
                'discount_amount' => 0,
                'line_total' => $lineSub + $lineTax,
            ]);
        }

        AuditLog::log('sales', 'create_so', "Created Sales Order {$orderNo}");
        return redirect()->route('sales.orders.show', $so)->with('success', "Sales Order {$orderNo} confirmed.");
    }

    public function orderShow(SalesOrder $order)
    {
        $order->load(['customer', 'items.item.unit', 'invoices', 'deliveries']);
        return view('sales.orders.show', compact('order'));
    }

    // ==================== SALES INVOICES ====================
    public function invoices(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = SalesInvoice::where('company_id', $companyId)->with(['customer', 'godown', 'journalEntry']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        $invoices = $query->latest('invoice_date')->paginate(15);
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->get();

        return view('sales.invoices.index', compact('invoices', 'customers'));
    }

    public function invoiceCreate(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $soId = $request->get('so_id');
        $so = $soId ? SalesOrder::where('company_id', $companyId)->with(['items.item', 'customer'])->find($soId) : null;

        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->whereIn('item_type', ['sales_item', 'both'])->where('status', 'active')->get();

        return view('sales.invoices.create', compact('so', 'customers', 'godowns', 'items'));
    }

    public function invoiceStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'godown_id' => 'required|exists:godowns,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'invoice_date' => 'required|date',
            'payment_terms' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.batch_number' => 'nullable|string|max:50',
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
            $invNo = 'INV-' . date('Y') . '-' . sprintf('%04d', SalesInvoice::where('company_id', $companyId)->count() + 1);

            $invoice = SalesInvoice::create([
                'company_id' => $companyId,
                'godown_id' => $data['godown_id'],
                'customer_id' => $data['customer_id'],
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'invoice_number' => $invNo,
                'invoice_date' => $data['invoice_date'],
                'payment_terms' => $data['payment_terms'] ?? 'Immediate',
                'due_date' => $data['due_date'] ?? null,
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

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $it['item_id'],
                    'batch_number' => $batchNo,
                    'quantity' => $it['quantity'],
                    'rate' => $it['rate'],
                    'tax_percentage' => $taxRate,
                    'tax_amount' => $lineTax,
                    'discount_amount' => 0,
                    'line_total' => $lineSub + $lineTax,
                ]);

                // Update stock outward
                $inventoryService->recordOutward(
                    $companyId, null, $data['godown_id'], $it['item_id'],
                    $it['quantity'], $batchNo, 'SalesInvoice', $invoice->id, $invNo,
                    "Dispatched on Invoice {$invNo}"
                );
            }

            // Post to Double-Entry Accounting
            $accountingService = new AccountingService();
            $salesLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Sales Account', 'Sales & Operating Revenue', 'Revenue');
            $gstOutputLedger = $accountingService->getOrCreateStandardLedger($companyId, 'GST Output Tax Liability (Payable)', 'Duties & Taxes', 'Liabilities');
            $customer = Customer::find($data['customer_id']);

            if ($customer && $customer->ledger) {
                $jv = $accountingService->createJournalEntry(
                    $companyId, null, 'Sales', $data['invoice_date'],
                    "Sales Invoice {$invNo} to {$customer->name}",
                    [
                        ['ledger_id' => $customer->ledger->id, 'debit' => $grandTotal, 'credit' => 0, 'narration' => 'Receivable from Customer'],
                        ['ledger_id' => $salesLedger->id, 'debit' => 0, 'credit' => $subtotal, 'narration' => 'Sales Revenue'],
                        ['ledger_id' => $gstOutputLedger->id, 'debit' => 0, 'credit' => $taxTotal, 'narration' => 'Output GST Payable'],
                    ],
                    'SalesInvoice', $invoice->id, $invNo
                );

                $invoice->update(['journal_entry_id' => $jv->id]);
            }

            if (!empty($data['sales_order_id'])) {
                SalesOrder::where('id', $data['sales_order_id'])->update(['status' => 'invoiced']);
            }

            AuditLog::log('sales', 'create_invoice', "Created Sales Invoice {$invNo}");
            return redirect()->route('sales.invoices.show', $invoice)->with('success', "Invoice {$invNo} issued, stock outward deducted, and ledger updated.");
        });
    }

    public function invoiceShow(SalesInvoice $invoice)
    {
        $invoice->load(['customer', 'godown', 'items.item.unit', 'journalEntry.lines.ledger', 'collections', 'company']);
        return view('sales.invoices.show', compact('invoice'));
    }

    public function invoicePrint(SalesInvoice $invoice)
    {
        $invoice->load(['customer', 'godown', 'items.item.unit', 'company']);
        return view('sales.invoices.print', compact('invoice'));
    }

    // ==================== DELIVERIES & DISPATCH ====================
    public function deliveries()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $deliveries = SalesDelivery::where('company_id', $companyId)
            ->with(['customer', 'vehicle', 'route'])
            ->latest('delivery_date')
            ->paginate(15);
        $vehicles = Vehicle::where('company_id', $companyId)->where('status', 'active')->get();
        $routes = Route::where('company_id', $companyId)->where('status', 'active')->get();
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->get();

        return view('sales.deliveries.index', compact('deliveries', 'vehicles', 'routes', 'customers'));
    }

    public function deliveryStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_invoice_id' => 'nullable|exists:sales_invoices,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'assigned_driver' => 'required|string|max:100',
            'delivery_date' => 'required|date',
            'delivery_address' => 'required|string',
            'delivery_remarks' => 'nullable|string',
        ]);

        $delNo = 'DEL-' . date('Y') . '-' . sprintf('%04d', SalesDelivery::where('company_id', $companyId)->count() + 1);

        $del = SalesDelivery::create([
            'company_id' => $companyId,
            'customer_id' => $data['customer_id'],
            'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
            'vehicle_id' => $data['vehicle_id'],
            'route_id' => $data['route_id'],
            'assigned_driver' => $data['assigned_driver'],
            'delivery_number' => $delNo,
            'delivery_date' => $data['delivery_date'],
            'delivery_address' => $data['delivery_address'],
            'delivery_status' => 'assigned',
            'delivery_remarks' => $data['delivery_remarks'] ?? null,
            'created_by' => auth()->id(),
        ]);

        AuditLog::log('sales', 'create_delivery', "Created Delivery Assignment {$delNo}");
        return back()->with('success', "Delivery {$delNo} assigned successfully.");
    }

    public function deliveryUpdateStatus(Request $request, SalesDelivery $delivery)
    {
        $data = $request->validate([
            'delivery_status' => 'required|in:assigned,in_transit,delivered,failed',
        ]);

        $delivery->update(['delivery_status' => $data['delivery_status']]);
        AuditLog::log('sales', 'update_delivery', "Updated Delivery {$delivery->delivery_number} status to {$data['delivery_status']}");
        return back()->with('success', "Delivery status updated to {$data['delivery_status']}.");
    }

    // ==================== CUSTOMER COLLECTIONS / RECEIPTS ====================
    public function collections()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $collections = CustomerCollection::where('company_id', $companyId)
            ->with(['customer', 'invoice', 'receiptLedger'])
            ->latest('collection_date')
            ->paginate(15);
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->get();
        $bankLedgers = Ledger::where('company_id', $companyId)->where('is_bank_or_cash', true)->get();

        return view('sales.collections.index', compact('collections', 'customers', 'bankLedgers'));
    }

    public function collectionStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_invoice_id' => 'nullable|exists:sales_invoices,id',
            'collection_date' => 'required|date',
            'collection_amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:Cash,Bank Transfer,Cheque,UPI,Other',
            'receipt_ledger_id' => 'required|exists:ledgers,id',
            'transaction_reference' => 'nullable|string|max:100',
            'collected_by' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $colNo = 'COL-' . date('Y') . '-' . sprintf('%04d', CustomerCollection::where('company_id', $companyId)->count() + 1);

        $collection = CustomerCollection::create([
            'company_id' => $companyId,
            'customer_id' => $data['customer_id'],
            'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
            'collection_number' => $colNo,
            'collection_date' => $data['collection_date'],
            'collection_amount' => $data['collection_amount'],
            'payment_mode' => $data['payment_mode'],
            'receipt_ledger_id' => $data['receipt_ledger_id'],
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'collected_by' => $data['collected_by'] ?? null,
            'remarks' => $data['remarks'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Accounting Entry
        $accountingService = new AccountingService();
        $customer = Customer::find($data['customer_id']);
        $bankLedger = Ledger::find($data['receipt_ledger_id']);

        if ($customer && $customer->ledger && $bankLedger) {
            $jv = $accountingService->createJournalEntry(
                $companyId, null, 'Receipt', $data['collection_date'],
                "Receipt from {$customer->name} via {$data['payment_mode']}",
                [
                    ['ledger_id' => $bankLedger->id, 'debit' => $data['collection_amount'], 'credit' => 0, 'narration' => 'Receipt in Bank/Cash Account'],
                    ['ledger_id' => $customer->ledger->id, 'debit' => 0, 'credit' => $data['collection_amount'], 'narration' => 'Credit Customer Account'],
                ],
                'CustomerCollection', $collection->id, $colNo
            );

            $collection->update(['journal_entry_id' => $jv->id]);

            if (!empty($data['sales_invoice_id'])) {
                $inv = SalesInvoice::find($data['sales_invoice_id']);
                if ($inv) {
                    $inv->paid_amount += $data['collection_amount'];
                    $inv->balance_amount = max(0, $inv->grand_total - $inv->paid_amount);
                    $inv->payment_status = $inv->balance_amount == 0 ? 'paid' : 'partial';
                    $inv->save();
                }
            }
        }

        AuditLog::log('sales', 'create_collection', "Collected {$data['collection_amount']} from Customer #{$data['customer_id']}");
        return back()->with('success', "Collection {$colNo} recorded and journal entry posted.");
    }
}


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
use Illuminate\Validation\Rule;

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
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => [
                'required',
                Rule::exists('items', 'id')->where('company_id', $companyId),
            ],
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.selling_price' => 'required|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $customer = Customer::where('company_id', $companyId)->findOrFail($data['customer_id']);

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
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($order->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company sales order.');
        }

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
        $items = Item::where('company_id', $companyId)->whereIn('item_type', ['sales_item', 'both'])->where('status', 'active')->with('unit')->get();
        $units = \App\Models\Unit::where('company_id', $companyId)->where('status', 'active')->get();
        $bankLedgers = Ledger::where('company_id', $companyId)->where('is_bank_or_cash', true)->where('status', 'active')->get();

        return view('sales.invoices.create', compact('so', 'customers', 'godowns', 'items', 'units', 'bankLedgers'));
    }

    public function invoiceStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'sales_order_id' => [
                'nullable',
                Rule::exists('sales_orders', 'id')->where('company_id', $companyId),
            ],
            'invoice_date' => 'required|date',
            'payment_terms' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'payment_mode' => 'required|in:Credit,Cash,Bank Transfer,UPI,Cheque',
            'discount_amount' => 'nullable|numeric|min:0',
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
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
            'receipt_ledger_id' => [
                'nullable',
                'required_if:payment_mode,Cash,Bank Transfer,UPI,Cheque',
                Rule::exists('ledgers', 'id')->where('company_id', $companyId),
            ],
            'amount_collected' => 'nullable|numeric|min:0',
            'transaction_reference' => 'nullable|string|max:100',
        ]);

        $customer = Customer::where('company_id', $companyId)->findOrFail($data['customer_id']);
        $godown = Godown::where('company_id', $companyId)->findOrFail($data['godown_id']);

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['invoice_date'])
            ->where('end_date', '>=', $data['invoice_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->withErrors(['invoice_date' => 'Cannot record sales invoice in a closed financial year period.']);
        }

        return DB::transaction(function () use ($companyId, $data) {
            $billDiscount = (float)($data['discount_amount'] ?? 0);
            $subtotal = 0;
            $totalItemDiscount = 0;
            $taxTotal = 0;
            $processedItems = [];

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
            $grandTotal = max(0, ($subtotal - $totalDiscount) + $taxTotal);
            $invNo = 'INV-' . date('Y') . '-' . sprintf('%04d', SalesInvoice::where('company_id', $companyId)->count() + 1);

            $paidAmount = 0;
            $paymentMode = $data['payment_mode'] ?? 'Credit';
            if ($paymentMode !== 'Credit' && !empty($data['receipt_ledger_id'])) {
                $paidAmount = min($grandTotal, (float)($data['amount_collected'] ?? $grandTotal));
            }

            $balanceAmount = max(0, $grandTotal - $paidAmount);
            $paymentStatus = $balanceAmount == 0 && $paidAmount > 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $invoice = SalesInvoice::create([
                'company_id' => $companyId,
                'godown_id' => $data['godown_id'],
                'customer_id' => $data['customer_id'],
                'sales_order_id' => $data['sales_order_id'] ?? null,
                'invoice_number' => $invNo,
                'invoice_date' => $data['invoice_date'],
                'payment_terms' => $data['payment_terms'] ?? ($paymentMode === 'Credit' ? 'Net 30 Days' : 'Immediate (' . $paymentMode . ')'),
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'discount_amount' => $totalDiscount,
                'grand_total' => $grandTotal,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'payment_status' => $paymentStatus,
                'stock_updated' => true,
                'remarks' => $data['remarks'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $inventoryService = new InventoryService();

            foreach ($processedItems as $p) {
                $it = $p['raw'];
                $conversion = $p['conversion'];
                $batchNo = !empty($it['batch_number']) ? $it['batch_number'] : 'DEFAULT';

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $it['item_id'],
                    'unit_id' => $conversion['unit_id'],
                    'entered_quantity' => $conversion['entered_quantity'],
                    'base_quantity' => $conversion['base_quantity'],
                    'batch_number' => $batchNo,
                    'quantity' => $conversion['base_quantity'],
                    'rate' => $it['rate'],
                    'tax_percentage' => $p['tax_rate'],
                    'tax_amount' => $p['line_tax'],
                    'discount_amount' => $p['line_disc'],
                    'line_total' => $p['taxable'] + $p['line_tax'],
                ]);

                // Update stock outward using normalized base quantity
                $inventoryService->recordOutward(
                    $companyId, null, $data['godown_id'], $it['item_id'],
                    (float)$conversion['base_quantity'], $batchNo, 'SalesInvoice', $invoice->id, $invNo,
                    "Dispatched on Invoice {$invNo}"
                );
            }

            // Post to Double-Entry Accounting
            $accountingService = new AccountingService();
            $salesLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Sales Account', 'Sales & Operating Revenue', 'Revenue');
            $gstOutputLedger = $accountingService->getOrCreateStandardLedger($companyId, 'GST Output Tax Liability (Payable)', 'Duties & Taxes', 'Liabilities');
            $customer = Customer::find($data['customer_id']);

            if ($customer && $customer->ledger) {
                $netSales = max(0, $subtotal - $totalDiscount);
                $jv = $accountingService->createJournalEntry(
                    $companyId, null, 'Sales', $data['invoice_date'],
                    "Sales Invoice {$invNo} to {$customer->name}",
                    [
                        ['ledger_id' => $customer->ledger->id, 'debit' => $grandTotal, 'credit' => 0, 'narration' => 'Receivable from Customer'],
                        ['ledger_id' => $salesLedger->id, 'debit' => 0, 'credit' => $netSales, 'narration' => 'Sales Revenue net of discounts'],
                        ['ledger_id' => $gstOutputLedger->id, 'debit' => 0, 'credit' => $taxTotal, 'narration' => 'Output GST Payable'],
                    ],
                    'SalesInvoice', $invoice->id, $invNo
                );

                $invoice->update(['journal_entry_id' => $jv->id]);

                // Record immediate payment collection if cash/bank/upi
                if ($paidAmount > 0 && !empty($data['receipt_ledger_id'])) {
                    $colNo = 'COL-' . date('Y') . '-' . sprintf('%04d', CustomerCollection::where('company_id', $companyId)->count() + 1);
                    $receiptLedger = Ledger::find($data['receipt_ledger_id']);

                    $collection = CustomerCollection::create([
                        'company_id' => $companyId,
                        'customer_id' => $data['customer_id'],
                        'sales_invoice_id' => $invoice->id,
                        'collection_number' => $colNo,
                        'collection_date' => $data['invoice_date'],
                        'collection_amount' => $paidAmount,
                        'payment_mode' => $paymentMode,
                        'receipt_ledger_id' => $data['receipt_ledger_id'],
                        'transaction_reference' => $data['transaction_reference'] ?? null,
                        'collected_by' => auth()->user()->name ?? 'Counter Staff',
                        'remarks' => "Immediate counter collection for Invoice {$invNo}",
                        'created_by' => auth()->id(),
                    ]);

                    if ($receiptLedger) {
                        $receiptJv = $accountingService->createJournalEntry(
                            $companyId, null, 'Receipt', $data['invoice_date'],
                            "Counter collection from {$customer->name} for Invoice {$invNo}",
                            [
                                ['ledger_id' => $receiptLedger->id, 'debit' => $paidAmount, 'credit' => 0, 'narration' => "Receipt via {$paymentMode}"],
                                ['ledger_id' => $customer->ledger->id, 'debit' => 0, 'credit' => $paidAmount, 'narration' => "Settlement of Invoice {$invNo}"],
                            ],
                            'CustomerCollection', $collection->id, $colNo
                        );
                        $collection->update(['journal_entry_id' => $receiptJv->id]);
                    }
                }
            }

            if (!empty($data['sales_order_id'])) {
                SalesOrder::where('id', $data['sales_order_id'])->update(['status' => 'invoiced']);
            }

            AuditLog::log('sales', 'create_invoice', "Created Sales Invoice {$invNo}");
            return redirect()->route('sales.invoices.show', $invoice)->with('success', "Invoice {$invNo} issued, stock outward deducted, and accounts posted.");
        });
    }

    public function invoiceEdit(SalesInvoice $invoice)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($invoice->company_id != $companyId) {
            abort(403);
        }

        $invoice->load(['customer', 'godown', 'items.item.unit', 'items.unit']);
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->whereIn('item_type', ['sales_item', 'both'])->where('status', 'active')->with('unit')->get();
        $units = \App\Models\Unit::where('company_id', $companyId)->where('status', 'active')->get();

        return view('sales.invoices.edit', compact('invoice', 'customers', 'godowns', 'items', 'units'));
    }

    public function invoiceUpdate(Request $request, SalesInvoice $invoice)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($invoice->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company sales invoice.');
        }

        $data = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'invoice_date' => 'required|date',
            'payment_terms' => 'nullable|string|max:100',
            'due_date' => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
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
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.rate' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $customer = Customer::where('company_id', $companyId)->findOrFail($data['customer_id']);
        $godown = Godown::where('company_id', $companyId)->findOrFail($data['godown_id']);

        return DB::transaction(function () use ($companyId, $invoice, $data) {
            $inventoryService = new InventoryService();

            // 1. Revert previous outward quantities back to stock batches
            foreach ($invoice->items as $oldItem) {
                $revertQty = (float)($oldItem->base_quantity ?: $oldItem->quantity);
                $inventoryService->revertOutward(
                    $invoice->godown_id,
                    $oldItem->item_id,
                    $revertQty,
                    $oldItem->batch_number ?? 'DEFAULT'
                );
            }

            // 2. Compute new totals
            $billDiscount = (float)($data['discount_amount'] ?? 0);
            $subtotal = 0;
            $totalItemDiscount = 0;
            $taxTotal = 0;
            $processedItems = [];

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
            $grandTotal = max(0, ($subtotal - $totalDiscount) + $taxTotal);
            $paidAmount = (float)$invoice->paid_amount;
            $balanceAmount = max(0, $grandTotal - $paidAmount);
            $paymentStatus = $balanceAmount == 0 && $paidAmount > 0 ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            // 3. Update existing SalesInvoice record
            $invoice->update([
                'godown_id' => $data['godown_id'],
                'customer_id' => $data['customer_id'],
                'invoice_date' => $data['invoice_date'],
                'payment_terms' => $data['payment_terms'] ?? $invoice->payment_terms,
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'discount_amount' => $totalDiscount,
                'grand_total' => $grandTotal,
                'balance_amount' => $balanceAmount,
                'payment_status' => $paymentStatus,
                'remarks' => $data['remarks'] ?? null,
            ]);

            // 4. Delete old line items and insert new line items
            $invoice->items()->delete();

            foreach ($processedItems as $p) {
                $it = $p['raw'];
                $conversion = $p['conversion'];
                $batchNo = !empty($it['batch_number']) ? $it['batch_number'] : 'DEFAULT';

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $invoice->id,
                    'item_id' => $it['item_id'],
                    'unit_id' => $conversion['unit_id'],
                    'entered_quantity' => $conversion['entered_quantity'],
                    'base_quantity' => $conversion['base_quantity'],
                    'batch_number' => $batchNo,
                    'quantity' => $conversion['base_quantity'],
                    'rate' => $it['rate'],
                    'tax_percentage' => $p['tax_rate'],
                    'tax_amount' => $p['line_tax'],
                    'discount_amount' => $p['line_disc'],
                    'line_total' => $p['taxable'] + $p['line_tax'],
                ]);

                // Deduct updated outward stock using base quantity
                $inventoryService->recordOutward(
                    $companyId, null, $data['godown_id'], $it['item_id'],
                    (float)$conversion['base_quantity'], $batchNo, 'SalesInvoice', $invoice->id, $invoice->invoice_number,
                    "Dispatched on Invoice {$invoice->invoice_number} (Updated)"
                );
            }

            // 5. Update Double-Entry Accounting
            $accountingService = new AccountingService();
            $salesLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Sales Account', 'Sales & Operating Revenue', 'Revenue');
            $gstOutputLedger = $accountingService->getOrCreateStandardLedger($companyId, 'GST Output Tax Liability (Payable)', 'Duties & Taxes', 'Liabilities');
            $customer = Customer::find($data['customer_id']);

            if ($customer && $customer->ledger && $invoice->journalEntry) {
                $netSales = max(0, $subtotal - $totalDiscount);
                $accountingService->updateJournalEntry(
                    $invoice->journalEntry,
                    $data['invoice_date'],
                    "Sales Invoice {$invoice->invoice_number} to {$customer->name} (Updated)",
                    [
                        ['ledger_id' => $customer->ledger->id, 'debit' => $grandTotal, 'credit' => 0, 'narration' => 'Receivable from Customer'],
                        ['ledger_id' => $salesLedger->id, 'debit' => 0, 'credit' => $netSales, 'narration' => 'Sales Revenue net of discounts'],
                        ['ledger_id' => $gstOutputLedger->id, 'debit' => 0, 'credit' => $taxTotal, 'narration' => 'Output GST Payable'],
                    ]
                );
            }

            AuditLog::log('sales', 'update_invoice', "Updated Sales Invoice {$invoice->invoice_number}");
            return redirect()->route('sales.invoices.show', $invoice)->with('success', "Sales invoice {$invoice->invoice_number} updated successfully.");
        });
    }

    public function invoiceShow(SalesInvoice $invoice)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($invoice->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company sales invoice.');
        }

        $invoice->load(['customer', 'godown', 'items.item.unit', 'journalEntry.lines.ledger', 'collections', 'company', 'returns.items.item']);
        return view('sales.invoices.show', compact('invoice'));
    }

    // ==================== SALES RETURNS / CREDIT NOTES ====================
    public function returns(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = SalesReturn::where('company_id', $companyId)->with(['customer', 'invoice', 'godown', 'items.item']);

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->get('customer_id'));
        }

        $returns = $query->latest('return_date')->paginate(15);
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->get();

        return view('sales.returns.index', compact('returns', 'customers'));
    }

    public function returnCreate(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $customers = Customer::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->whereIn('item_type', ['sales_item', 'both'])->where('status', 'active')->with('unit')->get();

        $invId = $request->get('sales_invoice_id');
        $invoice = $invId ? SalesInvoice::where('company_id', $companyId)->with(['items.item.unit', 'customer'])->find($invId) : null;

        return view('sales.returns.create', compact('customers', 'godowns', 'items', 'invoice'));
    }

    public function returnStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],
            'godown_id' => [
                'required',
                Rule::exists('godowns', 'id')->where('company_id', $companyId),
            ],
            'sales_invoice_id' => [
                'nullable',
                Rule::exists('sales_invoices', 'id')->where('company_id', $companyId),
            ],
            'return_date' => 'required|date',
            'return_reason' => 'required|string',
            'stock_condition' => 'required|in:accept_to_stock,damaged_scrap',
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

        $customer = Customer::where('company_id', $companyId)->findOrFail($data['customer_id']);
        $godown = Godown::where('company_id', $companyId)->findOrFail($data['godown_id']);

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['return_date'])
            ->where('end_date', '>=', $data['return_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->withErrors(['return_date' => 'Cannot record sales return in a closed financial year period.']);
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
                    'item' => $itemModel,
                    'conversion' => $conversion,
                    'line_sub' => $lineSub,
                    'tax_rate' => $taxRate,
                    'line_tax' => $lineTax,
                ];
            }

            $grandTotal = $subtotal + $taxTotal;
            $retNo = 'SRT-' . date('Y') . '-' . sprintf('%04d', SalesReturn::where('company_id', $companyId)->count() + 1);

            $sr = SalesReturn::create([
                'company_id' => $companyId,
                'customer_id' => $data['customer_id'],
                'godown_id' => $data['godown_id'],
                'sales_invoice_id' => $data['sales_invoice_id'] ?? null,
                'return_number' => $retNo,
                'return_date' => $data['return_date'],
                'total_amount' => $grandTotal,
                'return_reason' => $data['return_reason'],
                'stock_condition' => $data['stock_condition'],
                'status' => 'approved',
                'created_by' => auth()->id(),
            ]);

            $inventoryService = new InventoryService();
            foreach ($processedItems as $p) {
                $it = $p['raw'];
                $conversion = $p['conversion'];
                $lineTax = $p['line_tax'];
                $batchNo = !empty($it['batch_number']) ? $it['batch_number'] : 'DEFAULT';

                SalesReturnItem::create([
                    'sales_return_id' => $sr->id,
                    'item_id' => $it['item_id'],
                    'unit_id' => $conversion['unit_id'],
                    'entered_quantity' => $conversion['entered_quantity'],
                    'base_quantity' => $conversion['base_quantity'],
                    'batch_number' => $batchNo,
                    'return_quantity' => $conversion['base_quantity'],
                    'rate' => $it['rate'],
                    'tax_amount' => $lineTax,
                    'total_amount' => $p['line_sub'] + $lineTax,
                ]);

                // If condition is accepted back to stock, inward it back to godown using base quantity
                if ($data['stock_condition'] === 'accept_to_stock') {
                    $inventoryService->recordInward(
                        $companyId, null, $data['godown_id'], $it['item_id'],
                        (float)$conversion['base_quantity'], (float)$it['rate'], $batchNo, null,
                        'SalesReturn', $sr->id, $retNo,
                        "Sales return inward {$retNo} from Customer #{$data['customer_id']}"
                    );
                }
            }

            // Post Credit Note Journal Entry
            $accountingService = new AccountingService();
            $salesReturnLedger = $accountingService->getOrCreateStandardLedger($companyId, 'Sales Returns & Inward Allowances', 'Sales & Operating Revenue', 'Revenue');
            $gstOutputLedger = $accountingService->getOrCreateStandardLedger($companyId, 'GST Output Tax Liability (Payable)', 'Duties & Taxes', 'Liabilities');
            $customer = Customer::find($data['customer_id']);

            if ($customer && $customer->ledger) {
                $jv = $accountingService->createJournalEntry(
                    $companyId, null, 'Sales Return', $data['return_date'],
                    "Credit Note {$retNo} to {$customer->name}: {$data['return_reason']}",
                    [
                        ['ledger_id' => $salesReturnLedger->id, 'debit' => $subtotal, 'credit' => 0, 'narration' => 'Sales Return Debit'],
                        ['ledger_id' => $gstOutputLedger->id, 'debit' => $taxTotal, 'credit' => 0, 'narration' => 'Reverse Output GST Liability'],
                        ['ledger_id' => $customer->ledger->id, 'debit' => 0, 'credit' => $grandTotal, 'narration' => 'Credit Note to Customer'],
                    ],
                    'SalesReturn', $sr->id, $retNo
                );

                $sr->update(['journal_entry_id' => $jv->id]);

                // If linked to an invoice, reduce balance
                if (!empty($data['sales_invoice_id'])) {
                    $inv = SalesInvoice::find($data['sales_invoice_id']);
                    if ($inv && $inv->balance_amount > 0) {
                        $inv->balance_amount = max(0, $inv->balance_amount - $grandTotal);
                        if ($inv->balance_amount == 0 && $inv->paid_amount > 0) {
                            $inv->payment_status = 'paid';
                        }
                        $inv->save();
                    }
                }
            }

            AuditLog::log('sales', 'create_return', "Created Sales Return {$retNo} from Customer #{$data['customer_id']}");
            return redirect()->route('sales.returns.show', $sr)->with('success', "Credit Note {$retNo} issued and customer ledger credited.");
        });
    }

    public function returnShow(SalesReturn $return)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($return->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company sales return.');
        }

        $return->load(['customer', 'invoice', 'godown', 'items.item.unit', 'journalEntry.lines.ledger']);
        return view('sales.returns.show', compact('return'));
    }

    public function invoicePrint(SalesInvoice $invoice)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($invoice->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company sales invoice.');
        }

        $invoice->load(['customer', 'godown', 'items.item.unit', 'company']);
        return view('sales.invoices.print', compact('invoice'));
    }

    public function invoicePdf(SalesInvoice $invoice)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($invoice->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company sales invoice.');
        }

        $invoice->load(['customer', 'godown', 'items.item.unit', 'company']);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('sales.invoices.pdf', compact('invoice'));
        return $pdf->download("invoice_{$invoice->invoice_number}.pdf");
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
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],
            'sales_invoice_id' => [
                'nullable',
                Rule::exists('sales_invoices', 'id')->where('company_id', $companyId),
            ],
            'vehicle_id' => [
                'required',
                Rule::exists('vehicles', 'id')->where('company_id', $companyId),
            ],
            'route_id' => [
                'required',
                Rule::exists('routes', 'id')->where('company_id', $companyId),
            ],
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
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($delivery->company_id != $companyId) {
            abort(403, 'Unauthorized access to cross-company delivery.');
        }

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
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('company_id', $companyId),
            ],
            'sales_invoice_id' => [
                'nullable',
                Rule::exists('sales_invoices', 'id')->where('company_id', $companyId),
            ],
            'collection_date' => 'required|date',
            'collection_amount' => 'required|numeric|min:1',
            'payment_mode' => 'required|in:Cash,Bank Transfer,Cheque,UPI,Other',
            'receipt_ledger_id' => [
                'required',
                Rule::exists('ledgers', 'id')->where('company_id', $companyId),
            ],
            'transaction_reference' => 'nullable|string|max:100',
            'collected_by' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $customer = Customer::where('company_id', $companyId)->findOrFail($data['customer_id']);

        $isClosedPeriod = \App\Models\FinancialYear::where('company_id', $companyId)
            ->where('start_date', '<=', $data['collection_date'])
            ->where('end_date', '>=', $data['collection_date'])
            ->where('is_closed', true)
            ->exists();
        if ($isClosedPeriod) {
            return back()->withInput()->withErrors(['collection_date' => 'Cannot record collection in a closed financial year period.']);
        }

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


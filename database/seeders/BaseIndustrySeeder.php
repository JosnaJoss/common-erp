<?php

namespace Database\Seeders;

use App\Models\AccountGroup;
use App\Models\Attendance;
use App\Models\Bom;
use App\Models\BomItem;
use App\Models\Branch;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\CrmLead;
use App\Models\Customer;
use App\Models\CustomerCollection;
use App\Models\Department;
use App\Models\FinancialYear;
use App\Models\Godown;
use App\Models\GoodsReceiptNote;
use App\Models\GrnItem;
use App\Models\Holiday;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LeaveApplication;
use App\Models\Ledger;
use App\Models\Location;
use App\Models\Overtime;
use App\Models\Payroll;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\PurchaseEntry;
use App\Models\PurchaseEntryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\QualityCheck;
use App\Models\Role;
use App\Models\Route;
use App\Models\SalesDelivery;
use App\Models\SalesDeliveryItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

abstract class BaseIndustrySeeder extends Seeder
{
    protected InventoryService $inventoryService;
    protected AccountingService $accountingService;

    public function __construct()
    {
        $this->inventoryService = new InventoryService();
        $this->accountingService = new AccountingService();
    }

    /**
     * Create or retrieve company and setup modules
     */
    protected function initCompany(string $code, array $companyData, array $modules): Company
    {
        $company = Company::firstOrCreate(['code' => $code], $companyData);

        $allModuleKeys = ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'];
        foreach ($allModuleKeys as $modKey) {
            $isEnabled = in_array($modKey, $modules);
            CompanyModule::firstOrCreate(
                ['company_id' => $company->id, 'module_key' => $modKey],
                ['is_enabled' => $isEnabled]
            );
        }

        return $company;
    }

    /**
     * Setup Financial Year and standard double-entry Chart of Accounts
     */
    protected function initChartOfAccounts(Company $company, string $bankLedgerName, string $accNo, string $ifsc): array
    {
        // 1. Financial Year
        FinancialYear::firstOrCreate([
            'company_id' => $company->id,
            'title' => 'FY 2026-2027',
        ], [
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_closed' => false,
        ]);

        // 2. Account Groups
        $assetGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Current Assets'],
            ['nature' => 'Assets', 'code' => 'CA-01']
        );

        $fixedAssetGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Fixed Assets'],
            ['nature' => 'Assets', 'code' => 'FA-01']
        );

        $bankCashGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Bank & Cash Accounts'],
            ['nature' => 'Assets', 'code' => 'BK-01', 'parent_id' => $assetGroup->id]
        );

        $debtorGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Sundry Debtors (Customers)'],
            ['nature' => 'Assets', 'code' => 'SD-01', 'parent_id' => $assetGroup->id]
        );

        $liabilityGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Current Liabilities'],
            ['nature' => 'Liabilities', 'code' => 'CL-01']
        );

        $creditorGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Sundry Creditors (Suppliers)'],
            ['nature' => 'Liabilities', 'code' => 'SC-01', 'parent_id' => $liabilityGroup->id]
        );

        $dutiesTaxesGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Duties & Taxes'],
            ['nature' => 'Liabilities', 'code' => 'DT-01', 'parent_id' => $liabilityGroup->id]
        );

        $equityGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Capital & Reserves'],
            ['nature' => 'Equity', 'code' => 'EQ-01']
        );

        $revenueGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Sales & Operating Revenue'],
            ['nature' => 'Revenue', 'code' => 'REV-01']
        );

        $expenseGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Direct Expenses & Purchases'],
            ['nature' => 'Expenses', 'code' => 'EXP-01']
        );

        $indirectExpenseGroup = AccountGroup::firstOrCreate(
            ['company_id' => $company->id, 'name' => 'Administrative & Indirect Expenses'],
            ['nature' => 'Expenses', 'code' => 'EXP-02']
        );

        // 3. Standard Ledgers
        $cashLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Cash in Hand'], [
            'account_group_id' => $bankCashGroup->id,
            'opening_balance' => 150000.00,
            'balance_type' => 'debit',
            'current_balance' => 150000.00,
            'is_bank_or_cash' => true,
            'status' => 'active',
        ]);

        $bankLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => $bankLedgerName], [
            'account_group_id' => $bankCashGroup->id,
            'opening_balance' => 1250000.00,
            'balance_type' => 'debit',
            'current_balance' => 1250000.00,
            'is_bank_or_cash' => true,
            'bank_account_no' => $accNo,
            'bank_ifsc' => $ifsc,
            'bank_name' => explode(' ', $bankLedgerName)[0] ?? 'Bank',
            'status' => 'active',
        ]);

        $salesLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Sales Account'], [
            'account_group_id' => $revenueGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'credit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $salesReturnLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Sales Return Account'], [
            'account_group_id' => $revenueGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'debit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $purchaseLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Purchase Account'], [
            'account_group_id' => $expenseGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'debit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $purchaseReturnLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Purchase Return Account'], [
            'account_group_id' => $expenseGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'credit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $salaryLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Salaries & Staff Wages'], [
            'account_group_id' => $indirectExpenseGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'debit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $rentLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Facility Rent & Lease'], [
            'account_group_id' => $indirectExpenseGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'debit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $utilityLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Electricity & Utilities'], [
            'account_group_id' => $indirectExpenseGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'debit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $freightLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Freight & Logistics Outward'], [
            'account_group_id' => $indirectExpenseGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'debit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $packagingLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Packaging Materials & Consumables'], [
            'account_group_id' => $expenseGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'debit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $gstInputLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'GST Input Tax Credit (Receivable)'], [
            'account_group_id' => $assetGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'debit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $gstOutputLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'GST Output Tax Liability (Payable)'], [
            'account_group_id' => $dutiesTaxesGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'credit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $pfLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'PF & ESI Payable'], [
            'account_group_id' => $dutiesTaxesGroup->id,
            'opening_balance' => 0.00,
            'balance_type' => 'credit',
            'current_balance' => 0.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        // Share Capital (Balancing Equity: Bank 1250000 + Cash 150000 = 1400000)
        $capitalLedger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Promoters Share Capital'], [
            'account_group_id' => $equityGroup->id,
            'opening_balance' => 1400000.00,
            'balance_type' => 'credit',
            'current_balance' => 1400000.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        return compact(
            'assetGroup', 'fixedAssetGroup', 'bankCashGroup', 'debtorGroup',
            'liabilityGroup', 'creditorGroup', 'dutiesTaxesGroup', 'equityGroup',
            'revenueGroup', 'expenseGroup', 'indirectExpenseGroup',
            'cashLedger', 'bankLedger', 'salesLedger', 'salesReturnLedger',
            'purchaseLedger', 'purchaseReturnLedger', 'salaryLedger', 'rentLedger',
            'utilityLedger', 'freightLedger', 'packagingLedger', 'gstInputLedger',
            'gstOutputLedger', 'pfLedger', 'capitalLedger'
        );
    }

    /**
     * Create Supplier along with dedicated Creditor Ledger
     */
    protected function createSupplierWithLedger(Company $company, AccountGroup $creditorGroup, array $data): Supplier
    {
        $opening = $data['opening_balance'] ?? 0;
        $ledger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => $data['name']], [
            'account_group_id' => $creditorGroup->id,
            'opening_balance' => $opening,
            'balance_type' => 'credit',
            'current_balance' => $opening,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        return Supplier::firstOrCreate(['company_id' => $company->id, 'code' => $data['code']], [
            'name' => $data['name'],
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'city' => $data['city'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
            'pan_number' => $data['pan_number'] ?? null,
            'account_group_id' => $creditorGroup->id,
            'ledger_id' => $ledger->id,
            'opening_balance' => $opening,
            'opening_balance_date' => '2026-04-01',
            'account_type' => 'credit',
            'current_balance' => $opening,
            'status' => 'active',
        ]);
    }

    /**
     * Create Customer along with dedicated Debtor Ledger
     */
    protected function createCustomerWithLedger(Company $company, AccountGroup $debtorGroup, array $data): Customer
    {
        $opening = $data['opening_balance'] ?? 0;
        $ledger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => $data['name']], [
            'account_group_id' => $debtorGroup->id,
            'opening_balance' => $opening,
            'balance_type' => 'debit',
            'current_balance' => $opening,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        return Customer::firstOrCreate(['company_id' => $company->id, 'code' => $data['code']], [
            'name' => $data['name'],
            'shop_name' => $data['shop_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'pincode' => $data['pincode'] ?? null,
            'city' => $data['city'] ?? null,
            'territory' => $data['territory'] ?? null,
            'route_id' => $data['route_id'] ?? null,
            'location_id' => $data['location_id'] ?? null,
            'gst_number' => $data['gst_number'] ?? null,
            'pan_number' => $data['pan_number'] ?? null,
            'account_group_id' => $debtorGroup->id,
            'ledger_id' => $ledger->id,
            'price_level' => $data['price_level'] ?? 'normal',
            'opening_balance' => $opening,
            'opening_balance_date' => '2026-04-01',
            'account_type' => 'debit',
            'current_balance' => $opening,
            'status' => 'active',
        ]);
    }

    /**
     * Record Initial Opening Stock Batch
     */
    protected function recordOpeningStock(
        Company $company,
        Branch $branch,
        Godown $godown,
        Item $item,
        float $quantity,
        float $unitCost,
        string $batchNo = 'DEFAULT',
        ?string $expiryDate = null
    ): void {
        $existing = StockBatch::where('godown_id', $godown->id)
            ->where('item_id', $item->id)
            ->where('batch_no', $batchNo)
            ->first();

        if (!$existing) {
            $this->inventoryService->recordInward(
                $company->id,
                $branch->id,
                $godown->id,
                $item->id,
                $quantity,
                $unitCost,
                $batchNo,
                $expiryDate,
                'OpeningStock',
                null,
                'OPN-' . $item->code,
                "Opening Stock for {$item->name}"
            );
        }
    }

    /**
     * Complete Purchase Workflow: PO -> GRN -> Purchase Entry -> Stock Inward -> Supplier Payment -> Ledger Post
     */
    protected function recordPurchaseWorkflow(
        Company $company,
        Branch $branch,
        Godown $godown,
        Supplier $supplier,
        array $itemRows,
        array $params,
        array $ledgers
    ): PurchaseEntry {
        $poNumber = $params['po_number'];
        $grnNumber = $params['grn_number'];
        $purchaseNumber = $params['purchase_number'];
        $invoiceNumber = $params['invoice_number'];
        $date = $params['date'];

        // 1. Purchase Order
        $subtotal = 0;
        $taxAmount = 0;
        foreach ($itemRows as $row) {
            $lineSub = $row['quantity'] * $row['rate'];
            $lineTax = $lineSub * (($row['tax_percent'] ?? 18) / 100);
            $subtotal += $lineSub;
            $taxAmount += $lineTax;
        }
        $grandTotal = $subtotal + $taxAmount;
        $paidAmount = $params['paid_amount'] ?? 0;
        $balanceAmount = $grandTotal - $paidAmount;
        $paymentStatus = $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

        $po = PurchaseOrder::firstOrCreate(['company_id' => $company->id, 'po_number' => $poNumber], [
            'branch_id' => $branch->id,
            'supplier_id' => $supplier->id,
            'po_date' => $date,
            'expected_delivery_date' => Carbon::parse($date)->addDays(5)->toDateString(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total_amount' => $grandTotal,
            'status' => 'received',
            'terms_and_conditions' => 'Standard procurement terms, payment per credit agreement.',
        ]);

        foreach ($itemRows as $row) {
            $lineSub = $row['quantity'] * $row['rate'];
            $taxPct = $row['tax_percent'] ?? 18;
            $lineTax = $lineSub * ($taxPct / 100);
            PurchaseOrderItem::firstOrCreate([
                'purchase_order_id' => $po->id,
                'item_id' => $row['item']->id,
            ], [
                'quantity' => $row['quantity'],
                'received_quantity' => $row['quantity'],
                'rate' => $row['rate'],
                'tax_percentage' => $taxPct,
                'tax_amount' => $lineTax,
                'discount_amount' => 0,
                'total_amount' => $lineSub + $lineTax,
            ]);
        }

        // 2. Goods Receipt Note (GRN)
        $grn = GoodsReceiptNote::firstOrCreate(['company_id' => $company->id, 'grn_number' => $grnNumber], [
            'branch_id' => $branch->id,
            'godown_id' => $godown->id,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po->id,
            'received_date' => $date,
            'received_by' => $params['received_by'] ?? 'Warehouse Receiving Team',
            'status' => 'stock_updated',
            'remarks' => "GRN verified against {$poNumber}",
        ]);

        foreach ($itemRows as $row) {
            GrnItem::firstOrCreate([
                'goods_receipt_note_id' => $grn->id,
                'item_id' => $row['item']->id,
            ], [
                'ordered_quantity' => $row['quantity'],
                'received_quantity' => $row['quantity'],
                'accepted_quantity' => $row['quantity'],
                'rejected_quantity' => 0,
                'batch_number' => $row['batch_no'] ?? 'DEFAULT',
                'expiry_date' => $row['expiry_date'] ?? null,
            ]);
        }

        // 3. Purchase Entry (Bill)
        $pe = PurchaseEntry::firstOrCreate(['company_id' => $company->id, 'purchase_number' => $purchaseNumber], [
            'branch_id' => $branch->id,
            'godown_id' => $godown->id,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po->id,
            'goods_receipt_note_id' => $grn->id,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $date,
            'payment_type' => $paidAmount >= $grandTotal ? 'Bank' : 'Credit',
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'grand_total' => $grandTotal,
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'payment_status' => $paymentStatus,
            'stock_updated' => true,
            'remarks' => $params['remarks'] ?? "Procurement bill from {$supplier->name}",
        ]);

        foreach ($itemRows as $row) {
            $lineSub = $row['quantity'] * $row['rate'];
            $taxPct = $row['tax_percent'] ?? 18;
            $lineTax = $lineSub * ($taxPct / 100);
            $batchNo = $row['batch_no'] ?? 'DEFAULT';

            PurchaseEntryItem::firstOrCreate([
                'purchase_entry_id' => $pe->id,
                'item_id' => $row['item']->id,
            ], [
                'batch_number' => $batchNo,
                'weight' => $row['item']->weight ?? 1.0,
                'quantity' => $row['quantity'],
                'rate' => $row['rate'],
                'tax_percentage' => $taxPct,
                'tax_amount' => $lineTax,
                'discount_amount' => 0,
                'line_total' => $lineSub + $lineTax,
            ]);

            // Stock Inward
            $this->inventoryService->recordInward(
                $company->id,
                $branch->id,
                $godown->id,
                $row['item']->id,
                $row['quantity'],
                $row['rate'],
                $batchNo,
                $row['expiry_date'] ?? null,
                'PurchaseEntry',
                $pe->id,
                $purchaseNumber,
                "Inward via bill {$invoiceNumber}"
            );
        }

        // 4. Double Entry for Purchase
        $suppLedger = $supplier->ledger;
        if ($suppLedger && !$pe->journal_entry_id) {
            $lines = [
                ['ledger_id' => $ledgers['purchaseLedger']->id, 'debit' => $subtotal, 'credit' => 0, 'narration' => "Purchase of goods via {$invoiceNumber}"],
            ];
            if ($taxAmount > 0) {
                $lines[] = ['ledger_id' => $ledgers['gstInputLedger']->id, 'debit' => $taxAmount, 'credit' => 0, 'narration' => 'GST Input Tax Credit'];
            }
            $lines[] = ['ledger_id' => $suppLedger->id, 'debit' => 0, 'credit' => $grandTotal, 'narration' => "Payable to {$supplier->name}"];

            $jvPurchase = $this->accountingService->createJournalEntry(
                $company->id,
                $branch->id,
                'Purchase',
                $date,
                "Purchase bill {$invoiceNumber} from {$supplier->name}",
                $lines,
                'PurchaseEntry',
                $pe->id,
                $purchaseNumber
            );
            $pe->update(['journal_entry_id' => $jvPurchase->id]);
        }

        // 5. Payment to Supplier (if paidAmount > 0)
        if ($paidAmount > 0 && $suppLedger) {
            $payNum = $params['payment_number'] ?? ('PAY-' . substr($purchaseNumber, 4));
            $payDate = $params['payment_date'] ?? Carbon::parse($date)->addDays(3)->toDateString();

            $sp = SupplierPayment::firstOrCreate(['company_id' => $company->id, 'payment_number' => $payNum], [
                'branch_id' => $branch->id,
                'supplier_id' => $supplier->id,
                'purchase_entry_id' => $pe->id,
                'payment_date' => $payDate,
                'payment_amount' => $paidAmount,
                'payment_mode' => 'Bank Transfer',
                'payment_ledger_id' => $ledgers['bankLedger']->id,
                'transaction_reference' => 'TXN-' . rand(100000, 999999),
                'remarks' => "Payment against bill {$invoiceNumber}",
            ]);

            if (!$sp->journal_entry_id) {
                $jvPay = $this->accountingService->createJournalEntry(
                    $company->id,
                    $branch->id,
                    'Payment',
                    $payDate,
                    "Disbursement to {$supplier->name} against bill {$invoiceNumber}",
                    [
                        ['ledger_id' => $suppLedger->id, 'debit' => $paidAmount, 'credit' => 0, 'narration' => 'Debit Supplier Ledger'],
                        ['ledger_id' => $ledgers['bankLedger']->id, 'debit' => 0, 'credit' => $paidAmount, 'narration' => 'Credit Company Bank Account'],
                    ],
                    'SupplierPayment',
                    $sp->id,
                    $payNum
                );
                $sp->update(['journal_entry_id' => $jvPay->id]);
            }
        }

        if ($suppLedger) {
            $suppLedger->recalculateBalance();
            $supplier->update(['current_balance' => $suppLedger->current_balance]);
        }

        return $pe;
    }

    /**
     * Complete Sales Workflow: SO -> Sales Invoice -> Stock Outward -> Sales Delivery -> Customer Collection -> Ledger Post
     */
    protected function recordSalesWorkflow(
        Company $company,
        Branch $branch,
        Godown $godown,
        Customer $customer,
        array $itemRows,
        array $params,
        array $ledgers,
        ?Vehicle $vehicle = null,
        ?Route $route = null
    ): SalesInvoice {
        $soNumber = $params['order_number'];
        $invoiceNumber = $params['invoice_number'];
        $deliveryNumber = $params['delivery_number'];
        $date = $params['date'];

        $subtotal = 0;
        $taxAmount = 0;
        foreach ($itemRows as $row) {
            $lineSub = $row['quantity'] * $row['rate'];
            $lineTax = $lineSub * (($row['tax_percent'] ?? 18) / 100);
            $subtotal += $lineSub;
            $taxAmount += $lineTax;
        }
        $grandTotal = $subtotal + $taxAmount;
        $paidAmount = $params['paid_amount'] ?? 0;
        $balanceAmount = $grandTotal - $paidAmount;
        $paymentStatus = $paidAmount >= $grandTotal ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

        // 1. Sales Order
        $so = SalesOrder::firstOrCreate(['company_id' => $company->id, 'order_number' => $soNumber], [
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'order_date' => $date,
            'expected_delivery_date' => Carbon::parse($date)->addDays(3)->toDateString(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'grand_total' => $grandTotal,
            'status' => 'delivered',
            'notes' => $params['notes'] ?? "Sales order for {$customer->name}",
        ]);

        foreach ($itemRows as $row) {
            $lineSub = $row['quantity'] * $row['rate'];
            $taxPct = $row['tax_percent'] ?? 18;
            $lineTax = $lineSub * ($taxPct / 100);
            SalesOrderItem::firstOrCreate([
                'sales_order_id' => $so->id,
                'item_id' => $row['item']->id,
            ], [
                'quantity' => $row['quantity'],
                'invoiced_quantity' => $row['quantity'],
                'delivered_quantity' => $row['quantity'],
                'selling_price' => $row['rate'],
                'tax_percentage' => $taxPct,
                'tax_amount' => $lineTax,
                'discount_amount' => 0,
                'line_total' => $lineSub + $lineTax,
            ]);
        }

        // 2. Sales Invoice
        $si = SalesInvoice::firstOrCreate(['company_id' => $company->id, 'invoice_number' => $invoiceNumber], [
            'branch_id' => $branch->id,
            'godown_id' => $godown->id,
            'customer_id' => $customer->id,
            'sales_order_id' => $so->id,
            'invoice_date' => $date,
            'payment_terms' => $params['payment_terms'] ?? '15 Days',
            'due_date' => Carbon::parse($date)->addDays(15)->toDateString(),
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'grand_total' => $grandTotal,
            'paid_amount' => $paidAmount,
            'balance_amount' => $balanceAmount,
            'payment_status' => $paymentStatus,
            'stock_updated' => true,
            'remarks' => "Sales Invoice for {$customer->name}",
        ]);

        foreach ($itemRows as $row) {
            $lineSub = $row['quantity'] * $row['rate'];
            $taxPct = $row['tax_percent'] ?? 18;
            $lineTax = $lineSub * ($taxPct / 100);
            $batchNo = $row['batch_no'] ?? 'DEFAULT';

            SalesInvoiceItem::firstOrCreate([
                'sales_invoice_id' => $si->id,
                'item_id' => $row['item']->id,
            ], [
                'batch_number' => $batchNo,
                'quantity' => $row['quantity'],
                'rate' => $row['rate'],
                'tax_percentage' => $taxPct,
                'tax_amount' => $lineTax,
                'discount_amount' => 0,
                'line_total' => $lineSub + $lineTax,
            ]);

            // Stock Outward (allow negative fallback to maintain smooth seeding)
            $this->inventoryService->recordOutward(
                $company->id,
                $branch->id,
                $godown->id,
                $row['item']->id,
                $row['quantity'],
                $batchNo,
                'SalesInvoice',
                $si->id,
                $invoiceNumber,
                "Dispatch for invoice {$invoiceNumber}",
                true
            );
        }

        // 3. Sales Delivery
        $sd = SalesDelivery::firstOrCreate(['company_id' => $company->id, 'delivery_number' => $deliveryNumber], [
            'branch_id' => $branch->id,
            'customer_id' => $customer->id,
            'sales_order_id' => $so->id,
            'sales_invoice_id' => $si->id,
            'vehicle_id' => $vehicle?->id,
            'route_id' => $route?->id,
            'assigned_driver' => $vehicle?->assigned_driver ?? 'Logistics Fleet Driver',
            'delivery_date' => Carbon::parse($date)->addDays(1)->toDateString(),
            'delivery_address' => $customer->address,
            'delivery_status' => 'delivered',
            'delivery_remarks' => 'Delivered and acknowledged by customer representative.',
        ]);

        foreach ($itemRows as $row) {
            SalesDeliveryItem::firstOrCreate([
                'sales_delivery_id' => $sd->id,
                'item_id' => $row['item']->id,
            ], [
                'batch_number' => $row['batch_no'] ?? 'DEFAULT',
                'quantity' => $row['quantity'],
            ]);
        }

        // 4. Double Entry for Sales
        $custLedger = $customer->ledger;
        if ($custLedger && !$si->journal_entry_id) {
            $lines = [
                ['ledger_id' => $custLedger->id, 'debit' => $grandTotal, 'credit' => 0, 'narration' => "Receivable from {$customer->name}"],
                ['ledger_id' => $ledgers['salesLedger']->id, 'debit' => 0, 'credit' => $subtotal, 'narration' => "Sales of goods via {$invoiceNumber}"],
            ];
            if ($taxAmount > 0) {
                $lines[] = ['ledger_id' => $ledgers['gstOutputLedger']->id, 'debit' => 0, 'credit' => $taxAmount, 'narration' => 'GST Output Tax Liability'];
            }

            $jvSales = $this->accountingService->createJournalEntry(
                $company->id,
                $branch->id,
                'Sales',
                $date,
                "Sales invoice {$invoiceNumber} to {$customer->name}",
                $lines,
                'SalesInvoice',
                $si->id,
                $invoiceNumber
            );
            $si->update(['journal_entry_id' => $jvSales->id]);
        }

        // 5. Customer Collection (if paidAmount > 0)
        if ($paidAmount > 0 && $custLedger) {
            $colNum = $params['collection_number'] ?? ('COL-' . substr($invoiceNumber, 4));
            $colDate = $params['collection_date'] ?? Carbon::parse($date)->addDays(4)->toDateString();

            $col = CustomerCollection::firstOrCreate(['company_id' => $company->id, 'collection_number' => $colNum], [
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'sales_invoice_id' => $si->id,
                'collection_date' => $colDate,
                'collection_amount' => $paidAmount,
                'payment_mode' => 'Bank Transfer',
                'receipt_ledger_id' => $ledgers['bankLedger']->id,
                'transaction_reference' => 'NEFT-' . rand(100000, 999999),
                'collected_by' => 'Finance & Billing Desk',
                'remarks' => "Receipt against invoice {$invoiceNumber}",
            ]);

            if (!$col->journal_entry_id) {
                $jvColl = $this->accountingService->createJournalEntry(
                    $company->id,
                    $branch->id,
                    'Receipt',
                    $colDate,
                    "Receipt from {$customer->name} against Invoice {$invoiceNumber}",
                    [
                        ['ledger_id' => $ledgers['bankLedger']->id, 'debit' => $paidAmount, 'credit' => 0, 'narration' => 'Debit Bank Account'],
                        ['ledger_id' => $custLedger->id, 'debit' => 0, 'credit' => $paidAmount, 'narration' => 'Credit Customer Ledger'],
                    ],
                    'CustomerCollection',
                    $col->id,
                    $colNum
                );
                $col->update(['journal_entry_id' => $jvColl->id]);
            }
        }

        if ($custLedger) {
            $custLedger->recalculateBalance();
            $customer->update(['current_balance' => $custLedger->current_balance]);
        }

        return $si;
    }

    /**
     * Record Purchase Return
     */
    protected function recordPurchaseReturn(
        Company $company,
        Branch $branch,
        Godown $godown,
        Supplier $supplier,
        PurchaseEntry $pe,
        array $returnItems,
        array $ledgers,
        string $returnNo,
        string $date,
        string $reason
    ): PurchaseReturn {
        $totalAmount = 0;
        foreach ($returnItems as $item) {
            $totalAmount += $item['quantity'] * $item['rate'];
        }

        $pr = PurchaseReturn::firstOrCreate(['company_id' => $company->id, 'return_number' => $returnNo], [
            'branch_id' => $branch->id,
            'godown_id' => $godown->id,
            'supplier_id' => $supplier->id,
            'purchase_entry_id' => $pe->id,
            'return_date' => $date,
            'total_amount' => $totalAmount,
            'return_reason' => $reason,
            'status' => 'approved',
        ]);

        foreach ($returnItems as $item) {
            $lineTotal = $item['quantity'] * $item['rate'];
            PurchaseReturnItem::firstOrCreate([
                'purchase_return_id' => $pr->id,
                'item_id' => $item['item']->id,
            ], [
                'batch_number' => $item['batch_no'] ?? 'DEFAULT',
                'return_quantity' => $item['quantity'],
                'rate' => $item['rate'],
                'tax_amount' => 0,
                'total_amount' => $lineTotal,
            ]);

            // Deduct stock returned to supplier
            $this->inventoryService->recordOutward(
                $company->id,
                $branch->id,
                $godown->id,
                $item['item']->id,
                $item['quantity'],
                $item['batch_no'] ?? 'DEFAULT',
                'PurchaseReturn',
                $pr->id,
                $returnNo,
                "Return to {$supplier->name}: {$reason}",
                true
            );
        }

        // Double Entry for Purchase Return: Debit Supplier, Credit Purchase Return
        $suppLedger = $supplier->ledger;
        if ($suppLedger && !$pr->journal_entry_id) {
            $jv = $this->accountingService->createJournalEntry(
                $company->id,
                $branch->id,
                'Purchase Return',
                $date,
                "Purchase return {$returnNo} to {$supplier->name}",
                [
                    ['ledger_id' => $suppLedger->id, 'debit' => $totalAmount, 'credit' => 0, 'narration' => 'Debit Supplier Account (Return)'],
                    ['ledger_id' => $ledgers['purchaseReturnLedger']->id, 'debit' => 0, 'credit' => $totalAmount, 'narration' => 'Credit Purchase Return Account'],
                ],
                'PurchaseReturn',
                $pr->id,
                $returnNo
            );
            $pr->update(['journal_entry_id' => $jv->id]);
            $suppLedger->recalculateBalance();
            $supplier->update(['current_balance' => $suppLedger->current_balance]);
        }

        return $pr;
    }

    /**
     * Record Sales Return
     */
    protected function recordSalesReturn(
        Company $company,
        Branch $branch,
        Godown $godown,
        Customer $customer,
        SalesInvoice $si,
        array $returnItems,
        array $ledgers,
        string $returnNo,
        string $date,
        string $reason
    ): SalesReturn {
        $totalAmount = 0;
        foreach ($returnItems as $item) {
            $totalAmount += $item['quantity'] * $item['rate'];
        }

        $sr = SalesReturn::firstOrCreate(['company_id' => $company->id, 'return_number' => $returnNo], [
            'branch_id' => $branch->id,
            'godown_id' => $godown->id,
            'customer_id' => $customer->id,
            'sales_invoice_id' => $si->id,
            'return_date' => $date,
            'total_amount' => $totalAmount,
            'return_reason' => $reason,
            'stock_condition' => 'accept_to_stock',
            'status' => 'approved',
        ]);

        foreach ($returnItems as $item) {
            $lineTotal = $item['quantity'] * $item['rate'];
            SalesReturnItem::firstOrCreate([
                'sales_return_id' => $sr->id,
                'item_id' => $item['item']->id,
            ], [
                'batch_number' => $item['batch_no'] ?? 'DEFAULT',
                'return_quantity' => $item['quantity'],
                'rate' => $item['rate'],
                'tax_amount' => 0,
                'total_amount' => $lineTotal,
            ]);

            // Inward stock back to warehouse
            $this->inventoryService->recordInward(
                $company->id,
                $branch->id,
                $godown->id,
                $item['item']->id,
                $item['quantity'],
                $item['rate'],
                $item['batch_no'] ?? 'DEFAULT',
                null,
                'SalesReturn',
                $sr->id,
                $returnNo,
                "Sales return from {$customer->name}: {$reason}"
            );
        }

        // Double Entry for Sales Return: Debit Sales Return, Credit Customer
        $custLedger = $customer->ledger;
        if ($custLedger && !$sr->journal_entry_id) {
            $jv = $this->accountingService->createJournalEntry(
                $company->id,
                $branch->id,
                'Sales Return',
                $date,
                "Sales return {$returnNo} from {$customer->name}",
                [
                    ['ledger_id' => $ledgers['salesReturnLedger']->id, 'debit' => $totalAmount, 'credit' => 0, 'narration' => 'Debit Sales Return Account'],
                    ['ledger_id' => $custLedger->id, 'debit' => 0, 'credit' => $totalAmount, 'narration' => 'Credit Customer Account (Return Credit)'],
                ],
                'SalesReturn',
                $sr->id,
                $returnNo
            );
            $sr->update(['journal_entry_id' => $jv->id]);
            $custLedger->recalculateBalance();
            $customer->update(['current_balance' => $custLedger->current_balance]);
        }

        return $sr;
    }

    /**
     * Record Operating Expense Voucher
     */
    protected function recordExpenseVoucher(
        Company $company,
        Branch $branch,
        Ledger $expenseLedger,
        Ledger $bankLedger,
        float $amount,
        string $date,
        string $narration
    ): JournalEntry {
        return $this->accountingService->createJournalEntry(
            $company->id,
            $branch->id,
            'Payment',
            $date,
            $narration,
            [
                ['ledger_id' => $expenseLedger->id, 'debit' => $amount, 'credit' => 0, 'narration' => $narration],
                ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => $amount, 'narration' => 'Paid via Bank'],
            ],
            'ExpenseVoucher',
            null,
            'EXP-' . rand(10000, 99999)
        );
    }

    /**
     * Seed Staff Member and attendance/payroll
     */
    protected function createStaff(Company $company, Branch $branch, Department $dept, array $data): Staff
    {
        return Staff::firstOrCreate(['company_id' => $company->id, 'employee_id' => $data['employee_id']], [
            'branch_id' => $branch->id,
            'department_id' => $dept->id,
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? '+91 98' . rand(10000000, 99999999),
            'aadhaar_number' => $data['aadhaar_number'] ?? (rand(1000, 9999) . ' ' . rand(1000, 9999) . ' ' . rand(1000, 9999)),
            'pan_number' => $data['pan_number'] ?? ('ABCDE' . rand(1000, 9999) . 'F'),
            'date_of_birth' => $data['date_of_birth'] ?? '1992-05-15',
            'gender' => $data['gender'] ?? 'Male',
            'blood_group' => $data['blood_group'] ?? 'O+',
            'highest_education' => $data['highest_education'] ?? 'Bachelor Degree',
            'marital_status' => $data['marital_status'] ?? 'Single',
            'permanent_address' => $data['address'] ?? 'City Center',
            'temporary_address' => $data['address'] ?? 'City Center',
            'designation' => $data['designation'],
            'employee_type' => $data['employee_type'] ?? 'salaried',
            'salary_amount' => $data['salary_amount'] ?? 40000.00,
            'basic_salary' => $data['basic_salary'] ?? (($data['salary_amount'] ?? 40000.00) * 0.5),
            'hra' => $data['hra'] ?? (($data['salary_amount'] ?? 40000.00) * 0.3),
            'other_allowances' => $data['other_allowances'] ?? (($data['salary_amount'] ?? 40000.00) * 0.2),
            'pf_applicable' => $data['pf_applicable'] ?? true,
            'pf_number' => 'MH/MUM/00' . rand(10000, 99999),
            'pf_uan' => '100' . rand(100000000, 999999999),
            'pf_rate_percent' => 12.00,
            'esi_applicable' => $data['esi_applicable'] ?? false,
            'bank_name' => $data['bank_name'] ?? 'HDFC Bank Ltd',
            'bank_ifsc' => $data['bank_ifsc'] ?? 'HDFC0001234',
            'bank_branch' => 'Main Branch',
            'bank_account_type' => 'Salary',
            'bank_holder_name' => "{$data['first_name']} {$data['last_name']}",
            'bank_account_number' => '50100' . rand(1000000, 9999999),
            'date_of_joining' => $data['date_of_joining'] ?? '2023-01-10',
            'status' => 'active',
        ]);
    }

    /**
     * Process 5-day attendance and October 2026 payroll for a staff member
     */
    protected function processStaffAttendanceAndPayroll(Company $company, Staff $staff, array $ledgers): void
    {
        // 5 Days Attendance
        for ($d = 12; $d <= 16; $d++) {
            $attDate = sprintf('2026-10-%02d', $d);
            Attendance::firstOrCreate([
                'company_id' => $company->id,
                'staff_id' => $staff->id,
                'attendance_date' => $attDate,
            ], [
                'branch_id' => $staff->branch_id,
                'department_id' => $staff->department_id,
                'check_in_time' => '09:05:00',
                'check_out_time' => '18:10:00',
                'attendance_status' => 'Present',
                'working_hours' => 8.5,
                'overtime_hours' => 0.5,
                'remarks' => 'Regular working hours',
            ]);
        }

        // Payroll for October 2026
        $gross = (float)$staff->basic_salary + (float)$staff->hra + (float)$staff->other_allowances;
        $pfDeduction = $staff->pf_applicable ? ($staff->basic_salary * 0.12) : 0;
        $esiDeduction = $staff->esi_applicable ? ($staff->salary_amount * 0.0075) : 0;
        $totDeduction = $pfDeduction + $esiDeduction;
        $net = $gross - $totDeduction;

        $payroll = Payroll::firstOrCreate([
            'company_id' => $company->id,
            'staff_id' => $staff->id,
            'salary_month' => '2026-10',
        ], [
            'branch_id' => $staff->branch_id,
            'department_id' => $staff->department_id,
            'basic_salary' => $staff->basic_salary,
            'hra' => $staff->hra,
            'other_allowances' => $staff->other_allowances,
            'incentives' => 0,
            'overtime_amount' => 0,
            'pf_deduction' => $pfDeduction,
            'esi_deduction' => $esiDeduction,
            'other_deductions' => 0,
            'gross_salary' => $gross,
            'total_deductions' => $totDeduction,
            'net_salary' => $net,
            'payment_date' => '2026-10-31',
            'payment_mode' => 'Bank Transfer',
            'transaction_reference' => 'SAL-OCT26-' . $staff->employee_id,
            'payroll_status' => 'paid',
        ]);

        if (!$payroll->journal_entry_id) {
            $lines = [
                ['ledger_id' => $ledgers['salaryLedger']->id, 'debit' => $gross, 'credit' => 0, 'narration' => "Salary for {$staff->full_name} (Oct 2026)"],
            ];
            if ($pfDeduction > 0) {
                $lines[] = ['ledger_id' => $ledgers['pfLedger']->id, 'debit' => 0, 'credit' => $pfDeduction, 'narration' => "PF Deduction - {$staff->full_name}"];
            }
            $lines[] = ['ledger_id' => $ledgers['bankLedger']->id, 'debit' => 0, 'credit' => $net, 'narration' => "Net Salary Disbursed to {$staff->full_name}"];

            $jv = $this->accountingService->createJournalEntry(
                $company->id,
                $staff->branch_id,
                'Payroll',
                '2026-10-31',
                "Salary disbursement for {$staff->full_name} for October 2026",
                $lines,
                'Payroll',
                $payroll->id,
                "PAYR-{$staff->employee_id}-202610"
            );
            $payroll->update(['journal_entry_id' => $jv->id]);
        }
    }

    /**
     * Create Bill of Materials (BOM)
     */
    protected function createBOM(
        Company $company,
        Item $finishedItem,
        string $bomName,
        float $outputQty,
        array $rawItemQtys,
        string $desc = ''
    ): Bom {
        $bom = Bom::firstOrCreate([
            'company_id' => $company->id,
            'bom_name' => $bomName,
        ], [
            'finished_item_id' => $finishedItem->id,
            'output_quantity' => $outputQty,
            'description' => $desc,
            'status' => 'active',
        ]);

        foreach ($rawItemQtys as $raw) {
            BomItem::firstOrCreate([
                'bom_id' => $bom->id,
                'raw_item_id' => $raw['item']->id,
            ], [
                'quantity' => $raw['quantity'],
            ]);
        }

        return $bom;
    }

    /**
     * Record Production Order & Batches with consumption and generation
     */
    protected function recordProductionBatch(
        Company $company,
        Branch $branch,
        Bom $bom,
        Item $finishedItem,
        Godown $srcGodown,
        Godown $destGodown,
        string $orderNo,
        string $batchNo,
        float $plannedQty,
        float $producedQty,
        string $startDate,
        string $status,
        array $rawItemsUsed,
        ?string $endDate = null
    ): ProductionOrder {
        $po = ProductionOrder::firstOrCreate(['company_id' => $company->id, 'order_number' => $orderNo], [
            'branch_id' => $branch->id,
            'bom_id' => $bom->id,
            'finished_item_id' => $finishedItem->id,
            'source_godown_id' => $srcGodown->id,
            'dest_godown_id' => $destGodown->id,
            'batch_number' => $batchNo,
            'start_date' => $startDate,
            'end_date' => $endDate ?? Carbon::parse($startDate)->addDays(2)->toDateString(),
            'planned_quantity' => $plannedQty,
            'produced_quantity' => $producedQty,
            'status' => $status,
        ]);

        if ($status === 'completed' && $producedQty > 0) {
            // 1. Consume raw materials (outward from source godown)
            foreach ($rawItemsUsed as $raw) {
                $this->inventoryService->recordOutward(
                    $company->id,
                    $branch->id,
                    $srcGodown->id,
                    $raw['item']->id,
                    $raw['quantity'],
                    $raw['batch_no'] ?? 'DEFAULT',
                    'ProductionOrder',
                    $po->id,
                    $orderNo,
                    "Consumed in Production Order {$orderNo}",
                    true
                );
            }

            // 2. Generate finished goods (inward to destination godown)
            $unitCost = $finishedItem->min_price > 0 ? ($finishedItem->min_price * 0.7) : 50.0;
            $this->inventoryService->recordInward(
                $company->id,
                $branch->id,
                $destGodown->id,
                $finishedItem->id,
                $producedQty,
                $unitCost,
                $batchNo,
                Carbon::parse($startDate)->addMonths(12)->toDateString(),
                'ProductionOrder',
                $po->id,
                $orderNo,
                "Finished goods generated via Production Order {$orderNo}"
            );
        }

        return $po;
    }

    /**
     * Record Quality Check (QC)
     */
    protected function recordQualityCheck(
        Company $company,
        Branch $branch,
        Godown $godown,
        Item $item,
        string $inspNo,
        string $refType,
        ?int $refId,
        ?string $refNo,
        string $date,
        string $result,
        float $accQty,
        float $rejQty,
        string $remarks,
        ?User $approver = null
    ): QualityCheck {
        return QualityCheck::firstOrCreate(['company_id' => $company->id, 'inspection_number' => $inspNo], [
            'branch_id' => $branch->id,
            'godown_id' => $godown->id,
            'department_id' => null,
            'item_id' => $item->id,
            'reference_type' => $refType,
            'reference_id' => $refId,
            'reference_number' => $refNo,
            'inspection_date' => $date,
            'weight_before_testing' => $accQty + $rejQty,
            'unit_before_testing' => $item->unit?->symbol ?? 'KG',
            'weight_after_testing' => $accQty,
            'unit_after_testing' => $item->unit?->symbol ?? 'KG',
            'quality_test_details' => 'Physical, moisture, and sensory specifications test.',
            'result' => $result,
            'accepted_quantity' => $accQty,
            'rejected_quantity' => $rejQty,
            'remarks' => $remarks,
            'checked_by' => 'Quality Inspection Officer',
            'approval_status' => 'approved',
            'approved_by' => $approver?->id,
        ]);
    }
}

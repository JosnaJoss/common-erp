<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerCollection;
use App\Models\Godown;
use App\Models\GoodsReceiptNote;
use App\Models\GrnItem;
use App\Models\Item;
use App\Models\Payroll;
use App\Models\PurchaseEntry;
use App\Models\PurchaseEntryItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\Staff;
use App\Models\StockAdjustment;
use App\Models\StockAdjustmentItem;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\AccountingService;
use App\Services\InventoryService;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'APEX-IND')->first();
        if (!$company) return;

        $inventoryService = new InventoryService();
        $accountingService = new AccountingService();

        $godownCentral = Godown::where('company_id', $company->id)->where('code', 'WH-MUM-01')->first();
        $godownRaw = Godown::where('company_id', $company->id)->where('code', 'WH-MUM-RAW')->first();
        $itemMotor5HP = Item::where('company_id', $company->id)->where('code', 'ITM-MTR-001')->first();
        $itemMotor3HP = Item::where('company_id', $company->id)->where('code', 'ITM-MTR-002')->first();
        $itemSteelRod = Item::where('company_id', $company->id)->where('code', 'ITM-STL-025')->first();
        $itemCopper = Item::where('company_id', $company->id)->where('code', 'ITM-COP-001')->first();

        $suppTata = Supplier::where('company_id', $company->id)->where('code', 'SUPP-TATA-01')->first();
        $custMahavir = Customer::where('company_id', $company->id)->where('code', 'CUST-MAH-001')->first();
        $custLarsen = Customer::where('company_id', $company->id)->where('code', 'CUST-LAR-002')->first();
        $branch = $company->branches()->first();

        // 1. Initial Opening Stock in Godown
        if ($itemMotor5HP && $godownCentral) {
            $inventoryService->recordInward(
                $company->id, $branch->id, $godownCentral->id, $itemMotor5HP->id,
                25.000, 12500.00, 'BATCH-2026-A1', null, 'OpeningStock', 1, 'OPN-001', 'Initial Opening Stock'
            );
        }

        if ($itemMotor3HP && $godownCentral) {
            $inventoryService->recordInward(
                $company->id, $branch->id, $godownCentral->id, $itemMotor3HP->id,
                40.000, 8000.00, 'BATCH-2026-A2', null, 'OpeningStock', 2, 'OPN-002', 'Initial Opening Stock'
            );
        }

        if ($itemSteelRod && $godownRaw) {
            $inventoryService->recordInward(
                $company->id, $branch->id, $godownRaw->id, $itemSteelRod->id,
                500.000, 250.00, 'BATCH-STL-01', null, 'OpeningStock', 3, 'OPN-003', 'Initial Steel Rod Stock'
            );
        }

        if ($itemCopper && $godownRaw) {
            $inventoryService->recordInward(
                $company->id, $branch->id, $godownRaw->id, $itemCopper->id,
                200.000, 700.00, 'BATCH-COP-01', null, 'OpeningStock', 4, 'OPN-004', 'Initial Copper Stock'
            );
        }

        // 2. Real Purchase Workflow (PO -> GRN -> Purchase Entry -> Ledger Post)
        if ($suppTata && $itemSteelRod && $godownRaw) {
            // PO
            $po = PurchaseOrder::firstOrCreate(['company_id' => $company->id, 'po_number' => 'PO-2026-0001'], [
                'branch_id' => $branch->id,
                'supplier_id' => $suppTata->id,
                'po_date' => '2026-10-15',
                'expected_delivery_date' => '2026-10-25',
                'subtotal' => 75000.00,
                'tax_amount' => 13500.00,
                'discount_amount' => 0.00,
                'total_amount' => 88500.00,
                'terms_and_conditions' => 'Payment within 30 days of receipt',
                'status' => 'received',
            ]);

            PurchaseOrderItem::firstOrCreate(['purchase_order_id' => $po->id, 'item_id' => $itemSteelRod->id], [
                'quantity' => 300.000,
                'received_quantity' => 300.000,
                'rate' => 250.00,
                'tax_percentage' => 18.00,
                'tax_amount' => 13500.00,
                'discount_amount' => 0.00,
                'total_amount' => 88500.00,
            ]);

            // GRN
            $grn = GoodsReceiptNote::firstOrCreate(['company_id' => $company->id, 'grn_number' => 'GRN-2026-0001'], [
                'branch_id' => $branch->id,
                'godown_id' => $godownRaw->id,
                'supplier_id' => $suppTata->id,
                'purchase_order_id' => $po->id,
                'received_date' => '2026-10-20',
                'received_by' => 'Mahesh Gaikwad',
                'status' => 'stock_updated',
                'remarks' => 'All items received in good condition',
            ]);

            GrnItem::firstOrCreate(['goods_receipt_note_id' => $grn->id, 'item_id' => $itemSteelRod->id], [
                'ordered_quantity' => 300.000,
                'received_quantity' => 300.000,
                'accepted_quantity' => 300.000,
                'rejected_quantity' => 0.000,
                'batch_number' => 'BATCH-STL-02',
                'expiry_date' => null,
            ]);

            // Purchase Entry
            $pe = PurchaseEntry::firstOrCreate(['company_id' => $company->id, 'purchase_number' => 'PUR-2026-0001'], [
                'branch_id' => $branch->id,
                'godown_id' => $godownRaw->id,
                'supplier_id' => $suppTata->id,
                'purchase_order_id' => $po->id,
                'goods_receipt_note_id' => $grn->id,
                'invoice_number' => 'TATA/INV/9821',
                'invoice_date' => '2026-10-21',
                'payment_type' => 'Credit',
                'subtotal' => 75000.00,
                'tax_amount' => 13500.00,
                'discount_amount' => 0.00,
                'grand_total' => 88500.00,
                'paid_amount' => 40000.00,
                'balance_amount' => 48500.00,
                'payment_status' => 'partial',
                'stock_updated' => true,
                'remarks' => 'Procurement of structural steel',
            ]);

            PurchaseEntryItem::firstOrCreate(['purchase_entry_id' => $pe->id, 'item_id' => $itemSteelRod->id], [
                'batch_number' => 'BATCH-STL-02',
                'weight' => 1.000,
                'quantity' => 300.000,
                'rate' => 250.00,
                'tax_percentage' => 18.00,
                'tax_amount' => 13500.00,
                'discount_amount' => 0.00,
                'line_total' => 88500.00,
            ]);

            // Stock update
            $inventoryService->recordInward(
                $company->id, $branch->id, $godownRaw->id, $itemSteelRod->id,
                300.000, 250.00, 'BATCH-STL-02', null, 'PurchaseEntry', $pe->id, $pe->purchase_number, 'Purchase bill received'
            );

            // Double Entry Accounting for Purchase
            $purchaseLedger = $accountingService->getOrCreateStandardLedger($company->id, 'Purchase Account', 'Direct Expenses & Purchases', 'Expenses');
            $gstInputLedger = $accountingService->getOrCreateStandardLedger($company->id, 'GST Input Tax Credit (Receivable)', 'Current Assets', 'Assets');
            $suppLedger = $suppTata->ledger;

            if ($suppLedger) {
                $jvPurchase = $accountingService->createJournalEntry(
                    $company->id, $branch->id, 'Purchase', '2026-10-21',
                    "Purchase Invoice {$pe->invoice_number} from {$suppTata->name}",
                    [
                        ['ledger_id' => $purchaseLedger->id, 'debit' => 75000.00, 'credit' => 0, 'narration' => 'Purchase of Materials'],
                        ['ledger_id' => $gstInputLedger->id, 'debit' => 13500.00, 'credit' => 0, 'narration' => 'GST Input 18%'],
                        ['ledger_id' => $suppLedger->id, 'debit' => 0, 'credit' => 88500.00, 'narration' => 'Payable to Tata Steel'],
                    ],
                    'PurchaseEntry', $pe->id, $pe->purchase_number
                );
                $pe->update(['journal_entry_id' => $jvPurchase->id]);

                // Partial payment to Supplier
                $bankLedger = $accountingService->getOrCreateStandardLedger($company->id, 'HDFC Bank Ltd (Current A/c 50200012345678)', 'Bank & Cash Accounts', 'Assets');
                $sp = SupplierPayment::firstOrCreate(['company_id' => $company->id, 'payment_number' => 'PAY-2026-0001'], [
                    'branch_id' => $branch->id,
                    'supplier_id' => $suppTata->id,
                    'purchase_entry_id' => $pe->id,
                    'payment_date' => '2026-10-28',
                    'payment_amount' => 40000.00,
                    'payment_mode' => 'Bank Transfer',
                    'payment_ledger_id' => $bankLedger->id,
                    'transaction_reference' => 'HDFCN2610280011',
                    'remarks' => 'Part payment against bill TATA/INV/9821',
                ]);

                $jvPay = $accountingService->createJournalEntry(
                    $company->id, $branch->id, 'Payment', '2026-10-28',
                    "Payment to {$suppTata->name} against bill {$pe->invoice_number}",
                    [
                        ['ledger_id' => $suppLedger->id, 'debit' => 40000.00, 'credit' => 0, 'narration' => 'Debit Supplier Account'],
                        ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => 40000.00, 'narration' => 'Credit Bank Account'],
                    ],
                    'SupplierPayment', $sp->id, $sp->payment_number
                );
                $sp->update(['journal_entry_id' => $jvPay->id]);
            }
        }

        // 3. Real Sales Workflow (SO -> Sales Invoice -> Stock Outward -> Customer Payment)
        if ($custMahavir && $itemMotor5HP && $godownCentral) {
            $so = SalesOrder::firstOrCreate(['company_id' => $company->id, 'order_number' => 'SO-2026-0001'], [
                'branch_id' => $branch->id,
                'customer_id' => $custMahavir->id,
                'order_date' => '2026-10-22',
                'expected_delivery_date' => '2026-10-26',
                'subtotal' => 82500.00,
                'tax_amount' => 14850.00,
                'discount_amount' => 0.00,
                'grand_total' => 97350.00,
                'status' => 'delivered',
                'notes' => 'Supply 5 Nos 5HP Induction Motors',
            ]);

            SalesOrderItem::firstOrCreate(['sales_order_id' => $so->id, 'item_id' => $itemMotor5HP->id], [
                'quantity' => 5.000,
                'invoiced_quantity' => 5.000,
                'delivered_quantity' => 5.000,
                'selling_price' => 16500.00,
                'tax_percentage' => 18.00,
                'tax_amount' => 14850.00,
                'discount_amount' => 0.00,
                'line_total' => 97350.00,
            ]);

            $si = SalesInvoice::firstOrCreate(['company_id' => $company->id, 'invoice_number' => 'INV-2026-0001'], [
                'branch_id' => $branch->id,
                'godown_id' => $godownCentral->id,
                'customer_id' => $custMahavir->id,
                'sales_order_id' => $so->id,
                'invoice_date' => '2026-10-24',
                'payment_terms' => '15 Days',
                'due_date' => '2026-11-08',
                'subtotal' => 82500.00,
                'tax_amount' => 14850.00,
                'discount_amount' => 0.00,
                'grand_total' => 97350.00,
                'paid_amount' => 50000.00,
                'balance_amount' => 47350.00,
                'payment_status' => 'partial',
                'stock_updated' => true,
                'remarks' => 'Dispatch to Mahavir Engineering Works',
            ]);

            SalesInvoiceItem::firstOrCreate(['sales_invoice_id' => $si->id, 'item_id' => $itemMotor5HP->id], [
                'batch_number' => 'BATCH-2026-A1',
                'quantity' => 5.000,
                'rate' => 16500.00,
                'tax_percentage' => 18.00,
                'tax_amount' => 14850.00,
                'discount_amount' => 0.00,
                'line_total' => 97350.00,
            ]);

            // Stock Outward
            $inventoryService->recordOutward(
                $company->id, $branch->id, $godownCentral->id, $itemMotor5HP->id,
                5.000, 'BATCH-2026-A1', 'SalesInvoice', $si->id, $si->invoice_number, 'Dispatch to Customer'
            );

            // Double Entry Accounting for Sales
            $salesLedger = $accountingService->getOrCreateStandardLedger($company->id, 'Sales Account', 'Sales & Operating Revenue', 'Revenue');
            $gstOutputLedger = $accountingService->getOrCreateStandardLedger($company->id, 'GST Output Tax Liability (Payable)', 'Duties & Taxes', 'Liabilities');
            $custLedger = $custMahavir->ledger;

            if ($custLedger) {
                $jvSales = $accountingService->createJournalEntry(
                    $company->id, $branch->id, 'Sales', '2026-10-24',
                    "Sales Invoice {$si->invoice_number} to {$custMahavir->name}",
                    [
                        ['ledger_id' => $custLedger->id, 'debit' => 97350.00, 'credit' => 0, 'narration' => 'Receivable from Mahavir Eng'],
                        ['ledger_id' => $salesLedger->id, 'debit' => 0, 'credit' => 82500.00, 'narration' => 'Sales of Motors'],
                        ['ledger_id' => $gstOutputLedger->id, 'debit' => 0, 'credit' => 14850.00, 'narration' => 'GST Output 18%'],
                    ],
                    'SalesInvoice', $si->id, $si->invoice_number
                );
                $si->update(['journal_entry_id' => $jvSales->id]);

                // Customer Collection
                $bankLedger = $accountingService->getOrCreateStandardLedger($company->id, 'HDFC Bank Ltd (Current A/c 50200012345678)', 'Bank & Cash Accounts', 'Assets');
                $coll = CustomerCollection::firstOrCreate(['company_id' => $company->id, 'collection_number' => 'COL-2026-0001'], [
                    'branch_id' => $branch->id,
                    'customer_id' => $custMahavir->id,
                    'sales_invoice_id' => $si->id,
                    'collection_date' => '2026-10-29',
                    'collection_amount' => 50000.00,
                    'payment_mode' => 'Bank Transfer',
                    'receipt_ledger_id' => $bankLedger->id,
                    'transaction_reference' => 'NEFT-MAH-9021',
                    'collected_by' => 'Anita Kapoor',
                    'remarks' => 'Part payment from customer',
                ]);

                $jvColl = $accountingService->createJournalEntry(
                    $company->id, $branch->id, 'Receipt', '2026-10-29',
                    "Receipt from {$custMahavir->name} against Invoice {$si->invoice_number}",
                    [
                        ['ledger_id' => $bankLedger->id, 'debit' => 50000.00, 'credit' => 0, 'narration' => 'Debit Bank Account'],
                        ['ledger_id' => $custLedger->id, 'debit' => 0, 'credit' => 50000.00, 'narration' => 'Credit Customer Account'],
                    ],
                    'CustomerCollection', $coll->id, $coll->collection_number
                );
                $coll->update(['journal_entry_id' => $jvColl->id]);
            }
        }

        // 4. Staff Attendance & Payroll Processing
        $staffMembers = Staff::where('company_id', $company->id)->get();
        foreach ($staffMembers as $staff) {
            // Seed 5 days of attendance
            for ($d = 1; $d <= 5; $d++) {
                $attDate = sprintf('2026-10-%02d', $d + 10);
                Attendance::firstOrCreate([
                    'company_id' => $company->id,
                    'staff_id' => $staff->id,
                    'attendance_date' => $attDate,
                ], [
                    'branch_id' => $staff->branch_id,
                    'department_id' => $staff->department_id,
                    'check_in_time' => '09:05:00',
                    'check_out_time' => '18:15:00',
                    'attendance_status' => 'Present',
                    'working_hours' => 8.5,
                    'overtime_hours' => 0.5,
                    'remarks' => 'On time',
                ]);
            }

            // Monthly Payroll for October 2026
            $pfDeduction = $staff->pf_applicable ? ($staff->basic_salary * 0.12) : 0;
            $esiDeduction = $staff->esi_applicable ? ($staff->salary_amount * 0.0075) : 0;
            $gross = $staff->basic_salary + $staff->hra + $staff->other_allowances;
            $net = $gross - $pfDeduction - $esiDeduction;

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
                'total_deductions' => $pfDeduction + $esiDeduction,
                'net_salary' => $net,
                'payment_date' => '2026-10-31',
                'payment_mode' => 'Bank Transfer',
                'transaction_reference' => 'SAL-OCT26-' . $staff->employee_id,
                'payroll_status' => 'paid',
            ]);

            // Accounting entry for Payroll
            $salaryExpLedger = $accountingService->getOrCreateStandardLedger($company->id, 'Salaries & Staff Wages', 'Administrative & Indirect Expenses', 'Expenses');
            $pfPayableLedger = $accountingService->getOrCreateStandardLedger($company->id, 'PF & ESI Payable', 'Duties & Taxes', 'Liabilities');
            $bankLedger = $accountingService->getOrCreateStandardLedger($company->id, 'HDFC Bank Ltd (Current A/c 50200012345678)', 'Bank & Cash Accounts', 'Assets');

            $lines = [
                ['ledger_id' => $salaryExpLedger->id, 'debit' => $gross, 'credit' => 0, 'narration' => "Salary for {$staff->full_name}"],
            ];
            if ($pfDeduction > 0) {
                $lines[] = ['ledger_id' => $pfPayableLedger->id, 'debit' => 0, 'credit' => $pfDeduction, 'narration' => 'PF Deduction'];
            }
            $lines[] = ['ledger_id' => $bankLedger->id, 'debit' => 0, 'credit' => $net, 'narration' => "Net Salary Disbursed to {$staff->full_name}"];

            $jvPayroll = $accountingService->createJournalEntry(
                $company->id, $staff->branch_id, 'Payroll', '2026-10-31',
                "Salary disbursement for {$staff->full_name} for October 2026",
                $lines,
                'Payroll', $payroll->id, "PAYR-{$staff->employee_id}-202610"
            );
            $payroll->update(['journal_entry_id' => $jvPayroll->id]);
        }
    }
}


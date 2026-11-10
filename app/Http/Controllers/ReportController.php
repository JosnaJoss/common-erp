<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Godown;
use App\Models\Item;
use App\Models\JournalEntry;
use App\Models\Ledger;
use App\Models\Payroll;
use App\Models\PurchaseEntry;
use App\Models\SalesInvoice;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // ==================== FINANCIAL REPORTS ====================
    public function trialBalance(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $ledgers = Ledger::where('company_id', $companyId)
            ->with('accountGroup')
            ->get();

        $rows = [];
        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($ledgers as $ledger) {
            $ledger->recalculateBalance();
            $bal = (float)$ledger->current_balance;
            if ($bal == 0) continue;

            $debit = $ledger->balance_type === 'debit' ? $bal : 0;
            $credit = $ledger->balance_type === 'credit' ? $bal : 0;

            $totalDebit += $debit;
            $totalCredit += $credit;

            $rows[] = [
                'ledger' => $ledger,
                'nature' => $ledger->accountGroup ? $ledger->accountGroup->nature : 'Assets',
                'debit' => $debit,
                'credit' => $credit,
            ];
        }

        return view('reports.financial.trial_balance', compact('rows', 'totalDebit', 'totalCredit'));
    }

    public function profitAndLoss(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $startDate = $request->get('start_date', Carbon::now()->startOfYear()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        // Revenue Ledgers
        $revenueLedgers = Ledger::where('company_id', $companyId)
            ->whereHas('accountGroup', fn($q) => $q->where('nature', 'Revenue'))
            ->get();

        $totalRevenue = 0;
        $revenueRows = [];
        foreach ($revenueLedgers as $l) {
            $l->recalculateBalance();
            $val = (float)$l->current_balance;
            $totalRevenue += $val;
            $revenueRows[] = ['ledger' => $l, 'amount' => $val];
        }

        // Expense Ledgers
        $expenseLedgers = Ledger::where('company_id', $companyId)
            ->whereHas('accountGroup', fn($q) => $q->where('nature', 'Expenses'))
            ->get();

        $totalExpense = 0;
        $expenseRows = [];
        foreach ($expenseLedgers as $l) {
            $l->recalculateBalance();
            $val = (float)$l->current_balance;
            $totalExpense += $val;
            $expenseRows[] = ['ledger' => $l, 'amount' => $val];
        }

        $netProfit = $totalRevenue - $totalExpense;

        return view('reports.financial.profit_and_loss', compact(
            'revenueRows', 'totalRevenue', 'expenseRows', 'totalExpense', 'netProfit', 'startDate', 'endDate'
        ));
    }

    public function balanceSheet(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $assetLedgers = Ledger::where('company_id', $companyId)
            ->whereHas('accountGroup', fn($q) => $q->where('nature', 'Assets'))
            ->get();

        $totalAssets = 0;
        $assetRows = [];
        foreach ($assetLedgers as $l) {
            $l->recalculateBalance();
            $val = (float)$l->current_balance;
            $totalAssets += $val;
            $assetRows[] = ['ledger' => $l, 'amount' => $val];
        }

        $liabilityLedgers = Ledger::where('company_id', $companyId)
            ->whereHas('accountGroup', fn($q) => $q->whereIn('nature', ['Liabilities', 'Equity']))
            ->get();

        $totalLiabilities = 0;
        $liabilityRows = [];
        foreach ($liabilityLedgers as $l) {
            $l->recalculateBalance();
            $val = (float)$l->current_balance;
            $totalLiabilities += $val;
            $liabilityRows[] = ['ledger' => $l, 'amount' => $val];
        }

        return view('reports.financial.balance_sheet', compact(
            'assetRows', 'totalAssets', 'liabilityRows', 'totalLiabilities'
        ));
    }

    public function dayBook(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $date = $request->get('date', Carbon::today()->toDateString());

        $entries = JournalEntry::where('company_id', $companyId)
            ->where('voucher_date', $date)
            ->with(['lines.ledger', 'creator'])
            ->get();

        return view('reports.financial.day_book', compact('entries', 'date'));
    }

    // ==================== INVENTORY & STOCK REPORTS ====================
    public function stockValuation(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $godownId = $request->get('godown_id');

        $query = StockBatch::where('company_id', $companyId)->with(['item.unit', 'godown']);
        if ($godownId) {
            $query->where('godown_id', $godownId);
        }

        $batches = $query->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();

        $totalValuation = $batches->sum(fn($b) => $b->quantity * $b->cost_price);

        return view('reports.inventory.valuation', compact('batches', 'godowns', 'totalValuation', 'godownId'));
    }

    public function reorderAlertReport()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $items = Item::where('company_id', $companyId)
            ->with(['unit', 'category'])
            ->where('status', 'active')
            ->get()
            ->filter(fn($i) => $i->totalStockQuantity() <= (float)$i->reorder_quantity);

        return view('reports.inventory.reorder_alerts', compact('items'));
    }

    // ==================== PURCHASE & SALES REGISTERS ====================
    public function salesRegister(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $invoices = SalesInvoice::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->with('customer')
            ->get();

        $totalSubtotal = $invoices->sum('subtotal');
        $totalTax = $invoices->sum('tax_amount');
        $totalGrand = $invoices->sum('grand_total');
        $totalPaid = $invoices->sum('paid_amount');
        $totalBalance = $invoices->sum('balance_amount');

        return view('reports.sales.sales_register', compact(
            'invoices', 'startDate', 'endDate', 'totalSubtotal', 'totalTax', 'totalGrand', 'totalPaid', 'totalBalance'
        ));
    }

    public function purchaseRegister(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $startDate = $request->get('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', Carbon::now()->toDateString());

        $entries = PurchaseEntry::where('company_id', $companyId)
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->with('supplier')
            ->get();

        $totalSubtotal = $entries->sum('subtotal');
        $totalTax = $entries->sum('tax_amount');
        $totalGrand = $entries->sum('grand_total');
        $totalPaid = $entries->sum('paid_amount');
        $totalBalance = $entries->sum('balance_amount');

        return view('reports.purchase.purchase_register', compact(
            'entries', 'startDate', 'endDate', 'totalSubtotal', 'totalTax', 'totalGrand', 'totalPaid', 'totalBalance'
        ));
    }

    public function customerOutstanding()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $customers = Customer::where('company_id', $companyId)
            ->where('current_balance', '>', 0)
            ->with('ledger')
            ->get();

        $totalOutstanding = $customers->sum('current_balance');
        return view('reports.sales.customer_outstanding', compact('customers', 'totalOutstanding'));
    }

    public function supplierOutstanding()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $suppliers = Supplier::where('company_id', $companyId)
            ->where('current_balance', '>', 0)
            ->with('ledger')
            ->get();

        $totalOutstanding = $suppliers->sum('current_balance');
        return view('reports.purchase.supplier_outstanding', compact('suppliers', 'totalOutstanding'));
    }

    // ==================== PAYROLL REPORT ====================
    public function payrollReport(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $month = $request->get('month', Carbon::now()->format('Y-m'));

        $payrolls = Payroll::where('company_id', $companyId)
            ->where('salary_month', $month)
            ->with('staff.department')
            ->get();

        $totalGross = $payrolls->sum('gross_salary');
        $totalDeductions = $payrolls->sum('total_deductions');
        $totalNet = $payrolls->sum('net_salary');

        return view('reports.staff.payroll_report', compact('payrolls', 'month', 'totalGross', 'totalDeductions', 'totalNet'));
    }
}


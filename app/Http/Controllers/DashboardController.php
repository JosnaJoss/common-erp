<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\GoodsReceiptNote;
use App\Models\Item;
use App\Models\PurchaseEntry;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\Staff;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $company = CompanyContextService::getActiveCompany();

        if (!$companyId) {
            return view('dashboard.welcome');
        }

        // Metrics
        $totalCustomers = Customer::where('company_id', $companyId)->count();
        $totalSuppliers = Supplier::where('company_id', $companyId)->count();
        $totalStaff = Staff::where('company_id', $companyId)->where('status', 'active')->count();
        $totalItems = Item::where('company_id', $companyId)->where('status', 'active')->count();

        // Financial & Sales Metrics
        $today = Carbon::today()->toDateString();
        $thisMonth = Carbon::now()->startOfMonth()->toDateString();

        $todaySales = SalesInvoice::where('company_id', $companyId)->where('invoice_date', $today)->sum('grand_total');
        $monthSales = SalesInvoice::where('company_id', $companyId)->where('invoice_date', '>=', $thisMonth)->sum('grand_total');

        $todayPurchases = PurchaseEntry::where('company_id', $companyId)->where('invoice_date', $today)->sum('grand_total');
        $monthPurchases = PurchaseEntry::where('company_id', $companyId)->where('invoice_date', '>=', $thisMonth)->sum('grand_total');

        $customerOutstanding = Customer::where('company_id', $companyId)->sum('current_balance');
        $supplierOutstanding = Supplier::where('company_id', $companyId)->sum('current_balance');

        $pendingPurchaseRequests = PurchaseRequest::where('company_id', $companyId)->where('status', 'pending')->count();
        $pendingSalesOrders = SalesOrder::where('company_id', $companyId)->whereIn('status', ['confirmed', 'draft'])->count();

        // Stock Valuation
        $stockValuation = DB::table('stock_batches')
            ->where('company_id', $companyId)
            ->selectRaw('SUM(quantity * cost_price) as total_val')
            ->value('total_val') ?? 0;

        // Low stock items
        $lowStockItems = Item::where('company_id', $companyId)
            ->with('unit')
            ->where('status', 'active')
            ->get()
            ->filter(function ($item) {
                return $item->totalStockQuantity() <= (float)$item->reorder_quantity;
            });

        // Recent Invoices
        $recentInvoices = SalesInvoice::where('company_id', $companyId)
            ->with(['customer', 'godown'])
            ->latest('invoice_date')
            ->take(5)
            ->get();

        // Recent Purchases
        $recentPurchases = PurchaseEntry::where('company_id', $companyId)
            ->with(['supplier', 'godown'])
            ->latest('invoice_date')
            ->take(5)
            ->get();

        // Monthly Sales & Purchases Data for Chart.js (Last 6 months)
        $months = [];
        $salesData = [];
        $purchaseData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $monthKey = $date->format('M Y');
            $yearMonth = $date->format('Y-m');
            $months[] = $monthKey;

            $salesTotal = SalesInvoice::where('company_id', $companyId)
                ->whereYear('invoice_date', $date->year)
                ->whereMonth('invoice_date', $date->month)
                ->sum('grand_total');
            $salesData[] = (float)$salesTotal;

            $purchaseTotal = PurchaseEntry::where('company_id', $companyId)
                ->whereYear('invoice_date', $date->year)
                ->whereMonth('invoice_date', $date->month)
                ->sum('grand_total');
            $purchaseData[] = (float)$purchaseTotal;
        }

        return view('dashboard.index', compact(
            'company',
            'totalCustomers',
            'totalSuppliers',
            'totalStaff',
            'totalItems',
            'todaySales',
            'monthSales',
            'todayPurchases',
            'monthPurchases',
            'customerOutstanding',
            'supplierOutstanding',
            'pendingPurchaseRequests',
            'pendingSalesOrders',
            'stockValuation',
            'lowStockItems',
            'recentInvoices',
            'recentPurchases',
            'months',
            'salesData',
            'purchaseData'
        ));
    }
}


<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Common ERP Web Routes
|--------------------------------------------------------------------------
*/

// Public Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Authenticated Application Routes
Route::middleware(['auth', 'company.context'])->group(function () {
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');

    // Company & Branch Switcher & Management
    Route::post('/company/switch', [CompanyController::class, 'switchCompany'])->name('company.switch');
    Route::post('/branch/switch', [CompanyController::class, 'switchBranch'])->name('branch.switch');
    Route::resource('companies', CompanyController::class)->except(['destroy', 'show']);
    Route::post('/companies/{company}/branches', [CompanyController::class, 'branchStore'])->name('companies.branches.store');

    // Master Data
    Route::prefix('master')->name('master.')->group(function () {
        // Departments
        Route::get('/departments', [MasterDataController::class, 'departments'])->name('departments.index');
        Route::post('/departments', [MasterDataController::class, 'departmentStore'])->name('departments.store');
        Route::put('/departments/{department}', [MasterDataController::class, 'departmentUpdate'])->name('departments.update');
        Route::delete('/departments/{department}', [MasterDataController::class, 'departmentDestroy'])->name('departments.destroy');

        // Godowns
        Route::get('/godowns', [MasterDataController::class, 'godowns'])->name('godowns.index');
        Route::post('/godowns', [MasterDataController::class, 'godownStore'])->name('godown.store');
        Route::post('/godowns/new', [MasterDataController::class, 'godownStore'])->name('godowns.store');
        Route::put('/godowns/{godown}', [MasterDataController::class, 'godownUpdate'])->name('godowns.update');
        Route::delete('/godowns/{godown}', [MasterDataController::class, 'godownDestroy'])->name('godowns.destroy');

        // Units
        Route::get('/units', [MasterDataController::class, 'units'])->name('units.index');
        Route::post('/units', [MasterDataController::class, 'unitStore'])->name('units.store');
        Route::put('/units/{unit}', [MasterDataController::class, 'unitUpdate'])->name('units.update');

        // Categories & Subcategories
        Route::get('/categories', [MasterDataController::class, 'categories'])->name('categories.index');
        Route::post('/categories', [MasterDataController::class, 'categoryStore'])->name('categories.store');
        Route::post('/subcategories', [MasterDataController::class, 'subcategoryStore'])->name('subcategories.store');

        // Products
        Route::get('/products', [MasterDataController::class, 'products'])->name('products.index');
        Route::post('/products', [MasterDataController::class, 'productStore'])->name('products.store');

        // Items (with 3-tier pricing)
        Route::get('/items', [MasterDataController::class, 'items'])->name('items.index');
        Route::get('/items/create', [MasterDataController::class, 'itemCreate'])->name('items.create');
        Route::post('/items', [MasterDataController::class, 'itemStore'])->name('items.store');
        Route::get('/items/{item}/edit', [MasterDataController::class, 'itemEdit'])->name('items.edit');
        Route::put('/items/{item}', [MasterDataController::class, 'itemUpdate'])->name('items.update');
        Route::delete('/items/{item}', [MasterDataController::class, 'itemDestroy'])->name('items.destroy');

        // Vehicles & Routes
        Route::get('/vehicles', [MasterDataController::class, 'vehicles'])->name('vehicles.index');
        Route::post('/vehicles', [MasterDataController::class, 'vehicleStore'])->name('vehicles.store');
        Route::get('/routes', [MasterDataController::class, 'routes'])->name('routes.index');
        Route::post('/routes', [MasterDataController::class, 'routeStore'])->name('routes.store');
    });

    // Stakeholders: Suppliers & Customers
    Route::resource('suppliers', SupplierController::class)->except(['destroy']);
    Route::resource('customers', CustomerController::class)->except(['destroy']);

    // Staff & HR Management
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/', [StaffController::class, 'index'])->name('index');
        Route::get('/create', [StaffController::class, 'create'])->name('create');
        Route::post('/', [StaffController::class, 'store'])->name('store');
        Route::get('/{staff}', [StaffController::class, 'show'])->name('show')->where('staff', '[0-9]+');
        Route::get('/{staff}/edit', [StaffController::class, 'edit'])->name('edit')->where('staff', '[0-9]+');
        Route::put('/{staff}', [StaffController::class, 'update'])->name('update')->where('staff', '[0-9]+');
        Route::delete('/{staff}', [StaffController::class, 'destroy'])->name('destroy')->where('staff', '[0-9]+');
        Route::post('/{staff}/documents', [StaffController::class, 'uploadDocument'])->name('documents.upload')->where('staff', '[0-9]+');

        // Shifts & Holidays
        Route::get('/shifts/list', [AttendanceController::class, 'shifts'])->name('shifts.index');
        Route::post('/shifts', [AttendanceController::class, 'shiftStore'])->name('shifts.store');
        Route::get('/holidays/list', [AttendanceController::class, 'holidays'])->name('holidays.index');
        Route::post('/holidays', [AttendanceController::class, 'holidayStore'])->name('holidays.store');

        // Attendance
        Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendance.index');
        Route::post('/attendance', [AttendanceController::class, 'store'])->name('attendance.store');
        Route::post('/attendance/bulk', [AttendanceController::class, 'bulkStore'])->name('attendance.bulk');

        // Leaves
        Route::get('/leaves/list', [LeaveController::class, 'index'])->name('leaves.index');
        Route::post('/leaves', [LeaveController::class, 'store'])->name('leaves.store');
        Route::post('/leaves/{leaveApplication}/status', [LeaveController::class, 'updateStatus'])->name('leaves.status');

        // Overtime
        Route::get('/overtime/list', [AttendanceController::class, 'overtime'])->name('overtime.index');
        Route::post('/overtime', [AttendanceController::class, 'overtimeStore'])->name('overtime.store');
        Route::post('/overtime/{overtime}/approve', [AttendanceController::class, 'overtimeApprove'])->name('overtime.approve');

        // Payroll
        Route::get('/payroll/list', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('/payroll/{payroll}/payslip', [PayrollController::class, 'payslip'])->name('payroll.payslip');
        Route::post('/payroll/{payroll}/pay', [PayrollController::class, 'pay'])->name('payroll.pay');
    });

    // Stock & Inventory
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/overview', [InventoryController::class, 'overview'])->name('overview');
        Route::get('/movements', [InventoryController::class, 'movements'])->name('movements');
        Route::get('/inward', [InventoryController::class, 'inwardCreate'])->name('inward.create');
        Route::post('/inward', [InventoryController::class, 'inwardStore'])->name('inward.store');
        Route::get('/outward', [InventoryController::class, 'outwardCreate'])->name('outward.create');
        Route::post('/outward', [InventoryController::class, 'outwardStore'])->name('outward.store');
        Route::get('/transfers', [InventoryController::class, 'transfers'])->name('transfers');
        Route::post('/transfers', [InventoryController::class, 'transferStore'])->name('transfers.store');
        Route::get('/adjustments', [InventoryController::class, 'adjustments'])->name('adjustments');
        Route::post('/adjustments', [InventoryController::class, 'adjustmentStore'])->name('adjustments.store');
        Route::get('/alerts', [InventoryController::class, 'alerts'])->name('alerts');
    });

    // Procurement & Purchases
    Route::prefix('purchase')->name('purchase.')->group(function () {
        Route::get('/requests', [PurchaseController::class, 'requests'])->name('requests.index');
        Route::post('/requests', [PurchaseController::class, 'requestStore'])->name('requests.store');
        Route::post('/requests/{purchaseRequest}/approve', [PurchaseController::class, 'requestApprove'])->name('requests.approve');

        Route::get('/orders', [PurchaseController::class, 'orders'])->name('orders.index');
        Route::get('/orders/create', [PurchaseController::class, 'orderCreate'])->name('orders.create');
        Route::post('/orders', [PurchaseController::class, 'orderStore'])->name('orders.store');
        Route::get('/orders/{order}', [PurchaseController::class, 'orderShow'])->name('orders.show');

        Route::get('/grns', [PurchaseController::class, 'grns'])->name('grns.index');
        Route::get('/grns/create', [PurchaseController::class, 'grnCreate'])->name('grns.create');
        Route::post('/grns', [PurchaseController::class, 'grnStore'])->name('grns.store');
        Route::get('/grns/{grn}', [PurchaseController::class, 'grnShow'])->name('grns.show');

        Route::get('/entries', [PurchaseController::class, 'entries'])->name('entries.index');
        Route::get('/entries/create', [PurchaseController::class, 'entryCreate'])->name('entries.create');
        Route::post('/entries', [PurchaseController::class, 'entryStore'])->name('entries.store');
        Route::get('/entries/{entry}', [PurchaseController::class, 'entryShow'])->name('entries.show');

        Route::get('/payments', [PurchaseController::class, 'payments'])->name('payments.index');
        Route::post('/payments', [PurchaseController::class, 'paymentStore'])->name('payments.store');
    });

    // Sales & Distribution
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/orders', [SalesController::class, 'orders'])->name('orders.index');
        Route::get('/orders/create', [SalesController::class, 'orderCreate'])->name('orders.create');
        Route::post('/orders', [SalesController::class, 'orderStore'])->name('orders.store');
        Route::get('/orders/{order}', [SalesController::class, 'orderShow'])->name('orders.show');

        Route::get('/invoices', [SalesController::class, 'invoices'])->name('invoices.index');
        Route::get('/invoices/create', [SalesController::class, 'invoiceCreate'])->name('invoices.create');
        Route::post('/invoices', [SalesController::class, 'invoiceStore'])->name('invoices.store');
        Route::get('/invoices/{invoice}', [SalesController::class, 'invoiceShow'])->name('invoices.show');
        Route::get('/invoices/{invoice}/print', [SalesController::class, 'invoicePrint'])->name('invoices.print');

        Route::get('/deliveries', [SalesController::class, 'deliveries'])->name('deliveries.index');
        Route::post('/deliveries', [SalesController::class, 'deliveryStore'])->name('deliveries.store');
        Route::post('/deliveries/{delivery}/status', [SalesController::class, 'deliveryUpdateStatus'])->name('deliveries.status');

        Route::get('/collections', [SalesController::class, 'collections'])->name('collections.index');
        Route::post('/collections', [SalesController::class, 'collectionStore'])->name('collections.store');
    });

    // Accounts & Double-Entry Finance
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/chart-of-accounts', [AccountsController::class, 'chartOfAccounts'])->name('chart_of_accounts');

        Route::get('/ledgers', [AccountsController::class, 'ledgers'])->name('ledgers.index');
        Route::post('/ledgers', [AccountsController::class, 'ledgerStore'])->name('ledgers.store');
        Route::get('/ledgers/{ledger}/statement', [AccountsController::class, 'ledgerStatement'])->name('ledgers.statement');

        Route::get('/vouchers', [AccountsController::class, 'journalEntries'])->name('vouchers.index');
        Route::get('/vouchers/create', [AccountsController::class, 'journalCreate'])->name('vouchers.create');
        Route::post('/vouchers', [AccountsController::class, 'journalStore'])->name('vouchers.store');
        Route::get('/vouchers/{entry}', [AccountsController::class, 'journalShow'])->name('vouchers.show');

        Route::get('/financial-years', [AccountsController::class, 'financialYears'])->name('financial_years.index');
        Route::post('/financial-years', [AccountsController::class, 'financialYearStore'])->name('financial_years.store');
    });

    // Reports (Financial, Inventory, Sales, Purchase, Staff)
    Route::prefix('reports')->name('reports.')->group(function () {
        // Financial
        Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('financial.trial_balance');
        Route::get('/profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('financial.profit_and_loss');
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('financial.balance_sheet');
        Route::get('/day-book', [ReportController::class, 'dayBook'])->name('financial.day_book');

        // Inventory
        Route::get('/stock-valuation', [ReportController::class, 'stockValuation'])->name('inventory.valuation');
        Route::get('/reorder-alerts', [ReportController::class, 'reorderAlertReport'])->name('inventory.reorder_alerts');

        // Sales & Customers
        Route::get('/sales-register', [ReportController::class, 'salesRegister'])->name('sales.sales_register');
        Route::get('/customer-outstanding', [ReportController::class, 'customerOutstanding'])->name('sales.customer_outstanding');

        // Procurement & Suppliers
        Route::get('/purchase-register', [ReportController::class, 'purchaseRegister'])->name('purchase.purchase_register');
        Route::get('/supplier-outstanding', [ReportController::class, 'supplierOutstanding'])->name('purchase.supplier_outstanding');

        // Staff & Payroll
        Route::get('/payroll-report', [ReportController::class, 'payrollReport'])->name('staff.payroll_report');
    });

    // System Settings & Security
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/users', [SettingsController::class, 'users'])->name('users.index');
        Route::post('/users', [SettingsController::class, 'userStore'])->name('users.store');
        Route::post('/users/{user}/update', [SettingsController::class, 'userUpdate'])->name('users.update');

        Route::get('/roles', [SettingsController::class, 'roles'])->name('roles.index');
        Route::post('/roles', [SettingsController::class, 'roleStore'])->name('roles.store');
        Route::get('/roles/{role}/edit', [SettingsController::class, 'roleEdit'])->name('roles.edit');
        Route::post('/roles/{role}/update', [SettingsController::class, 'roleUpdate'])->name('roles.update');

        Route::get('/modules', [SettingsController::class, 'modules'])->name('modules.index');
        Route::post('/modules', [SettingsController::class, 'modulesUpdate'])->name('modules.update');

        Route::get('/audit-logs', [SettingsController::class, 'auditLogs'])->name('audit_logs.index');
    });
});

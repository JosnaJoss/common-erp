<?php

use App\Http\Controllers\AccountsController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BankReconciliationController;
use App\Http\Controllers\CapitalController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DailyOperationController;
use App\Http\Controllers\DailyOperationReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PettyCashController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StaffAdvanceLoanController;
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
        Route::get('/categories/{category}/edit', [MasterDataController::class, 'categoryEdit'])->name('categories.edit');
        Route::put('/categories/{category}', [MasterDataController::class, 'categoryUpdate'])->name('categories.update');

        Route::post('/subcategories', [MasterDataController::class, 'subcategoryStore'])->name('subcategories.store');
        Route::get('/subcategories/{subcategory}/edit', [MasterDataController::class, 'subcategoryEdit'])->name('subcategories.edit');
        Route::put('/subcategories/{subcategory}', [MasterDataController::class, 'subcategoryUpdate'])->name('subcategories.update');

        // Products
        Route::get('/products', [MasterDataController::class, 'products'])->name('products.index');
        Route::post('/products', [MasterDataController::class, 'productStore'])->name('products.store');
        Route::get('/products/{product}/edit', [MasterDataController::class, 'productEdit'])->name('products.edit');
        Route::put('/products/{product}', [MasterDataController::class, 'productUpdate'])->name('products.update');

        // Items (with 3-tier pricing)
        Route::get('/items', [MasterDataController::class, 'items'])->name('items.index');
        Route::get('/items/create', [MasterDataController::class, 'itemCreate'])->name('items.create');
        Route::post('/items', [MasterDataController::class, 'itemStore'])->name('items.store');
        Route::get('/items/{item}/edit', [MasterDataController::class, 'itemEdit'])->name('items.edit');
        Route::put('/items/{item}', [MasterDataController::class, 'itemUpdate'])->name('items.update');
        Route::delete('/items/{item}', [MasterDataController::class, 'itemDestroy'])->name('items.destroy');

        // Vehicles
        Route::get('/vehicles', [MasterDataController::class, 'vehicles'])->name('vehicles.index');
        Route::post('/vehicles', [MasterDataController::class, 'vehicleStore'])->name('vehicles.store');

        // Territory & Route Management (Routes, Cities, Locations)
        Route::get('/routes', [MasterDataController::class, 'routes'])->name('routes.index');
        Route::post('/routes', [MasterDataController::class, 'routeStore'])->name('routes.store');
        Route::put('/routes/{route}', [MasterDataController::class, 'routeUpdate'])->name('routes.update');
        Route::delete('/routes/{route}', [MasterDataController::class, 'routeDestroy'])->name('routes.destroy');

        // Cities
        Route::get('/cities', [MasterDataController::class, 'routes'])->name('cities.index');
        Route::post('/cities', [MasterDataController::class, 'cityStore'])->name('cities.store');
        Route::put('/cities/{city}', [MasterDataController::class, 'cityUpdate'])->name('cities.update');
        Route::delete('/cities/{city}', [MasterDataController::class, 'cityDestroy'])->name('cities.destroy');

        // Locations
        Route::get('/locations', [MasterDataController::class, 'routes'])->name('locations.index');
        Route::post('/locations', [MasterDataController::class, 'locationStore'])->name('locations.store');
        Route::put('/locations/{location}', [MasterDataController::class, 'locationUpdate'])->name('locations.update');
        Route::delete('/locations/{location}', [MasterDataController::class, 'locationDestroy'])->name('locations.destroy');

        // Dependent dropdown helper endpoints
        Route::get('/branches/{branch}/cities', [MasterDataController::class, 'getCitiesByBranch'])->name('branches.cities');
        Route::get('/cities/{city}/locations', [MasterDataController::class, 'getLocationsByCity'])->name('cities.locations');
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
        Route::post('/leaves/{leave}/status', [LeaveController::class, 'updateStatus'])->name('leaves.status');

        // Overtime
        Route::get('/overtime/list', [AttendanceController::class, 'overtime'])->name('overtime.index');
        Route::post('/overtime', [AttendanceController::class, 'overtimeStore'])->name('overtime.store');
        Route::post('/overtime/{overtime}/approve', [AttendanceController::class, 'overtimeApprove'])->name('overtime.approve');

        // Payroll
        Route::get('/payroll/list', [PayrollController::class, 'index'])->name('payroll.index');
        Route::post('/payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
        Route::get('/payroll/{payroll}/payslip', [PayrollController::class, 'payslip'])->name('payroll.payslip');
        Route::post('/payroll/{payroll}/pay', [PayrollController::class, 'pay'])->name('payroll.pay');
        Route::post('/payroll/bulk-pay', [PayrollController::class, 'bulkPay'])->name('payroll.bulk_pay');

        // Salary Advances
        Route::get('/advances', [StaffAdvanceLoanController::class, 'advancesIndex'])->name('advances.index');
        Route::post('/advances', [StaffAdvanceLoanController::class, 'advanceStore'])->name('advances.store');

        // Staff Loans
        Route::get('/loans', [StaffAdvanceLoanController::class, 'loansIndex'])->name('loans.index');
        Route::post('/loans', [StaffAdvanceLoanController::class, 'loanStore'])->name('loans.store');
        Route::get('/loans/{loan}', [StaffAdvanceLoanController::class, 'loanShow'])->name('loans.show');
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
        Route::post('/transfers/{transfer}/dispatch', [InventoryController::class, 'transferDispatch'])->name('transfers.dispatch');
        Route::post('/transfers/{transfer}/receive', [InventoryController::class, 'transferReceive'])->name('transfers.receive');
        Route::get('/adjustments', [InventoryController::class, 'adjustments'])->name('adjustments');
        Route::post('/adjustments', [InventoryController::class, 'adjustmentStore'])->name('adjustments.store');
        Route::get('/alerts', [InventoryController::class, 'alerts'])->name('alerts');
        Route::get('/opening-stock', [InventoryController::class, 'openingStock'])->name('opening_stock.index');
        Route::post('/opening-stock', [InventoryController::class, 'openingStockStore'])->name('opening_stock.store');
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
        Route::get('/entries/{entry}/edit', [PurchaseController::class, 'entryEdit'])->name('entries.edit');
        Route::put('/entries/{entry}', [PurchaseController::class, 'entryUpdate'])->name('entries.update');

        Route::get('/payments', [PurchaseController::class, 'payments'])->name('payments.index');
        Route::post('/payments', [PurchaseController::class, 'paymentStore'])->name('payments.store');

        Route::get('/returns', [PurchaseController::class, 'returns'])->name('returns.index');
        Route::get('/returns/create', [PurchaseController::class, 'returnCreate'])->name('returns.create');
        Route::post('/returns', [PurchaseController::class, 'returnStore'])->name('returns.store');
        Route::get('/returns/{return}', [PurchaseController::class, 'returnShow'])->name('returns.show');
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
        Route::get('/invoices/{invoice}/pdf', [SalesController::class, 'invoicePdf'])->name('invoices.pdf');
        Route::get('/invoices/{invoice}/edit', [SalesController::class, 'invoiceEdit'])->name('invoices.edit');
        Route::put('/invoices/{invoice}', [SalesController::class, 'invoiceUpdate'])->name('invoices.update');

        Route::get('/deliveries', [SalesController::class, 'deliveries'])->name('deliveries.index');
        Route::post('/deliveries', [SalesController::class, 'deliveryStore'])->name('deliveries.store');
        Route::post('/deliveries/{delivery}/status', [SalesController::class, 'deliveryUpdateStatus'])->name('deliveries.status');

        Route::get('/collections', [SalesController::class, 'collections'])->name('collections.index');
        Route::post('/collections', [SalesController::class, 'collectionStore'])->name('collections.store');

        Route::get('/returns', [SalesController::class, 'returns'])->name('returns.index');
        Route::get('/returns/create', [SalesController::class, 'returnCreate'])->name('returns.create');
        Route::post('/returns', [SalesController::class, 'returnStore'])->name('returns.store');
        Route::get('/returns/{return}', [SalesController::class, 'returnShow'])->name('returns.show');
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

        // Bank Reconciliation (BRS)
        Route::prefix('bank-reconciliation')->name('bank_reconciliation.')->group(function () {
            Route::get('/', [BankReconciliationController::class, 'index'])->name('index');
            Route::get('/create', [BankReconciliationController::class, 'create'])->name('create');
            Route::post('/', [BankReconciliationController::class, 'store'])->name('store');
            Route::get('/{bankReconciliation}', [BankReconciliationController::class, 'show'])->name('show');
            Route::post('/{bankReconciliation}/import', [BankReconciliationController::class, 'importStatement'])->name('import');
            Route::post('/{bankReconciliation}/match', [BankReconciliationController::class, 'match'])->name('match');
            Route::post('/{bankReconciliation}/unmatch', [BankReconciliationController::class, 'unmatch'])->name('unmatch');
            Route::post('/{bankReconciliation}/clear/{item}', [BankReconciliationController::class, 'toggleClearance'])->name('clear');
            Route::post('/{bankReconciliation}/adjustment', [BankReconciliationController::class, 'recordAdjustment'])->name('adjustment');
            Route::post('/{bankReconciliation}/complete', [BankReconciliationController::class, 'complete'])->name('complete');
            Route::get('/{bankReconciliation}/report', [BankReconciliationController::class, 'report'])->name('report');
        });

        // Petty Cash Module
        Route::prefix('petty-cash')->name('petty_cash.')->group(function () {
            Route::get('/', [PettyCashController::class, 'index'])->name('index');
            Route::post('/accounts', [PettyCashController::class, 'accountsStore'])->name('accounts.store');
            Route::post('/categories', [PettyCashController::class, 'categoriesStore'])->name('categories.store');
            Route::get('/vouchers/create', [PettyCashController::class, 'voucherCreate'])->name('vouchers.create');
            Route::post('/vouchers', [PettyCashController::class, 'voucherStore'])->name('vouchers.store');
            Route::get('/vouchers/{voucher}', [PettyCashController::class, 'voucherShow'])->name('vouchers.show');
            Route::get('/vouchers/{voucher}/print', [PettyCashController::class, 'voucherPrint'])->name('vouchers.print');
            Route::post('/replenish', [PettyCashController::class, 'replenishStore'])->name('replenish.store');
            Route::get('/daybook', [PettyCashController::class, 'daybook'])->name('daybook');
        });

        // Capital Investment & Drawings Module
        Route::prefix('capital')->name('capital.')->group(function () {
            Route::get('/', [CapitalController::class, 'index'])->name('index');
            Route::post('/investors', [CapitalController::class, 'investorStore'])->name('investors.store');
            Route::put('/investors/{investor}', [CapitalController::class, 'investorUpdate'])->name('investors.update');
            Route::get('/investors/{investor}', [CapitalController::class, 'investorShow'])->name('investors.show');
            Route::post('/transactions', [CapitalController::class, 'transactionStore'])->name('transactions.store');
            Route::get('/statement/{investor?}', [CapitalController::class, 'statement'])->name('statement');
        });
    });

    // Daily Operations & Work Reports
    Route::prefix('operations')->name('daily_operations.')->group(function () {
        Route::get('/', [DailyOperationController::class, 'index'])->name('index');
        Route::get('/create', [DailyOperationController::class, 'create'])->name('create');
        Route::post('/', [DailyOperationController::class, 'store'])->name('store');
        Route::get('/{daily_operation}', [DailyOperationController::class, 'show'])->name('show');
        Route::post('/{daily_operation}/submit', [DailyOperationController::class, 'submit'])->name('submit');
        Route::post('/{daily_operation}/approve', [DailyOperationController::class, 'approve'])->name('approve');
        Route::post('/{daily_operation}/reject', [DailyOperationController::class, 'reject'])->name('reject');
        Route::get('/helpers/department-staff/{department}', [DailyOperationController::class, 'getDepartmentStaff'])->name('helpers.staff');
        Route::get('/helpers/item-stock', [DailyOperationController::class, 'getItemStock'])->name('helpers.stock');
    });

    // Reports (Financial, Inventory, Sales, Purchase, Staff)
    Route::prefix('reports')->name('reports.')->group(function () {
        // Daily Operations Executive Report
        Route::get('/daily-operations', [DailyOperationReportController::class, 'index'])->name('daily_operations.index');

        // Financial
        Route::get('/trial-balance', [ReportController::class, 'trialBalance'])->name('financial.trial_balance');
        Route::get('/profit-and-loss', [ReportController::class, 'profitAndLoss'])->name('financial.profit_and_loss');
        Route::get('/balance-sheet', [ReportController::class, 'balanceSheet'])->name('financial.balance_sheet');
        Route::get('/cash-flow', [ReportController::class, 'cashFlow'])->name('financial.cash_flow');
        Route::get('/day-book', [ReportController::class, 'dayBook'])->name('financial.day_book');

        // Inventory
        Route::get('/stock-valuation', [ReportController::class, 'stockValuation'])->name('inventory.valuation');
        Route::get('/inventory-reconciliation', [ReportController::class, 'inventoryReconciliation'])->name('inventory.reconciliation');
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

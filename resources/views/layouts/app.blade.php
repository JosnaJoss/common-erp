<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - {{ config('app.name', 'Common ERP') }}</title>

    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        :root {
            --sidebar-bg: #0f172a;
            --sidebar-hover: #1e293b;
            --sidebar-active: #2563eb;
            --sidebar-text: #cbd5e1;
            --content-bg: #f8fafc;
        }

        body {
            background-color: var(--content-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #334155;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        #sidebar {
            width: 270px;
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            min-height: 100vh;
            transition: all 0.25s ease;
            z-index: 1000;
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            border-bottom: 1px solid #1e293b;
            text-decoration: none;
        }

        .sidebar-brand i {
            font-size: 1.5rem;
            margin-right: 0.75rem;
            color: #38bdf8;
        }

        .sidebar-nav {
            padding: 1rem 0;
            overflow-y: auto;
            max-height: calc(100vh - 75px);
        }

        .sidebar-heading {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.75rem 1.5rem 0.25rem;
            color: #64748b;
            font-weight: 700;
        }

        .nav-link-custom {
            display: flex;
            align-items: center;
            padding: 0.6rem 1.5rem;
            color: var(--sidebar-text);
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }

        .nav-link-custom i {
            font-size: 1.1rem;
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .nav-link-custom:hover {
            color: #ffffff;
            background-color: var(--sidebar-hover);
        }

        .nav-link-custom.active {
            color: #ffffff;
            background-color: var(--sidebar-hover);
            border-left-color: var(--sidebar-active);
            font-weight: 600;
        }

        .sidebar-submenu {
            padding-left: 2.75rem;
            background-color: rgba(15, 23, 42, 0.4);
        }

        .sidebar-submenu .nav-link-custom {
            padding: 0.45rem 1rem;
            font-size: 0.82rem;
        }

        /* Top Header */
        .top-navbar {
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 999;
        }

        .main-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .content-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .card-custom {
            border: 1px solid #e2e8f0;
            border-radius: 0.65rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            background-color: #ffffff;
        }

        .badge-status {
            font-weight: 600;
            padding: 0.35em 0.65em;
            border-radius: 0.375rem;
            font-size: 0.75rem;
        }

        @media print {
            #sidebar, .top-navbar, .no-print {
                display: none !important;
            }
            .content-area, .main-wrapper {
                margin: 0 !important;
                padding: 0 !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body>

<div class="main-wrapper">
    <!-- SIDEBAR -->
    <nav id="sidebar">
        <a href="{{ route('dashboard') }}" class="sidebar-brand">
            <i class="bi bi-boxes"></i>
            <span>COMMON ERP</span>
        </a>

        @php
            $activeCompany = \App\Services\CompanyContextService::getActiveCompany();
            $user = auth()->user();
        @endphp

        <div class="sidebar-nav">
            <div class="sidebar-heading">Core</div>
            <a href="{{ route('dashboard') }}" class="nav-link-custom {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <!-- Multi-Company -->
            @if($user && ($user->isSuperAdmin() || $user->hasRole('company-admin')))
            <div class="sidebar-heading">Enterprise Setup</div>
            <a href="{{ route('companies.index') }}" class="nav-link-custom {{ request()->routeIs('companies.*') ? 'active' : '' }}">
                <i class="bi bi-building"></i> Companies & Branches
            </a>
            @endif

            <!-- Master Data -->
            <div class="sidebar-heading">Master Data</div>
            <a class="nav-link-custom d-flex justify-content-between" data-bs-toggle="collapse" href="#masterSubmenu">
                <span><i class="bi bi-folder2-open"></i> Master Registry</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ request()->is('master*') ? 'show' : '' }}" id="masterSubmenu">
                <div class="sidebar-submenu">
                    <a href="{{ route('master.items.index') }}" class="nav-link-custom {{ request()->routeIs('master.items.*') ? 'active' : '' }}">Items & Price Levels</a>
                    <a href="{{ route('master.categories.index') }}" class="nav-link-custom {{ request()->routeIs('master.categories.*') ? 'active' : '' }}">Categories</a>
                    <a href="{{ route('master.products.index') }}" class="nav-link-custom {{ request()->routeIs('master.products.*') ? 'active' : '' }}">Products</a>
                    <a href="{{ route('master.units.index') }}" class="nav-link-custom {{ request()->routeIs('master.units.*') ? 'active' : '' }}">Units of Measure</a>
                    <a href="{{ route('master.godowns.index') }}" class="nav-link-custom {{ request()->routeIs('master.godowns.*') ? 'active' : '' }}">Godowns / Warehouses</a>
                    <a href="{{ route('master.departments.index') }}" class="nav-link-custom {{ request()->routeIs('master.departments.*') ? 'active' : '' }}">Departments</a>
                    @if(\App\Services\CompanyContextService::isModuleEnabled('vehicle_route'))
                    <a href="{{ route('master.vehicles.index') }}" class="nav-link-custom {{ request()->routeIs('master.vehicles.*') ? 'active' : '' }}">Vehicles & Drivers</a>
                    <a href="{{ route('master.routes.index') }}" class="nav-link-custom {{ request()->routeIs('master.routes.*') ? 'active' : '' }}">Territory & Routes</a>
                    @endif
                </div>
            </div>

            <!-- Stakeholders -->
            <div class="sidebar-heading">Stakeholders</div>
            <a href="{{ route('suppliers.index') }}" class="nav-link-custom {{ request()->routeIs('suppliers.*') ? 'active' : '' }}">
                <i class="bi bi-truck"></i> Suppliers
            </a>
            <a href="{{ route('customers.index') }}" class="nav-link-custom {{ request()->routeIs('customers.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i> Customers & Pricing
            </a>

            <!-- Staff & HR -->
            @if(\App\Services\CompanyContextService::isModuleEnabled('staff_hr'))
            <div class="sidebar-heading">Staff & Human Resources</div>
            <a class="nav-link-custom d-flex justify-content-between" data-bs-toggle="collapse" href="#staffSubmenu">
                <span><i class="bi bi-person-badge"></i> Staff & Payroll</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ request()->is('staff*') ? 'show' : '' }}" id="staffSubmenu">
                <div class="sidebar-submenu">
                    <a href="{{ route('staff.index') }}" class="nav-link-custom {{ request()->routeIs('staff.index', 'staff.show', 'staff.create', 'staff.edit') ? 'active' : '' }}">Staff Directory</a>
                    <a href="{{ route('staff.attendance.index') }}" class="nav-link-custom {{ request()->routeIs('staff.attendance.*') ? 'active' : '' }}">Daily Attendance</a>
                    <a href="{{ route('staff.payroll.index') }}" class="nav-link-custom {{ request()->routeIs('staff.payroll.*') ? 'active' : '' }}">Payroll Processing</a>
                    <a href="{{ route('staff.leaves.index') }}" class="nav-link-custom {{ request()->routeIs('staff.leaves.*') ? 'active' : '' }}">Leave Requests</a>
                    <a href="{{ route('staff.overtime.index') }}" class="nav-link-custom {{ request()->routeIs('staff.overtime.*') ? 'active' : '' }}">Overtime Tracking</a>
                    <a href="{{ route('staff.shifts.index') }}" class="nav-link-custom {{ request()->routeIs('staff.shifts.*') ? 'active' : '' }}">Shifts & Rosters</a>
                    <a href="{{ route('staff.holidays.index') }}" class="nav-link-custom {{ request()->routeIs('staff.holidays.*') ? 'active' : '' }}">Holiday Calendar</a>
                </div>
            </div>
            @endif

            <!-- Inventory -->
            @if(\App\Services\CompanyContextService::isModuleEnabled('inventory'))
            <div class="sidebar-heading">Inventory & Warehousing</div>
            <a class="nav-link-custom d-flex justify-content-between" data-bs-toggle="collapse" href="#inventorySubmenu">
                <span><i class="bi bi-box-seam"></i> Stock Management</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ request()->is('inventory*') ? 'show' : '' }}" id="inventorySubmenu">
                <div class="sidebar-submenu">
                    <a href="{{ route('inventory.overview') }}" class="nav-link-custom {{ request()->routeIs('inventory.overview') ? 'active' : '' }}">Stock Balances & Batches</a>
                    <a href="{{ route('inventory.movements') }}" class="nav-link-custom {{ request()->routeIs('inventory.movements') ? 'active' : '' }}">Stock Movement Ledger</a>
                    <a href="{{ route('inventory.inward.create') }}" class="nav-link-custom {{ request()->routeIs('inventory.inward.*') ? 'active' : '' }}">Stock Inward</a>
                    <a href="{{ route('inventory.outward.create') }}" class="nav-link-custom {{ request()->routeIs('inventory.outward.*') ? 'active' : '' }}">Stock Outward</a>
                    <a href="{{ route('inventory.transfers') }}" class="nav-link-custom {{ request()->routeIs('inventory.transfers*') ? 'active' : '' }}">Godown Transfers</a>
                    <a href="{{ route('inventory.adjustments') }}" class="nav-link-custom {{ request()->routeIs('inventory.adjustments*') ? 'active' : '' }}">Stock Adjustments</a>
                    <a href="{{ route('inventory.alerts') }}" class="nav-link-custom {{ request()->routeIs('inventory.alerts*') ? 'active' : '' }}">Low Stock Alerts</a>
                </div>
            </div>
            @endif

            <!-- Purchase Management -->
            @if(\App\Services\CompanyContextService::isModuleEnabled('purchase'))
            <div class="sidebar-heading">Procurement</div>
            <a class="nav-link-custom d-flex justify-content-between" data-bs-toggle="collapse" href="#purchaseSubmenu">
                <span><i class="bi bi-cart-check"></i> Purchase Operations</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ request()->is('purchase*') ? 'show' : '' }}" id="purchaseSubmenu">
                <div class="sidebar-submenu">
                    <a href="{{ route('purchase.requests.index') }}" class="nav-link-custom {{ request()->routeIs('purchase.requests.*') ? 'active' : '' }}">Purchase Requests</a>
                    <a href="{{ route('purchase.orders.index') }}" class="nav-link-custom {{ request()->routeIs('purchase.orders.*') ? 'active' : '' }}">Purchase Orders (PO)</a>
                    <a href="{{ route('purchase.grns.index') }}" class="nav-link-custom {{ request()->routeIs('purchase.grns.*') ? 'active' : '' }}">Goods Receipts (GRN)</a>
                    <a href="{{ route('purchase.entries.index') }}" class="nav-link-custom {{ request()->routeIs('purchase.entries.*') ? 'active' : '' }}">Purchase Bills / Inward</a>
                    <a href="{{ route('purchase.payments.index') }}" class="nav-link-custom {{ request()->routeIs('purchase.payments.*') ? 'active' : '' }}">Supplier Payments</a>
                </div>
            </div>
            @endif

            <!-- Sales Management -->
            @if(\App\Services\CompanyContextService::isModuleEnabled('sales'))
            <div class="sidebar-heading">Commercial & Sales</div>
            <a class="nav-link-custom d-flex justify-content-between" data-bs-toggle="collapse" href="#salesSubmenu">
                <span><i class="bi bi-receipt"></i> Sales Operations</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ request()->is('sales*') ? 'show' : '' }}" id="salesSubmenu">
                <div class="sidebar-submenu">
                    <a href="{{ route('sales.orders.index') }}" class="nav-link-custom {{ request()->routeIs('sales.orders.*') ? 'active' : '' }}">Sales Orders (SO)</a>
                    <a href="{{ route('sales.invoices.index') }}" class="nav-link-custom {{ request()->routeIs('sales.invoices.*') ? 'active' : '' }}">Sales Invoices</a>
                    <a href="{{ route('sales.deliveries.index') }}" class="nav-link-custom {{ request()->routeIs('sales.deliveries.*') ? 'active' : '' }}">Dispatch & Route Delivery</a>
                    <a href="{{ route('sales.collections.index') }}" class="nav-link-custom {{ request()->routeIs('sales.collections.*') ? 'active' : '' }}">Customer Collections</a>
                </div>
            </div>
            @endif

            <!-- Accounts & Finance -->
            @if(\App\Services\CompanyContextService::isModuleEnabled('accounts'))
            <div class="sidebar-heading">Finance & Bookkeeping</div>
            <a class="nav-link-custom d-flex justify-content-between" data-bs-toggle="collapse" href="#accountsSubmenu">
                <span><i class="bi bi-journal-text"></i> Double-Entry Accounts</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ request()->is('accounts*') ? 'show' : '' }}" id="accountsSubmenu">
                <div class="sidebar-submenu">
                    <a href="{{ route('accounts.chart_of_accounts') }}" class="nav-link-custom {{ request()->routeIs('accounts.chart_of_accounts') ? 'active' : '' }}">Chart of Accounts</a>
                    <a href="{{ route('accounts.ledgers.index') }}" class="nav-link-custom {{ request()->routeIs('accounts.ledgers.*') ? 'active' : '' }}">Ledgers & Balances</a>
                    <a href="{{ route('accounts.vouchers.index') }}" class="nav-link-custom {{ request()->routeIs('accounts.vouchers.*') ? 'active' : '' }}">Journal Entries / Vouchers</a>
                    <a href="{{ route('accounts.financial_years.index') }}" class="nav-link-custom {{ request()->routeIs('accounts.financial_years.*') ? 'active' : '' }}">Financial Years</a>
                </div>
            </div>
            @endif

            <!-- Reports -->
            <div class="sidebar-heading">Intelligence & Reports</div>
            <a class="nav-link-custom d-flex justify-content-between" data-bs-toggle="collapse" href="#reportsSubmenu">
                <span><i class="bi bi-bar-chart-line"></i> ERP Reports</span>
                <i class="bi bi-chevron-down small"></i>
            </a>
            <div class="collapse {{ request()->is('reports*') ? 'show' : '' }}" id="reportsSubmenu">
                <div class="sidebar-submenu">
                    <a href="{{ route('reports.financial.trial_balance') }}" class="nav-link-custom {{ request()->routeIs('reports.financial.trial_balance') ? 'active' : '' }}">Trial Balance</a>
                    <a href="{{ route('reports.financial.profit_and_loss') }}" class="nav-link-custom {{ request()->routeIs('reports.financial.profit_and_loss') ? 'active' : '' }}">Profit & Loss</a>
                    <a href="{{ route('reports.financial.balance_sheet') }}" class="nav-link-custom {{ request()->routeIs('reports.financial.balance_sheet') ? 'active' : '' }}">Balance Sheet</a>
                    <a href="{{ route('reports.financial.day_book') }}" class="nav-link-custom {{ request()->routeIs('reports.financial.day_book') ? 'active' : '' }}">Day Book</a>
                    <a href="{{ route('reports.sales.sales_register') }}" class="nav-link-custom {{ request()->routeIs('reports.sales.sales_register') ? 'active' : '' }}">Sales Register</a>
                    <a href="{{ route('reports.purchase.purchase_register') }}" class="nav-link-custom {{ request()->routeIs('reports.purchase.purchase_register') ? 'active' : '' }}">Purchase Register</a>
                    <a href="{{ route('reports.inventory.valuation') }}" class="nav-link-custom {{ request()->routeIs('reports.inventory.valuation') ? 'active' : '' }}">Stock Valuation</a>
                    <a href="{{ route('reports.sales.customer_outstanding') }}" class="nav-link-custom {{ request()->routeIs('reports.sales.customer_outstanding') ? 'active' : '' }}">Customer Outstanding</a>
                    <a href="{{ route('reports.purchase.supplier_outstanding') }}" class="nav-link-custom {{ request()->routeIs('reports.purchase.supplier_outstanding') ? 'active' : '' }}">Supplier Outstanding</a>
                    <a href="{{ route('reports.staff.payroll_report') }}" class="nav-link-custom {{ request()->routeIs('reports.staff.payroll_report') ? 'active' : '' }}">Payroll Register</a>
                </div>
            </div>

            <!-- Administration & Settings -->
            @if($user && ($user->isSuperAdmin() || $user->hasRole('company-admin')))
            <div class="sidebar-heading">Administration</div>
            <a href="{{ route('settings.users.index') }}" class="nav-link-custom {{ request()->routeIs('settings.users.*') ? 'active' : '' }}">
                <i class="bi bi-person-gear"></i> User Management
            </a>
            <a href="{{ route('settings.roles.index') }}" class="nav-link-custom {{ request()->routeIs('settings.roles.*') ? 'active' : '' }}">
                <i class="bi bi-shield-lock"></i> Roles & Permissions
            </a>
            <a href="{{ route('settings.modules.index') }}" class="nav-link-custom {{ request()->routeIs('settings.modules.*') ? 'active' : '' }}">
                <i class="bi bi-toggles"></i> Module Activation
            </a>
            <a href="{{ route('settings.audit_logs.index') }}" class="nav-link-custom {{ request()->routeIs('settings.audit_logs.*') ? 'active' : '' }}">
                <i class="bi bi-activity"></i> Audit Logs
            </a>
            @endif
        </div>
    </nav>

    <!-- CONTENT WRAPPER -->
    <div class="content-area">
        <!-- TOP NAVBAR -->
        <header class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" id="sidebarToggle">
                    <i class="bi bi-list"></i>
                </button>

                <!-- Company Switcher -->
                <div class="dropdown">
                    @php
                        $allCompanies = \App\Models\Company::where('status', 'active')->get();
                    @endphp
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 fw-semibold text-dark" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-building text-primary"></i>
                        <span>{{ $activeCompany ? $activeCompany->name : 'Select Company' }}</span>
                    </button>
                    <ul class="dropdown-menu shadow-sm">
                        <li><h6 class="dropdown-header">Switch Active Company</h6></li>
                        @foreach($allCompanies as $comp)
                        <li>
                            <form action="{{ route('company.switch') }}" method="POST">
                                @csrf
                                <input type="hidden" name="company_id" value="{{ $comp->id }}">
                                <button type="submit" class="dropdown-item d-flex justify-content-between align-items-center {{ $activeCompany && $activeCompany->id === $comp->id ? 'active' : '' }}">
                                    <span>{{ $comp->name }}</span>
                                    <span class="badge bg-secondary ms-2 text-capitalize">{{ $comp->business_type }}</span>
                                </button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <!-- Branch Switcher -->
                @if($activeCompany && $activeCompany->branches->count() > 0)
                @php
                    $activeBranch = \App\Services\CompanyContextService::getActiveBranch();
                @endphp
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 text-secondary" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-geo-alt"></i>
                        <span>{{ $activeBranch ? $activeBranch->name : 'All Branches' }}</span>
                    </button>
                    <ul class="dropdown-menu shadow-sm">
                        <li><h6 class="dropdown-header">Branches in {{ $activeCompany->code }}</h6></li>
                        @foreach($activeCompany->branches as $br)
                        <li>
                            <form action="{{ route('branch.switch') }}" method="POST">
                                @csrf
                                <input type="hidden" name="branch_id" value="{{ $br->id }}">
                                <button type="submit" class="dropdown-item {{ $activeBranch && $activeBranch->id === $br->id ? 'active' : '' }}">
                                    {{ $br->name }} ({{ $br->code }})
                                </button>
                            </form>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Quick Create Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-primary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-plus-circle"></i> Quick New
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li><a class="dropdown-item" href="{{ route('sales.invoices.create') }}"><i class="bi bi-receipt me-2 text-primary"></i> New Sales Invoice</a></li>
                        <li><a class="dropdown-item" href="{{ route('purchase.entries.create') }}"><i class="bi bi-bag-plus me-2 text-success"></i> New Purchase Bill</a></li>
                        <li><a class="dropdown-item" href="{{ route('accounts.vouchers.create') }}"><i class="bi bi-journal-plus me-2 text-info"></i> New Journal Entry</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('customers.create') }}"><i class="bi bi-person-plus me-2"></i> Add Customer</a></li>
                        <li><a class="dropdown-item" href="{{ route('suppliers.create') }}"><i class="bi bi-truck me-2"></i> Add Supplier</a></li>
                        <li><a class="dropdown-item" href="{{ route('master.items.create') }}"><i class="bi bi-box me-2"></i> Add Item</a></li>
                    </ul>
                </div>

                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle fs-5 text-secondary"></i>
                        <span class="d-none d-sm-inline fw-semibold">{{ auth()->user()->name ?? 'User' }}</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="px-3 py-2 border-bottom">
                            <div class="fw-bold">{{ auth()->user()->name }}</div>
                            <small class="text-muted">{{ auth()->user()->role->name ?? 'Staff' }}</small>
                        </li>
                        <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person me-2"></i> My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- MAIN CONTENT BODY -->
        <main class="p-4 flex-grow-1">
            <!-- Flash Messages -->
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-check-circle-fill fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if(session('error') || session('danger'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div>{{ session('error') ?? session('danger') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon-fill me-1"></i> Please fix the following errors:</div>
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </main>

        <footer class="text-center py-3 text-muted small border-top bg-white no-print">
            {{ $activeCompany ? $activeCompany->name : 'Common ERP' }} &copy; {{ date('Y') }} &middot; Enterprise Resource Planning Engine
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        sidebar.classList.toggle('d-none');
    });
</script>
@stack('scripts')
</body>
</html>

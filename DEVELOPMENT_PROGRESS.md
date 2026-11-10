# Common ERP Software - Development Progress & Technical Documentation

## Overview
This document provides a comprehensive log of the architecture, database schema, domain services, controllers, Blade views, workflows, and automated tests developed for the **Common ERP Software**.

The application is built on **Laravel 10**, **PHP 8.2**, **MariaDB/MySQL**, **Bootstrap 5**, and **Chart.js**. It implements true multi-tenancy (multi-company and multi-branch data isolation), granular Role-Based Access Control (RBAC), and flexible module toggles so that diverse businesses (Manufacturing, Wholesale, Distribution, Trading, Retail, and Services) run on a unified codebase.

---

## 1. Technology Stack & Verification
- **Framework:** Laravel 10.50.4
- **Language:** PHP 8.2.4
- **Database:** MariaDB 10.4.28 (Port 3306)
- **Frontend:** Laravel Blade + Bootstrap 5.3 + Bootstrap Icons 1.11
- **Visualizations:** Chart.js 4.4.1
- **Testing:** PHPUnit with 10 Feature/Unit tests (57 assertions, 100% pass)

---

## 2. General Architecture & Multi-Tenancy

### Multi-Company & Branch Isolation
- Support for multiple companies and multiple branches per company.
- Every master entity and financial transaction is scoped by `company_id` and `branch_id`.
- The top navigation bar includes live dropdown switchers for Active Company and Active Branch.
- `App\Services\CompanyContextService` resolves the active session tenant.
- `App\Http\Middleware\SetCompanyContext` automatically injects active tenant parameters and shares `$activeCompany` and `$activeBranch` with all Blade views.

### Configurable Modular Architecture
Each company dynamically activates or deactivates modules without code modification via the `company_modules` table:
- `staff_hr`: Staff directory, attendance, shifts, leaves, overtime, statutory PF/ESI, payroll.
- `inventory`: Warehousing, multi-godown stock, batches (FIFO), movements, physical adjustments, transfers, alerts.
- `purchase`: Requisitions, POs, GRNs, QC inspections, vendor bills, supplier payments.
- `sales`: Sales Orders, 3-tier customer pricing, GST invoices, route dispatches, collections.
- `accounts`: Chart of Accounts, ledgers, balanced journal vouchers, financial statements.
- `production`: Bill of Materials (BOM), production orders, raw-material conversions.
- `vehicle_route`: Logistics fleet, vehicle fitness/insurance, delivery routes.
- `quality`: QC inspections, acceptance/rejection workflows.
- `crm`: Customer leads, pipeline stages.

---

## 3. Database Schema & Migrations

All 11 migrations are executed in ordered sequences:
1. `2024_01_01_000001_create_companies_and_branches_tables.php`
   - `companies` (name, code, business_type, registration_no, gst_no, pan_no, financial_year_start/end, status)
   - `branches` (company_id, name, code, is_main_branch, address, city, state, postal_code, status)
   - `company_modules` (company_id, module_key, is_enabled)
2. `2024_01_01_000002_create_roles_and_permissions_tables.php`
   - `roles` (name, slug, description, is_system)
   - `permissions` (name, slug, module, action: view, add, edit, delete, approve, export)
   - `role_permissions` (role_id, permission_id)
3. `2024_01_01_000003_create_users_table.php`
   - `users` (company_id, branch_id, role_id, name, email, password, phone, is_active, last_login_at)
   - `user_company_branches` (user_id, company_id, branch_id)
4. `2024_01_01_000004_create_master_tables.php`
   - `departments`, `godowns`, `units`, `categories`, `subcategories`, `products`, `items` (3-tier prices: `min_price`, `normal_price`, `max_price`), `cities`, `locations`, `vehicles`, `routes`, `route_cities`, `route_locations`
5. `2024_01_01_000005_create_accounts_and_finance_tables.php`
   - `financial_years`, `account_groups` (parent_id, nature: Assets, Liabilities, Equity, Revenue, Expenses), `ledgers` (opening_balance, current_balance, balance_type, is_bank_or_cash), `journal_entries` (voucher_no, voucher_type, voucher_date, total_debit, total_credit), `journal_lines` (ledger_id, debit, credit, narration), `payment_allocations`
6. `2024_01_01_000006_create_suppliers_and_customers_tables.php`
   - `suppliers` (ledger_id, code, gst_number, pan_number, current_balance)
   - `customers` (ledger_id, code, pricing_level: minimum, normal, maximum, credit_limit, current_balance)
7. `2024_01_01_000007_create_staff_and_hr_tables.php`
   - `staff` (employee_id, basic_salary, hra, pf_applicable, pf_rate_percent, esi_applicable, esi_rate_percent, bank_account_number, driving_licence_number)
   - `staff_documents`, `shifts`, `holidays`, `attendances`, `leave_applications`, `staff_targets`, `incentives`, `staff_allowances`, `overtimes`, `payrolls`
8. `2024_01_01_000008_create_stock_and_inventory_tables.php`
   - `stock_batches` (godown_id, item_id, batch_no, quantity, cost_price, expiry_date)
   - `stock_movements` (godown_id, item_id, batch_id, movement_type: inward, outward, transfer, adjustment)
   - `stock_transfers`, `stock_transfer_items`, `stock_adjustments`, `stock_adjustment_items`, `stock_requests`
9. `2024_01_01_000009_create_purchase_tables.php`
   - `purchase_requests`, `purchase_request_items`, `purchase_orders`, `purchase_order_items`, `goods_receipt_notes`, `grn_items`, `quality_checks`, `purchase_entries`, `purchase_entry_items`, `purchase_returns`, `supplier_payments`
10. `2024_01_01_000010_create_sales_tables.php`
    - `sales_orders`, `sales_order_items`, `sales_invoices`, `sales_invoice_items`, `sales_deliveries`, `sales_delivery_items`, `sales_returns`, `customer_collections`
11. `2024_01_01_000011_create_audit_logs_and_settings_tables.php`
    - `system_settings`, `audit_logs`, `boms`, `bom_items`, `production_orders`, `crm_leads`

---

## 4. Domain Services

### `App\Services\AccountingService`
- `createJournalEntry()`: Creates atomic double-entry vouchers ensuring `abs(sum(debit) - sum(credit)) < 0.001`.
- `updateLedgerBalance()`: Real-time recalculation of subsidiary debtor/creditor balances and general ledger balances.
- `generateVoucherNumber()`: Formats unique sequential vouchers (e.g. `JV-2026-0001`, `PAY-2026-0001`).
- `getOrCreateStandardLedger()`: Automatic resolution of standard ledgers (Purchase Account, Sales Account, GST Input Tax Credit, GST Output Tax Liability).

### `App\Services\InventoryService`
- `recordInward()`: Adds stock into designated godown and batch (or creates new batch); logs immutable stock movement.
- `recordOutward()`: Validates sufficient stock exists before deduction, preventing negative balances; logs stock movement.
- `transferStock()`: Atomic inter-godown transfer with source deduction and destination addition.

### `App\Services\CompanyContextService`
- Manages active company and active branch in session.
- Provides cached checking of company module enablement (`isModuleEnabled($moduleKey)`).

---

## 5. Completed Controllers & Workflows
- **`AuthController`**: Login, Logout, Profile update, Password change with Hash verification and Audit Logging.
- **`DashboardController`**: KPI cards, Monthly Sales vs Purchases Chart.js trends, low stock alerts, recent transactions.
- **`CompanyController`**: Multi-company CRUD, branch creation, active company/branch session switching.
- **`MasterDataController`**: Departments, Godowns, Units of Measure, Categories, Subcategories, Products, Items (with 3-tier pricing), Vehicles, Delivery Routes.
- **`SupplierController` & `CustomerController`**: Full profile management with automatic double-entry subsidiary ledger synchronization.
- **`StaffController`, `AttendanceController`, `LeaveController`, `PayrollController`**: Complete HR suite, shifts, holidays, overtime, statutory PF & ESI computations, and printable payslips.
- **`InventoryController`**: Overview, stock ledger/movements, direct inward/outward, inter-godown transfers, physical count adjustments, and reorder alerts.
- **`PurchaseController`**: Purchase requests, PO creation, Goods Receipt Notes (GRN), purchase invoice billing (with automatic stock inward and debit/credit ledger posting), and supplier disbursements.
- **`SalesController`**: Sales order confirmation, GST tax invoicing (with stock outward deduction and debit/credit ledger posting), official printable GST invoice, dispatch/delivery routing, and customer collections.
- **`AccountsController`**: Hierarchical Chart of Accounts, Ledger master, Account Passbook statement, multi-line balanced Journal Vouchers, and Financial Years.
- **`ReportController`**: Trial Balance (Debit = Credit verification), Profit & Loss statement, Balance Sheet, Day Book, Stock Valuation, Reorder alerts, Sales & Purchase registers, Customer/Supplier outstandings, and Payroll summary.
- **`SettingsController`**: User management, custom roles, permission matrix configuration, company module activation, and audit logs.

---

## 6. Verification & Automated Test Results
Run via `php artisan test`:
```
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\ErpCompleteWorkflowTest
  ✓ login screen renders and admin can authenticate
  ✓ dashboard renders with metrics and charts
  ✓ inventory overview and batch tracking
  ✓ purchase inward updates stock and posts balanced accounting journal
  ✓ sales invoice deducts stock and posts balanced accounting journal
  ✓ customer collection receipt and journal posting
  ✓ payroll generation with statutory pf and esi deductions
  ✓ financial reports trial balance debits equal credits

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

  Tests:    10 passed (57 assertions)
  Duration: 3.18s
```

All 10 tests across the entire application pass with zero errors and zero warnings.

---

## 7. Demo Accounts & Seed Data
The database has been pre-seeded with 3 realistic companies:
1. **Apex Industrial Solutions Pvt Ltd** (`code: APEX-IND`): Manufacturing, Distribution & Trading.
2. **Apex Mart Wholesale & Retail** (`code: APEX-MART`): Retail & Wholesale.
3. **Apex Tech & Facility Services** (`code: APEX-SERV`): Service-based business (Inventory disabled).

### Login Credentials
- **Super Admin:** `admin@erp.com` / `password123`
- **Company Admin:** `admin@apexindustrial.com` / `password123`
- **Accountant:** `accounts@apexindustrial.com` / `password123`
- **Inventory Manager:** `inventory@apexindustrial.com` / `password123`
- **Sales Manager:** `sales@apexindustrial.com` / `password123`
- **Purchase Manager:** `purchase@apexindustrial.com` / `password123`
- **HR Manager:** `hr@apexindustrial.com` / `password123`

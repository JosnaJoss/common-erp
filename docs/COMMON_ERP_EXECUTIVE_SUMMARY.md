# Common ERP – Executive Summary & Application Overview

## 1. Executive Summary & Product Identity

**Common ERP** is a centralized, multi-company, multi-industry Enterprise Resource Planning (ERP) platform developed using **Laravel 10.50.4** and **MySQL 8.0 / MariaDB** on **PHP 8.2.4**. The primary architectural mission of the platform is to provide a single, reusable codebase capable of servicing distinct industry verticals—including food manufacturing, garment manufacturing and retail, beverage bottling, jewelry retail, technical services, wholesale distribution, and engineering—under an isolated multi-tenant architecture.

The application operates as a **monolithic server-side rendered application** using Laravel Blade, Bootstrap 5, and vanilla JavaScript. It avoids the operational complexity of multi-database tenancy by employing a **shared database schema with discriminator-column (`company_id`) multi-tenancy**, enforced strictly at the HTTP session and Eloquent query boundary.

### High-Level System Metrics
| Metric | Count / Status | Verification Reference |
| :--- | :--- | :--- |
| **Laravel Framework Version** | `10.50.4` | `composer.json` / `artisan --version` |
| **PHP Runtime Requirement** | `^8.2` (Running 8.2.4) | `composer.json` |
| **Database Tables** | `96` (66 tenant-scoped, 30 child/system) | MySQL Schema Inspection |
| **Eloquent Models** | `89` Active Models | `app/Models/*.php` |
| **Controllers** | `23` Files (22 functional + base) | `app/Http/Controllers/*.php` |
| **Core Service Classes** | `5` Domain Services | `app/Services/*.php` |
| **Web Routes Defined** | `233` Active Routes | `routes/web.php` |
| **API Routes Defined** | `1` Default Sanctum Route (`/api/user`) | `routes/api.php` |
| **Blade Views** | `120` Template Files | `resources/views/**/*.blade.php` |
| **Automated Test Suite** | **47 Passed (761 assertions, 0 errors, 0 failures)** | `tests/Feature/*.php`, `tests/Unit/*.php` |
| **Active Tenant Companies** | `15` (3 Preserved Historic + 12 Seeded Demo) | `companies` table |
| **Commercial Readiness Score** | **68% / 100% (Prototype / Managed Pilot Ready)** | Operational Risk Assessment |

---

## 2. Multi-Tenancy Model & Security Posture

### Multi-Tenancy Architecture
Common ERP implements **single-database, column-level shared-schema multi-tenancy**. 
- The tenant context is determined at runtime via the authenticated user's session: `session(['active_company_id'])`.
- Tenancy is intercepted on every HTTP request by the custom middleware `\App\Http\Middleware\SetCompanyContext`.
- The central service `\App\Services\CompanyContextService` resolves the active company, shares it with all Blade views (`view()->share('currentCompany')`), and guarantees that tenant-specific queries are bound to the active context.

### Tenant Isolation & Security Hardening
Following Phase 3 security corrections, the platform enforces strict cross-company isolation:
1. **Model Binding & Record Ownership Checks**: Controllers explicitly verify that fetched records belong to `CompanyContextService::getActiveCompanyId()`, aborting with HTTP `403 Forbidden` on mismatch.
2. **Forged Foreign Key Rejection**: Form requests and controller validations utilize scoped database rules (e.g., `Rule::exists('customers', 'id')->where('company_id', $companyId)`) to prevent cross-company entity associations.
3. **Automated Security Verification**: The security perimeter is defended by `tests/Feature/TenantBoundarySecurityTest.php` with **15 automated negative security tests** covering Customer, Supplier, Staff, Ledger, Purchase, Sales, Stock Transfers, Daily Operations, and Overtime approvals.

---

## 3. High-Level Module Readiness Summary

| Module Domain | Controller / Service | Primary Database Tables | Feature Status | Commercial Readiness |
| :--- | :--- | :--- | :--- | :---: |
| **Core & Multi-Company** | `CompanyController`<br>`CompanyContextService` | `companies`, `company_settings`, `branches` | `IMPLEMENTED AND VERIFIED` | **90%** |
| **Authentication & RBAC** | Standard Laravel Auth<br>`CheckPermission` | `users`, `roles`, `permissions`, `role_has_permissions` | `IMPLEMENTED AND VERIFIED` | **85%** |
| **Master Data Management** | `MasterDataController` | `categories`, `products`, `units`, `customers`, `suppliers`, `routes`, `vehicles` | `IMPLEMENTED AND VERIFIED` | **95%** |
| **Purchasing & Landed Cost** | `PurchaseController`<br>`InventoryService` | `purchase_entries`, `purchase_items`, `purchase_charges`, `purchase_orders` | `IMPLEMENTED AND VERIFIED` | **90%** |
| **Sales & POS Billing** | `SalesController`<br>`InventoryService` | `sales_invoices`, `sales_invoice_items`, `sales_orders`, `sales_deliveries` | `IMPLEMENTED AND VERIFIED` | **85%** |
| **Warehouse & Inventory** | `InventoryController`<br>`InventoryService` | `inventory_stocks`, `stock_ledger_entries`, `stock_transfers`, `stock_transfer_items` | `IMPLEMENTED AND VERIFIED` | **90%** |
| **Manufacturing Operations** | `DailyOperationController`<br>`DailyOperationService` | `daily_operations`, `daily_operation_materials`, `industry_process_templates` | `PARTIALLY IMPLEMENTED` | **60%** |
| **Discrete Manufacturing (MRP)** | *No Controller / No Views* | `boms`, `bom_items`, `production_orders`, `quality_checks` | `BACKEND ONLY / UI MISSING` | **15%** |
| **Fleet & Logistics** | `MasterDataController`<br>`SalesController` | `vehicles`, `routes`, `drivers`, `sales_deliveries` | `PARTIALLY IMPLEMENTED` | **50%** |
| **HR & Payroll** | `StaffController`<br>`PayrollController` | `staff`, `attendance`, `payroll_periods`, `payroll_slips`, `staff_loans`, `advances` | `IMPLEMENTED AND VERIFIED` | **85%** |
| **Financial Accounting** | `AccountingController`<br>`AccountingService` | `chart_of_accounts`, `journal_vouchers`, `journal_entries`, `bank_reconciliations` | `IMPLEMENTED AND VERIFIED` | **85%** |
| **Reporting & BI** | `ReportController` | Views querying Ledgers, Registers, Day Books, Invoices | `PARTIALLY IMPLEMENTED` | **70%** |
| **Integrations & External APIs**| *No Dedicated Controller* | `notification_logs`, `api_settings` (empty) | `NOT IMPLEMENTED` | **0%** |

---

## 4. Key Strengths of the Implementation

1. **Strict Double-Entry Ledger Posting**:
   - `AccountingService` ensures all journal vouchers are balanced (Debits equal Credits within a 0.05 tolerance margin).
   - Core financial movements—Purchase entries, Landed charges, Supplier payments, Sales invoices, Customer counter collections, Payroll disbursements, Capital investments, and Petty cash—automatically generate compliant double-entry vouchers linked to the Chart of Accounts and active Financial Year.
2. **Negative Stock Prevention & Multi-Location Inventory**:
   - `InventoryService` enforces a strict negative stock guard on outward movements (`recordOutward`), throwing a `RuntimeException` if stock is insufficient.
   - Comprehensive multi-location and batch tracking with landed-cost capitalization.
   - Support for 2-step dispatched and received inventory transfers between warehouses.
3. **Advanced Payroll Engine**:
   - Automated deduction of Loss of Pay (LOP) based on monthly calendar attendance days.
   - Statutory calculations for Provident Fund (PF) and Employee State Insurance (ESI).
   - Automated recovery of staff advances and loan installments directly during payroll generation.
4. **Multi-Unit Conversion System**:
   - `UnitConversionService` dynamically normalizes transactions in secondary units (e.g., Grams to Kilograms, Milliliters to Liters, Dozens to Pieces) to base units, preventing inventory drift.
5. **Multi-Industry Template Mechanism**:
   - Daily operations utilize reusable JSON process templates (`industry_process_templates`) allowing dynamic material consumption, worker piece-rate labor calculation, and automatic multi-location stock distribution.

---

## 5. Critical Deficiencies & Architectural Gaps

1. **Disconnected Discrete Manufacturing (BOM / MRP)**:
   - While database tables for Bills of Material (`boms`, `bom_items`), Production Orders (`production_orders`), Work Centers (`work_centers`), and Quality Inspections (`quality_checks`) exist, **no routes, controllers, or Blade views exist** to operate them.
   - All current manufacturing is routed through the simplified `DailyOperationController`.
2. **Manufacturing Cost Double-Entry Decoupling**:
   - While `DailyOperationService` updates physical raw material and finished goods inventory atomically, **it does not generate accounting journal vouchers** to capitalize labor, raw materials, and overheads into Work-in-Progress (WIP) or Finished Goods inventory asset accounts.
3. **Financial Reporting Gaps**:
   - **Cash Flow Statement is completely absent**.
   - **Balance Sheet does not dynamically integrate current-period Net Profit/Loss** from the Profit & Loss statement into the Equity section, requiring manual closing journal entries to balance.
4. **Logistics & Delivery Limitations**:
   - While delivery route assignment and status tracking (`assigned`, `in_transit`, `delivered`, `failed`) exist, there is no delivery note itemized deduction (stock is deducted upon sales invoice creation), no proof of delivery (digital signature), and no GPS driver coordination.
5. **Zero External Integrations**:
   - No payment gateway integrations (Razorpay, Stripe, UPI).
   - No automated SMS / WhatsApp gateway dispatch.
   - No Indian GST E-Way Bill or E-Invoice API integration.
   - No cloud file storage (S3/GCS); uploads reside on local disk.

---

## 6. Strategic Verdict & Next Steps

Common ERP exhibits a **strong, secure, and disciplined architectural foundation**. The multi-tenant security boundary, double-entry financial core, negative stock prevention, and unit conversion algorithms are genuinely operational and verified by 761 test assertions.

However, the application is currently a **pilot-stage monolithic ERP** suited for controlled, managed SME business deployments. It cannot be deployed as an unassisted multi-tenant SaaS or sold to high-volume manufacturing enterprises without completing the discrete MRP workflow, linking manufacturing to accounting, adding a Cash Flow statement, and implementing automated billing/export capabilities.


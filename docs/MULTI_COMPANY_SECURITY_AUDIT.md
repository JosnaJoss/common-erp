# Common ERP – Multi-Company Security & Tenant Isolation Audit

## 1. Executive Summary: Security Architecture

Common ERP implements a **shared database schema with discriminator column (`company_id`) multi-tenancy**. In such architectures, the absolute highest risk is cross-tenant data leakage or forged foreign key injection.

Following the Phase 3 Multi-Company Security Hardening initiative, the application underwent comprehensive security corrections. A dedicated negative security test suite (`tests/Feature/TenantBoundarySecurityTest.php`) was implemented, executing **15 exhaustive cross-company penetration scenarios**. All 15 tests pass with **0 failures and 0 security bypasses**.

---

## 2. The 20-Point Multi-Company Isolation Audit

| # | Inspection Point | Security Check | Mechanism Enforced | Code Reference | Audit Result |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **1** | Session Context Guard | Verify active company ID is bound to authenticated session | Stored in `session('active_company_id')`, validated against user permissions | `CompanyContextService::getActiveCompanyId()` | **PASS** |
| **2** | Middleware Execution | Intercept every web request to bind tenant context | `\App\Http\Middleware\SetCompanyContext` registered in `web` pipeline | `app/Http/Middleware/SetCompanyContext.php` | **PASS** |
| **3** | Direct URL ID Tampering (Read) | Prevent viewing Company B records via URL tampering | Explicit `company_id !== activeCompanyId` check aborting `403` | In all resource controllers (`show()` methods) | **PASS** |
| **4** | Direct URL ID Tampering (Update) | Prevent editing/updating Company B records via URL | Explicit ownership check prior to validation and update | In all resource controllers (`edit()`, `update()`) | **PASS** |
| **5** | Direct URL ID Tampering (Delete) | Prevent deleting Company B records | Explicit ownership check prior to `delete()` | In all resource controllers (`destroy()`) | **PASS** |
| **6** | Customer Isolation | Prevent access to Company B customer profiles | Route model binding ownership validation + 403 abort | `MasterDataController.php` | **PASS** |
| **7** | Supplier Isolation | Prevent access to Company B supplier profiles | Route model binding ownership validation + 403 abort | `MasterDataController.php` | **PASS** |
| **8** | Staff Directory Isolation | Prevent viewing, updating, or deleting Company B staff | Explicit `staff->company_id` verification | `StaffController.php` | **PASS** |
| **9** | Staff Branch/Dept Tampering | Prevent assigning Company B branch/department to staff | Scoped `Rule::exists('branches')->where('company_id', $cid)` | `StaffController@store` | **PASS** |
| **10** | Sales Order Foreign Key Injection | Reject sales order referencing Company B customer | Scoped validation rule on `customer_id` | `SalesController@storeOrder` | **PASS** |
| **11** | Sales Invoice Entity Tampering | Reject sales invoice referencing Company B warehouse/customer | Scoped `Rule::exists` on all entity IDs | `SalesController@storeInvoice` | **PASS** |
| **12** | Purchase Entry Foreign Key Injection | Reject purchase entry referencing Company B supplier | Scoped `Rule::exists('suppliers')->where('company_id')` | `PurchaseController@storeEntry` | **PASS** |
| **13** | Purchase Order Isolation | Prevent viewing or modifying Company B purchase orders | Explicit check on `purchase_order->company_id` | `PurchaseController@showOrder` | **PASS** |
| **14** | Purchase Requisition Isolation | Prevent viewing or approving Company B purchase requests | Explicit check on `purchase_request->company_id` | `PurchaseController@approveRequest` | **PASS** |
| **15** | Journal Voucher Cross-Posting | Prevent creating JV with accounts from Company B | Scoped validation verifying account belongs to active company | `AccountingController@storeVoucher` | **PASS** |
| **16** | Ledger Statement Privacy | Prevent viewing Company B ledger entries or statements | Scoped query in `AccountingController@ledger` | `AccountingController.php` | **PASS** |
| **17** | Inter-Company Stock Transfers | Prevent unauthorized transfers between different companies | Validates both Source and Dest warehouses belong to active tenant | `InventoryController@storeTransfer` | **PASS** |
| **18** | Daily Operation Isolation | Prevent viewing or completing Company B daily operations | Explicit `company_id` check on operation | `DailyOperationController@show` | **PASS** |
| **19** | Overtime Approval Isolation | Prevent approving overtime for Company B staff | Explicit `overtime->company_id` check | `StaffController@approveOvertime` | **PASS** |
| **20** | Unauthorized Company Switching | Prevent regular user from switching to unauthorized company | Validates target ID against `user_companies` relation | `CompanyController@switchCompany` | **PASS** |

---

## 3. Negative Security Testing Evidence (`TenantBoundarySecurityTest.php`)

All 15 security penetration tests in `tests/Feature/TenantBoundarySecurityTest.php` pass without regression. Below is the automated test execution matrix:

```
PASS  Tests\Feature\TenantBoundarySecurityTest
✓ company a user can view own customer but cannot view company b customer                 0.31s  
✓ company a user cannot edit or update company b customer                                 0.07s  
✓ company a user cannot delete company b customer                                         0.16s  
✓ company a user cannot view edit or update company b supplier                            0.13s  
✓ company a user cannot view modify or delete company b staff                             0.23s  
✓ company a user cannot view company b ledger statement or journal                        0.56s  
✓ forged foreign key sales order rejected when referencing company b customer             0.23s  
✓ forged foreign key sales invoice rejected when referencing company b records            0.10s  
✓ forged foreign key purchase entry rejected when referencing company b records           0.09s  
✓ forged cross company journal voucher rejected                                           0.07s  
✓ forged staff creation rejected when referencing company b branch or department          0.08s  
✓ cross company stock transfer dispatch and receive rejected                              0.09s  
✓ cross company daily operation show and actions rejected                                 0.11s  
✓ cross company purchase order and request rejected                                       0.11s  
✓ cross company overtime approval rejected                                                0.05s  
```

### Detailed Penetration Scenario Analysis

#### Scenario 1: Direct Resource ID Manipulation (Insecure Direct Object Reference - IDOR)
- **Attack Vector**: User of Company A inspects a customer URL (`/master/customers/42`) and changes the ID to `43` (belonging to Company B).
- **Defense Mechanism**:
  ```php
  $customer = Customer::findOrFail($id);
  if ($customer->company_id !== CompanyContextService::getActiveCompanyId()) {
      abort(403, 'Unauthorized company access');
  }
  ```
- **Result**: The application immediately terminates with HTTP 403 Forbidden. No data from Company B is rendered.

#### Scenario 2: Forged Foreign Key Injection (Cross-Tenant Association Attack)
- **Attack Vector**: User of Company A crafts a `POST /sales/orders` request with valid items, but injects a `customer_id` belonging to Company B to siphon pricing or billing data.
- **Defense Mechanism**:
  ```php
  $request->validate([
      'customer_id' => [
          'required',
          Rule::exists('customers', 'id')->where(function ($query) use ($activeCompanyId) {
              $query->where('company_id', $activeCompanyId);
          }),
      ],
  ]);
  ```
- **Result**: Laravel validation fails with an unprocessable entity / validation redirect. The cross-tenant record is never created.

#### Scenario 3: Cross-Tenant Financial Ledger Pollution
- **Attack Vector**: Attendant of Company A attempts to post a manual Journal Voucher debiting an Expense Account from Company A and crediting an Asset Account from Company B.
- **Defense Mechanism**:
  `AccountingController@storeVoucher` verifies every submitted `account_id` against `chart_of_accounts` where `company_id = activeCompanyId`.
- **Result**: Rejected at validation boundary with HTTP 302 / 422.

---

## 4. Database Schema Scoping Analysis

The MySQL database contains **96 total tables**.
- **66 Tables** contain an explicit `company_id` foreign key with indexed lookups:
  - `companies`, `company_settings`, `branches`, `chart_of_accounts`, `financial_years`, `journal_vouchers`, `journal_entries`, `categories`, `subcategories`, `products`, `item_units`, `customers`, `suppliers`, `purchase_requests`, `purchase_orders`, `purchase_entries`, `sales_orders`, `sales_invoices`, `customer_receipts`, `supplier_payments`, `inventory_stocks`, `stock_ledger_entries`, `stock_transfers`, `stock_adjustments`, `daily_operations`, `staff`, `attendance`, `payroll_periods`, `payroll_slips`, `staff_loans`, `advances`, `vehicles`, `routes`, etc.
- **30 Tables** are child or relational join tables that inherit tenant scoping through their parent headers:
  - Examples: `sales_invoice_items` (child of `sales_invoices`), `purchase_items` (child of `purchase_entries`), `stock_transfer_items` (child of `stock_transfers`), `daily_operation_materials` (child of `daily_operations`), `bom_items` (child of `boms`), `role_has_permissions`, `users_companies`.
- **System Global Tables**: `users`, `roles`, `permissions`, `cities`, `industry_process_templates`.

---

## 5. Residual Vulnerabilities & Hardening Recommendations

While HTTP route boundaries and controller actions are fully defended against IDOR and foreign key forgery, the following residual architectural risks should be noted:

1. **Absence of Global Eloquent Scope (`CompanyScope`)**:
   - Currently, tenant isolation relies on explicit controller queries (e.g. `Customer::where('company_id', $cid)`).
   - If a developer introduces a new controller or developer script and forgets to append `->where('company_id', ...)`, data leakage could occur.
   - **Recommendation**: Implement an Eloquent `TenantScope` extending `Illuminate\Database\Eloquent\Scope` applied globally to all tenant models.
2. **Asynchronous Queues & Background Jobs**:
   - Currently, Common ERP has no background jobs (`app/Jobs` is empty).
   - If background queue workers (e.g. Redis/Horizon) are introduced, worker processes do not possess an HTTP session.
   - **Recommendation**: Ensure any future Job payload explicitly serializes `company_id` and restores `CompanyContextService::setActiveCompanyId()` inside the job's `handle()` method.
3. **Raw SQL / Direct DB Facade Queries**:
   - Any raw queries (`DB::table(...)` or `DB::select(...)`) bypass Eloquent. Audit confirms all 22 functional controllers use Eloquent queries, but strict code review policies must be maintained.


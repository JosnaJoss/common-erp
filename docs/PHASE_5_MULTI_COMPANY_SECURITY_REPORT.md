# Common ERP – Phase 5 Multi-Company Security Report

## 1. Security & Tenant Boundary Architecture

Common ERP enforces multi-tenant isolation across all ERP entities through:
1. `CompanyContextService` and `company` session middleware.
2. Tenant ownership verification in all controller actions.
3. Explicit foreign-key tenant scoping rules in request validation (`Rule::exists(table, id)->where('company_id', $companyId)`).
4. Automated tenant boundary test suite verifying cross-company isolation.

---

## 2. Phase 5 Multi-Company Security Enhancements

### 2.1 Daily Operation Reversal Endpoint Security
- **Route:** `POST /operations/{daily_operation}/reverse`
- **Protection:**
  - Validates `$daily_operation->company_id === CompanyContextService::getActiveCompanyId()`.
  - Attempts by a user authenticated in Company A to reverse an operation belonging to Company B immediately abort with HTTP `403 Forbidden`.
  - Verifies user permissions (`daily_operations.reverse` or administrator).
- **Automated Verification:** `PhaseFiveEnhancementTest::test_multi_company_security_on_phase_five_enhancements` confirms cross-company reversal attempts return HTTP 403.

---

### 2.2 Manufacturing Overhead Allocation Foreign-Key Scoping
- **Endpoint:** `POST /operations`
- **Validation Rule:**
  ```php
  'overheads.*.expense_ledger_id' => [
      'nullable',
      Rule::exists('ledgers', 'id')->where('company_id', $companyId),
  ],
  ```
- **Vulnerability Prevented:** Prevents a tenant from allocating manufacturing costs against expense ledgers belonging to another company, which could lead to cross-company ledger pollution and distorted financial statements.
- **Automated Verification:** Tested with forged cross-company expense ledger IDs; correctly triggers a 422 Unprocessable Entity / validation session error.

---

### 2.3 Payroll Labor Settlement Company Scoping
- **Method:** `PayrollController::disburseSinglePayroll`
- **Protection:**
  - Queries `DailyOperationEmployee` joined to `daily_operations` strictly filtering by `company_id = $companyId`.
  - Queries `Manufacturing Labor Payable` and `Salaries & Staff Wages` ledgers scoped by `company_id`.
  - Prevents labor accrued in Company B from ever offsetting payroll expenses in Company A.

---

### 2.4 Financial & Inventory Report Tenant Boundary Isolation
- **Endpoints:**
  - `GET /reports/cash-flow`
  - `GET /reports/inventory-reconciliation`
- **Protection:**
  - All balance, movement, and ledger queries in `ReportController` enforce `where('company_id', $companyId)`.
  - Physical stock queries filter batches and godowns strictly by `company_id`.
  - No data leakage occurs across tenants.

---

## 3. Automated Security Test Results

The dedicated tenant boundary security test suite (`TenantBoundarySecurityTest`) and the Phase 5 enhancement suite verify complete isolation across all modules:

| Test Case | Method | Protected Entity | Result |
|---|---|---|---|
| `test_cross_company_daily_operation_show_and_actions_rejected` | GET / POST | Daily Operation Show, Submit, Approve, Reject | PASS (403) |
| `test_multi_company_security_on_phase_five_enhancements` | POST | Daily Operation Reversal & Overhead Ledger Scoping | PASS (403 / 422) |
| `test_company_a_user_can_view_own_customer_but_cannot_view_company_b_customer` | GET | Customer Profile | PASS (403) |
| `test_company_a_user_cannot_edit_or_update_company_b_customer` | POST / PUT | Customer Modification | PASS (403) |
| `test_company_a_user_cannot_delete_company_b_customer` | DELETE | Customer Deletion | PASS (403) |
| `test_company_a_user_cannot_view_edit_or_update_company_b_supplier` | GET / PUT | Supplier Master Data | PASS (403) |
| `test_company_a_user_cannot_view_modify_or_delete_company_b_staff` | GET / PUT / DELETE | Employee HR Records | PASS (403) |
| `test_company_a_user_cannot_view_company_b_ledger_statement_or_journal` | GET | Accounting Journals & Ledgers | PASS (403) |
| `test_forged_foreign_key_sales_order_rejected_when_referencing_company_b_customer` | POST | Sales Order Foreign Keys | PASS (Validation Error) |
| `test_forged_foreign_key_sales_invoice_rejected_when_referencing_company_b_records` | POST | Sales Invoice Foreign Keys | PASS (Validation Error) |
| `test_forged_foreign_key_purchase_entry_rejected_when_referencing_company_b_records` | POST | Purchase Entry Foreign Keys | PASS (Validation Error) |
| `test_forged_cross_company_journal_voucher_rejected` | POST | Journal Voucher Line Ledgers | PASS (Validation Error) |
| `test_forged_staff_creation_rejected_when_referencing_company_b_branch_or_department` | POST | Staff Department & Branch FKs | PASS (Validation Error) |
| `test_cross_company_stock_transfer_dispatch_and_receive_rejected` | POST | Warehouse Stock Transfers | PASS (403) |
| `test_cross_company_purchase_order_and_request_rejected` | POST | Procurement Workflow | PASS (403) |
| `test_cross_company_overtime_approval_rejected` | POST | HR Overtime Approvals | PASS (403) |

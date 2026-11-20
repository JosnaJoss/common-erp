# Common ERP – Phase 4: Multi-Company Security Regression & Boundary Audit Report

## 1. Executive Summary

A core architectural pillar of Common ERP is multi-company tenant isolation. In Phase 3, extensive multi-company security corrections were implemented and verified across 15 dedicated tenant boundary tests. 

During Phase 4, new features, financial reconciliation queries, and inventory transaction endpoints were introduced:
- Bulk opening stock storage with journal generation.
- Physical stock adjustments storage with shortage/surplus/damage journal posting.
- Daily manufacturing cost capitalization and reversal.
- Cash Flow Statement report queries.
- Inventory vs General Ledger reconciliation report queries.

This audit report certifies that **zero cross-tenant data leakage** or authorization bypasses were introduced during Phase 4. All modified and newly created controller methods, services, and queries strictly adhere to the company scoping rules defined in `CompanyContextService`.

---

## 2. Multi-Company Architecture & Tenant Isolation Strategy

Common ERP enforces tenant isolation using an explicit, query-level scoping strategy:
1. **Active Company Scoping:** `CompanyContextService::getActiveCompanyId()` retrieves the currently selected tenant ID from the authenticated user session.
2. **Explicit Foreign Key Enforcement:** Every insert, update, query, and lookup must filter explicitly by `company_id`.
3. **No Global Eloquent Scopes:** As established in Phase 3 preservation rules, global Eloquent scopes are avoided to prevent silent query corruption, unexpected behavior in background jobs, or hidden data leaking in un-scoped relationship hydration.
4. **Validation Rules with Tenant Scoping:** Form requests enforce `Rule::exists('table', 'id')->where('company_id', $companyId)`.

---

## 3. Detailed Audit of Phase 4 Endpoints & Services

### 3.1. Bulk Opening Stock (`POST /inventory/opening-stock/store`)
- **Controller:** `InventoryController@openingStockStore`
- **Security Scrutiny:**
  - `godown_id` is validated using `Rule::exists('godowns', 'id')->where('company_id', $companyId)`.
  - Every entry in `items.*.item_id` is validated using `Rule::exists('items', 'id')->where('company_id', $companyId)`.
  - Inside the database transaction, `Godown::where('id', $godownId)->where('company_id', $companyId)->firstOrFail()` and `Item::where('id', $itemId)->where('company_id', $companyId)->firstOrFail()` ensure that forged IDs cannot be processed.
  - The resulting `StockBatch`, `StockMovement`, `JournalEntry`, and `JournalLine` records are tagged with `$companyId`.

### 3.2. Stock Adjustments (`POST /inventory/adjustments/store`)
- **Controller:** `InventoryController@adjustmentStore`
- **Security Scrutiny:**
  - `godown_id` and `item_id` are both validated with `Rule::exists(...)->where('company_id', $companyId)`.
  - `StockBatch::where('godown_id', ...)->where('item_id', ...)->first()` looks up batches only within the validated company godown.
  - The created `StockAdjustment` record, `StockMovement`, and the corresponding `JournalEntry` are bound to `$companyId`.

### 3.3. Daily Operations Post & Reversal (`DailyOperationService`)
- **Service:** `DailyOperationService::postOperationStock` & `reverseOperation`
- **Security Scrutiny:**
  - Operation lookup is locked with `lockForUpdate()`.
  - Consumed raw materials outward and produced finished goods inward are executed with `$companyId` extracted directly from the verified `DailyOperation` record.
  - Standard ledgers resolved via `AccountingService::getOrCreateStandardLedger` are scoped by `$companyId`.
  - Reversal operation restricts rollback to records where `company_id = $companyId`.

### 3.4. Cash Flow Statement (`GET /reports/cash-flow`)
- **Controller:** `ReportController@cashFlow`
- **Security Scrutiny:**
  - Financial year lookup: `FinancialYear::where('company_id', $companyId)`.
  - Journal entries queried: `JournalEntry::where('company_id', $companyId)`.
  - Ledgers queried: `Ledger::where('company_id', $companyId)`.
  - Cash and Bank ledgers: `Ledger::where('company_id', $companyId)->where('is_bank_or_cash', true)`.

### 3.5. Inventory Valuation Reconciliation (`GET /reports/inventory-reconciliation`)
- **Controller:** `ReportController@inventoryReconciliation`
- **Security Scrutiny:**
  - Physical batches queried: `StockBatch::where('company_id', $companyId)`.
  - Inventory asset ledgers queried: `Ledger::where('company_id', $companyId)`.
  - Godowns and Items queried: Scoped strictly to `$companyId`.

---

## 4. Tenant Boundary Security Test Results

The full automated security test suite was executed against the modified codebase:

| Test Case | Scenario Description | Result | Status |
| :--- | :--- | :--- | :--- |
| `TenantBoundarySecurityTest::test_company_a_user_can_view_own_customer_but_cannot_view_company_b_customer` | Read isolation on Customers | HTTP 403 Forbidden | **PASS** |
| `TenantBoundarySecurityTest::test_company_a_user_cannot_edit_or_update_company_b_customer` | Write isolation on Customers | HTTP 403 Forbidden | **PASS** |
| `TenantBoundarySecurityTest::test_company_a_user_cannot_delete_company_b_customer` | Delete isolation on Customers | HTTP 403 Forbidden | **PASS** |
| `TenantBoundarySecurityTest::test_company_a_user_cannot_view_edit_or_update_company_b_supplier` | CRUD isolation on Suppliers | HTTP 403 Forbidden | **PASS** |
| `TenantBoundarySecurityTest::test_company_a_user_cannot_view_modify_or_delete_company_b_staff` | CRUD isolation on Staff | HTTP 403 Forbidden | **PASS** |
| `TenantBoundarySecurityTest::test_company_a_user_cannot_view_company_b_ledger_statement_or_journal` | Financial statement isolation | HTTP 403 Forbidden | **PASS** |
| `TenantBoundarySecurityTest::test_forged_foreign_key_sales_order_rejected_when_referencing_company_b_customer` | Forged FK attack on Sales Order | Session Validation Error | **PASS** |
| `TenantBoundarySecurityTest::test_forged_foreign_key_sales_invoice_rejected_when_referencing_company_b_records` | Forged FK attack on Sales Invoice | Session Validation Error | **PASS** |
| `TenantBoundarySecurityTest::test_forged_foreign_key_purchase_entry_rejected_when_referencing_company_b_records` | Forged FK attack on Purchase Entry | Session Validation Error | **PASS** |
| `TenantBoundarySecurityTest::test_forged_cross_company_journal_voucher_rejected` | Forged FK attack on Journal Entry | Validation Error | **PASS** |
| `TenantBoundarySecurityTest::test_forged_staff_creation_rejected_when_referencing_company_b_branch_or_department` | Forged FK attack on HR Staff | Session Validation Error | **PASS** |
| `TenantBoundarySecurityTest::test_cross_company_stock_transfer_dispatch_and_receive_rejected` | Forged transfer attack | HTTP 403 Forbidden | **PASS** |
| `TenantBoundarySecurityTest::test_cross_company_daily_operation_show_and_actions_rejected` | Forged Daily Operation access | HTTP 403 Forbidden | **PASS** |
| `TenantBoundarySecurityTest::test_cross_company_purchase_order_and_request_rejected` | Forged PO / PR access | HTTP 403 Forbidden | **PASS** |
| `TenantBoundarySecurityTest::test_cross_company_overtime_approval_rejected` | Forged Overtime approval | HTTP 403 Forbidden | **PASS** |
| `FinancialAndInventoryIntegrityTest::test_multi_company_security_rejects_cross_company_godown_and_items` | Forged Opening Stock & Adjustments on Company B godowns & items | Session Validation Error (`godown_id`, `item_id`) | **PASS** |

**Summary:** 16 out of 16 security regression tests passed successfully. No tenant boundary regressions detected.

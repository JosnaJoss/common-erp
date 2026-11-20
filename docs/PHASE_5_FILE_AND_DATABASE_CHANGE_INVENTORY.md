# Common ERP – Phase 5 File and Database Change Inventory

## 1. Database Schema Changes

All schema changes were performed through a single, additive, non-destructive migration:
`database/migrations/2024_01_05_000001_enhance_phase_5_accounting_and_manufacturing.php`.

No tables were dropped, no columns were modified destructively, and no existing records were altered or lost.

### 1.1 Table Alterations & Additions

| Table Name | Action | Details | Purpose |
|---|---|---|---|
| `daily_operations` | ADD COLUMN | `total_overhead_cost` (decimal:12,2, default 0) | Track total manufacturing overhead allocated to operation |
| `daily_operations` | ADD COLUMN | `reversed_at` (timestamp, nullable) | Timestamp when operation was reversed |
| `daily_operations` | ADD COLUMN | `reversed_by` (foreignId to users, nullable) | User who authorized and executed the reversal |
| `manufacturing_overhead_allocations` | CREATE TABLE | `id`, `company_id`, `branch_id`, `daily_operation_id`, `expense_ledger_id`, `overhead_category`, `allocation_method`, `allocation_basis_value`, `rate_per_unit`, `allocated_amount`, `notes`, `created_by`, `timestamps` | Auditable capture of individual overhead allocations |
| `ledgers` | ADD COLUMN | `is_inventory` (boolean, default false) | Explicit classification of inventory asset ledgers |
| `ledgers` | ADD COLUMN | `inventory_type` (string:30, nullable) | Categorization (`raw_materials`, `finished_goods`, `wip`, `general`) |
| `payrolls` | ADD COLUMN | `manufacturing_labor_settled` (decimal:12,2, default 0) | Amount of accrued factory labor settled against payable liability |
| `payrolls` | ADD COLUMN | `administrative_salary_expense` (decimal:12,2, default 0) | Remaining administrative salary expensed to P&L |

---

## 2. File Modification & Creation Inventory

### 2.1 New Files Created

1. **Migration:**
   - `database/migrations/2024_01_05_000001_enhance_phase_5_accounting_and_manufacturing.php`
2. **Model:**
   - `app/Models/ManufacturingOverheadAllocation.php`
3. **Automated Feature Test:**
   - `tests/Feature/PhaseFiveEnhancementTest.php`
4. **Documentation Reports:**
   - `docs/PHASE_5_IMPLEMENTATION_COMPLETION_REPORT.md`
   - `docs/PHASE_5_ACCOUNTING_VALIDATION_REPORT.md`
   - `docs/PHASE_5_MULTI_COMPANY_SECURITY_REPORT.md`
   - `docs/PHASE_5_FILE_AND_DATABASE_CHANGE_INVENTORY.md`

### 2.2 Existing Files Enhanced

1. **`app/Models/DailyOperation.php`**
   - Added `total_overhead_cost`, `reversed_at`, `reversed_by` to `$fillable`.
   - Added casts for decimal and datetime.
   - Added relationships: `overheadAllocations()` and `reverser()`.
   - Added helper method: `isReversed(): bool`.
2. **`app/Models/Ledger.php`**
   - Added `is_inventory`, `inventory_type` to `$fillable` and casts.
   - Added date-bounded balance methods: `getBalanceAsOf(?string $asOfDate)` and `getMovementBetween(string $startDate, string $endDate)`.
3. **`app/Models/Payroll.php`**
   - Added `manufacturing_labor_settled` and `administrative_salary_expense` to `$fillable` and `$casts`.
4. **`app/Services/DailyOperationService.php`**
   - Enhanced `createOperation()` to process and record overhead allocations.
   - Enhanced `postOperationStock()` to capitalize overhead into Finished Goods and credit `Manufacturing Overhead Absorbed`.
   - Implemented `reverseOperation()` with period lock checks, downstream stock consumption checks, stock rollback, mirror journal reversal, and audit recording.
5. **`app/Services/AccountingService.php`**
   - Updated `getOrCreateStandardLedger()` to classify standard inventory accounts with `is_inventory = true` and `inventory_type`.
6. **`app/Http/Controllers/DailyOperationController.php`**
   - Added `reverse()` action with tenant verification, authorization, and reason validation.
   - Enhanced `store()` validation rules to validate `overheads` and enforce tenant scoping on `expense_ledger_id`.
7. **`app/Http/Controllers/PayrollController.php`**
   - Enhanced `disburseSinglePayroll()` to compute accrued factory labor, debit `Manufacturing Labor Payable`, debit `Salaries & Staff Wages` only for administrative excess, and save settlement breakdown.
8. **`app/Http/Controllers/ReportController.php`**
   - Enhanced `cashFlow()` to compute working capital movements as period deltas ($\Delta \text{Balance}$) using `getMovementBetween()` and reconcile opening + change = closing.
   - Enhanced `inventoryReconciliation()` to query ledgers with `is_inventory = true` and calculate exact physical vs GL variance.
9. **`routes/web.php`**
   - Added `POST /operations/{daily_operation}/reverse` route (`daily_operations.reverse`).
10. **`resources/views/daily_operations/show.blade.php`**
    - Added Reverse Operation button, confirmation modal with mandatory reason, reversal audit alert banner, and overhead allocations display table.

---

## 3. Verification & Git Working Tree Status

```text
60 tests passed (908 assertions)
0 failures, 0 errors, 0 warnings
```
All files are cleanly structured, fully linted with PHP 8.2+, and strictly adhere to Laravel conventions and the existing ERP architecture.

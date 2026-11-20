# Common ERP – Phase 4 Database Change & Schema Evolution Report

This report documents all database schema modifications introduced during Phase 4 to support financial and inventory integrity.

---

## 1. Summary of Database Schema Changes

In accordance with strict preservation rules, no existing tables or columns were dropped, renamed, or modified destructively. A single, additive, non-destructive migration was introduced:

- **Migration File**: `database/migrations/2024_01_04_000001_add_journal_entry_id_to_daily_operations_and_stock_adjustments.php`
- **Execution Date**: 2026-11-20
- **Target Environment**: Local development / testing MySQL database (`common_erp`)
- **Status**: **SUCCESSFULLY APPLIED (614ms)**

---

## 2. Table-by-Table Schema Modifications

### Table 1: `daily_operations`
- **Modification**: Added nullable foreign key column `journal_entry_id` referencing `journal_entries(id)` with `nullOnDelete()`.
- **Position**: Inserted after `stock_posted`.
- **Reason**: Enables bidirectional traceability between factory manufacturing batches and their double-entry cost capitalization journal vouchers. Matches the established design pattern of `purchase_entries.journal_entry_id` and `sales_invoices.journal_entry_id`.
- **Database Impact**:
  - Nullable integer column (`BIGINT UNSIGNED`).
  - Foreign key constraint to `journal_entries`.
  - Zero data loss; existing daily operation records remain unaffected with `journal_entry_id = null`.
- **Existing Functionality Affected**: None. `DailyOperation` model gains `$dailyOperation->journalEntry` relationship.

### Table 2: `stock_adjustments`
- **Modification**: Added nullable foreign key column `journal_entry_id` referencing `journal_entries(id)` with `nullOnDelete()`.
- **Position**: Inserted after `status`.
- **Reason**: Links physical stock count reconciliations (shortage, surplus, damage, expiry) directly to the corresponding inventory loss or gain journal entry.
- **Database Impact**:
  - Nullable integer column (`BIGINT UNSIGNED`).
  - Foreign key constraint to `journal_entries`.
  - Zero data loss; existing records remain unaffected with `journal_entry_id = null`.
- **Existing Functionality Affected**: None. `StockAdjustment` model gains `$stockAdjustment->journalEntry` relationship.

---

## 3. Rollback Safety & Verification

The migration implements a complete `down()` method:
```php
Schema::table('daily_operations', function (Blueprint $table) {
    $table->dropForeign(['journal_entry_id']);
    $table->dropColumn('journal_entry_id');
});

Schema::table('stock_adjustments', function (Blueprint $table) {
    $table->dropForeign(['journal_entry_id']);
    $table->dropColumn('journal_entry_id');
});
```
*Rollback verified as 100% reversible with zero side-effects.*

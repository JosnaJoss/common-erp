# Internal Stock Movement & Department-Wise Inventory Management
## Current Architecture Audit & Technical Readiness Assessment

**Document Date:** November 2026  
**System:** Common ERP (Multi-Company, Multi-Branch ERP Engine)  
**Audit Purpose:** Comprehensive inspection of the existing database, models, controllers, services, routes, permissions, accounting, and business workflows to design the Internal Stock Movement, Department-Wise Stock Configuration, Request/Approval Workflow, Batch-Wise Dispatch, In-Transit Stock Tracking, Receipt & Discrepancy Management, and Production/Packing Integration.

---

## 1. Existing Relevant Database Tables

| Table Name | Primary Purpose | Existing Key Columns | Notes & Status |
|---|---|---|---|
| `branches` | Physical Branch Locations | `id`, `company_id`, `name`, `code`, `is_main_branch`, `status` | Needs stock configuration fields (`stock_mode`, `central_godown_id`). |
| `departments` | Organizational Business Units | `id`, `company_id`, `branch_id`, `name`, `code`, `department_type`, `head_staff_id`, `supervisor_staff_id`, `status` | Needs stock configuration fields (`maintains_stock`, `default_godown_id`, `source_godown_id`). |
| `godowns` | Physical/Controlled Inventory Locations | `id`, `company_id`, `branch_id`, `department_id`, `vehicle_id`, `name`, `code`, `location_type`, `responsible_staff`, `status` | Fully reusable. Already supports location types (`warehouse`, `production_unit`, `packing_unit`, `sales_outlet`, `van_mobile`, etc.) and departmental mapping. |
| `stock_batches` | Physical Batch & Expiry Storage | `id`, `company_id`, `branch_id`, `godown_id`, `item_id`, `batch_no`, `batch_name`, `quantity`, `cost_price`, `added_date`, `expiry_date`, `status` | Unique composite `['godown_id', 'item_id', 'batch_no']`. Fully reusable. Tracks real on-hand batch quantities per godown. |
| `stock_movements` | Immutable Stock Ledger Audit Trail | `id`, `company_id`, `branch_id`, `godown_id`, `item_id`, `batch_id`, `movement_date`, `movement_type`, `reference_type`, `reference_id`, `reference_no`, `quantity`, `unit_cost`, `balance_after`, `remarks` | Fully reusable. Movement types include `inward`, `outward`, `transfer_in`, `transfer_out`, `adjustment_add`, `adjustment_sub`. |
| `stock_transfers` | Warehouse/Godown Stock Movements | `id`, `company_id`, `transfer_no`, `transfer_date`, `source_branch_id`, `source_godown_id`, `dest_branch_id`, `dest_godown_id`, `status` (`pending`, `in_transit`, `completed`, `cancelled`), `dispatched_by`, `dispatched_at`, `received_by`, `received_at`, `remarks` | Exists and functional. Needs enhancement for linking `stock_request_id`, receiver remarks, expanded statuses (`draft`, `pending`, `ready_for_dispatch`, `in_transit`, `partially_received`, `received`, `completed`, `cancelled`). |
| `stock_transfer_items` | Transferred Item Rows | `id`, `stock_transfer_id`, `item_id`, `batch_id`, `quantity` | Exists. Needs enhancement for `stock_request_item_id`, `dispatched_qty`, `received_qty`, `damaged_qty`, `shortage_qty`, `in_transit_qty`. |
| `stock_requests` | Inter-departmental material requests | `id`, `company_id`, `branch_id`, `request_no`, `movement_type`, `request_date`, `from_department_id`, `to_department_id`, `reference_file`, `remarks`, `status` (`pending`, `approved`, `issued`, `rejected`) | Exists in schema. Needs enhancement for `from_godown_id`, `source_godown_id`, `required_date`, `priority`, `rejection_reason`, `approved_by`, `approved_at`, and expanded statuses (`draft`, `requested`, `approved`, `partially_approved`, `rejected`, `completed`, `cancelled`). |
| `stock_request_items` | Requested item details | `id`, `stock_request_id`, `item_id`, `requested_qty`, `issued_qty` | Exists. Needs enhancement for `unit_id`, `approved_qty`, `dispatched_qty`, `received_qty`, `preferred_batch_id`, `remarks`. |
| `stock_adjustments` | Physical count & write-off entries | `id`, `company_id`, `branch_id`, `godown_id`, `adjustment_no`, `adjustment_date`, `reason`, `status`, `journal_entry_id`, `approved_by` | Fully functional with automated double-entry financial posting. |
| `stock_adjustment_items` | Adjusted item lines | `id`, `stock_adjustment_id`, `item_id`, `batch_id`, `current_stock`, `actual_stock`, `adjustment_qty`, `type` (`increase`, `decrease`) | Fully functional. |
| `daily_operations` | Production Activity Execution | `id`, `company_id`, `branch_id`, `department_id`, `operation_number`, `process_name`, `approval_status`, `stock_posted`, `total_material_cost`, etc. | Integrates with inventory posting. Needs enhancement to respect department stock configuration. |
| `daily_operation_materials` | Consumed Materials | `id`, `daily_operation_id`, `item_id`, `source_godown_id`, `batch_no`, `consumed_quantity`, `unit_cost`, `total_cost` | Already tracks `source_godown_id` and `batch_no`. |
| `daily_operation_outputs` | Produced Finished/Semi-Finished Goods | `id`, `daily_operation_id`, `item_id`, `dest_godown_id`, `batch_number`, `produced_quantity`, `unit_cost`, `total_cost` | Already tracks `dest_godown_id` and `batch_number`. |

---

## 2. Existing Models and Relationships

1. **`App\Models\Branch`**:
   - Relationships: `company()`, `departments()`, `godowns()`, `staff()`.
   - Enhancement: Add `centralGodown()` relationship and helper `isCentralizedStock()`.
2. **`App\Models\Department`**:
   - Relationships: `company()`, `branch()`, `parentDepartment()`, `subDepartments()`, `headStaff()`, `supervisorStaff()`, `staff()`.
   - Enhancement: Add `defaultGodown()`, `sourceGodown()`, and helper `maintainsIndependentStock()`.
3. **`App\Models\Godown`**:
   - Relationships: `company()`, `branch()`, `department()`, `vehicle()`, `batches()`.
   - Helper methods: `isMobileVan()`, `isSalesOutlet()`.
4. **`App\Models\StockBatch`**:
   - Relationships: `company()`, `branch()`, `godown()`, `item()`.
5. **`App\Models\StockMovement`**:
   - Relationships: `godown()`, `item()`, `batch()`.
6. **`App\Models\StockTransfer`**:
   - Relationships: `sourceBranch()`, `destBranch()`, `sourceGodown()`, `destGodown()`, `dispatcher()`, `receiver()`, `creator()`, `items()`.
   - Enhancement: Add `request()`, `discrepancies()`.
7. **`App\Models\StockTransferItem`**:
   - Relationships: `transfer()`, `item()`, `batch()`.
   - Enhancement: Add `requestItem()`, `discrepancy()`.
8. **`App\Models\StockRequest` & `StockRequestItem`**:
   - Relationships: `fromDepartment()`, `toDepartment()`, `items()`.
   - Enhancement: Add `fromGodown()`, `sourceGodown()`, `approver()`, `transfers()`.
9. **`App\Models\DailyOperation` & `DailyOperationMaterial`**:
   - Relationships: `department()`, `materials()`, `outputs()`, `wastages()`, `distributions()`.

---

## 3. Existing Controllers, Services and Routes

### Controllers
- **`App\Http\Controllers\InventoryController`**:
  - Handles `overview` (`inventory.overview`), `movements` (`inventory.movements`), `inward` (`inventory.inward.*`), `outward` (`inventory.outward.*`), `transfers` (`inventory.transfers`, `inventory.transfers.store`, `inventory.transfers.dispatch`, `inventory.transfers.receive`), `adjustments` (`inventory.adjustments.*`), `openingStock` (`inventory.opening_stock.*`), `alerts` (`inventory.alerts`).
- **`App\Http\Controllers\DailyOperationController`**:
  - Handles `index`, `create`, `store`, `show`, `submit`, `approve`, `reject`, `reverse`.
- **`App\Http\Controllers\MasterDataController`**:
  - Handles `godowns` (`master.godowns.*`), `departments` (`master.departments.*`).

### Services
- **`App\Services\InventoryService`**:
  - `recordInward(...)`: Increases batch quantity, records `StockMovement` (type: inward).
  - `recordOutward(...)`: Decreases batch quantity with negative stock prevention check, records `StockMovement` (type: outward).
  - `transferStock(...)`: Immediate direct transfer between godowns.
  - `dispatchTransfer(...)`: Deducts source godown stock, transitions transfer status to `in_transit`.
  - `receiveTransfer(...)`: Inwards destination godown stock, transitions transfer status to `completed`.
- **`App\Services\DailyOperationService`**:
  - `createOperation(...)`: Calculates labor, material, wastage, overhead.
  - `postOperationStock(...)`: Deducts consumed raw materials from `source_godown_id` via `recordOutward`, records outputs to `dest_godown_id` via `recordInward`.
- **`App\Services\AccountingService`**:
  - `getOrCreateStandardLedger(...)`, `createJournalEntry(...)`.

---

## 4. Existing Blade Views

- `resources/views/inventory/overview.blade.php`: Stock balances, batches, valuation.
- `resources/views/inventory/movements.blade.php`: Audit ledger of stock movements.
- `resources/views/inventory/transfers.blade.php`: Godown transfer listing, creation modal, and dispatch/receive triggers.
- `resources/views/inventory/adjustments.blade.php`: Physical stock adjustment listing and creation modal.
- `resources/views/inventory/opening_stock.blade.php`: Bulk opening stock initialization.
- `resources/views/daily_operations/create.blade.php` & `show.blade.php`: Production operation entry and review.
- `resources/views/master/godowns/index.blade.php`: Warehouses and godowns registry.
- `resources/views/master/departments/index.blade.php`: Department master registry.

---

## 5. Existing Inventory & Stock Deduction Mechanisms

1. Every item maintains total physical quantity across batches located in godowns:
   `Item::totalStockQuantity() = sum(stock_batches.quantity)`.
2. A stock location is identified by `godowns.id`.
3. Stock deduction occurs via `InventoryService::recordOutward(...)`. It verifies that `availableQty >= requestedQty` (unless explicitly configured to allow negative stock). If insufficient, an Exception is thrown.
4. Stock additions occur via `InventoryService::recordInward(...)`. It finds or creates the corresponding batch record for that godown and increments the quantity.
5. Every inward and outward change logs a permanent record in `stock_movements`.

---

## 6. Existing Batch Management

1. `stock_batches` table holds `godown_id`, `item_id`, `batch_no`, `quantity`, `cost_price`, `added_date`, `expiry_date`.
2. Unique index `['godown_id', 'item_id', 'batch_no']` guarantees that a batch exists per godown.
3. When stock moves from Godown A to Godown B, the batch identity (`batch_no`, `cost_price`, `expiry_date`) must be preserved in Godown B's `stock_batches` record. New batch numbers are not invented during internal transfers.

---

## 7. Existing Production & Packing Integration

1. `daily_operations` records the executing department via `department_id`.
2. `daily_operation_materials` explicitly holds `source_godown_id` and `batch_no` for each raw material consumed.
3. `daily_operation_outputs` holds `dest_godown_id` and `batch_number` for finished goods produced.
4. Currently, `DailyOperationController::create()` populates godowns for the user to manually pick `source_godown_id`.
5. **Required Enhancement**: In Centralized Stock mode, `source_godown_id` must automatically bind to the branch's designated central warehouse. In Multiple-Location mode, if the department maintains stock, it must bind to the department's dedicated godown; if it does not maintain stock, it binds to its designated source godown.

---

## 8. Existing Permission Architecture

1. Permissions follow the format: `module | screen | action | name`.
2. Relevant existing permissions:
   - `inventory | transfers | view, add, edit, delete, approve, export`
   - `inventory | requests | view, add, edit, delete, approve, export`
   - `inventory | adjustments | view, add, edit, delete, approve, export`
   - `master_data | godowns | view, add, edit, delete, approve, export`
   - `master_data | departments | view, add, edit, delete, approve, export`
3. Role-based check: `auth()->user()->role->permissions`.
4. Company-specific roles and platform super-admin isolation were verified and hardened in Batch 7.

---

## 9. Existing Accounting Treatment

1. Double-entry accounting is maintained by `AccountingService`.
2. Standard inventory ledgers used:
   - `Stock on Hand` (Current Assets, Assets)
   - `Inventory Shortage & Loss` (Direct Expenses & Purchases, Expenses)
   - `Damaged & Expired Stock Expense` (Direct Expenses & Purchases, Expenses)
   - `Inventory Surplus & Gain` (Sales & Operating Revenue, Revenue)
3. Internal stock movements between warehouse and department stock locations within the same company do NOT alter total asset valuation or require P&L entries because goods remain company inventory.
4. Discrepancy write-offs (unrecoverable loss or damage during transit) DO require journal entries debiting `Damaged & Expired Stock Expense` or `Inventory Shortage & Loss` and crediting `Stock on Hand`.

---

## 10. What Can Be Reused

1. `godowns` table as the physical stock location entity.
2. `stock_batches` table for batch-level tracking.
3. `stock_movements` table as the audit ledger.
4. `stock_transfers` & `stock_transfer_items` as the transfer and dispatch records.
5. `stock_requests` & `stock_request_items` as the material requisition records.
6. `stock_adjustments` & `stock_adjustment_items` for reconciling write-offs.
7. `InventoryService` for inward, outward, and batch stock adjustments.
8. `AccountingService` for standard journal creation.
9. Existing Blade layouts and components (`resources/views/layouts/app.blade.php`).

---

## 11. What Needs Enhancement

1. **`branches` table**: Add `stock_mode` (`centralized` vs `multiple_locations`) and `central_godown_id`.
2. **`departments` table**: Add `maintains_stock` (boolean), `default_godown_id`, and `source_godown_id`.
3. **`stock_requests` table**: Add `from_godown_id`, `source_godown_id`, `required_date`, `priority`, `rejection_reason`, `approved_by`, `approved_at`, expanded status lifecycle.
4. **`stock_request_items` table**: Add `unit_id`, `approved_qty`, `dispatched_qty`, `received_qty`, `preferred_batch_id`, `remarks`.
5. **`stock_transfers` table**: Add `stock_request_id`, `dispatch_reference`, `receiver_remarks`, expanded statuses (`draft`, `pending`, `ready_for_dispatch`, `in_transit`, `partially_received`, `received`, `completed`, `cancelled`).
6. **`stock_transfer_items` table**: Add `stock_request_item_id`, `dispatched_qty`, `received_qty`, `damaged_qty`, `shortage_qty`, `in_transit_qty`.
7. **`InventoryService`**: Support partial dispatch, partial receipt, in-transit stock calculation, and discrepancy recording.
8. **`DailyOperationService`**: Check branch stock mode and department configuration to auto-assign and validate the correct stock source.

---

## 12. What Is Genuinely Missing

1. **Discrepancy Tracking Table (`stock_discrepancies`)**: An auditable entity to track shortages, damaged goods, wrong items, wrong batches, and quality rejections with statuses (`reported`, `under_investigation`, `pending_additional_dispatch`, `pending_approval`, `resolved`, `cancelled`).
2. **Discrepancy Write-Off Approval History (`stock_discrepancy_approvals`)**: Auditable logs of write-off approvals, decision remarks, and quantity/value limits.
3. **Dedicated UI Screens & Navigation**:
   - Branch & Department Stock Configuration Screen.
   - Internal Stock Requisition (Create, List, Approve, Reject).
   - Stock Dispatch Screen with batch selection and available stock display.
   - Receipt Confirmation Screen with shortage/damage reporting.
   - Discrepancy Investigation & Resolution Screen.
   - Write-Off Approval Screen enforcing separation of duties.
   - In-Transit Stock Report & Location-Wise Stock Reports.

---

## 13. Proposed Database Changes (Non-Destructive)

A single forward migration will:
1. Add columns to `branches`: `stock_mode` (string/enum default `centralized`), `central_godown_id` (foreignId nullable).
2. Add columns to `departments`: `maintains_stock` (boolean default false), `default_godown_id` (foreignId nullable), `source_godown_id` (foreignId nullable).
3. Enhance `stock_requests`: `from_godown_id`, `source_godown_id`, `required_date`, `priority`, `rejection_reason`, `approved_by`, `approved_at`.
4. Enhance `stock_request_items`: `unit_id`, `approved_qty`, `dispatched_qty`, `received_qty`, `preferred_batch_id`, `remarks`.
5. Enhance `stock_transfers`: `stock_request_id`, `dispatch_reference`, `receiver_remarks`.
6. Enhance `stock_transfer_items`: `stock_request_item_id`, `dispatched_qty`, `received_qty`, `damaged_qty`, `shortage_qty`, `in_transit_qty`.
7. Create table `stock_discrepancies`.
8. Create table `stock_discrepancy_approvals`.

All added foreign keys are nullable and have appropriate index and constraint names. No existing tables, records, or columns are dropped or truncated.

---

## 14. Risks to Existing Data and Modules & Mitigation Plan

| Risk | Impact | Mitigation Strategy |
|---|---|---|
| Modifying existing transfer workflows | High | Existing transfers without `stock_request_id` continue to function as direct godown transfers (`mode = direct`). Legacy endpoints remain intact. |
| Negative stock on dispatch | High | Validate available batch quantity in source godown using `lockForUpdate()` within database transactions. |
| Breaking existing production/daily operations tests | Critical | Default department configuration sets `maintains_stock = false` and assigns the default central godown so that existing Daily Operations workflows and tests consume directly as before without requiring internal transfers. |
| Over-receipt or over-dispatch | High | Server-side validation strictly enforces: `dispatched_qty <= approved_qty - already_dispatched_qty` and `received_qty + shortage_qty + damaged_qty <= dispatched_qty`. |
| Separation-of-duty violation in write-offs | Medium | Server-side validation blocks any user from approving a write-off if `auth()->id() == $discrepancy->created_by`. |
| Database migration failures | Critical | All migration operations check `Schema::hasColumn()` and `Schema::hasTable()` before altering, allowing idempotent safe execution. |

---

## Conclusion

The architecture inspection confirms that the Common ERP foundation contains robust inventory and double-entry accounting engines. By extending the existing `departments`, `branches`, `stock_requests`, and `stock_transfers` structures and adding the discrepancy management tables, the internal stock movement and department-wise inventory management system can be implemented cleanly, securely, and without parallel architectures or regression risks.

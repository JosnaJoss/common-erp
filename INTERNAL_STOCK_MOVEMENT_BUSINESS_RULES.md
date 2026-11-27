# INTERNAL STOCK MOVEMENT & DEPARTMENT-WISE INVENTORY MANAGEMENT
## Comprehensive Business Rules & System Invariants

### 1. Branch & Department Configuration Rules

#### Rule 1.1: Branch Inventory Operating Mode
- A branch operates in one of two modes:
  1. `centralized`: All branch materials reside in a designated primary central warehouse (`central_godown_id`). Subordinate departments do not maintain independent stores.
  2. `multi_location`: Individual departments may establish and maintain independent stock locations (`default_godown_id`).

#### Rule 1.2: Department Stock-Holding Eligibility
- If a department has `maintains_stock = true`:
  - It MUST have an assigned `default_godown_id`.
  - It maintains independent item batch balances.
  - Manufacturing/packing processes running in this department consume materials directly from `default_godown_id`.
- If a department has `maintains_stock = false`:
  - It does NOT maintain a separate stock location.
  - It draws materials from its assigned `source_godown_id` or the branch `central_godown_id`.
  - Manufacturing/packing processes consume materials directly from the central warehouse.

#### Rule 1.3: Safe Transition Invariant (Zero-Balance Protection)
- A department CANNOT change `maintains_stock` from `true` to `false` if its assigned `default_godown_id` holds active inventory balances (`quantity > 0`).
- The user must either transfer out all stock or post stock adjustments to reduce balance to zero before de-registering independent stock tracking. Attempting to switch with active balances throws a validation exception.

---

### 2. Requisition Rules

#### Rule 2.1: Requisition Creation
- Any department user with `stock_requests.create` can create an internal requisition (`stock_requests` and `stock_request_items`).
- The requisition specifies destination department, source warehouse, items, requested quantities, priority (`low`, `normal`, `high`, `urgent`), and required date.
- Initial status is `pending`. Line item status is `pending`.

#### Rule 2.2: Requisition Approval & Rejection
- Only users with `stock_requests.approve` (e.g., Warehouse Manager, Branch Manager, Admin) can review and approve requisitions.
- An approver can:
  1. **Approve Full Quantity**: `approved_qty = requested_qty`.
  2. **Approve Partial Quantity**: `0 < approved_qty < requested_qty`.
  3. **Reject Requisition**: Requires a **mandatory rejection reason**. Status transitions to `rejected`.
- Quantities approved cannot exceed the original requested quantity.

---

### 3. Dispatch & In-Transit Control Rules

#### Rule 3.1: Batch Availability Invariant
- Dispatch can only occur against an `approved` or `partially_approved` requisition.
- The warehouse staff selects specific physical batches (`StockBatch`) in the source warehouse.
- Dispatched quantity for any batch CANNOT exceed the current available physical quantity in that batch. Over-dispatch throws an exception.

#### Rule 3.2: Cumulative Dispatch Limit
- The sum of cumulative dispatched quantities across all dispatches for a line item cannot exceed `approved_qty`.
- Multiple dispatches against a single requisition are fully supported until `dispatched_qty == approved_qty`.

#### Rule 3.3: In-Transit Accounting Rule
- When dispatch is saved:
  1. A `StockMovement` of type `OUTWARD` is posted immediately, deducting stock from the source warehouse batch.
  2. The transfer is assigned status `in_transit`.
  3. The transfer item records `in_transit_qty = dispatched_qty` and `received_qty = 0`.
- **System Invariant**: Dispatched stock is NOT marked as consumed or sold. It remains legally owned by the company in transit between locations.

---

### 4. Physical Receipt & Discrepancy Rules

#### Rule 4.1: Physical Count Verification
- The receiving department physically inspects received goods before acknowledging the transfer.
- The receiver inputs:
  - `received_qty`: Accepted goods in sound condition.
  - `damaged_qty`: Goods damaged or spoiled in transit.
  - `shortage_qty`: Goods missing from the package compared to the dispatch note.

#### Rule 4.2: Inward Stock Posting
- Only the `received_qty` (accepted goods) triggers a `StockMovement` of type `INWARD` into the destination godown batch.
- Damaged and missing quantities are NEVER posted into the destination stock batch.

#### Rule 4.3: Transfer Completion Invariant
- A transfer is marked `completed` IF AND ONLY IF `received_qty == dispatched_qty` (zero variance across all items).
- If any discrepancy exists (`damaged_qty > 0` or `shortage_qty > 0` or `received_qty < dispatched_qty`):
  - Transfer status MUST be set to `partially_received`.
  - Transfer CANNOT be marked `completed` while unresolved discrepancies exist.
  - A `StockDiscrepancy` record is automatically generated for each variance line.

---

### 5. Discrepancy Resolution & Write-Off Rules

#### Rule 5.1: Discrepancy Lifecycle Stages
- `reported`: Initial status upon delivery recording.
- `under_investigation`: Assigned when warehouse manager logs investigation notes.
- `pending_approval`: Assigned when formal write-off justification is submitted.
- `resolved`: Discrepancy closed via one of the 4 resolution options.
- `rejected`: Write-off rejected by authorized manager.

#### Rule 5.2: Resolution Options
- **Option A (`replacement_dispatch`)**: Warehouse issues a replacement dispatch from remaining requisition approved quota.
- **Option B (`separate_delivery`)**: Discrepant missing goods arrived in a subsequent shipment.
- **Option C (`loss_write_off`)**: Approved write-off of transit damage or unrecoverable loss.
- **Option D (`cancelled`)**: Discrepancy voided or requisition balance cancelled.

#### Rule 5.3: Separation of Duties Invariant
- The user who reported or received the delivery, or submitted the write-off request, **CANNOT approve the write-off**.
- Approval MUST be performed by an independent authorized user (e.g., Branch Manager, Finance Manager, or Company Admin).
- Violation of this invariant immediately halts execution with:
  `"Separation of duties violation: The user who reported or requested the discrepancy write-off cannot approve it."`

#### Rule 5.4: Double-Entry Financial Write-Off Rule
- When write-off is approved:
  1. A `StockAdjustment` record is created (type decrease, status approved).
  2. A balanced `JournalEntry` is automatically posted:
     - **DEBIT**: Inventory Loss / Transit Damage Ledger (Expense Account).
     - **CREDIT**: Stock in Hand Ledger (Current Asset Account).
  3. `discrepancy.stock_adjustment_id` is linked to the created adjustment.

---

### 6. Production & Packing Consumption Rules

#### Rule 6.1: Automated Godown Resolution in `DailyOperationService`
- When manufacturing, packing, or processing operations are recorded via `DailyOperationService`:
  1. If the executing department has `maintains_stock = true`, materials MUST be consumed from the department's `default_godown_id`. Active batches are verified in that specific godown.
  2. If the executing department has `maintains_stock = false`, materials are consumed from the department's `source_godown_id` or the branch `central_godown_id`.
- The system automatically handles this resolution without requiring manual user godown overrides on every operation line.

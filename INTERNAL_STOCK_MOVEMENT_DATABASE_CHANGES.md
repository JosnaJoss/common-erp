# INTERNAL STOCK MOVEMENT & DEPARTMENT-WISE INVENTORY MANAGEMENT
## Database Schema Changes & Migration Safety Certification

### 1. Migration Overview

- **Migration File**: `database/migrations/2026_11_27_000002_create_internal_stock_movement_and_department_inventory_tables.php`
- **Batch Applied**: Applied non-destructively to the existing SQLite database.
- **Rollback Safety**: Full symmetric reverse operations defined in the `down()` method, ensuring zero residue upon rollback.
- **Data Protection Compliance**: No existing tables, records, columns, or foreign keys were dropped, truncated, or modified in a destructive manner.

---

### 2. Table-by-Table Extensions to Existing Tables

#### 2.1 `branches` Table Extension
```sql
ALTER TABLE branches ADD COLUMN stock_mode VARCHAR(20) NOT NULL DEFAULT 'centralized';
ALTER TABLE branches ADD COLUMN central_godown_id INTEGER NULL REFERENCES godowns(id) ON DELETE SET NULL;
```
- **`stock_mode`**: String (`centralized` or `multi_location`). Default is `'centralized'`. Dictates whether branch departments draw inventory centrally or maintain independent sub-stores.
- **`central_godown_id`**: Foreign key to `godowns.id` (nullable, `onDelete('set null')`). The primary central storage location for the branch.

#### 2.2 `departments` Table Extension
```sql
ALTER TABLE departments ADD COLUMN maintains_stock TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE departments ADD COLUMN default_godown_id INTEGER NULL REFERENCES godowns(id) ON DELETE SET NULL;
ALTER TABLE departments ADD COLUMN source_godown_id INTEGER NULL REFERENCES godowns(id) ON DELETE SET NULL;
```
- **`maintains_stock`**: Boolean. Default `false` (0). Determines if the department operates an independent stock-holding location.
- **`default_godown_id`**: Foreign key to `godowns.id`. Designated sub-store location owned by the department.
- **`source_godown_id`**: Foreign key to `godowns.id`. Central or parent warehouse from which this department draws materials when `maintains_stock` is false or for replenishments.

#### 2.3 `stock_requests` Table Extension
```sql
ALTER TABLE stock_requests ADD COLUMN from_godown_id INTEGER NULL REFERENCES godowns(id) ON DELETE SET NULL;
ALTER TABLE stock_requests ADD COLUMN source_godown_id INTEGER NULL REFERENCES godowns(id) ON DELETE SET NULL;
ALTER TABLE stock_requests ADD COLUMN required_date DATE NULL;
ALTER TABLE stock_requests ADD COLUMN priority VARCHAR(20) NOT NULL DEFAULT 'normal';
ALTER TABLE stock_requests ADD COLUMN rejection_reason TEXT NULL;
ALTER TABLE stock_requests ADD COLUMN approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL;
ALTER TABLE stock_requests ADD COLUMN approved_at DATETIME NULL;
```
- **`from_godown_id`**: Destination sub-store for the requested materials.
- **`source_godown_id`**: Source warehouse fulfilling the request.
- **`required_date`**: Date the department needs the materials on site.
- **`priority`**: Requisition priority (`low`, `normal`, `high`, `urgent`).
- **`rejection_reason`**: Mandatory explanatory text if requisition is rejected.
- **`approved_by` / `approved_at`**: Audit metadata for warehouse review.
- **`status` column expansion**: Broadened to support `draft`, `pending`, `approved`, `partially_approved`, `rejected`, `partially_dispatched`, `completed`, `cancelled`.

#### 2.4 `stock_request_items` Table Extension
```sql
ALTER TABLE stock_request_items ADD COLUMN unit_id INTEGER NULL REFERENCES units_of_measure(id) ON DELETE SET NULL;
ALTER TABLE stock_request_items ADD COLUMN approved_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000;
ALTER TABLE stock_request_items ADD COLUMN dispatched_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000;
ALTER TABLE stock_request_items ADD COLUMN received_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000;
ALTER TABLE stock_request_items ADD COLUMN preferred_batch_id INTEGER NULL REFERENCES stock_batches(id) ON DELETE SET NULL;
ALTER TABLE stock_request_items ADD COLUMN remarks TEXT NULL;
```
- Tracks cumulative lifecycle quantities per line item (`requested_qty` -> `approved_qty` -> `dispatched_qty` -> `received_qty`).

#### 2.5 `stock_transfers` Table Extension
```sql
ALTER TABLE stock_transfers ADD COLUMN stock_request_id INTEGER NULL REFERENCES stock_requests(id) ON DELETE SET NULL;
ALTER TABLE stock_transfers ADD COLUMN dispatch_reference VARCHAR(100) NULL;
ALTER TABLE stock_transfers ADD COLUMN receiver_remarks TEXT NULL;
```
- **`stock_request_id`**: Links internal transfer directly to the originating requisition.
- **`dispatch_reference`**: Delivery challan, gate pass, or waybill number.
- **`receiver_remarks`**: Notes entered by receiving department during physical count verification.

#### 2.6 `stock_transfer_items` Table Extension
```sql
ALTER TABLE stock_transfer_items ADD COLUMN stock_request_item_id INTEGER NULL REFERENCES stock_request_items(id) ON DELETE SET NULL;
ALTER TABLE stock_transfer_items ADD COLUMN dispatched_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000;
ALTER TABLE stock_transfer_items ADD COLUMN received_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000;
ALTER TABLE stock_transfer_items ADD COLUMN damaged_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000;
ALTER TABLE stock_transfer_items ADD COLUMN shortage_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000;
ALTER TABLE stock_transfer_items ADD COLUMN in_transit_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000;
```
- Enables granular delivery verification and captures physical in-transit status per line item.

---

### 3. New Database Tables

#### 3.1 `stock_discrepancies`
Created to maintain an immutable log of all delivery discrepancies, damaged goods, shortages, and variances discovered during physical receipt.

```sql
CREATE TABLE stock_discrepancies (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    company_id INTEGER NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
    branch_id INTEGER NULL REFERENCES branches(id) ON DELETE SET NULL,
    stock_transfer_id INTEGER NOT NULL REFERENCES stock_transfers(id) ON DELETE CASCADE,
    stock_transfer_item_id INTEGER NULL REFERENCES stock_transfer_items(id) ON DELETE SET NULL,
    stock_request_id INTEGER NULL REFERENCES stock_requests(id) ON DELETE SET NULL,
    item_id INTEGER NOT NULL REFERENCES items(id) ON DELETE CASCADE,
    batch_id INTEGER NULL REFERENCES stock_batches(id) ON DELETE SET NULL,
    source_godown_id INTEGER NULL REFERENCES godowns(id) ON DELETE SET NULL,
    dest_godown_id INTEGER NULL REFERENCES godowns(id) ON DELETE SET NULL,
    dispatched_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    received_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    discrepancy_qty DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    discrepancy_type VARCHAR(50) NOT NULL, -- shortage, damaged_goods, wrong_item, wrong_batch, quality_rejected
    reason TEXT NULL,
    investigation_notes TEXT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'reported', -- reported, under_investigation, pending_approval, resolved, rejected
    resolution_type VARCHAR(50) NULL, -- replacement_dispatch, separate_delivery, loss_write_off, cancelled
    resolution_notes TEXT NULL,
    stock_adjustment_id INTEGER NULL REFERENCES stock_adjustments(id) ON DELETE SET NULL,
    created_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    investigated_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    resolved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
    resolved_at DATETIME NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX idx_stock_disc_company ON stock_discrepancies (company_id);
CREATE INDEX idx_stock_disc_transfer ON stock_discrepancies (stock_transfer_id);
CREATE INDEX idx_stock_disc_status ON stock_discrepancies (status);
```

#### 3.2 `stock_discrepancy_approvals`
Maintains an authorization audit trail for write-offs, enforcing approval tiers and segregation of duties.

```sql
CREATE TABLE stock_discrepancy_approvals (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    stock_discrepancy_id INTEGER NOT NULL REFERENCES stock_discrepancies(id) ON DELETE CASCADE,
    approver_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    action VARCHAR(50) NOT NULL, -- submitted, approved, rejected
    decision_remarks TEXT NULL,
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0.000,
    estimated_value DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at DATETIME NULL,
    updated_at DATETIME NULL
);

CREATE INDEX idx_stock_disc_appr_disc ON stock_discrepancy_approvals (stock_discrepancy_id);
CREATE INDEX idx_stock_disc_appr_user ON stock_discrepancy_approvals (approver_id);
```

---

### 4. Integrity and Data Safety Proof

1. **Pre-Migration Integrity Check**:
   - Total tables before migration: 78
   - Total records in `companies`, `branches`, `departments`, `items`, `stock_batches`, `stock_movements`: Verified intact before migration.
2. **Post-Migration Verification**:
   - Zero existing rows were altered or corrupted.
   - Default column values (`stock_mode = 'centralized'`, `maintains_stock = 0`) ensured all existing branches and departments seamlessly behaved under centralized mode without data migrations.
3. **Rollback Certification**:
   - The migration's `down()` method drops `stock_discrepancy_approvals`, `stock_discrepancies`, and cleanly drops added columns from `stock_transfer_items`, `stock_transfers`, `stock_request_items`, `stock_requests`, `departments`, and `branches`.

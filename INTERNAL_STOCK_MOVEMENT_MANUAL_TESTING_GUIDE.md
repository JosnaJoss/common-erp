# INTERNAL STOCK MOVEMENT & DEPARTMENT-WISE INVENTORY MANAGEMENT
## End-to-End User & Quality Assurance Manual Testing Guide

### Prerequisites & Preparation

1. **Web Server**: Ensure the application is served locally (e.g., via `php artisan serve`).
2. **Access URL**: Navigate to `http://127.0.0.1:8000`.
3. **User Logins**:
   - **Administrator**: `admin@test.com` / `password` (Super Admin or Company Admin)
   - **Warehouse Manager**: User with role `inventory-manager`
   - **Department Supervisor**: User with role `production-head` or `operator`

---

### Step 1: Configuring Branch Stock Operating Mode

1. In the sidebar, navigate to **Inventory** > **Stock Configuration** (Route: `/inventory/configuration`).
2. Select your active Branch from the selector.
3. Review the **Branch Stock Mode** options:
   - **Centralized Warehouse Mode**: All departments consume materials directly from a single warehouse.
   - **Multiple Stock Locations Mode**: Departments can operate independent sub-stores.
4. Set the mode to **Multiple Stock Locations Mode**.
5. Designate the **Central Warehouse** (e.g., *Main Raw Material Godown*).
6. Click **Save Configuration**.
7. **Expected Result**: A green flash message confirms *"Inventory configuration updated successfully."* The branch record in `branches` updates `stock_mode = 'multi_location'` and assigns `central_godown_id`.

---

### Step 2: Configuring Department Stock Locations

1. On the same **Stock Configuration** screen, scroll down to the **Department Stock Locations** table.
2. Locate the **Production Department**:
   - Check the box **Maintains Independent Stock**.
   - Select **Production Store / Floor Godown** as the **Assigned Stock Godown**.
   - Select **Main Raw Material Godown** as the **Default Source Godown**.
3. Locate the **Retail Packing Department**:
   - Uncheck **Maintains Independent Stock** (consumes directly).
   - Set **Default Source Godown** to **Main Raw Material Godown**.
4. Click **Save Configuration**.
5. **Safe Transition Test**:
   - If you later attempt to uncheck "Maintains Independent Stock" on a department that has remaining stock in its store, the system will block the change with an error: *"Cannot disable stock-holding for department: Godown currently holds active inventory balance."*
6. **Expected Result**: The configuration saves. Production holds independent stock; Packing operates in centralized direct-draw mode.

---

### Step 3: Creating an Internal Material Requisition

1. In the sidebar, navigate to **Inventory** > **Stock Requisitions** (Route: `/inventory/requests`).
2. Click the **+ New Requisition** button in the top right.
3. On the creation form:
   - **Requesting Department**: Select *Production Department*.
   - **Source Warehouse**: Select *Main Raw Material Godown*.
   - **Destination Sub-Store**: Automatically defaults to *Production Store*.
   - **Required Date**: Select tomorrow's date.
   - **Priority**: Select **High** or **Urgent**.
4. Add Line Items:
   - **Item**: *Red Chilli Whole (RAW-CHILLI-001)*.
   - **Requested Quantity**: `100.000` KG.
   - **Preferred Batch (Optional)**: Leave blank or select a preferred lot.
   - **Remarks**: *"Urgent raw material required for Batch PRD-2026-001"*.
5. Click **Submit Requisition**.
6. **Expected Result**: The requisition is created with status **Pending Review** (`pending`). The requisition number (e.g., `REQ-20261127-1042`) is generated and listed on the requisition dashboard.

---

### Step 4: Warehouse Review & Approval / Rejection

1. Log in or switch to the **Warehouse Manager**.
2. Navigate to **Inventory** > **Stock Requisitions** and click **View** on the newly created requisition.
3. Inspect the requested items against the warehouse's current stock on hand.
4. **Testing Partial Approval**:
   - Enter `80.000` in the **Approved Quantity** field (approving 80 out of 100 KG).
   - Click **Approve Requisition**.
   - **Expected Result**: Status changes to **Partially Approved** (`partially_approved`). An audit log records who approved it and at what timestamp.
5. **Testing Rejection (Alternative Scenario)**:
   - If clicking **Reject**, a modal prompts for a mandatory rejection reason. Submitting without a reason fails validation. Submitting with *"Insufficient stock until next delivery"* updates status to **Rejected**.

---

### Step 5: Dispatching Stock with Batch Selection

1. Open the approved requisition. Click **Dispatch Goods** (Route: `/inventory/requests/{id}/dispatch`).
2. On the Dispatch screen:
   - **Waybill / Gate Pass Ref**: Enter `GATE-PASS-8841`.
   - In the line item row, enter **Dispatch Quantity**: `80.000`.
   - **Batch Selection**: Select active batch `BAT-CHILLI-001` (shows available stock: 500 KG).
3. Click **Confirm & Dispatch Stock**.
4. **Expected Result**:
   - A `StockMovement` of type `OUTWARD` immediately deducts 80 KG from `BAT-CHILLI-001` in the Main Warehouse.
   - A `StockTransfer` record (e.g., `TRF-20261127-9912`) is created in status **In Transit** (`in_transit`).
   - The transfer item displays `dispatched_qty = 80.000`, `in_transit_qty = 80.000`, and `received_qty = 0.000`.
   - Warehouse stock balance decreases by 80 KG, but destination stock has **NOT** yet increased.

---

### Step 6 & 7: Physical Receipt Verification & Discrepancy Recording

1. Log in as the **Receiving Supervisor** or **Production Department Head**.
2. Navigate to **Inventory** > **Stock Transfers** and find transfer `TRF-20261127-9912`.
3. Click **Verify & Receive Goods** (Route: `/inventory/transfers/{id}/receive`).
4. On the Receiving screen, enter physical count verification:
   - **Accepted Quantity (`received_qty`)**: Enter `72.000` KG.
   - **Damaged Quantity (`damaged_qty`)**: Enter `5.000` KG.
   - **Shortage Quantity (`shortage_qty`)**: Enter `3.000` KG.
   - **Discrepancy Type**: Select **Damaged Goods & Shortage**.
   - **Reason**: *"1 bag of 5 KG soaked in water during transit; 3 KG missing from container count."*
   - **Receiver Remarks**: *"Photographs captured and sent to warehouse."*
5. Click **Confirm Physical Receipt**.
6. **Expected Result**:
   - Exactly 72.000 KG is posted via `StockMovement` (INWARD) into the Production Store.
   - The transfer status is set to **Partially Received** (`partially_received`). It is **NOT** marked completed.
   - A `StockDiscrepancy` record is automatically created in status **Reported** (`reported`) with 8.000 KG variance.

---

### Step 8: Discrepancy Investigation & Loss Write-Off Approval

1. Navigate to **Inventory** > **Stock Discrepancies** (Route: `/inventory/discrepancies`).
2. Click **View Details** on the reported discrepancy.
3. **Investigate**:
   - Enter Investigation Notes: *"Inspected damaged bag; contents moldy and unusable. Driver confirms container seal was broken."*
   - Click **Update Investigation** (`status = 'under_investigation'`).
4. **Submit for Write-Off**:
   - Click **Submit for Write-Off Approval**.
   - Enter Justification: *"5 KG wet damage + 3 KG shortage unrecoverable from transporter."*
   - Status updates to **Pending Approval** (`pending_approval`).
5. **Testing Separation of Duties Violation**:
   - Attempt to click **Approve Write-Off** using the same user account that created/reported the discrepancy.
   - **Expected Result**: System halts with an error: *"Separation of duties violation: The user who reported or requested the discrepancy write-off cannot approve it."*
6. **Authorized Manager Approval**:
   - Log in as **Branch Manager** or **Company Admin** (a different user).
   - Navigate to the discrepancy and click **Approve Loss Write-Off**.
   - Enter Approval Remarks: *"Approved write-off. Claim filed with transporter."*
7. **Expected Result**:
   - Discrepancy status updates to **Resolved** (`resolved`), resolution type **Loss Write-Off** (`loss_write_off`).
   - A `StockAdjustment` record is generated.
   - A balanced `JournalEntry` is posted:
     - **Debit**: Inventory Loss / Damaged Goods Account.
     - **Credit**: Stock in Hand Account.

---

### Step 9: Manufacturing Consumption Auto-Resolution

1. Navigate to **Manufacturing** > **Daily Operations** > **New Operation**.
2. Select **Department**: *Production Department* (which has `maintains_stock = true`).
3. Add raw material *Red Chilli Whole*.
4. **Expected Result**: The system automatically pulls from the **Production Store** (`default_godown_id`) and validates against batches physically residing in that department store.
5. Create another operation selecting the **Retail Packing Department** (which has `maintains_stock = false`).
6. **Expected Result**: The system automatically pulls directly from the **Main Raw Material Godown** (`source_godown_id`) without requiring internal transfers.

---

### Step 10: Viewing Real-Time Inventory & Movement Reports

Navigate through all 7 specialized reports in the sidebar to verify data fidelity:
1. **Stock Requests Report** (`/inventory/reports/requests`): Displays full status breakdown (Pending, Approved, Rejected, Partially Dispatched).
2. **Stock Transfers Report** (`/inventory/reports/transfers`): Shows all dispatches with source, destination, waybill, and items.
3. **Pending Receipts Report** (`/inventory/reports/pending-receipts`): Filters in-transit transfers awaiting destination delivery count.
4. **Stock Discrepancies Report** (`/inventory/reports/discrepancies`): Displays discrepancies categorized by type (shortage, damaged, wrong item) and financial write-off status.
5. **In-Transit Stock Report** (`/inventory/reports/in-transit`): Real-time view of inventory currently between warehouses.
6. **Location-Wise Stock Report** (`/inventory/reports/location-stock`): Inventory breakdown across Central Warehouse, Production Store, and other departmental godowns.
7. **Material Consumption Report** (`/inventory/reports/consumption`): Detailed shop-floor consumption analytics by department and batch.

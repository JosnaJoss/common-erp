# INTERNAL STOCK MOVEMENT & DEPARTMENT-WISE INVENTORY MANAGEMENT
## Role-Based Access Control (RBAC) & Permission Matrix

### 1. Overview of System Roles

The Common ERP system manages user operations across the internal inventory movement lifecycle utilizing 6 primary operational roles:
1. **Super Admin (`super-admin`)**: Global system administrator across all companies and branches.
2. **Company Admin (`company-admin`)**: Executive administrator of the specific operating company.
3. **Branch Manager (`branch-manager` / `general-manager`)**: Authorized supervisor responsible for branch-level controls and financial approvals.
4. **Warehouse Manager (`inventory-manager` / `warehouse-manager`)**: Head of central warehouse storage, dispatch, and physical staging.
5. **Department Head (`production-head` / `packing-head`)**: Head of shop-floor manufacturing or packing operations raising requisitions.
6. **Floor Supervisor / Receiving Operator (`operator` / `store-clerk`)**: Shop-floor staff performing physical count verification and receipt.

---

### 2. Comprehensive Permission Matrix

| Permission Name | Permission Slug | Module | Super Admin | Company Admin | Branch Manager | Warehouse Manager | Dept Head | Floor Op |
|---|---|---|:---:|:---:|:---:|:---:|:---:|:---:|
| **Manage Branch Stock Config** | `branch_stock_config.manage` | Configuration |  |  |  | ❌ | ❌ | ❌ |
| **View Branch Stock Config** | `branch_stock_config.view` | Configuration |  |  |  |  |  | ❌ |
| **View Stock Requisitions** | `stock_requests.view` | Requisitions |  |  |  |  |  |  |
| **Create Stock Requisitions** | `stock_requests.create` | Requisitions |  |  |  | ❌ |  | ❌ |
| **Approve / Reject Requisitions** | `stock_requests.approve` | Requisitions |  |  |  |  | ❌ | ❌ |
| **Dispatch Requisitions** | `stock_transfers.dispatch` | Dispatch |  |  |  |  | ❌ | ❌ |
| **View Stock Transfers** | `stock_transfers.view` | Transfers |  |  |  |  |  |  |
| **Receive Physical Transfers** | `stock_transfers.receive` | Receiving |  |  | ❌ | ❌ |  |  |
| **View Discrepancies** | `discrepancies.view` | Discrepancies |  |  |  |  |  |  |
| **Report Discrepancies** | `discrepancies.create` | Discrepancies |  |  | ❌ | ❌ |  |  |
| **Investigate Discrepancies** | `discrepancies.investigate` | Discrepancies |  |  |  |  | ❌ | ❌ |
| **Approve Discrepancy Write-Off** | `discrepancies.approve_write_off` | Write-Offs |  |  |  | ❌* | ❌ | ❌ |
| **View Inventory Reports** | `inventory_reports.view` | Reports |  |  |  |  |  | ❌ |

*\* Note: Warehouse Manager may possess write-off approval privileges in small setups, but the core system separation-of-duties invariant strictly prevents them from approving any write-off that they personally reported or submitted.*

---

### 3. Detailed Permission Specifications & Enforcements

#### 3.1 `branch_stock_config.manage`
- **Description**: Ability to toggle branch operating mode (`centralized` vs `multi_location`), set the branch central godown, and modify department stock-holding status and godown assignments.
- **UI Enforcement**: The configuration action button and edit controls on `resources/views/inventory/configuration.blade.php` are rendered only if the user has permission.
- **Controller & Service Enforcement**: Guarded by permission middleware and checked in `InventoryController::updateConfiguration()` and `InventoryService::updateBranchStockMode()`. Throws a validation exception if stock exists when attempting to disable stock-holding on a department.

#### 3.2 `stock_requests.create`
- **Description**: Ability to submit internal material requisitions for department consumption or sub-store replenishment.
- **UI Enforcement**: "Create Requisition" buttons shown on request index screen.
- **Controller & Service Enforcement**: Validated in `InventoryController::storeRequest()` and executed in `InventoryService::createStockRequest()`.

#### 3.3 `stock_requests.approve`
- **Description**: Authority to review pending requisitions and approve full/partial quantities or reject with mandatory justification.
- **UI Enforcement**: "Review & Approve" button on request details screen is visible only to approvers.
- **Controller Enforcement**: `InventoryController::approveRequest()` and `rejectRequest()`. Requires `rejection_reason` when rejecting.

#### 3.4 `stock_transfers.dispatch`
- **Description**: Authority to physically dispatch approved materials from the source warehouse, select active batches, and record waybill/challan references.
- **UI Enforcement**: "Dispatch Goods" button rendered only for approved or partially dispatched requests.
- **Controller & Service Enforcement**: `InventoryController::dispatchRequest()` validates batch stock quantities and records `OUTWARD` movement.

#### 3.5 `stock_transfers.receive`
- **Description**: Authority to physically count incoming shipments, enter accepted quantities, and report shortages or damages.
- **UI Enforcement**: "Verify & Receive" action on transfer listing.
- **Controller & Service Enforcement**: `InventoryController::receiveTransfer()` routes accepted goods to destination godown via `INWARD` movement and spawns `StockDiscrepancy` records for variances.

#### 3.6 `discrepancies.approve_write_off` & Separation of Duties
- **Description**: Authority to sanction loss write-offs for damaged or unrecovered items in transit, triggering financial journal entries.
- **UI Enforcement**: "Approve Write-Off" button rendered on discrepancy details screen.
- **Controller & Service Enforcement**:
  - `InventoryController::approveWriteOff()`
  - `InventoryService::approveDiscrepancyWriteOff()`
  - **Strict Segregation Check**: The system blocks execution if the approver ID matches `discrepancy.created_by` or any `approver_id` who submitted the request:
  ```php
  if (($discrepancy->created_by && $discrepancy->created_by === $userId) || 
      ($submittedBy && (int)$submittedBy === $userId)) {
      throw new Exception("Separation of duties violation: The user who reported or requested the discrepancy write-off cannot approve it.");
  }
  ```

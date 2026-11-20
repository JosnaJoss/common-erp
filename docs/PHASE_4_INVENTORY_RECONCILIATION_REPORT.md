# Common ERP – Phase 4: Inventory Valuation & General Ledger Reconciliation Report

## 1. Executive Summary

In enterprise resource planning, financial statements cannot be audited or certified without provable reconciliation between the physical inventory assets stored in godowns/warehouses and the inventory asset accounts maintained in the General Ledger.

Prior to Phase 4, Common ERP maintained physical inventory batch counts in `stock_batches` and movement history in `stock_movements`. However:
1. **Opening Stock:** Entering opening inventory created physical batches but generated zero General Ledger entries, creating an immediate unbalance in Opening Equity.
2. **Stock Adjustments:** Physical counts identifying shortages, surpluses, or damages updated quantities without posting corresponding expense, revenue, or write-off journals.
3. **No Reconciliation Tooling:** There was no reporting mechanism to compare total physical valuation against the General Ledger inventory asset balances (`Stock on Hand`, `Raw Materials Inventory`, `Finished Goods Inventory`).

**Phase 4 Resolution:**
- Opening stock and stock adjustments now atomically post balanced double-entry vouchers to the General Ledger.
- A dedicated **Inventory Valuation & GL Reconciliation Engine** has been implemented (`/reports/inventory-reconciliation`) that queries batch-level physical valuations across all godowns and contrasts them directly against the relevant GL asset accounts, reporting any variance down to the individual godown and item level.

---

## 2. Inventory Costing Methodology

Common ERP utilizes batch-specific actual cost valuation, supporting FIFO and weighted moving average cost tracking across all companies:

1. **Purchases / Inward Goods:** Each inward transaction registers a batch with its purchase/cost price:
   $$\text{Batch Valuation} = \text{Batch Quantity} \times \text{Cost Price}$$
2. **Manufacturing Inward:** Finished goods batches record capitalized manufacturing unit costs derived from direct materials plus direct labor:
   $$\text{FG Unit Cost} = \frac{\text{Direct Material Cost} + \text{Direct Labor Cost}}{\text{Units Produced}}$$
3. **Stock Adjustments & Write-Offs:** Re-measured using the existing batch cost price. If no batch cost exists, the system cascades safely to `Item::cost_price` $\rightarrow$ `Item::purchase_price` $\rightarrow$ default baseline cost.

---

## 3. Opening Stock Accounting Workflow

When initializing a new company, branch, or financial year:

1. **User Workflow:** Navigates to **Inventory $\rightarrow$ Bulk Opening Stock** (`/inventory/opening-stock`). Selects warehouse/godown, inputs item quantities and unit costs.
2. **Physical Updates:** `InventoryService::recordInward()` creates or increments the batch in `stock_batches` with reference `OpeningStock` and generates corresponding audit records in `stock_movements`.
3. **Automated Balanced Journal Entry:**
   - **Voucher Type:** `Journal` (Reference: `OpeningStock`)
   - **Debit:** `Stock on Hand` (Current Assets) – ₹ Total Inward Valuation
   - **Credit:** `Opening Balance Equity` (Capital & Reserves / Equity) – ₹ Total Inward Valuation
   - **Financial Invariant:** Initial assets perfectly match initial equity ($Assets = Equity$).

---

## 4. Physical Stock Adjustment Accounting

Discrepancies identified during cycle counts or annual stocktaking are entered via **Inventory $\rightarrow$ Stock Adjustments** (`/inventory/adjustments`). Common ERP classifies and accounts for variances automatically based on the variance sign and stated reason:

| Adjustment Type | Condition / Trigger | Debit Ledger | Credit Ledger | Financial Classification |
| :--- | :--- | :--- | :--- | :--- |
| **Physical Shortage** | Actual < System ($diff < 0$) | `Inventory Shortage & Loss` | `Stock on Hand` | Direct Operational Expense |
| **Physical Surplus** | Actual > System ($diff > 0$) | `Stock on Hand` | `Inventory Surplus & Gain` | Operating Revenue / Gain |
| **Damaged / Expired** | Actual < System with keyword "damage", "expire", "spoil" | `Damaged & Expired Stock Expense` | `Stock on Hand` | Extraordinary Loss / Write-off |

Every adjustment updates the primary `StockAdjustment` record with the resulting `journal_entry_id` within a single database transaction, ensuring zero orphan records.

---

## 5. Physical vs Ledger Reconciliation Engine

The newly created controller action `ReportController::inventoryReconciliation()` provides real-time audit verification:

### Mathematical Reconciliation Model
$$\text{Total Physical Valuation} = \sum_{\text{all active batches}} (\text{batch.quantity} \times \text{batch.cost\_price})$$

$$\text{Total Ledger Inventory Assets} = \sum_{\text{Inventory Asset Ledgers}} \text{ledger.current\_balance}$$
*(Covering `Stock on Hand`, `Raw Materials Inventory`, and `Finished Goods Inventory`)*

$$\text{Reconciliation Variance} = \text{Total Physical Valuation} - \text{Total Ledger Inventory Assets}$$

### System Report Output (`/reports/inventory-reconciliation`):
1. **Summary KPI Cards:**
   - Total Physical Valuation (Sum of physical warehouse stock)
   - Total General Ledger Valuation (Sum of asset ledger balances)
   - Net Variance (Highlighted green if ₹0.00, amber/red if variance detected)
2. **Item & Godown Breakdown Table:**
   - Columns: Item Code, Item Name, Godown, Current Batch Quantity, Unit Cost, Total Valuation, Last Movement Date.
3. **Inventory Asset Accounts Breakdown:**
   - Detail of each GL account code, account title, current debit balance, and last transaction voucher date.

---

## 6. Multi-Godown & Multi-Branch Aggregation

The reconciliation engine respects multi-company boundaries:
- Enforces strict tenant separation via `CompanyContextService::getActiveCompanyId()`.
- Supports filtering across individual branches or specific godowns.
- Consolidates valuation for multi-warehouse companies without cross-tenant data leakage.

---

## 7. Verification Evidence

Automated validation via `FinancialAndInventoryIntegrityTest`:
- **Opening Stock Verification:** Validated 100 units raw + 50 units FG inward totaling ₹10,000.00 posted Debit `Stock on Hand` ₹10,000.00 and Credit `Opening Balance Equity` ₹10,000.00.
- **Adjustment Verification:** Validated Shortage (-₹500.00), Surplus (+₹750.00), and Damage (-₹500.00) JVs with correct ledger classification.
- **Reconciliation Engine:** Tested route `reports.inventory.reconciliation`, confirming view variables `totalPhysicalValuation`, `totalLedgerValuation`, and `variance` compute accurately and return HTTP 200 OK.

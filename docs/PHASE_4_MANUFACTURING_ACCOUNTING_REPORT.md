# Common ERP – Phase 4: Manufacturing Cost Capitalization & Operational Accounting Report

## 1. Executive Summary

In traditional ERP implementations, manufacturing operations and financial accounting frequently operate in disconnected silos. Prior to Phase 4 in Common ERP, the `daily_operations` manufacturing module tracked labor outputs, raw material consumption, and finished goods receipts operationally, but failed to post financial journal vouchers to the General Ledger. 

This created critical financial integrity anomalies:
1. **Uncapitalized Production Costs:** Raw materials were depleted physically without crediting the Raw Materials Inventory asset ledger.
2. **Unrecorded Labor Liabilities:** Direct production wages accrued on the shop floor were not recognized in accounting as liabilities or capitalized into product costs.
3. **Unreflected Finished Goods Assets:** Finished goods batches entered warehouses without an asset-side valuation debit to Finished Goods Inventory.
4. **Distorted Cost of Goods Sold (COGS):** When goods were sold, the accounting system had no accurate inventory asset base, distorting gross margin analysis.

**Phase 4 Resolution:**
A fully automated, atomic double-entry cost capitalization engine has been implemented within `DailyOperationService`. When a daily manufacturing operation is posted, Common ERP executes stock movements and simultaneously generates a balanced, multi-legged journal voucher linking physical inventory movements directly to the General Ledger.

---

## 2. Cost Capitalization Accounting Formula & Mechanics

Common ERP utilizes standard absorption costing principles to capitalize production costs directly into finished goods inventory.

### Mathematical Formulation
$$\text{Total Capitalized FG Cost} = \sum (\text{Direct Consumed Raw Materials}) + \sum (\text{Direct Piece-Rate / Hourly Labor}) - \text{Scrap Offset}$$

$$\text{Unit Cost per Finished Unit} = \frac{\text{Total Capitalized FG Cost}}{\text{Total Finished Goods Quantity Produced}}$$

### Applied Numerical Example (from Production Test Run)
- **Consumed Raw Materials:** 20.00 Kg of Raw Red Chilli @ ₹50.00/Kg = **₹1,000.00**
- **Direct Manufacturing Labor:** 10 units processed @ ₹20.00/unit = **₹200.00**
- **Normal Scrap / Wastage:** ₹0.00
- **Total Production Cost Capitalized:** ₹1,000.00 + ₹200.00 = **₹1,200.00**
- **Finished Goods Produced:** 18.00 Kg of Chilli Masala Powder
- **Unit Cost Calculated:** ₹1,200.00 / 18.00 Kg = **₹66.67 / Kg**

---

## 3. Double-Entry Journal Voucher Structure

Every manufacturing post executes an atomic journal entry under voucher type `Journal` with reference `DailyOperation`:

```
========================================================================================
Voucher: JV-20240104-0001 | Reference: DailyOperation #OP-20240104-001
========================================================================================
Account / Ledger                Type               Debit (₹)         Credit (₹)
----------------------------------------------------------------------------------------
Finished Goods Inventory        Current Asset      1,200.00                 -
Raw Materials Inventory         Current Asset             -          1,000.00
Manufacturing Labor Payable     Current Liability         -            200.00
----------------------------------------------------------------------------------------
TOTAL                                              1,200.00          1,200.00
========================================================================================
STATUS: BALANCED (Debit == Credit)
```

### When Production Scrap or Wastage Occurs:
If abnormal spoilage or unrecoverable loss occurs during the batch:
```
Debit:  Finished Goods Inventory        (Net value of good output)
Debit:  Production Scrap & Wastage      (Abnormal loss recognized in P&L)
Credit: Raw Materials Inventory         (Total raw materials consumed)
Credit: Manufacturing Labor Payable     (Total labor applied)
```

Both scenarios maintain strict balance ($Debit = Credit$) ensuring Trial Balance integrity across all accounting periods.

---

## 4. Direct Labor & Payroll Accrual Integration

Common ERP supports two manufacturing compensation structures:
1. **Piece-Rate Compensation:** Calculated as `units_completed * rate_per_unit`.
2. **Hourly Labor Compensation:** Calculated as `hours_worked * hourly_rate`.

During operation posting, the total wage earned by all assigned shop-floor staff is credited to **Manufacturing Labor Payable** (`Current Liabilities`). 

### Downstream Payroll Settlement:
When the monthly or weekly payroll cycle is processed:
1. `StaffPayroll` calculates gross wages, tax withholdings, and net salary.
2. The payroll settlement journal voucher debits **Manufacturing Labor Payable** (and Salaried Staff Expense) and credits **Bank / Cash Account**.
3. This eliminates duplicate expense recognition: labor is capitalized into inventory at time of manufacture, and converted to Cost of Goods Sold only upon sales delivery.

---

## 5. Multi-Godown Transfers and In-Process Distribution

In multi-stage manufacturing (such as Raw Warehouse $\rightarrow$ Factory Floor $\rightarrow$ Curing Godown $\rightarrow$ Finished Packaging Godown), `DailyOperationService` handles simultaneous stock movements:
1. **Raw Material Consumption:** Decrements batch stock at `source_godown_id`.
2. **Finished Output Registration:** Inwards physical batch stock to `dest_godown_id`.
3. **Finished Goods Distribution:** If the daily operation includes secondary distributions to dispatch vehicles or distribution branches, `DailyOperationDistribution` records are processed atomically within the same database transaction.

---

## 6. Duplicate Posting Prevention & Atomicity

To guarantee high-concurrency safety and financial integrity:
1. **Row-Level Locking:** `DailyOperation::where('id', $operationId)->lockForUpdate()->first()` prevents concurrent worker processes from posting the same batch simultaneously.
2. **State Guard:**
   ```php
   if ($operation->stock_posted) {
       throw new \Exception("Stock and accounting for operation #{$operation->operation_number} has already been posted.");
   }
   ```
3. **Transactional Boundary:** The entire process—raw stock deduction, finished stock inward, distribution transfers, and journal entry generation—is wrapped in `DB::transaction()`. Any failure rolls back all physical and financial changes.
4. **Traceability:** The primary key of the generated `JournalEntry` is stored directly on `daily_operations.journal_entry_id`.

---

## 7. Operational Reversal & Cancellation Workflow

When an approved manufacturing run is voided or rejected due to quality failure or clerical error, `DailyOperationService::reverseOperation()` ensures financial and physical synchronization:

1. **Physical Inventory Unwinding:**
   - Finished goods batches created by the operation are deducted from inventory.
   - Consumed raw materials are credited back to their source batches.
2. **Financial Reversal Voucher:**
   - A mirror-image reversing journal entry is generated:
     ```
     Debit:  Raw Materials Inventory         (Restores Asset)
     Debit:  Manufacturing Labor Payable     (Reverses Liability)
     Credit: Finished Goods Inventory        (Reduces Asset)
     ```
   - Reference type set to `DailyOperationReversal` with parent operation ID.
3. **Audit Trail:**
   - The operation's `stock_posted` flag is reset to `false`.
   - The approval status is set to `rejected` with an explicit reason logged.

---

## 8. Verification & Test Evidence

The manufacturing accounting flow has been tested and verified via `FinancialAndInventoryIntegrityTest::test_manufacturing_daily_operation_capitalizes_costs_into_finished_goods_inventory()`:

- **Raw Stock Decrement:** Verified from 100.00 Kg to 80.00 Kg.
- **FG Stock Creation:** Verified batch `FG-BATCH-01` created with quantity 18.00 Kg.
- **Journal Entry:** Total Debit = ₹1,200.00, Total Credit = ₹1,200.00.
- **Debit Breakdown:** `Finished Goods Inventory` ₹1,200.00.
- **Credit Breakdown:** `Raw Materials Inventory` ₹1,000.00, `Manufacturing Labor Payable` ₹200.00.
- **Reversal:** Raw stock restored to 100.00 Kg, FG reduced to 0.00 Kg, balanced reversal JV posted.

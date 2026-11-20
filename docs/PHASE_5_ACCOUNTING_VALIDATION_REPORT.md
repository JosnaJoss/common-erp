# Common ERP – Phase 5 Accounting Validation Report

## 1. Accounting Architecture & Standards Compliance

Phase 5 introduces comprehensive GAAP/IAS 2 (Inventories) and IAS 7 (Statement of Cash Flows) compliant accounting mechanisms into Common ERP. All accounting events generate balanced double-entry vouchers with complete journal line narrations, strict tenant scoping, and financial period verification.

---

## 2. Standardized Double-Entry Journal Entries

### 2.1 Manufacturing Production & Overhead Capitalization (IAS 2)

Under IAS 2, the cost of inventories includes all costs of purchase, costs of conversion (direct labor), and other costs incurred in bringing the inventories to their present location and condition (systematic allocation of fixed and variable production overhead).

#### Business Event:
A production batch consumes ₹1,000 in raw materials, incurs ₹250 in direct labor, and allocates ₹300 in factory power overhead to produce 10 units of Finished Goods.

#### Capitalization Journal Voucher:
| Account / Ledger | Group / Category | Debit (₹) | Credit (₹) | Narration |
|---|---|---|---|---|
| Finished Goods Inventory | Current Assets | 1,550.00 | - | Finished goods capitalized for Op #DOP-202611-001 |
| Raw Materials Inventory | Current Assets | - | 1,000.00 | Raw materials consumed in Op #DOP-202611-001 |
| Manufacturing Labor Payable | Current Liabilities | - | 250.00 | Direct labor wages recognized for Op #DOP-202611-001 |
| Manufacturing Overhead Absorbed | Direct Expenses (Contra) | - | 300.00 | Applied manufacturing overhead for Op #DOP-202611-001 |
| **TOTAL** | | **1,550.00** | **1,550.00** | **Balanced Double-Entry ($\Delta = 0.00$)** |

> [!NOTE]
> Crediting `Manufacturing Overhead Absorbed` under the Expense category offsets the actual incurred factory electricity expense already booked in the period, ensuring that overhead is neither omitted from inventory valuation nor double-counted in P&L.

---

### 2.2 Manufacturing Reversal Journal Entry

When an operation is cancelled or reversed due to formula discrepancies or defective batches, the original journal is marked `is_reversed = true` and an exact mirror entry is posted.

#### Reversal Journal Voucher:
| Account / Ledger | Group / Category | Debit (₹) | Credit (₹) | Narration |
|---|---|---|---|---|
| Raw Materials Inventory | Current Assets | 1,000.00 | - | Reversal of Op #DOP-202611-001: Raw materials restored |
| Manufacturing Labor Payable | Current Liabilities | 250.00 | - | Reversal of Op #DOP-202611-001: Direct labor reversed |
| Manufacturing Overhead Absorbed | Direct Expenses (Contra) | 300.00 | - | Reversal of Op #DOP-202611-001: Applied overhead reversed |
| Finished Goods Inventory | Current Assets | - | 1,550.00 | Reversal of Op #DOP-202611-001: Finished goods deducted |
| **TOTAL** | | **1,550.00** | **1,550.00** | **Balanced Double-Entry ($\Delta = 0.00$)** |

---

### 2.3 Payroll Labor Settlement & Double-Counting Prevention

When factory workers who have earned direct piece-rate or hourly wages on production operations are processed through monthly payroll, their gross salary is split:
1. Direct manufacturing wages already capitalized into inventory are debited to `Manufacturing Labor Payable` (clearing the liability).
2. Administrative excess or indirect wages are debited to `Salaries & Staff Wages`.

#### Business Scenario:
A production worker with ₹30,000 gross salary earned ₹8,000 in accrued manufacturing wages during the month. Statutory deductions: PF ₹1,800, ESI ₹225. Net pay: ₹27,975.

#### Payroll Disbursement Journal Voucher:
| Account / Ledger | Group / Category | Debit (₹) | Credit (₹) | Narration |
|---|---|---|---|---|
| Manufacturing Labor Payable | Current Liabilities | 8,000.00 | - | Factory labor accrued in production settled |
| Salaries & Staff Wages | Indirect Expenses | 22,000.00 | - | Administrative / Indirect salary expense |
| Provident Fund Payable | Current Liabilities | - | 1,800.00 | Employee PF deduction |
| ESI Payable | Current Liabilities | - | 225.00 | Employee ESI deduction |
| Main Bank Account | Bank Accounts | - | 27,975.00 | Net salary disbursed via bank transfer |
| **TOTAL** | | **30,000.00** | **30,000.00** | **Balanced Double-Entry ($\Delta = 0.00$)** |

**P&L Impact:** The P&L reflects only ₹22,000 in direct salary expense. The remaining ₹8,000 sits capitalized inside inventory asset value until finished goods are sold as COGS, completely preventing duplicate expense booking.

---

### 2.4 Cash Flow Statement Period Delta Movement (IAS 7)

Working capital adjustments in the Indirect Cash Flow statement now calculate exact period movements between `start_date` and `end_date`:

$$\Delta \text{Receivables} = \text{Balance}_{end} - \text{Balance}_{start}$$
$$\Delta \text{Inventories} = \text{Balance}_{end} - \text{Balance}_{start}$$
$$\Delta \text{Payables} = \text{Balance}_{end} - \text{Balance}_{start}$$
$$\Delta \text{Taxes} = \text{Balance}_{end} - \text{Balance}_{start}$$

#### Indirect Method Reconciliation Formula:
$$\text{Net Cash Flow from Operating Activities} = \text{Net Profit} - \Delta \text{Receivables} - \Delta \text{Inventories} + \Delta \text{Payables} + \Delta \text{Taxes}$$

#### Cash Reconciliation Proof:
$$\text{Closing Cash Balance} = \text{Opening Cash Balance} + \text{Net Increase / (Decrease) in Cash}$$

Every execution of the Cash Flow Statement mathematically satisfies this invariant.

---

### 2.5 Inventory Physical vs General Ledger Reconciliation

The reconciliation report calculates:
1. **Physical Valuation:** Sum of `quantity * cost_price` across all active `StockBatch` records.
2. **General Ledger Valuation:** Sum of `current_balance` across all ledgers with `is_inventory = true`.
3. **Variance:** $\text{Variance} = \text{Physical Valuation} - \text{GL Valuation}$.
4. **Audit Status:** Marked "Reconciled" when $|\text{Variance}| \le 0.05$.

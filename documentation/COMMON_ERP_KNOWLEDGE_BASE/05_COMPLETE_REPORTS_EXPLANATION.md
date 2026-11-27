# COMMON ERP – COMPLETE REPORTS EXPLANATION GUIDE
## Comprehensive Study of All 14 Enterprise Reports: Formulas, Data Columns, Interpretation & Financial Integrity

---

### Navigation Index
- [1. Daily Operations Report](#1-daily-operations-report)
- [2. Trial Balance Report](#2-trial-balance-report)
- [3. Profit & Loss Account](#3-profit--loss-account)
- [4. Balance Sheet](#4-balance-sheet)
- [5. Cash Flow Statement](#5-cash-flow-statement)
- [6. Day Book](#6-day-book)
- [7. Sales Register](#7-sales-register)
- [8. Purchase Register](#8-purchase-register)
- [9. Stock Valuation Report](#9-stock-valuation-report)
- [10. Stock Reconciliation Report](#10-stock-reconciliation-report)
- [11. Customer Outstanding (Receivables)](#11-customer-outstanding-receivables)
- [12. Supplier Outstanding (Payables)](#12-supplier-outstanding-payables)
- [13. Payroll Summary Report](#13-payroll-summary-report)
- [14. Tax / GST Summary Report](#14-tax--gst-summary-report)

---

## 1. Daily Operations Report

### A. Report Identification
- **Report Name:** Daily Operations Manufacturing Summary
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Daily Operations Report
- **Route:** `reports.daily_operations.index` (`/reports/daily-operations`)
- **Controller & Method:** `App\Http\Controllers\ReportController@dailyOperations`
- **Database Tables Used:** `daily_operations`, `daily_operation_items`, `products`, `items`, `machines`

### B. What Does This Report Mean?
This report aggregates factory production output, shift performance, machine utilization hours, and material wastage across all manufacturing runs on a daily basis.

### C. Why Does a Business Need This Report?
Factory owners use it to detect production bottlenecks, monitor scrap/rejection rates, evaluate machine operator efficiency, and ensure that planned daily targets are met.

### D. Information Displayed
| Column Header | Description |
|---|---|
| Date | Date of the manufacturing shift |
| Shift | Operating shift (Morning / Evening / Night) |
| Finished Product | Product SKU manufactured |
| Target Quantity | Planned production output |
| Actual Output | Good sellable units produced |
| Scrap / Defect Qty | Units spoiled, rejected, or lost |
| Efficiency (%) | $\frac{\text{Actual Output}}{\text{Target Quantity}} \times 100$ |
| Machine Hours | Total machine run hours |
| Total Cost (₹) | Total capitalized production cost in INR |

### E. Available Filters
- Date From & Date To
- Machine / Work Center
- Finished Product / BOM Recipe

### F. Calculation Formula
$$\text{Scrap Rate (\%)} = \frac{\text{Scrap Quantity}}{\text{Actual Output} + \text{Scrap Quantity}} \times 100$$

### G. Sample Report Data
| Date | Shift | Product | Target | Actual | Scrap | Efficiency | Machine Hrs | Total Cost (₹) |
|---|---|---|---|---|---|---|---|---|
| 2026-11-20 | Morning | Chicken Masala 100g | 1,000 PKT | 992 PKT | 8 PKT | 99.2% | 3.5 hrs | ₹44,640.00 |
| 2026-11-20 | Evening | Meat Masala 200g | 500 PKT | 490 PKT | 10 PKT | 98.0% | 4.0 hrs | ₹44,100.00 |
| **Total** | | | **1,500 PKT** | **1,482 PKT** | **18 PKT** | **98.8%** | **7.5 hrs** | **₹88,740.00** |

### H. How to Interpret & Warning Signs
- **Warning Sign:** Efficiency below 95% or scrap rate above 2% indicates machine misalignment, poor quality packaging film, or operator carelessness.
- **Cost Alert:** Unusually high cost per unit indicates excessive machine downtime or raw material price increases.

### I. Related Reports
- Stock Movement Ledger (to audit raw material withdrawals)
- Stock Valuation Report (to verify finished goods additions)

### J. Implementation Verification
**Verified.** Controller queries `daily_operations` scoped to active `company_id`.

---

## 2. Trial Balance Report

### A. Report Identification
- **Report Name:** General Ledger Trial Balance
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Trial Balance
- **Route:** `reports.financial.trial_balance` (`/reports/financial/trial-balance`)
- **Controller & Method:** `App\Http\Controllers\ReportController@trialBalance`
- **Database Tables Used:** `account_ledgers`, `account_transactions`, `account_groups`

### B. What Does This Report Mean?
The Trial Balance is the ultimate proof of arithmetic accuracy in double-entry bookkeeping. It compiles the debit or credit closing balance of every ledger in the Chart of Accounts at a specified date.

### C. Why Does a Business Need This Report?
Before preparing the Profit & Loss statement or Balance Sheet, the accountant MUST verify that total debits equal total credits. If they do not balance, the books are corrupt.

### D. Information Displayed
| Column Header | Description |
|---|---|
| Account Code | Unique alphanumeric account number |
| Account Ledger Name | Official name of the account |
| Group Name | Parent account group (e.g. Current Assets, Sales Revenue) |
| Debit Balance (₹) | Closing balance if debits exceed credits |
| Credit Balance (₹) | Closing balance if credits exceed debits |

### E. Calculation Formula
$$\sum \text{Debit Balances} = \sum \text{Credit Balances}$$

### F. Sample Report Data
| Account Code | Account Ledger Name | Group | Debit (₹) | Credit (₹) |
|---|---|---|---|---|
| 10010 | HDFC Current Bank Account | Bank Accounts | 2,45,000.00 | - |
| 10020 | Cash in Hand | Cash Accounts | 18,500.00 | - |
| 10040 | Stock in Hand (Inventory) | Current Assets | 4,20,000.00 | - |
| 10050 | Sundry Debtors (Receivables) | Current Assets | 1,85,000.00 | - |
| 10080 | Input GST Tax Credit | Current Assets | 14,200.00 | - |
| 20010 | Sundry Creditors (Payables) | Current Liabilities | - | 1,62,000.00 |
| 20050 | Output GST Tax Liability | Current Liabilities | - | 18,500.00 |
| 30010 | Owner's Share Capital | Equity / Capital | - | 5,00,000.00 |
| 40010 | Sales Revenue | Operating Revenue | - | 6,50,000.00 |
| 50000 | Cost of Goods Sold (COGS) | Direct Expenses | 3,80,000.00 | - |
| 50010 | Salaries & Wages Expense | Indirect Expenses | 62,000.00 | - |
| 50020 | Factory Rent Expense | Indirect Expenses | 25,000.00 | - |
| 50030 | Electricity & Power | Indirect Expenses | 10,800.00 | - |
| **Total** | | | **₹13,60,500.00** | **₹13,60,500.00** |

### G. How to Interpret
- If the difference between Total Debits and Total Credits is non-zero, the ERP has a database integrity violation.
- In Common ERP, the test suite actively asserts `abs(debits - credits) <= 0.05`.

---

## 3. Profit & Loss Account

### A. Report Identification
- **Report Name:** Statement of Profit and Loss (Income Statement)
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Profit & Loss
- **Route:** `reports.financial.profit_and_loss` (`/reports/financial/profit-and-loss`)
- **Controller & Method:** `App\Http\Controllers\ReportController@profitAndLoss`
- **Database Tables Used:** `account_transactions`, `account_ledgers`, `account_groups`

### B. What Does This Report Mean?
Summarizes revenues, cost of goods sold, and operating overheads over a chosen period to determine whether the company generated a Net Profit or suffered a Net Loss.

### C. Calculation Formulas
$$\text{Gross Profit} = \text{Operating Sales Revenue} - \text{Cost of Goods Sold (COGS)}$$

$$\text{Net Operating Profit} = \text{Gross Profit} - \text{Total Operating Expenses} + \text{Other Income}$$

### D. Sample Report Data
```
PROFIT & LOSS STATEMENT (01-Apr-2026 to 23-Nov-2026)
--------------------------------------------------------
Operating Revenue:
  40010 Sales Revenue                         ₹6,50,000.00
  Total Revenue                                            ₹6,50,000.00

Cost of Goods Sold:
  50000 Cost of Goods Sold                   (₹3,80,000.00)
--------------------------------------------------------
GROSS PROFIT                                               ₹2,70,000.00 (Margin: 41.5%)

Operating & Administrative Expenses:
  50010 Salaries & Wages                       ₹62,000.00
  50020 Factory Rent                           ₹25,000.00
  50030 Electricity & Power                    ₹10,800.00
  50040 Vehicle Fuel & Delivery                 ₹8,500.00
  50050 Depreciation Expense                   ₹10,000.00
  Total Operating Expenses                                (₹1,16,300.00)
--------------------------------------------------------
NET PROFIT BEFORE TAX                                      ₹1,53,700.00 (Net Margin: 23.6%)
```

---

## 4. Balance Sheet

### A. Report Identification
- **Report Name:** Statement of Financial Position (Balance Sheet)
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Balance Sheet
- **Route:** `reports.financial.balance_sheet` (`/reports/financial/balance-sheet`)
- **Controller & Method:** `App\Http\Controllers\ReportController@balanceSheet`
- **Database Tables Used:** `account_ledgers`, `account_transactions`, `account_groups`

### B. What Does This Report Mean?
Presents a financial snapshot of everything the company owns (Assets) and owes (Liabilities and Equity) as of a specific date.

### C. Mathematical Integrity Check
$$\text{Total Assets} = \text{Total Liabilities} + \text{Equity} + \text{Current Period Net Profit}$$

### D. Sample Report Data
```
BALANCE SHEET AS AT 23-NOV-2026
===================================================================
ASSETS                                LIABILITIES & EQUITY
----------------------------------    -----------------------------
Non-Current Assets:                   Equity / Capital:
  Plant & Machinery     ₹3,50,000       Share Capital     ₹5,00,000
  Less: Depreciation    (₹10,000)       Current Profit    ₹1,53,700
  Net Fixed Assets      ₹3,40,000       Total Equity      ₹6,53,700

Current Assets:                       Current Liabilities:
  Inventory (Stock)     ₹4,20,000       Sundry Creditors  ₹1,62,000
  Sundry Debtors        ₹1,85,000       Output GST Tax      ₹18,500
  HDFC Bank Account     ₹2,45,000       Total Liabilities ₹1,80,500
  Cash in Hand            ₹18,500
  Input GST Tax Credit    ₹14,200
  Total Current Assets  ₹8,82,700
----------------------------------    -----------------------------
TOTAL ASSETS         ₹12,22,700.00    TOTAL LIAB & EQ  ₹12,22,700.00
===================================================================
```

---

## 5. Cash Flow Statement

### A. Report Identification
- **Report Name:** Statement of Cash Flows (Indirect Method)
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Cash Flow Statement
- **Route:** `reports.financial.cash_flow` (`/reports/financial/cash-flow`)
- **Controller & Method:** `App\Http\Controllers\ReportController@cashFlow`
- **Database Tables Used:** `account_transactions`, `account_ledgers`

### B. What Does This Report Mean?
Explains why cash increased or decreased during the period across three operational categories:
1. **Operating Activities:** Cash from sales collections minus supplier and expense cash payouts.
2. **Investing Activities:** Cash spent buying machinery or received from selling assets.
3. **Financing Activities:** Capital invested by owners or loan repayments.

---

## 6. Day Book

### A. Report Identification
- **Report Name:** Daily Transaction Journal (Day Book)
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Day Book
- **Route:** `reports.financial.day_book` (`/reports/financial/day-book`)
- **Database Tables:** `account_vouchers`, `account_transactions`
- **Purpose:** Chronological audit register of all accounting vouchers posted on a single day.

---

## 7. Sales Register

### A. Report Identification
- **Report Name:** Outward Sales Register (GST Compliant)
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Sales Register
- **Route:** `reports.sales.sales_register` (`/reports/sales/sales-register`)
- **Controller & Method:** `App\Http\Controllers\ReportController@salesRegister`
- **Database Tables:** `sales_invoices`, `sales_invoice_items`, `customers`

### B. Columns Displayed
- Invoice Number, Date, Customer Name, Customer GSTIN, Taxable Base Amount, CGST, SGST, IGST, Total Invoice Bill.
- **Business Use:** Direct source document for filing GSTR-1 government tax returns.

---

## 8. Purchase Register

### A. Report Identification
- **Report Name:** Inward Purchase Register (GST Input Credit)
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Purchase Register
- **Route:** `reports.purchase.purchase_register` (`/reports/purchase/purchase-register`)
- **Controller & Method:** `App\Http\Controllers\ReportController@purchaseRegister`
- **Database Tables:** `purchase_invoices`, `purchase_invoice_items`, `suppliers`
- **Business Use:** Direct source document for verifying Input Tax Credit (ITC) against GSTR-2B.

---

## 9. Stock Valuation Report

### A. Report Identification
- **Report Name:** Inventory Valuation Register
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Stock Valuation
- **Route:** `reports.inventory.valuation` (`/reports/inventory/valuation`)
- **Controller & Method:** `App\Http\Controllers\ReportController@stockValuation`
- **Database Tables:** `stock_batches`, `items`, `categories`, `godowns`

### B. Calculation Formula
$$\text{Total Stock Value} = \sum (\text{Batch Quantity} \times \text{Batch Unit Cost Rate})$$

### C. Sample Report Data
| Item Code | Item Description | Category | Godown | Current Qty | Unit Rate (₹) | Total Value (₹) |
|---|---|---|---|---|---|---|
| RM-CHI-01 | Whole Red Chilli | Raw Spices | Raw Shed 1 | 500 KG | ₹180.00 | ₹90,000.00 |
| RM-COR-01 | Coriander Seeds | Raw Spices | Raw Shed 1 | 1,000 KG | ₹95.00 | ₹95,000.00 |
| PKG-POU-100 | Masala Pouch 100g | Packaging | Central Godown | 8,000 NOS | ₹1.80 | ₹14,400.00 |
| FG-CHI-100 | Chilli Powder 100g | Finished Goods | Central Godown | 2,400 PKT | ₹22.50 | ₹54,000.00 |
| **Total** | | | | | | **₹2,53,400.00** |

---

## 10. Stock Reconciliation Report

### A. Report Identification
- **Report Name:** Physical Inventory vs. General Ledger Reconciliation
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Stock Reconciliation
- **Route:** `reports.inventory.reconciliation` (`/reports/inventory/reconciliation`)
- **Controller & Method:** `App\Http\Controllers\ReportController@stockReconciliation`
- **Database Tables:** `stock_batches`, `account_transactions`

### B. What Does This Report Mean?
Compares the total calculated valuation of all physical batches in warehouses against the running balance of the General Ledger Inventory Account (`10040`).
- **Target Variance:** Exactly ₹0.00.
- If variance exists, an unposted transaction, manual journal error, or missing stock adjustment has occurred.

---

## 11. Customer Outstanding (Receivables)

### A. Report Identification
- **Report Name:** Accounts Receivable Aging Report
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Customer Outstanding
- **Route:** `reports.sales.customer_outstanding` (`/reports/sales/customer-outstanding`)
- **Controller & Method:** `App\Http\Controllers\ReportController@customerOutstanding`
- **Database Tables:** `customers`, `sales_invoices`, `account_transactions`

### B. Information Displayed
- Customer Name, Credit Limit, Total Outstanding (₹), Current (0-15 Days), Overdue (16-30 Days), Overdue (>30 Days).
- **Business Use:** Directs daily telephone and in-person payment collections.

---

## 12. Supplier Outstanding (Payables)

### A. Report Identification
- **Report Name:** Accounts Payable Aging Report
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Supplier Outstanding
- **Route:** `reports.purchase.supplier_outstanding` (`/reports/purchase/supplier-outstanding`)
- **Controller & Method:** `App\Http\Controllers\ReportController@supplierOutstanding`
- **Database Tables:** `suppliers`, `purchase_invoices`, `account_transactions`
- **Business Use:** Cash management; ensures critical raw material suppliers are paid on time to prevent factory supply stoppages.

---

## 13. Payroll Summary Report

### A. Report Identification
- **Report Name:** Monthly Departmental Payroll Register
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Payroll Summary
- **Route:** `reports.payroll.summary` (`/reports/payroll/summary`)
- **Database Tables:** `payrolls`, `payroll_items`, `staff`, `departments`
- **Information Displayed:** Total Gross Pay, Statutory PF Deductions, ESI Deductions, Loan Deductions, Net Disbursable Wages by Department.

---

## 14. Tax / GST Summary Report

### A. Report Identification
- **Report Name:** Statutory GST Return Summary (GSTR-3B Computation)
- **Sidebar Location:** Intelligence & Reports -> ERP Reports -> Tax / GST Summary
- **Route:** `reports.tax.summary` (`/reports/tax/summary`)
- **Database Tables:** `sales_invoices`, `purchase_invoices`, `sales_returns`, `purchase_returns`

### B. Calculation Formula
$$\text{Net GST Cash Payable} = \max(0, \text{Total Output GST} - \text{Total Input Tax Credit})$$

- If Input GST exceeds Output GST, the surplus carries forward as an asset for future months.

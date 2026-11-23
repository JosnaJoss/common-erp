# Common ERP – End-User Operational Manual & Guide

## 1. Introduction & Navigating the System

Common ERP is a comprehensive, multi-company ERP designed to manage multiple businesses through a single web application interface.

### 1.1 Logging In & Switching Companies
1. Navigate to the application URL in any modern browser (Chrome, Edge, Firefox, Safari).
2. Enter your credentials:
   - **Default Administrator:** `admin@erp.com` / `admin@commonerp.test`
   - **Password:** `password`
3. **Company Switcher:** At the top right of the top navigation bar, the active company badge indicates the current operating business entity (e.g. *Apex Industrial Solutions Pvt Ltd*, *Malabar Foods & Spices*, etc.).
4. To switch company context, click the company dropdown menu and select the target company. All subsequent records, warehouses, and ledgers automatically filter to that company.

---

## 2. Master Data Management

### 2.1 Products & Items
- **Navigation:** `Inventory` → `Items / Products`
- **Creating a New Item:**
  1. Click **+ New Item**.
  2. Enter **Item Name**, **SKU / Code**, and select **Base Unit** (e.g. KG, PCS, LTR, MTR).
  3. Choose **Item Type**: `Raw Material`, `Sales Item`, `Packaging`, or `Service`.
  4. Enter standard **Purchase Price**, **Selling Price (Normal Price)**, and GST Tax Rate (0%, 5%, 12%, 18%, 28%).
  5. Specify **Reorder Level** to receive automated low-stock dashboard notifications.
  6. Click **Save Product**.

### 2.2 Customers & Suppliers
- **Customers:** `Sales` → `Customers` → **+ New Customer** (Enter GSTIN, contact details, billing/shipping address, and credit limit). A dedicated Sundry Debtors ledger is automatically linked.
- **Suppliers:** `Purchasing` → `Suppliers` → **+ New Supplier** (Enter PAN, GSTIN, payment terms, and bank details). A dedicated Sundry Creditors ledger is automatically linked.

---

## 3. Purchasing & Supplier Payables

### 3.1 Recording Purchase Invoices (Bills)
1. Navigate to `Purchasing` → `Purchase Entries (Bills)` → **+ New Purchase Entry**.
2. Select the **Supplier** and target **Warehouse / Godown**.
3. Enter the vendor's **Supplier Invoice Number** and **Invoice Date**.
4. In the items table, select the items, units, batch number (or leave default), entered quantity, and purchase rate.
5. **Landed Costs:** If the bill includes freight, packaging, or loading charges, enter them and choose the **Allocation Method** (`value`, `quantity`, or `equal`). The system automatically increases the unit landed cost of inventory batches.
6. Choose **Payment Type**:
   - `Credit`: Leaves the bill unpaid in accounts payable.
   - `Cash / Bank / UPI`: Prompts for payment ledger to record immediate payment disbursement.
7. Click **Save & Post Purchase Entry**. Inventory is inwarded immediately and a double-entry journal entry is posted.

### 3.2 Settle Supplier Payment
1. Navigate to `Purchasing` → `Supplier Payments` → **Record Payment**.
2. Select the **Supplier**, **Payment Date**, **Payment Mode** (*Bank Transfer*, *Cheque*, *Cash*), and source **Bank Ledger**.
3. Enter **Payment Amount** and **Transaction Reference**.
4. Click **Save Payment**. A Payment Journal is posted debiting the supplier and crediting the bank.

---

## 4. Sales & Customer Receivables

### 4.1 Creating Sales Invoices
1. Navigate to `Sales` → `Sales Invoices` → **+ New Sales Invoice**.
2. Select the **Customer** and the source **Warehouse / Godown**.
3. Specify **Invoice Date**, **Payment Terms**, and choose payment mode (`Credit` or immediate settlement).
4. Add items, specify the batch to pick from, entered quantity, and unit rate.
5. Review subtotal, item discounts, GST breakdown, and grand total.
6. Click **Generate Invoice**.
7. The system deducts physical batch stock, logs outward movement, updates customer receivables, and records the balanced revenue journal.
8. Click **Print / PDF** to generate a GST-compliant tax invoice.

### 4.2 Recording Customer Collections
1. Navigate to `Sales` → `Customer Collections` → **Record Collection**.
2. Select the **Customer**, **Collection Date**, **Payment Mode**, and destination **Bank / Cash Ledger**.
3. Enter **Amount Collected** and reference note.
4. Click **Record Receipt**. Accounts receivable is credited and bank balance increases.

---

## 5. Warehouse & Inventory Operations

### 5.1 Two-Step Inter-Warehouse Stock Transfers
1. Navigate to `Inventory` → `Stock Transfers` → **New Transfer**.
2. Select **Source Godown**, **Destination Godown**, items, batch numbers, and transfer quantities.
3. **Step 1 (Dispatch):** The dispatching warehouse manager clicks **Dispatch Transfer**. Stock is deducted from the source godown and marked *In-Transit*.
4. **Step 2 (Receive):** Upon physical arrival at the receiving warehouse, the receiving manager clicks **Confirm Receipt**. Stock is added to destination batches and marked *Completed*.

### 5.2 Physical Stock Adjustments (Shortage / Surplus / Damage)
1. Navigate to `Inventory` → `Stock Adjustments` → **Record Adjustment**.
2. Select **Godown**, **Item**, enter **Actual Counted Stock**, and provide the **Reason** (e.g. *Physical count variance*, *Damaged container*).
3. Click **Submit Adjustment**.
4. The system calculates the variance:
   - *Shortage:* Outwards stock and debits Scrap/Wastage expense.
   - *Surplus:* Inwards stock and credits Stock Surplus income.

---

## 6. Manufacturing & Daily Production Operations

### 6.1 Recording Daily Production
1. Navigate to `Manufacturing` → `Daily Operations` → **Record Daily Operation**.
2. Select **Department**, **Process Template** (or enter process name), and **Operation Date**.
3. **Employees Section:** Add participating workers, hours worked, completed units, and wage rates.
4. **Materials Consumed Section:** Select source godown, raw material items, batches, and consumed quantities.
5. **Production Output Section:** Select destination godown, finished good item, batch number, and produced quantity.
6. **Manufacturing Overheads Section:** Add allocated overheads (e.g. *Electricity*, *Factory Rent*, *Machine Maintenance*), basis values, and rate per unit.
7. Click **Save Operation**.
8. Click **Post Stock & Capitalize** to execute stock deduction for raw materials, finished goods stock addition, and capitalized double-entry journal voucher generation.

### 6.2 Manufacturing Reversal Workflow
If an operation was recorded with formula errors or bad quality:
1. Open the posted Daily Operation detail view (`/operations/{id}`).
2. Click the red **Reverse Operation** button in the header.
3. In the modal, enter the mandatory **Reversal Reason** (e.g. *Formula miscalculation batch scrapped*).
4. Click **Confirm Reversal**.
5. The system verifies that finished goods have not already been consumed/sold downstream, restores consumed raw materials, deducts finished goods, and posts an automatic mirror reversing journal entry.

---

## 7. Payroll & Employee Administration

### 7.1 Monthly Payroll Generation
1. Navigate to `HR & Payroll` → `Monthly Payroll`.
2. Select the **Salary Month** (e.g. `2026-11`) and click **Generate Monthly Payroll**.
3. The payroll engine aggregates daily attendance, calculates Loss of Pay (LOP) deductions, computes advance salary deductions, calculates loan EMI installments, and prepares gross-to-net pay.

### 7.2 Payroll Disbursement & Factory Labor Settlement
1. Review the generated payroll list.
2. Select employees and click **Bulk Disburse**.
3. Select **Payment Mode** (*Bank Transfer*), source **Bank Ledger**, and **Disbursement Date**.
4. Click **Confirm Payout**.
5. **Shop-Floor Labor Offset:** Any direct manufacturing wages earned by the worker during the month are debited to `Manufacturing Labor Payable`, while indirect/administrative salaries are debited to `Salaries & Staff Wages`, preventing duplicate P&L expenses.
6. Click **Payslip** on any record to view or print the employee's confidential pay slip.

---

## 8. Petty Cash Management

### 8.1 Recording Expense Vouchers
1. Navigate to `Accounts` → `Petty Cash` → **Record Voucher**.
2. Select the **Petty Cash Account**, **Category**, **Voucher Date**, **Amount**, and **Paid To**.
3. Upload supporting receipt document and click **Save Voucher**.
4. The custodial petty cash balance decreases and the expense is booked immediately.

### 8.2 Float Replenishment
1. When petty cash balance is low, click **Replenish Float**.
2. Select source **Bank Account**, enter **Replenishment Amount**, and **Date**.
3. Click **Confirm Replenishment**. Funds are transferred from the bank ledger back to the petty cash account.

---

## 9. Financial Reporting & Books Closing

### 9.1 Viewing Real-Time Financial Statements
- **Trial Balance:** `Reports` → `Financial` → `Trial Balance` (Verifies Debits = Credits across all accounts).
- **Profit & Loss:** `Reports` → `Financial` → `Profit & Loss` (Filter by start date and end date to review operating revenues, direct costs, and net margin).
- **Balance Sheet:** `Reports` → `Financial` → `Balance Sheet` (Displays Assets, Liabilities, Equity, and current-period profit integration).
- **Cash Flow Statement:** `Reports` → `Financial` → `Cash Flow Statement` (Indirect method displaying operating, investing, and financing cash flows with period delta working capital movements and reconciling opening/closing cash).
- **Inventory-to-GL Reconciliation:** `Reports` → `Inventory` → `Inventory Reconciliation` (Compares physical batch valuation against general ledger inventory asset accounts and computes variance).

### 9.2 Locking & Closing a Financial Year
1. Navigate to `Accounts` → `Financial Years`.
2. Review the list of accounting calendar years.
3. To lock an expired accounting year, click **Close Books** on that row and confirm the prompt.
4. Once closed, the system rejects any attempt to back-date purchase entries, sales invoices, journal vouchers, or manufacturing reversals into that year.
5. If an audit adjustment is required, an authorized manager may click **Reopen Books**.

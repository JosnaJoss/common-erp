import os

OUTPUT_DIR = os.path.join("documentation", "COMMON_ERP_KNOWLEDGE_BASE")
os.makedirs(OUTPUT_DIR, exist_ok=True)

print("Writing 04_COMPLETE_BUSINESS_WORKFLOWS.md...")

doc4_content = """# COMMON ERP – COMPLETE BUSINESS WORKFLOWS GUIDE
## End-to-End Operational Tracing: Procurement, Sales, Inventory, Manufacturing, HR, Accounting & Logistics

---

### Table of Contents
- [1. Procure-to-Pay (P2P) Workflow](#1-procure-to-pay-p2p-workflow)
- [2. Order-to-Cash (O2C) Workflow](#2-order-to-cash-o2c-workflow)
- [3. Inventory Lifecycle & Movement Workflow](#3-inventory-lifecycle--movement-workflow)
- [4. Manufacturing & Production Costing Workflow](#4-manufacturing--production-costing-workflow)
- [5. Human Resources & Payroll Lifecycle Workflow](#5-human-resources--payroll-lifecycle-workflow)
- [6. Financial Accounting & Period-End Closing Workflow](#6-financial-accounting--period-end-closing-workflow)
- [7. Fleet Dispatch & Route Delivery Workflow](#7-fleet-dispatch--route-delivery-workflow)
- [8. Capital Infusion & Equity Management Workflow](#8-capital-infusion--equity-management-workflow)

---

## 1. Procure-to-Pay (P2P) Workflow

The Procure-to-Pay cycle governs the complete lifecycle of requesting, ordering, receiving, verifying, and paying for raw materials, supplies, and services.

```mermaid
flowchart TD
    A["Purchase Request (Requisition)"] --> B["Purchase Order (PO) to Vendor"]
    B --> C["Goods Receipt Note (GRN) at Factory Gate"]
    C --> D["Purchase Bill / Inward Tax Invoice"]
    D --> E["Stock Inward & Accounting Voucher"]
    E --> F["Supplier Payment (Bank NEFT / Cheque)"]
```

### Step-by-Step Execution

#### Step 1: Internal Purchase Requisition
- **Screen:** Procurement -> Purchase Operations -> Purchase Requests (`/purchase/requests/create`)
- **Actor:** Factory Storekeeper or Production Supervisor
- **Action:** Storekeeper notices Coriander Seed inventory is near the 200 KG reorder level. Submits request for 1,000 KG.
- **Stock Impact:** None (Requisition is an internal notification).
- **Financial / Accounting Impact:** None.
- **Reports Affected:** Purchase Request Summary.

#### Step 2: Commercial Purchase Order (PO)
- **Screen:** Procurement -> Purchase Operations -> Purchase Orders (`/purchase/orders/create`)
- **Actor:** Procurement Manager
- **Action:** Selects approved vendor **Malwa Agricultural Spices**, selects item `Coriander Seeds A-Grade`, sets quantity `1,000 KG`, agreed rate `₹95.00/KG`, delivery deadline 5 days.
- **Stock Impact:** None (Goods have not arrived).
- **Financial / Accounting Impact:** None (Legal commitment, no balance sheet entry).
- **Reports Affected:** Pending PO Register.

#### Step 3: Physical Goods Receipt Note (GRN)
- **Screen:** Procurement -> Purchase Operations -> Goods Receipts (`/purchase/grns/create`)
- **Actor:** Warehouse Receiving Clerk
- **Action:** Truck arrives with 1,000 KG. Clerk weighs bags at weighbridge, performs moisture test, accepts 1,000 KG into `Raw Materials Shed 1`.
- **Stock Impact:** Physical stock increases by 1,000 KG.
- **Financial / Accounting Impact:** Pending invoice liability recognized if GRN clearing account is enabled.

#### Step 4: Purchase Bill / Tax Invoice Entry
- **Screen:** Procurement -> Purchase Operations -> Purchase Bills / Inward (`/purchase/entries/create`)
- **Actor:** Accounts Payable Clerk
- **Action:** Vendor's tax invoice arrives (`INV-9921`). Clerk verifies against PO and GRN. Inputs:
  - Base Amount: $1,000 \\times 95 = ₹95,000.00$
  - GST 5%: CGST 2.5% (₹2,375.00) + SGST 2.5% (₹2,375.00)
  - Total Invoice Bill: ₹99,750.00
- **Stock Impact:** Stock batch initialized with unit cost rate ₹95.00/KG.
- **Accounting Impact:**
  - **Debit:** Inventory - Raw Materials (`10040`): ₹95,000.00
  - **Debit:** Input CGST Tax Asset (`10080`): ₹2,375.00
  - **Debit:** Input SGST Tax Asset (`10081`): ₹2,375.00
  - **Credit:** Malwa Agricultural Spices (`20010`): ₹99,750.00
- **Reports Affected:** Purchase Register, Stock Valuation Report, Supplier Outstanding Report.

#### Step 5: Supplier Payment
- **Screen:** Procurement -> Purchase Operations -> Supplier Payments (`/purchase/payments/create`)
- **Actor:** Cashier / Finance Manager
- **Action:** Issues NEFT transfer from HDFC Bank to vendor for ₹99,750.00.
- **Accounting Impact:**
  - **Debit:** Malwa Agricultural Spices: ₹99,750.00
  - **Credit:** HDFC Bank Current Account: ₹99,750.00
- **Reports Affected:** Cash Flow Statement, Bank Book, Supplier Ledger.

---

## 2. Order-to-Cash (O2C) Workflow

The Order-to-Cash workflow governs selling merchandise to customers, dispatching goods, and collecting revenue.

```mermaid
flowchart TD
    A["Customer Quotation (Optional)"] --> B["Sales Order (SO) Booking"]
    B --> C["Warehouse Order Picking & QC"]
    C --> D["Sales Tax Invoice & Stock Relief"]
    D --> E["Vehicle Dispatch & Route Delivery"]
    E --> F["Customer Payment Collection"]
```

### Step-by-Step Execution

#### Step 1: Customer Sales Order
- **Screen:** Commercial & Sales -> Sales Operations -> Sales Orders (`/sales/orders/create`)
- **Actor:** Sales Representative
- **Action:** Customer **Ganesh Kirana Supermarket** orders 400 packets of Turmeric Powder 200g at ₹55.00/packet.
- **Data Entered:** Customer ID, Delivery Date, Item, Quantity (400 PKT), Unit Price (₹55.00).
- **Stock Impact:** None (Reservation only).
- **Financial / Accounting Impact:** None.

#### Step 2: Sales Tax Invoice Generation
- **Screen:** Commercial & Sales -> Sales Operations -> Sales Invoices (`/sales/invoices/create`)
- **Actor:** Billing Clerk / Accountant
- **Action:** Converts Sales Order to Sales Invoice.
  - Taxable Value: $400 \\times 55 = ₹22,000.00$
  - GST 5%: CGST 2.5% (₹550.00) + SGST 2.5% (₹550.00)
  - Total Invoice Amount: ₹23,100.00
  - Relieved Stock Cost: 400 PKT @ ₹38.00 FIFO cost = ₹15,200.00
- **Stock Impact:** Warehouse stock reduced by 400 PKT in `stock_batches` and `stock_movements`.
- **Accounting Impact:**
  - **Debit:** Ganesh Kirana (Debtor Asset): ₹23,100.00
  - **Credit:** Sales Revenue: ₹22,000.00
  - **Credit:** Output CGST Liability: ₹550.00
  - **Credit:** Output SGST Liability: ₹550.00
  - **Debit:** Cost of Goods Sold (COGS): ₹15,200.00
  - **Credit:** Inventory - Finished Goods: ₹15,200.00
- **Reports Affected:** Sales Register, Stock Valuation Report, Customer Outstanding, P&L.

#### Step 3: Dispatch & Route Delivery
- **Screen:** Commercial & Sales -> Sales Operations -> Dispatch & Route Delivery (`/sales/deliveries/create`)
- **Actor:** Dispatch Coordinator
- **Action:** Assigns invoice to Vehicle `MH-12-RN-4589` on `Pune North Route`. Driver prints trip delivery sheet.

#### Step 4: Customer Collection
- **Screen:** Commercial & Sales -> Sales Operations -> Customer Collections (`/sales/collections/create`)
- **Actor:** Accounts Cashier
- **Action:** Customer pays ₹23,100 via UPI on delivery.
- **Accounting Impact:**
  - **Debit:** HDFC Bank Current Account: ₹23,100.00
  - **Credit:** Ganesh Kirana (Debtor Asset): ₹23,100.00
- **Reports Affected:** Customer Ledger, Day Book, Cash Flow Statement.

---

## 3. Inventory Lifecycle & Movement Workflow

Manages materials from initial onboarding through inter-warehouse relocation and physical audit reconciliation.

```mermaid
flowchart TD
    A["Bulk Opening Stock (Day 1)"] --> B["Standard Stock Inward (Purchases)"]
    B --> C["Inter-Godown Stock Transfer"]
    C --> D["Production Consumption or Sales Relief"]
    D --> E["Periodic Stock Audit & Adjustment"]
```

### Key Workflow Stages

1. **Initial Onboarding:** Enter physical opening stock counts and unit valuation rates in Bulk Opening Stock (`/inventory/opening-stock`). Automatically capitalizes inventory asset against Opening Balance Equity.
2. **Relocation:** Use Godown Transfers (`/inventory/transfers/create`) to move stock from Factory to Retail Depot without changing financial value.
3. **Audit Reconciliation:** When physical stock differs from ERP books, post a Stock Adjustment (`/inventory/adjustments/create`).
   - If Shortage: Debits Inventory Variance Loss Expense, Credits Inventory Asset.
   - If Surplus: Debits Inventory Asset, Credits Inventory Variance Gain Income.

---

## 4. Manufacturing & Production Costing Workflow

Converts raw agricultural materials and packaging into finished sellable packaged goods while tracking direct labor and machine overhead costs.

```mermaid
flowchart TD
    A["Bill of Materials (BOM) Recipe Setup"] --> B["Daily Production Shift Run"]
    B --> C["Raw Materials & Packaging Deducted from Warehouse"]
    C --> D["Labor & Machine Overhead Applied"]
    D --> E["Unit Cost Calculated & Finished Goods Capitalized"]
```

### Production Costing Formula in Common ERP

$$\\text{Total Production Cost} = \\text{Direct Material Cost} + \\text{Direct Labor Cost} + \\text{Machine/Power Overhead} - \\text{Scrap Recovery}$$

$$\\text{Finished Unit Cost} = \\frac{\\text{Total Production Cost}}{\\text{Actual Output Yield Quantity}}$$

### Practical Production Example
- **Recipe:** Meat Masala 200g Pouch (BOM-MM-200G).
- **Inputs Consumed:**
  - Whole Spices: ₹42,000.00
  - Packaging Pouches (500 NOS): ₹1,500.00
  - Direct Labor (2 workers $\\times$ 4 hrs): ₹800.00
  - Machine Power (Grinder Mill 4 hrs): ₹600.00
  - Total Cost: ₹44,900.00
- **Yield Produced:** 495 Good Pouches; 5 damaged test pouches (Scrap value: ₹0).
- **Unit Cost Calculated:** $\\frac{44,900}{495} = ₹90.71/\\text{pouch}$.
- **Accounting Posting:**
  - **Debit:** Inventory - Finished Goods (`10041`): ₹44,450.00
  - **Debit:** Manufacturing Spoilage Loss (`50090`): ₹450.00
  - **Credit:** Inventory - Raw Materials (`10040`): ₹42,000.00
  - **Credit:** Inventory - Packaging (`10042`): ₹1,500.00
  - **Credit:** Direct Labor Clearing Account: ₹800.00
  - **Credit:** Factory Power Clearing Account: ₹600.00

---

## 5. Human Resources & Payroll Lifecycle Workflow

```mermaid
flowchart TD
    A["Staff Registration & Salary Setup"] --> B["Daily Muster Roll Attendance"]
    B --> C["Monthly Salary Processing Engine"]
    C --> D["Statutory Deductions (PF, ESI, Loan Recovery)"]
    D --> E["Automated Double-Entry Salary Expense Voucher"]
```

### Monthly Workflow Steps
1. **Attendance Tracking:** Record Present, Absent, Half-Day, and Overtime in Daily Attendance (`/staff/attendance`).
2. **Salary Advances:** If an employee took a loan, record it in Staff Advances (`/staff/advances/create`).
3. **Payroll Processing:** On the 30th of the month, run Payroll Processing (`/staff/payroll/create`). The ERP computes:
   $$\\text{Net Salary} = (\\text{Basic} + \\text{HRA} + \\text{Overtime}) - (\\text{PF} + \\text{ESI} + \\text{Advance Deduction})$$
4. **General Ledger Posting:** Automatically generates the balanced salary voucher debiting Salaries Expense and crediting PF Payable, ESI Payable, and Salaries Payable.

---

## 6. Financial Accounting & Period-End Closing Workflow

```mermaid
flowchart TD
    A["Operational Transactions (Sales, Purchases, Payroll)"] --> B["Daily Journal Adjustments (Depreciation, Petty Cash)"]
    B --> C["Bank Reconciliation Check"]
    C --> D["Trial Balance Arithmetic Proof"]
    D --> E["Profit & Loss Generation"]
    E --> F["Balance Sheet Finalization"]
```

### Period-End Financial Closing Steps
1. **Post Accruals & Non-Cash Entries:** Enter monthly depreciation and rent accruals via General Journal Vouchers (`/accounts/vouchers/create`).
2. **Reconcile Cash & Bank:** Reconcile bank statement with ERP bank ledger via Bank Reconciliation (`/accounts/bank-reconciliation`).
3. **Audit Arithmetic Balance:** Run the Trial Balance (`/reports/financial/trial-balance`). Ensure Total Debits exactly equal Total Credits.
4. **Inspect Profitability:** Generate Profit & Loss (`/reports/financial/profit-and-loss`) to review Gross Margin and Net Operating Income.
5. **Publish Balance Sheet:** Generate Balance Sheet (`/reports/financial/balance-sheet`). Ensure $\\text{Total Assets} = \\text{Total Liabilities} + \\text{Equity} + \\text{Current Profit}$.

---

## 7. Fleet Dispatch & Route Delivery Workflow

1. Invoices generated in Sales module with route assignments.
2. Dispatch Manager selects pending invoices in Dispatch & Route Delivery (`/sales/deliveries/create`).
3. System validates total order weight against vehicle payload capacity (e.g., maximum 1,500 KG).
4. Driver departs with printable Delivery Trip Sheet.
5. On return, driver records delivery confirmations and cash collected.

---

## 8. Capital Infusion & Equity Management Workflow

1. Business partners deposit initial investment funds into company bank account.
2. Accountant navigates to Accounts -> Capital & Equity (`/accounts/capital`).
3. Enter partner name, contribution amount, and receiving bank account.
4. Accounting impact:
   - **Debit:** HDFC Bank Current Account (Asset increases)
   - **Credit:** Partner Share Capital Account (Equity increases)
5. Instantly reflects in the Equity section of the Balance Sheet.
"""

with open(os.path.join(OUTPUT_DIR, "04_COMPLETE_BUSINESS_WORKFLOWS.md"), "w", encoding="utf-8") as f:
    f.write(doc4_content)

print("04_COMPLETE_BUSINESS_WORKFLOWS.md written successfully.")

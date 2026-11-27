import os

OUTPUT_DIR = os.path.join("documentation", "COMMON_ERP_KNOWLEDGE_BASE")
os.makedirs(OUTPUT_DIR, exist_ok=True)

# ---------------------------------------------------------
# DOCUMENT 07: MODULE DEPENDENCY AND DATA FLOW
# ---------------------------------------------------------
print("Writing 07_MODULE_DEPENDENCY_AND_DATA_FLOW.md...")

doc7_content = """# COMMON ERP – MODULE DEPENDENCY & DATA FLOW STUDY
## Technical & Functional Architecture: Inter-Module Linkages, Data Flowcharts & Multi-Tenant Scoping

---

### Navigation Index
- [1. Comprehensive Module Dependency Matrix](#1-comprehensive-module-dependency-matrix)
- [2. End-to-End Visual Data Flowcharts](#2-end-to-end-visual-data-flowcharts)
- [3. Multi-Tenant Data Ownership & Scoping Model](#3-multi-tenant-data-ownership--scoping-model)
- [4. Industry Configuration Matrix (Common vs. Vertical Modules)](#4-industry-configuration-matrix-common-vs-vertical-modules)

---

## 1. Comprehensive Module Dependency Matrix

Every module in Common ERP connects to upstream prerequisites and downstream consumers:

| Functional Module | Prerequisite Dependencies (Depends On) | Downstream Consumers (Used By) | Database Tables Involved |
|---|---|---|---|
| **Enterprise / Companies** | System Root | All Modules | `companies`, `branches`, `company_settings`, `users` |
| **Master: UOM** | Company | Items, BOM, Inventory, Purchasing, Sales | `units`, `unit_conversions` |
| **Master: Categories** | Company | Items, Inventory Valuation, Procurement | `categories` |
| **Master: Products** | Categories, Company | Items, BOM Recipes, Marketing Reports | `products` |
| **Master: Items** | Products, Categories, UOM, Godowns | Inventory, BOM, Procurement, Sales, Accounting | `items`, `price_levels`, `stock_batches` |
| **Master: Godowns** | Branches, Company | Inventory, Stock Transfers, GRN, Dispatch | `godowns` |
| **Master: Departments** | Company | Staff, Payroll, Daily Operations Costing | `departments` |
| **Master: Vehicles & Routes** | Company | Customers, Sales Dispatch, Delivery Trip Sheets | `vehicles`, `routes` |
| **Stakeholders: Suppliers** | Company, Chart of Accounts | Purchase Orders, GRN, Inward Bills, Payments | `suppliers`, `account_ledgers` |
| **Stakeholders: Customers** | Routes, Price Levels, Chart of Accounts | Sales Orders, Invoices, Delivery, Collections | `customers`, `account_ledgers` |
| **Staff & HR** | Departments, Company | Attendance, Advances, Payroll Processing | `staff`, `designations`, `shifts` |
| **Daily Attendance** | Staff | Payroll Processing, Labor Costing | `staff_attendances` |
| **Payroll Processing** | Staff, Attendance, Staff Advances, COA | Bank Disbursal, General Ledger Salary Expenses | `payrolls`, `payroll_items`, `account_vouchers` |
| **Manufacturing: BOM** | Items (Raw & Finished), UOM | Daily Operations Entry, Production Scheduling | `bill_of_materials`, `bom_items` |
| **Manufacturing: Daily Ops** | BOM, Items, Godowns, Machines, COA | Finished Goods Inventory, Spoilage, Ledger Postings | `daily_operations`, `stock_batches`, `account_transactions` |
| **Inventory: Batches** | Items, Godowns | FIFO Sales Relief, Stock Valuation, Adjustments | `stock_batches`, `stock_movements` |
| **Inventory: Transfers** | Godowns, Batches | Warehouse Relocation, In-transit Tracking | `stock_transfers`, `stock_transfer_items` |
| **Inventory: Adjustments** | Godowns, Batches, COA | Physical Audit Variance, P&L Gain/Loss Posting | `stock_adjustments`, `account_transactions` |
| **Procurement: Inward Bills** | Suppliers, Items, Godowns, COA | Stock Addition, Input GST Credit, Creditor Payable | `purchase_invoices`, `account_transactions` |
| **Commercial: Sales Bills** | Customers, Batches, Godowns, COA | Stock Relief, Revenue, Output GST, Debtors | `sales_invoices`, `account_transactions` |
| **Commercial: Collections** | Customers, Bank/Cash Accounts | Debtors Relief, Bank Balance Inflow | `account_vouchers`, `account_transactions` |
| **Finance: Chart of Accounts** | Company | All Financial Transactions, Balance Sheet, P&L | `account_groups`, `account_ledgers` |
| **Reports: Financial** | Account Transactions, Vouchers | Executive Management, Statutory Tax Authorities | Aggregation across `account_transactions` |

---

## 2. End-to-End Visual Data Flowcharts

### A. Procurement to Payment (P2P) Data Flow
```mermaid
sequenceDiagram
    autonumber
    actor Store as Storekeeper
    actor Buyer as Purchasing Dept
    actor Vendor as External Supplier
    actor Accounts as Accounts Dept
    
    Store->>Buyer: Internal Purchase Request
    Buyer->>Vendor: Purchase Order (PO)
    Vendor->>Store: Physical Goods Delivery
    Store->>Accounts: Goods Receipt Note (GRN)
    Vendor->>Accounts: Vendor Tax Invoice
    Accounts->>Accounts: Post Purchase Bill (Stock + Input GST + Creditor)
    Accounts->>Vendor: Bank NEFT Payment (Clears Creditor)
```

### B. Order to Cash (O2C) Data Flow
```mermaid
sequenceDiagram
    autonumber
    actor Client as Customer
    actor Sales as Sales Exec
    actor WH as Warehouse
    actor Billing as Accounts Dept
    
    Client->>Sales: Sales Order Booking
    Sales->>WH: Pick & Pack Order
    Billing->>Client: Sales Tax Invoice
    WH->>Client: Dispatch via Route Vehicle
    Billing->>Billing: Stock Relief (FIFO) + Output GST + Debtors Booking
    Client->>Billing: Customer Payment (UPI / Cheque)
    Billing->>Billing: Post Collection Voucher (Clears Debtors)
```

### C. Manufacturing & Cost Capitalization Flow
```mermaid
flowchart LR
    A["Raw Materials Godown"] -->|Issued Materials| C["Daily Operations Run"]
    B["Direct Labor & Power"] -->|Cost Allocation| C
    C -->|Capitalized Yield| D["Finished Goods Godown"]
    C -->|Spoilage / Loss| E["P&L Loss Expense"]
    D -->|FIFO Relief| F["Customer Sales Invoice"]
```

---

## 3. Multi-Tenant Data Ownership & Scoping Model

Common ERP utilizes a multi-tenant shared-database architecture with programmatic scoping.

```mermaid
flowchart TD
    User["Authenticated User Session"] --> Switcher["CompanyContextService"]
    Switcher --> Context["Active Company ID (Session: active_company_id)"]
    Context --> Query["Eloquent Global Scopes & Model Observers"]
    Query --> DB[("MySQL Database Tables: WHERE company_id = ?")]
```

### Key Security & Integrity Mechanisms
1. **`CompanyContextService::getActiveCompanyId()`**: Returns the currently active enterprise ID stored in session.
2. **Database Scoping:** Every business table (items, ledgers, vouchers, batches, customers) contains an indexed `company_id` foreign key.
3. **Cross-Tenant Prevention:** Users without multi-company privileges cannot switch companies or query records belonging to sister concerns.

---

## 4. Industry Configuration Matrix (Common vs. Vertical Modules)

Through **Administration -> Module Activation**, the system adapts dynamically to different industries:

| Module Name | Horizontal (All Industries) | Manufacturing (Food / Chem) | Retail Trading (Fashion / POS) | Distribution & Logistics |
|---|---|---|---|---|
| **Company & User Admin** | Required | Required | Required | Required |
| **Master Data (Items/UOM)** | Required | Required | Required | Required |
| **Inventory & Stock Batches** | Required | Required | Required | Required |
| **Procurement (Purchases)** | Required | Required | Required | Required |
| **Commercial Sales** | Required | Required | Required | Required |
| **Double-Entry Accounting** | Required | Required | Required | Required |
| **Manufacturing & BOM** | Optional | **Enabled** | Disabled | Disabled |
| **Machine Maintenance** | Optional | **Enabled** | Disabled | Disabled |
| **Vehicle Fleet & Routes** | Optional | Optional | Disabled | **Enabled** |
| **Staff & Payroll** | Optional | **Enabled** | Optional | **Enabled** |
"""

with open(os.path.join(OUTPUT_DIR, "07_MODULE_DEPENDENCY_AND_DATA_FLOW.md"), "w", encoding="utf-8") as f:
    f.write(doc7_content)

print("07_MODULE_DEPENDENCY_AND_DATA_FLOW.md written successfully.")

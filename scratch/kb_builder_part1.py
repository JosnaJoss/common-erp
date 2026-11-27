import os

OUTPUT_DIR = os.path.join("documentation", "COMMON_ERP_KNOWLEDGE_BASE")
os.makedirs(OUTPUT_DIR, exist_ok=True)

# ---------------------------------------------------------
# DOCUMENT 01: COMPLETE SIDEBAR AND SUBMENU GUIDE
# ---------------------------------------------------------
print("Writing 01_COMPLETE_SIDEBAR_AND_SUBMENU_GUIDE.md...")

doc1_content = """# COMMON ERP – COMPLETE SIDEBAR & SUBMENU KNOWLEDGE GUIDE
## Executive Business & Operational Reference for Every Navigation Menu and Submenu

---

### Navigation Index
- [1. Core Module](#1-core-module)
- [2. Enterprise Setup Module](#2-enterprise-setup-module)
- [3. Master Data Registry](#3-master-data-registry)
- [4. Stakeholders Module](#4-stakeholders-module)
- [5. Staff & Human Resources](#5-staff--human-resources)
- [6. Operations & Manufacturing](#6-operations--manufacturing)
- [7. Inventory & Godown](#7-inventory--godown)
- [8. Procurement (Purchases)](#8-procurement-purchases)
- [9. Commercial & Sales](#9-commercial--sales)
- [10. Finance & Bookkeeping](#10-finance--bookkeeping)
- [11. Intelligence & Reports](#11-intelligence--reports)
- [12. Administration & Security](#12-administration--security)

---

## 1. Core Module

### 1.1 Dashboard
- **Route:** `dashboard` (`/dashboard`)
- **Controller & Method:** `DashboardController@index`
- **Blade View:** `resources/views/dashboard.blade.php`
- **Database Tables Involved:** `sales_invoices`, `purchase_invoices`, `stock_batches`, `account_transactions`, `staff`, `companies`
- **Module Dependencies:** Depends on `sales`, `purchase`, `inventory`, `accounts`, `staff_hr`. Used by business owners, branch managers, and executives.
- **Implementation Status:** **Implemented** (verified live with multi-company and branch context filtering).

#### What does it mean?
The Dashboard is the operational control center or "cockpit" of the ERP. Just like an airplane pilot looks at altimeter, fuel, and speed gauges on a single cockpit screen, an executive or business owner looks at the Dashboard to instantly gauge the health of the business without running detailed reports.

#### Why does a business need this?
A business owner cannot spend 30 minutes every morning opening 10 different ledger screens to know if the company has cash, if sales are growing, or if inventory is running out. The Dashboard surfaces immediate key performance indicators (KPIs), operational alerts, and daily summaries in one consolidated view.

#### Business Activity
- Reviewing today's gross sales vs. today's purchases.
- Checking critical low-stock items requiring urgent purchase orders.
- Viewing bank and cash balances to ensure checks won't bounce.
- Monitoring pending approvals, pending dispatches, and recent activities.

#### What information is entered?
None. The Dashboard is strictly a read-only analytical view. It accepts filter parameters (e.g., date range, branch selection dropdown).

#### What information is displayed?
1. **Total Sales Today / This Month:** Formatted in ₹ INR.
2. **Total Purchases Today / This Month:** Total procurement expenses.
3. **Cash & Bank Balance:** Current liquid funds available across accounts.
4. **Receivables (Customer Outstanding):** Money customers owe the business.
5. **Payables (Supplier Outstanding):** Money the business owes to vendors.
6. **Low Stock Alerts Table:** Items where current stock is below reorder level.
7. **Recent Transactions Feed:** Latest 10 sales bills, purchase bills, and stock movements.

#### Business Process Sequence
- **Preceding Process:** System setup, historical transactions, daily operations postings.
- **Succeeding Process:** Clicking on alert widgets to navigate to Purchase Orders, Sales Invoices, or Stock Transfers.

---

## 2. Enterprise Setup Module

### 2.1 Companies & Branches
- **Route:** `companies.index`, `companies.create`, `companies.edit` (`/companies`)
- **Controller & Method:** `CompanyController@index`, `create`, `store`, `edit`, `update`
- **Blade Views:** `resources/views/companies/index.blade.php`, `create.blade.php`, `edit.blade.php`
- **Database Tables Involved:** `companies`, `branches`, `company_settings`, `users`
- **Module Dependencies:** Foundational root of the entire ERP. Every single transaction, item, ledger, and customer references `company_id`.
- **Implementation Status:** **Implemented** (Tenant isolation enforced via `CompanyContextService`).

#### What does it mean?
Enterprise Setup defines the legal entities (Companies) and physical operating locations (Branches / Divisions) that operate under this ERP instance. Common ERP is a true multi-tenant, multi-company architecture where a single ERP installation can host distinct business legal entities (e.g., a garment retail boutique, a spice manufacturing unit, and a transport agency) with strict financial and inventory isolation.

#### Why does a business need this?
Entrepreneurs frequently operate multiple sister concerns, distinct GST registrations, or retail branches. A centralized ERP eliminates duplicate servers while maintaining strict separation of legal books, GST returns, and stock balances.

#### Business Activity
- Creating a new legal company profile.
- Configuring company tax numbers (GSTIN, PAN, CIN).
- Establishing branch offices, regional warehouses, or retail showrooms.
- Switching between active company contexts using the top-bar switcher.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Company Name | Registered legal trade name | Legal entity name as per GST/PAN | Royal Spices Private Limited |
| Company Code | Short unique system identifier | 3 to 6 uppercase letters | RSPL |
| Business Type | Industry operating vertical | Pre-configured industry model | Manufacturing |
| Currency | Functional currency symbol/code | Primary currency used for financial books | INR (₹) |
| GSTIN | 15-digit Goods & Services Tax ID | Government issued tax registration | 27AAACR1234F1Z5 |
| PAN | Permanent Account Number | 10-digit income tax number | AAACR1234F |
| Registered Address | Official legal head office | Full physical address | Plot 42, MIDC Industrial Area, Pune 411018 |
| Default Warehouse | Primary storage godown | Linked default warehouse | Main Factory Godown |

#### What happens after saving?
1. Record inserted into `companies`.
2. Automatic provisioning of standard Chart of Accounts (Assets, Liabilities, Equity, Revenue, Expense) scoped to `company_id`.
3. Default primary branch created in `branches`.
4. Tenant scope initialized; users assigned to this company can switch to it.

---

## 3. Master Data Registry

Master data represents the non-transactional core business entities that must exist before any transaction (purchase, sale, stock entry) can take place.

### 3.1 Items & Price Levels
- **Route:** `master.items.index`, `master.items.create`, `master.items.edit` (`/master/items`)
- **Controller & Method:** `ItemController@index`, `create`, `store`, `edit`, `update`
- **Blade Views:** `resources/views/master/items/index.blade.php`, `create.blade.php`, `edit.blade.php`
- **Database Tables Involved:** `items`, `products`, `categories`, `units`, `price_levels`, `stock_batches`
- **Module Dependencies:** Used by Procurement, Inventory, Manufacturing, Commercial Sales, and Reports.
- **Implementation Status:** **Implemented** (Product link, multi-UOM, and image upload verified).

#### What does it mean?
The Item Master is the central catalog of every physical stock item, raw material, consumable, packaging supply, or finished product bought, manufactured, stocked, or sold by the business.

#### Why does a business need this?
Without a standardized item master, employees would enter arbitrary item names (e.g., "Chilli Pwd", "Mirchi", "Red Chilly"), making stock tracking, FIFO valuation, price control, and GST reporting impossible.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Product | Master product lineage | Parent commercial product group | Premium Masala Range |
| Item Name | Full descriptive name | Specific name of the SKU | Red Chilli Powder 500g Pouch |
| Item Code / SKU | Unique item identifier | Internal barcode or stock keeping unit | RCH-500G |
| Category | Product classification | Major group from Category Master | Spices & Seasonings |
| Unit of Measure | Primary unit of stocking | Base unit from UOM Master | PKT (Packet) |
| Purchase Price | Standard vendor cost | Default buying cost before tax | ₹140.00 |
| Selling Price | Standard customer price | Default wholesale/retail selling price | ₹185.00 |
| Tax / HSN Code | Harmonized System of Nomenclature | 6-8 digit GST HSN code | 0910 |
| GST Rate (%) | Applicable tax rate | Statutory GST percentage | 5% |
| Reorder Level | Minimum safe inventory threshold | Quantity that triggers restock warning | 50 PKT |
| Opening Stock | Initial quantity upon ERP setup | Stock in godown at go-live date | 200 PKT |

#### What happens after saving?
1. Record saved in `items` table with `company_id`.
2. If `opening_stock > 0`, an automated batch is created in `stock_batches`, a movement is logged in `stock_movements`, and a balancing journal voucher is posted in `account_transactions` (Debit: Inventory Asset, Credit: Opening Balance Equity).

---

### 3.2 Categories
- **Route:** `master.categories.index`, `master.categories.create`, `master.categories.edit` (`/master/categories`)
- **Controller & Method:** `CategoryController@index`, `store`, `update`, `destroy`
- **Blade Views:** `resources/views/master/categories/index.blade.php`
- **Database Tables Involved:** `categories`, `items`
- **Module Dependencies:** Pre-requisite for Item Master and Inventory Valuation reports.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Categories are logical hierarchical groupings used to organize items into broad families (e.g., Raw Spices, Packing Materials, Finished Goods, Office Stationery).

#### Why does a business need this?
Allows managerial reporting by category. A business owner can ask: "What was our total spice consumption vs. total packaging material cost this month?" Category filtering enables this aggregation instantly.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Category Name | Descriptive group name | Clear family title | Packaging Materials |
| Category Code | Short code | 3-5 alphanumeric identifier | PKG-MAT |
| Parent Category | Hierarchical parent | Optional parent category for sub-categories | None (Root) or Packaging |
| Description | Explanatory note | Internal notes regarding items in this group | Pouches, corrugated boxes, labels |

---

### 3.3 Products
- **Route:** `master.products.index`, `master.products.create`, `master.products.edit` (`/master/products`)
- **Controller & Method:** `ProductController@index`, `store`, `update`, `destroy`
- **Blade Views:** `resources/views/master/products/index.blade.php`
- **Database Tables Involved:** `products`, `items`, `bill_of_materials`
- **Module Dependencies:** Connects high-level brand products to specific packaged SKUs (Items) and Manufacturing BOMs.
- **Implementation Status:** **Implemented**.

#### What does it mean?
A Product represents the umbrella brand or generic merchandise concept (e.g., "Turmeric Powder"), whereas Items represent the sellable SKUs (e.g., "Turmeric Powder 100g", "Turmeric Powder 500g", "Turmeric Powder 1kg Bulk Sack").

#### Why does a business need this?
Enables brand-level performance tracking and centralized Bill of Materials (BOM) linkage. Marketing and management track sales by product family, while warehouse workers manage specific item SKUs.

---

### 3.4 Units of Measure (UOM)
- **Route:** `master.units.index`, `master.units.create`, `master.units.edit` (`/master/units`)
- **Controller & Method:** `UnitController@index`, `store`, `update`, `destroy`
- **Blade Views:** `resources/views/master/units/index.blade.php`
- **Database Tables Involved:** `units`, `unit_conversions`, `items`
- **Module Dependencies:** Vital for Inventory, BOM calculations, Purchase, and Sales.
- **Implementation Status:** **Implemented** (Multi-unit conversion engine verified).

#### What does it mean?
UOM defines the standard physical dimensions used to measure materials: weight (KG, G, TON), volume (LTR, ML), length (MTR), and discrete counts (NOS, PCS, BOX, DOZEN).

#### Why does a business need this?
Businesses buy in bulk (e.g., 50 KG bags or 1000 Liter drums) but consume or sell in small units (e.g., 500 Gram pouches or 250 ML bottles). The UOM module with unit conversions prevents calculation errors and inventory discrepancy.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Unit Name | Full formal name | Standard metric or count unit | Kilogram |
| Unit Code / Symbol | Short symbol | 2-4 letter abbreviation | KG |
| Unit Type | Measurement dimension | Weight, Volume, Length, Count | Weight |
| Allow Decimals | Decimal precision allowed | Yes for weight/volume, No for discrete pieces | Yes (Up to 3 decimals: 1.250 KG) |

---

### 3.5 Godowns / Warehouses
- **Route:** `master.godowns.index`, `master.godowns.create`, `master.godowns.edit` (`/master/godowns`)
- **Controller & Method:** `GodownController@index`, `store`, `update`, `destroy`
- **Blade Views:** `resources/views/master/godowns/index.blade.php`
- **Database Tables Involved:** `godowns`, `stock_batches`, `stock_movements`, `stock_transfers`
- **Module Dependencies:** Used by Procurement (Goods Receipts), Manufacturing, Stock Transfers, and Sales Dispatch.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Godowns (warehouses) are physical or logical storage locations where goods reside: Raw Material Godown, Processing Floor, Finished Goods Godown, Quarantine/Damaged Storage, or Showroom Shelf.

#### Why does a business need this?
If a company has 5,000 KG of flour, management must know exactly where it is: 4,000 KG in the Central Godown, 800 KG on the Factory Floor, and 200 KG in the Retail Store.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Godown Name | Location title | Descriptive physical title | Central Finished Goods Warehouse |
| Godown Code | Short identifier | Code used in stock transfer slips | WH-FG-01 |
| Branch | Associated branch | Branch entity that controls the warehouse | Main Pune Branch |
| Location / Address | Physical address | Street address or shed number | Shed 4, Industrial Park, Pune |
| Contact Person | Godown in-charge | Warehouse supervisor name | Ramesh Patil |
| Phone Number | Contact number | Mobile number of supervisor | +91 98765 43210 |

---

### 3.6 Departments
- **Route:** `master.departments.index`, `master.departments.create`, `master.departments.edit` (`/master/departments`)
- **Controller & Method:** `DepartmentController@index`, `store`, `update`, `destroy`
- **Blade Views:** `resources/views/master/departments/index.blade.php`
- **Database Tables Involved:** `departments`, `staff`, `daily_operations`
- **Module Dependencies:** Used by Staff/HR, Payroll, and Manufacturing operations.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Departments represent functional divisions within an organization: Procurement, Grinding & Blending, Packaging, Quality Control, Sales, Accounts, and Human Resources.

#### Why does a business need this?
Organizes employees into logical reporting units, enables departmental cost allocation, and tracks labor productivity per department.

---

### 3.7 Vehicles & Drivers
- **Route:** `master.vehicles.index`, `master.vehicles.create`, `master.vehicles.edit` (`/master/vehicles`)
- **Controller & Method:** `VehicleController@index`, `store`, `update`, `destroy`
- **Blade Views:** `resources/views/master/vehicles/index.blade.php`
- **Database Tables Involved:** `vehicles`, `vehicle_delivery_logs`, `sales_deliveries`
- **Module Dependencies:** Used by Sales Dispatch, Logistics, Fleet Maintenance, and Route Delivery.
- **Implementation Status:** **Implemented**.

#### What does it mean?
The Vehicle Master manages the company-owned or contracted fleet of delivery vans, trucks, and three-wheelers used to deliver customer goods or collect raw materials.

#### Why does a business need this?
Prevents delivery theft, tracks vehicle fuel/maintenance expenses, ensures vehicle fitness/insurance renewals, and optimizes route deliveries.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Vehicle Number | Legal registration number | State RTO registration plate | MH-12-RN-4589 |
| Vehicle Type | Class of vehicle | Van, Truck, Three-Wheeler, Tempo | 2.5 Ton Pickup Truck |
| Driver Name | Assigned primary driver | Name of the authorized driver | Suresh Shinde |
| Driver License No | Driving license number | Valid commercial RTO license | MH1220180045671 |
| Capacity (Kg) | Maximum payload limit | Safe load weight in KG | 2500 KG |
| Insurance Expiry | Expiry date of policy | Date for reminder notifications | 2027-03-31 |

---

### 3.8 Territory & Routes
- **Route:** `master.routes.index`, `master.routes.create`, `master.routes.edit` (`/master/routes`)
- **Controller & Method:** `RouteController@index`, `store`, `update`, `destroy`
- **Blade Views:** `resources/views/master/routes/index.blade.php`
- **Database Tables Involved:** `routes`, `customers`, `sales_deliveries`
- **Module Dependencies:** Used by Commercial Sales, Customer Master, and Fleet Delivery.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Routes represent fixed geographical delivery paths (e.g., "Pune North - Pimpri Chinchwad Route", "Mumbai South Route") where customers are located.

#### Why does a business need this?
Distributors deliver to 20-30 retail shops in a single vehicle trip. Grouping customers by Route enables batch dispatching, reducing fuel cost and transit delays.

---

## 4. Stakeholders Module

### 4.1 Suppliers
- **Route:** `suppliers.index`, `suppliers.create`, `suppliers.edit`, `suppliers.show` (`/suppliers`)
- **Controller & Method:** `SupplierController@index`, `create`, `store`, `edit`, `update`, `show`
- **Blade Views:** `resources/views/suppliers/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
- **Database Tables Involved:** `suppliers`, `purchase_invoices`, `purchase_orders`, `account_ledgers`, `account_transactions`
- **Module Dependencies:** Feeds Procurement, Purchase Invoices, Supplier Payments, and Accounts Payable (Creditors).
- **Implementation Status:** **Implemented** (automatic ledger synchronization verified).

#### What does it mean?
Suppliers (also known as Vendors or Creditors) are external businesses or farmers from whom your company purchases raw materials, packaging, tools, services, or finished resale goods.

#### Why does a business need this?
Tracks vendor addresses, payment terms, GST compliance status, contact persons, and running outstanding balances.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Supplier Name | Registered legal trading name | Vendor trade name | Bharat Agri Spices Farms |
| Contact Person | Representative name | Sales representative or manager | Anand Verma |
| Phone Number | Primary telephone | Official contact number | +91 94220 11223 |
| Email Address | Official email for POs | Email for purchase orders and notices | sales@bharatagri.com |
| GSTIN | 15-character GST number | Supplier's tax registration | 27AAEFB8890K1Z2 |
| PAN | Income tax identifier | 10-digit PAN | AAEFB8890K |
| Address | Physical factory/office address | Complete postal address | G-14, APMC Market Yard, Pune 411037 |
| Credit Period | Payment days allowed | Agreed credit period (Days) | 30 Days |
| Opening Balance | Historical balance owed | Balance at ERP migration date | ₹45,000.00 Cr |

#### What happens after saving?
1. Vendor saved in `suppliers`.
2. Automatic creation of a dedicated Subsidiary Ledger in `account_ledgers` under the group **Current Liabilities -> Sundry Creditors**.
3. If an opening balance is provided, a balancing credit journal is recorded.

---

### 4.2 Customers & Pricing
- **Route:** `customers.index`, `customers.create`, `customers.edit`, `customers.show` (`/customers`)
- **Controller & Method:** `CustomerController@index`, `create`, `store`, `edit`, `update`, `show`
- **Blade Views:** `resources/views/customers/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
- **Database Tables Involved:** `customers`, `price_levels`, `sales_invoices`, `account_ledgers`, `account_transactions`, `routes`
- **Module Dependencies:** Feeds Sales Orders, Invoices, Deliveries, Customer Collections, and Accounts Receivable (Debtors).
- **Implementation Status:** **Implemented**.

#### What does it mean?
Customers (also known as Debtors or Clients) are the individuals, retail stores, supermarkets, or institutional buyers who purchase your products or services.

#### Why does a business need this?
Enables tracking of customer contact details, tax numbers, credit limits, assigned delivery routes, assigned wholesale price tier, and running accounts receivable balances.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Customer Name | Retailer/Buyer commercial name | Shop or business name | Ganesh Kirana & Supermarket |
| Customer Code | Unique identification code | Internal customer code | CUST-0082 |
| Contact Person | Shop owner or buyer name | Person responsible for payments | Ganesh Shelke |
| Phone / Mobile | Contact phone | WhatsApp/Mobile for invoices | +91 98221 55667 |
| GSTIN | 15-character GST number | Buyer's GST number (or blank if un-registered) | 27ABCFG7788P1Z8 |
| Route | Assigned delivery territory | Linked route for logistics | Pune North Route |
| Price Level | Commercial tier | Retail, Wholesale, Super-Stockist | Wholesale Tier A |
| Credit Limit | Maximum unpaid amount allowed | Financial ceiling for credit sales | ₹1,00,000.00 |
| Credit Days | Allowed payment window | Days before invoices become overdue | 15 Days |
| Opening Balance | Uncollected prior balance | Balance at ERP start date | ₹12,500.00 Dr |

#### What happens after saving?
1. Customer record saved in `customers`.
2. Dedicated subsidiary ledger created in `account_ledgers` under **Current Assets -> Sundry Debtors**.
3. Ready for sales invoicing and payment receipts.

---

## 5. Staff & Human Resources

### 5.1 Staff Directory
- **Route:** `staff.index`, `staff.create`, `staff.edit`, `staff.show` (`/staff`)
- **Controller & Method:** `StaffController@index`, `create`, `store`, `edit`, `update`, `show`
- **Blade Views:** `resources/views/staff/index.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`
- **Database Tables Involved:** `staff`, `departments`, `designations`, `shifts`, `payroll_records`
- **Module Dependencies:** Prerequisite for Attendance, Payroll, and Machine Operator assignment.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Maintains complete employee employment records for factory workers, operators, drivers, sales representatives, accountants, and executives.

#### Why does a business need this?
Centralizes employee contracts, bank account details for salary transfers, statutory PF/ESI numbers, basic wage rates, and shift schedules.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Employee Code | Unique employee ID | Organization badge number | EMP-0104 |
| Full Name | Legal name | Employee passport/Aadhaar name | Prakash Baburao Jadhav |
| Department | Assigned department | Selected from Department Master | Grinding & Blending |
| Designation | Job title | Official role title | Machine Operator |
| Employment Type | Contract class | Permanent, Contract, Daily Wage | Permanent |
| Basic Salary | Fixed monthly base wage | Contracted basic wage in ₹ | ₹18,000.00 |
| HRA / Allowances | Additional monthly allowances | Housing/Transport/Special allowances | ₹4,500.00 |
| Bank Account No | Salary deposit account | Verified employee bank account | 001210200034567 |
| Bank IFSC Code | Bank branch routing code | RBI bank branch code | SBIN0001234 |
| Date of Joining | First working day | Formal hire date | 2024-01-15 |

---

### 5.2 Daily Attendance
- **Route:** `staff.attendance.index`, `staff.attendance.store` (`/staff/attendance`)
- **Controller & Method:** `StaffAttendanceController@index`, `store`
- **Blade Views:** `resources/views/staff/attendance/index.blade.php`
- **Database Tables Involved:** `staff_attendances`, `staff`
- **Module Dependencies:** Directly calculates payable days during monthly Payroll Processing.
- **Implementation Status:** **Implemented**.

#### What does it mean?
The digital muster roll where daily presence (Present, Absent, Half Day, Paid Leave, Overtime Hours) is recorded for every employee.

#### Why does a business need this?
Accurate salary computation requires exact records of days worked. Missing or incorrect attendance causes payroll disputes, labor law violations, or overpayment of wages.

---

### 5.3 Payroll Processing
- **Route:** `staff.payroll.index`, `staff.payroll.generate`, `staff.payroll.process` (`/staff/payroll`)
- **Controller & Method:** `StaffPayrollController@index`, `generate`, `process`
- **Blade Views:** `resources/views/staff/payroll/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables Involved:** `payrolls`, `payroll_items`, `staff`, `account_vouchers`, `account_transactions`
- **Module Dependencies:** Connects HR, Bank Disbursal, and Financial Bookkeeping.
- **Implementation Status:** **Implemented** (Integrated double-entry voucher generation verified).

#### What does it mean?
The monthly salary calculation engine that processes gross earnings (Basic + HRA + Overtime), calculates statutory deductions (PF, ESI, Professional Tax, Salary Advances), and computes Net Take-Home Pay.

#### Why does a business need this?
Automates salary slips, prevents manual spreadsheet calculation errors, and automatically records the company's monthly salary expense and liability in the General Ledger.

#### What happens after saving / processing?
1. Payroll record generated for the selected month/year.
2. Balanced double-entry voucher posted in `account_vouchers` and `account_transactions`:
   - **Debit:** Salary Expense Account (P&L Expense increases)
   - **Credit:** PF Payable Account (Liability)
   - **Credit:** ESI Payable Account (Liability)
   - **Credit:** Salaries Payable / Bank Clearing Account (Liability)

---

### 5.4 Advances & Deductions
- **Route:** `staff.advances.index`, `staff.advances.create`, `staff.advances.store` (`/staff/advances`)
- **Controller & Method:** `StaffAdvanceController@index`, `create`, `store`
- **Blade Views:** `resources/views/staff/advances/index.blade.php`
- **Database Tables Involved:** `staff_advances`, `staff`, `account_transactions`
- **Module Dependencies:** Deducted automatically during monthly Payroll Processing.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Tracks short-term salary loans, festive advances, or medical loans granted to employees, along with monthly installment recovery schedules.

---

## 6. Operations & Manufacturing

### 6.1 Daily Operations Entry
- **Route:** `operations.daily.index`, `operations.daily.create`, `operations.daily.store`, `operations.daily.show` (`/operations/daily`)
- **Controller & Method:** `DailyOperationController@index`, `create`, `store`, `show`
- **Blade Views:** `resources/views/operations/daily/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables Involved:** `daily_operations`, `daily_operation_items`, `stock_batches`, `stock_movements`, `account_transactions`
- **Module Dependencies:** Pulls raw materials from Inventory, uses BOM standards, outputs Finished Goods, and logs manufacturing cost journal entries.
- **Implementation Status:** **Implemented** (Multi-component cost capitalization and batch creation verified).

#### What does it mean?
The daily shop-floor manufacturing production voucher. It records the actual physical production run: how much raw material was consumed, which machine ran for how many hours, what labor was used, what wastage occurred, and what quantity of finished goods was produced.

#### Why does a business need this?
In a manufacturing business, finished goods do not appear out of thin air. When you manufacture 500 packets of Chicken Masala, raw spices and plastic pouches must be deducted from warehouse inventory, and finished goods must be added into stock at the true calculated production cost.

#### What data should I enter?
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Operation Date | Production run date | Today's date | 2026-11-23 |
| Production Shift | Working shift | Morning, Evening, Night | General Shift |
| Product / BOM | Finished product being produced | Selected BOM template | Chicken Masala 100g BOM |
| Target Quantity | Planned units | Number of sellable units | 1,000 PKT |
| Machine Used | Machinery involved | Grinder Mill #2 | Grinder Mill #2 |
| Machine Hours | Duration of machine run | Operating run time in hours | 4.5 Hours |
| Labor Headcount | Workers involved | Direct factory workers present | 3 Operators |
| Raw Material Consumed | Actual inputs issued | List of item codes, batches, and quantities | Chilli (150 KG), Coriander (250 KG), Pouches (1000) |
| Actual Output | Good units produced | Passed inspection count | 995 PKT |
| Scrap / Wastage | Spoilage / defective units | Rejection count | 5 PKT |

#### What happens after saving?
1. Production record created in `daily_operations`.
2. Stock reduction logged in `stock_movements` for every raw material item (Godown balance decreases).
3. New finished goods batch generated in `stock_batches` with calculated unit cost (Godown balance increases).
4. Balanced manufacturing journal voucher posted in `account_transactions`:
   - **Debit:** Finished Goods Inventory Asset (Capitalized Cost)
   - **Debit:** Scrap / Manufacturing Loss Expense (Wastage Cost)
   - **Credit:** Raw Material Inventory Asset (Consumed Cost)
   - **Credit:** Direct Labor Clearing Account (Labor Allocation)
   - **Credit:** Factory Overhead / Power Clearing Account (Machine Cost)

---

### 6.2 Bill of Materials (BOM)
- **Route:** `operations.bom.index`, `operations.bom.create`, `operations.bom.edit`, `operations.bom.show` (`/operations/bom`)
- **Controller & Method:** `BOMController@index`, `create`, `store`, `edit`, `show`
- **Blade Views:** `resources/views/operations/bom/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables Involved:** `bill_of_materials`, `bom_items`, `products`, `items`
- **Module Dependencies:** Blueprint for Daily Operations Entry and Production Planning.
- **Implementation Status:** **Implemented**.

#### What does it mean?
A Bill of Materials (BOM) is the master recipe or formulation for producing a standard unit of finished product. It lists every raw ingredient, sub-component, packaging material, and standard labor/machine time required.

#### Why does a business need this?
Ensures consistent product quality, prevents ingredient theft, enables standard costing, and allows the ERP to predict exactly how many raw materials will be needed to fulfill a large sales order.

---

### 6.3 Machine Maintenance
- **Route:** `operations.maintenance.index`, `operations.maintenance.create`, `operations.maintenance.store` (`/operations/maintenance`)
- **Controller & Method:** `MachineMaintenanceController@index`, `create`, `store`
- **Blade Views:** `resources/views/operations/maintenance/index.blade.php`
- **Database Tables Involved:** `machine_maintenances`, `machines`, `account_transactions`
- **Module Dependencies:** Affects machine availability, production scheduling, and factory overhead expenses.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Logs preventative servicing, oiling, blade sharpening, breakdown repairs, and spare part replacements for factory machinery.

---

### 6.4 Production Batches
- **Route:** `operations.batches.index`, `operations.batches.show` (`/operations/batches`)
- **Controller & Method:** `ProductionBatchController@index`, `show`
- **Blade Views:** `resources/views/operations/batches/index.blade.php`
- **Database Tables Involved:** `stock_batches`, `daily_operations`, `items`
- **Module Dependencies:** Connects factory production to inventory FIFO traceability and quality expiry tracking.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Tracks the specific batch lot numbers (e.g., `BAT-20261123-01`) generated during manufacturing runs, recording manufacture date, expiry date, unit production cost, and quality inspection sign-off.

---

## 7. Inventory & Godown

### 7.1 Current Stock & Balances
- **Route:** `inventory.current_stock` (`/inventory/current-stock`)
- **Controller & Method:** `InventoryController@currentStock`
- **Blade Views:** `resources/views/inventory/current_stock.blade.php`
- **Database Tables Involved:** `stock_batches`, `items`, `godowns`
- **Module Dependencies:** Consulted by Sales Orders, Purchase Orders, and Physical Audits.
- **Implementation Status:** **Implemented**.

#### What does it mean?
The live physical stock register showing exactly how many units of every item are currently sitting in each warehouse, broken down by batch, manufacturing date, and unit cost.

#### Why does a business need this?
Prevents taking sales orders for items that are out of stock and identifies dead/excess stock tying up working capital.

---

### 7.2 Stock Movement Ledger
- **Route:** `inventory.movements` (`/inventory/movements`)
- **Controller & Method:** `InventoryController@movements`
- **Blade Views:** `resources/views/inventory/movements.blade.php`
- **Database Tables Involved:** `stock_movements`, `items`, `godowns`
- **Module Dependencies:** Complete audit trail for all stock transactions.
- **Implementation Status:** **Implemented**.

#### What does it mean?
An unalterable chronological audit trail (like a bank passbook for physical goods) showing every single stock addition, deduction, transfer, or adjustment with timestamp, reference document, quantity, and running balance.

---

### 7.3 Stock Inward
- **Route:** `inventory.inward.create`, `inventory.inward.store` (`/inventory/inward`)
- **Controller & Method:** `InventoryController@createInward`, `storeInward`
- **Blade Views:** `resources/views/inventory/inward.blade.php`
- **Database Tables Involved:** `stock_batches`, `stock_movements`, `account_transactions`
- **Module Dependencies:** Used for manual inward receipts, sample receipts, or opening balances outside standard purchase bills.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Allows recording non-purchase physical receipts into a warehouse (e.g., promotional free samples from vendors, returned customer demo items, or recovered stock).

---

### 7.4 Stock Outward
- **Route:** `inventory.outward.create`, `inventory.outward.store` (`/inventory/outward`)
- **Controller & Method:** `InventoryController@createOutward`, `storeOutward`
- **Blade Views:** `resources/views/inventory/outward.blade.php`
- **Database Tables Involved:** `stock_batches`, `stock_movements`, `account_transactions`
- **Module Dependencies:** Used for non-sales dispatches (sampling, R&D testing, donations).
- **Implementation Status:** **Implemented**.

---

### 7.5 Godown Transfers
- **Route:** `inventory.transfers`, `inventory.transfers.create`, `inventory.transfers.store` (`/inventory/transfers`)
- **Controller & Method:** `InventoryTransferController@index`, `create`, `store`
- **Blade Views:** `resources/views/inventory/transfers/index.blade.php`, `create.blade.php`
- **Database Tables Involved:** `stock_transfers`, `stock_transfer_items`, `stock_batches`, `stock_movements`
- **Module Dependencies:** Moves stock between warehouses without altering total company inventory value.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Records the physical relocation of goods from one company warehouse to another (e.g., from Central Factory Godown to City Retail Godown).

#### What happens after saving?
1. Stock reduced from source godown in `stock_batches` and logged in `stock_movements`.
2. Stock increased in destination godown under matching batch details.
3. Total company asset value remains unchanged; only location changes.

---

### 7.6 Stock Adjustments
- **Route:** `inventory.adjustments`, `inventory.adjustments.create`, `inventory.adjustments.store` (`/inventory/adjustments`)
- **Controller & Method:** `InventoryAdjustmentController@index`, `create`, `store`
- **Blade Views:** `resources/views/inventory/adjustments/index.blade.php`, `create.blade.php`
- **Database Tables Involved:** `stock_adjustments`, `stock_batches`, `stock_movements`, `account_transactions`
- **Module Dependencies:** Harmonizes physical stock audit counts with system books and general ledger.
- **Implementation Status:** **Implemented** (Phase 4/5 full accounting integration verified).

#### What does it mean?
Corrects discrepancies between the system stock count and the actual physical count discovered during warehouse inspection (e.g., pilferage, evaporation, physical breakage, expired stock, or unrecorded surplus).

#### What happens after saving?
- If **Shortage / Damaged / Expired:**
  - `stock_movements`: Outward entry logged.
  - `account_transactions`: **Debit:** Stock Adjustment / Loss Expense, **Credit:** Inventory Asset.
- If **Surplus / Found:**
  - `stock_movements`: Inward entry logged.
  - `account_transactions`: **Debit:** Inventory Asset, **Credit:** Stock Adjustment / Surplus Income.

---

### 7.7 Bulk Opening Stock
- **Route:** `inventory.opening_stock.index`, `inventory.opening_stock.store` (`/inventory/opening-stock`)
- **Controller & Method:** `OpeningStockController@index`, `store`
- **Blade Views:** `resources/views/inventory/opening_stock.blade.php`
- **Database Tables Involved:** `items`, `stock_batches`, `stock_movements`, `account_transactions`
- **Module Dependencies:** Used during initial ERP implementation / go-live migration.
- **Implementation Status:** **Implemented** (Balanced journal posting verified).

#### What does it mean?
A high-speed spreadsheet-style grid allowing the accountant to enter existing physical inventory quantities and unit costs for all items on the day the company transitions to Common ERP.

---

### 7.8 Low Stock Alerts
- **Route:** `inventory.alerts` (`/inventory/alerts`)
- **Controller & Method:** `InventoryController@alerts`
- **Blade Views:** `resources/views/inventory/alerts.blade.php`
- **Database Tables Involved:** `items`, `stock_batches`
- **Module Dependencies:** Drives the Procurement Purchase Order cycle.
- **Implementation Status:** **Implemented**.

#### What does it mean?
An automated warning screen listing every item where `current_stock <= reorder_level`, ensuring the purchasing manager re-orders critical materials before the factory stops or retail shelves become empty.

---

## 8. Procurement (Purchases)

### 8.1 Purchase Requests
- **Route:** `purchase.requests.index`, `purchase.requests.create`, `purchase.requests.store` (`/purchase/requests`)
- **Controller & Method:** `PurchaseRequestController@index`, `create`, `store`
- **Blade Views:** `resources/views/purchase/requests/index.blade.php`
- **Database Tables Involved:** `purchase_requests`, `purchase_request_items`
- **Module Dependencies:** Raised by Department Heads / Godown Keepers; approved into Purchase Orders.
- **Implementation Status:** **Implemented**.

#### What does it mean?
An internal requisition document created by a department supervisor (e.g., Packaging Supervisor requesting 10,000 pouches) asking the Purchasing Department to buy materials.

---

### 8.2 Purchase Orders (PO)
- **Route:** `purchase.orders.index`, `purchase.orders.create`, `purchase.orders.store`, `purchase.orders.show` (`/purchase/orders`)
- **Controller & Method:** `PurchaseOrderController@index`, `create`, `store`, `show`
- **Blade Views:** `resources/views/purchase/orders/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables Involved:** `purchase_orders`, `purchase_order_items`, `suppliers`
- **Module Dependencies:** Legally binding purchase commitment sent to Supplier; converted into Goods Receipt (GRN) or Purchase Bill.
- **Implementation Status:** **Implemented**.

#### What does it mean?
A formal legal contract sent to an external vendor specifying items, agreed prices, delivery deadlines, payment terms, and delivery godown.

---

### 8.3 Goods Receipts (GRN)
- **Route:** `purchase.grns.index`, `purchase.grns.create`, `purchase.grns.store`, `purchase.grns.show` (`/purchase/grns`)
- **Controller & Method:** `PurchaseGRNController@index`, `create`, `store`, `show`
- **Blade Views:** `resources/views/purchase/grns/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables Involved:** `goods_receipt_notes`, `goods_receipt_items`, `stock_batches`, `stock_movements`
- **Module Dependencies:** Warehouse entry confirming physical delivery before vendor sends invoice.
- **Implementation Status:** **Implemented**.

#### What does it mean?
The document prepared by the warehouse receiving clerk when the delivery truck arrives. It records physical count, checks for transit damage, and logs stock arrival.

---

### 8.4 Purchase Bills / Inward
- **Route:** `purchase.entries.index`, `purchase.entries.create`, `purchase.entries.store`, `purchase.entries.show` (`/purchase/entries`)
- **Controller & Method:** `PurchaseEntryController@index`, `create`, `store`, `show`
- **Blade Views:** `resources/views/purchase/entries/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables Involved:** `purchase_invoices`, `purchase_invoice_items`, `stock_batches`, `stock_movements`, `account_vouchers`, `account_transactions`, `suppliers`
- **Module Dependencies:** Financial and stock cornerstone of Procurement. Increases inventory, records GST input tax credit, and increases Accounts Payable.
- **Implementation Status:** **Implemented** (Full tax calculation and journal creation verified).

#### What does it mean?
The official commercial tax invoice received from the vendor for goods supplied.

#### What happens after saving?
1. Record created in `purchase_invoices` with unique voucher number (e.g., `PUR-202611-0012`).
2. New stock batches created in `stock_batches` and inward logged in `stock_movements`.
3. Balanced journal entry posted in General Ledger:
   - **Debit:** Raw Material / Merchandise Inventory Asset (Base Price)
   - **Debit:** GST Input CGST Asset (Central GST)
   - **Debit:** GST Input SGST Asset (State GST)
   - **Credit:** Supplier Account / Sundry Creditors (Total Bill Amount)

---

### 8.5 Purchase Returns / Debit Notes
- **Route:** `purchase.returns.index`, `purchase.returns.create`, `purchase.returns.store` (`/purchase/returns`)
- **Controller & Method:** `PurchaseReturnController@index`, `create`, `store`
- **Blade Views:** `resources/views/purchase/returns/index.blade.php`, `create.blade.php`
- **Database Tables Involved:** `purchase_returns`, `purchase_return_items`, `stock_movements`, `account_transactions`
- **Module Dependencies:** Reverses vendor invoice and stock when goods are rejected.
- **Implementation Status:** **Implemented**.

#### What does it mean?
A document issued to a supplier when delivered goods are rejected due to poor quality, defect, or wrong specification, reducing the amount owed to that supplier.

#### What happens after saving?
- Stock is reduced in `stock_movements`.
- Accounting entry reverses the purchase liability:
  - **Debit:** Supplier Account / Sundry Creditors (Reduces money owed)
  - **Credit:** Inventory Asset (Reduces stock value)
  - **Credit:** GST Input Tax Reversal (Reduces input tax credit claim)

---

### 8.6 Supplier Payments
- **Route:** `purchase.payments.index`, `purchase.payments.create`, `purchase.payments.store` (`/purchase/payments`)
- **Controller & Method:** `PurchasePaymentController@index`, `create`, `store`
- **Blade Views:** `resources/views/purchase/payments/index.blade.php`, `create.blade.php`
- **Database Tables Involved:** `account_vouchers`, `account_transactions`, `suppliers`
- **Module Dependencies:** Reduces cash/bank balance and clears Accounts Payable.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Records the payment made to a vendor via Bank Cheque, NEFT/RTGS, UPI, or Cash against outstanding purchase bills.

#### What happens after saving?
- Double-entry payment voucher posted:
  - **Debit:** Supplier Account / Sundry Creditors (Reduces liability)
  - **Credit:** Bank Account or Cash Account (Asset decreases)

---

## 9. Commercial & Sales

### 9.1 Sales Orders (SO)
- **Route:** `sales.orders.index`, `sales.orders.create`, `sales.orders.store`, `sales.orders.show` (`/sales/orders`)
- **Controller & Method:** `SalesOrderController@index`, `create`, `store`, `show`
- **Blade Views:** `resources/views/sales/orders/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables Involved:** `sales_orders`, `sales_order_items`, `customers`
- **Module Dependencies:** Confirmed customer order; triggers production planning and warehouse packing.
- **Implementation Status:** **Implemented**.

#### What does it mean?
A confirmed booking from a customer committing to buy specific quantities of items at agreed prices. Does not reduce stock or affect accounting until invoiced or dispatched.

---

### 9.2 Sales Invoices
- **Route:** `sales.invoices.index`, `sales.invoices.create`, `sales.invoices.store`, `sales.invoices.show` (`/sales/invoices`)
- **Controller & Method:** `SalesInvoiceController@index`, `create`, `store`, `show`
- **Blade Views:** `resources/views/sales/invoices/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables Involved:** `sales_invoices`, `sales_invoice_items`, `stock_batches`, `stock_movements`, `account_vouchers`, `account_transactions`, `customers`
- **Module Dependencies:** Primary revenue and dispatch engine. Reduces inventory, logs Cost of Goods Sold (COGS), records GST liability, and creates Accounts Receivable.
- **Implementation Status:** **Implemented** (FIFO stock relief and balanced double-entry verified).

#### What does it mean?
The legal tax invoice issued to a customer demanding payment for sold merchandise or finished products.

#### What happens after saving?
1. Record created in `sales_invoices` (e.g., `SAL-202611-0045`).
2. Stock reduced from specified warehouse batches and logged in `stock_movements`.
3. Balanced double-entry voucher posted in General Ledger:
   - **Debit:** Customer Account / Sundry Debtors (Total Invoice Amount)
   - **Credit:** Sales Revenue Account (Base Selling Price)
   - **Credit:** GST Output CGST Payable (Tax Liability)
   - **Credit:** GST Output SGST Payable (Tax Liability)
   - *Simultaneously (Periodic/Perpetual Inventory Integration):*
   - **Debit:** Cost of Goods Sold (COGS Expense)
   - **Credit:** Finished Goods / Merchandise Inventory Asset

---

### 9.3 Sales Returns / Credit Notes
- **Route:** `sales.returns.index`, `sales.returns.create`, `sales.returns.store` (`/sales/returns`)
- **Controller & Method:** `SalesReturnController@index`, `create`, `store`
- **Blade Views:** `resources/views/sales/returns/index.blade.php`, `create.blade.php`
- **Database Tables Involved:** `sales_returns`, `sales_return_items`, `stock_batches`, `stock_movements`, `account_transactions`
- **Module Dependencies:** Reverses customer sales invoices and restocks returned goods.
- **Implementation Status:** **Implemented**.

#### What does it mean?
A document issued to a customer who has returned merchandise (e.g., damaged packaging or wrong delivery), reducing the amount they owe you.

---

### 9.4 Dispatch & Route Delivery
- **Route:** `sales.deliveries.index`, `sales.deliveries.create`, `sales.deliveries.store` (`/sales/deliveries`)
- **Controller & Method:** `SalesDeliveryController@index`, `create`, `store`
- **Blade Views:** `resources/views/sales/deliveries/index.blade.php`
- **Database Tables Involved:** `sales_deliveries`, `sales_invoices`, `vehicles`, `routes`
- **Module Dependencies:** Connects Sales Invoices to Fleet Management and Route Logistics.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Consolidates invoices assigned to a specific delivery vehicle and route into a delivery trip sheet with driver sign-off.

---

### 9.5 Customer Collections
- **Route:** `sales.collections.index`, `sales.collections.create`, `sales.collections.store` (`/sales/collections`)
- **Controller & Method:** `CustomerCollectionController@index`, `create`, `store`
- **Blade Views:** `resources/views/sales/collections/index.blade.php`, `create.blade.php`
- **Database Tables Involved:** `account_vouchers`, `account_transactions`, `customers`
- **Module Dependencies:** Increases bank/cash balance and reduces Accounts Receivable.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Records payments received from customers (via Cheque, UPI, Bank Transfer, or Cash) against unpaid sales invoices.

#### What happens after saving?
- Double-entry receipt voucher posted:
  - **Debit:** Bank Account or Cash Account (Asset increases)
  - **Credit:** Customer Account / Sundry Debtors (Asset decreases; outstanding cleared)

---

## 10. Finance & Bookkeeping

### 10.1 Chart of Accounts
- **Route:** `accounts.chart_of_accounts` (`/accounts/chart-of-accounts`)
- **Controller & Method:** `AccountingController@chartOfAccounts`
- **Blade Views:** `resources/views/accounts/chart_of_accounts.blade.php`
- **Database Tables Involved:** `account_groups`, `account_ledgers`
- **Module Dependencies:** The financial skeleton of the entire enterprise. Every transaction must route to a ledger defined here.
- **Implementation Status:** **Implemented** (5-category standard hierarchy verified).

#### What does it mean?
The complete structured catalog of every financial account used by the business to record financial transactions, categorized under:
1. **Assets** (What the company owns)
2. **Liabilities** (What the company owes)
3. **Equity / Capital** (Owner's net worth in the business)
4. **Revenue / Income** (Earnings from sales and services)
5. **Expenses** (Costs incurred to operate the business)

---

### 10.2 Ledgers & Balances
- **Route:** `accounts.ledgers.index`, `accounts.ledgers.show` (`/accounts/ledgers`)
- **Controller & Method:** `AccountingController@ledgers`, `showLedger`
- **Blade Views:** `resources/views/accounts/ledgers/index.blade.php`, `show.blade.php`
- **Database Tables Involved:** `account_ledgers`, `account_transactions`
- **Module Dependencies:** Consulted for auditing individual account histories.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Displays the running transactional history of a specific account (e.g., "HDFC Current Account" or "Electricity Expense Account") with date, reference, debit, credit, and running balance.

---

### 10.3 Journal Entries / Vouchers
- **Route:** `accounts.vouchers.index`, `accounts.vouchers.create`, `accounts.vouchers.store`, `accounts.vouchers.show` (`/accounts/vouchers`)
- **Controller & Method:** `AccountingController@vouchers`, `createVoucher`, `storeVoucher`
- **Blade Views:** `resources/views/accounts/vouchers/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables Involved:** `account_vouchers`, `account_transactions`, `account_ledgers`
- **Module Dependencies:** Universal double-entry journal entry screen for manual adjustments, depreciation, interest, and transfers.
- **Implementation Status:** **Implemented** (Strict validation: `sum(debit) == sum(credit)` enforced).

#### What does it mean?
The manual double-entry ledger interface for adjustments that don't originate from standard sales or purchase screens (e.g., posting monthly machinery depreciation, adjusting prepaid rent, or recording interest charges).

---

### 10.4 Bank Reconciliation
- **Route:** `accounts.bank_reconciliation.index`, `accounts.bank_reconciliation.store` (`/accounts/bank-reconciliation`)
- **Controller & Method:** `AccountingController@bankReconciliation`, `updateBankReconciliation`
- **Blade Views:** `resources/views/accounts/bank_reconciliation.blade.php`
- **Database Tables Involved:** `account_transactions`, `account_ledgers`
- **Module Dependencies:** Audits ERP bank balance against actual bank statement.
- **Implementation Status:** **Implemented**.

#### What does it mean?
Matching check deposits, uncleared checks, and bank charges recorded in the ERP against the official bank account passbook.

---

### 10.5 Petty Cash
- **Route:** `accounts.petty_cash.index`, `accounts.petty_cash.store` (`/accounts/petty-cash`)
- **Controller & Method:** `AccountingController@pettyCash`, `storePettyCash`
- **Blade Views:** `resources/views/accounts/petty_cash.blade.php`
- **Database Tables Involved:** `petty_cash_entries`, `account_transactions`
- **Module Dependencies:** Manages minor daily cash expenses (tea, local postage, minor repairs).
- **Implementation Status:** **Implemented**.

---

### 10.6 Capital & Equity
- **Route:** `accounts.capital.index`, `accounts.capital.store` (`/accounts/capital`)
- **Controller & Method:** `AccountingController@capital`, `storeCapital`
- **Blade Views:** `resources/views/accounts/capital.blade.php`
- **Database Tables Involved:** `capital_transactions`, `account_transactions`
- **Module Dependencies:** Records owner investments, share capital infusions, and partner drawings.
- **Implementation Status:** **Implemented**.

---

### 10.7 Financial Years
- **Route:** `accounts.financial_years.index`, `accounts.financial_years.store` (`/accounts/financial-years`)
- **Controller & Method:** `AccountingController@financialYears`, `storeFinancialYear`
- **Blade Views:** `resources/views/accounts/financial_years.blade.php`
- **Database Tables Involved:** `financial_years`
- **Module Dependencies:** Controls open and locked accounting periods (e.g., FY 2025-26: 01-Apr to 31-Mar).
- **Implementation Status:** **Implemented**.

---

## 11. Intelligence & Reports

### 11.1 Daily Operations Report
- **Route:** `reports.daily_operations.index` (`/reports/daily-operations`)
- **Controller & Method:** `ReportController@dailyOperations`
- **Blade Views:** `resources/views/reports/daily_operations.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Aggregates factory output, machine utilization, labor hours, and scrap rates by date.

### 11.2 Trial Balance
- **Route:** `reports.financial.trial_balance` (`/reports/financial/trial-balance`)
- **Controller & Method:** `ReportController@trialBalance`
- **Blade Views:** `resources/views/reports/financial/trial_balance.blade.php`
- **Status:** **Implemented** (Double-entry integrity check: Total Debits must equal Total Credits).
- **Purpose:** Proves arithmetic mathematical accuracy of the general ledger before generating P&L and Balance Sheet.

### 11.3 Profit & Loss Account
- **Route:** `reports.financial.profit_and_loss` (`/reports/financial/profit-and-loss`)
- **Controller & Method:** `ReportController@profitAndLoss`
- **Blade Views:** `resources/views/reports/financial/profit_and_loss.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Calculates Net Profit or Loss for a period: `Total Revenue - Total Cost of Goods Sold - Operating Expenses`.

### 11.4 Balance Sheet
- **Route:** `reports.financial.balance_sheet` (`/reports/financial/balance-sheet`)
- **Controller & Method:** `ReportController@balanceSheet`
- **Blade Views:** `resources/views/reports/financial/balance_sheet.blade.php`
- **Status:** **Implemented** (Integrated current period profit and accounting equation check: `Assets = Liabilities + Equity`).
- **Purpose:** Shows the financial position and solvency of the enterprise on a specific date.

### 11.5 Cash Flow Statement
- **Route:** `reports.financial.cash_flow` (`/reports/financial/cash-flow`)
- **Controller & Method:** `ReportController@cashFlow`
- **Blade Views:** `resources/views/reports/financial/cash_flow.blade.php`
- **Status:** **Implemented** (Operating, Investing, Financing activities breakdown verified).
- **Purpose:** Explains where cash came from and where cash was spent during the accounting period.

### 11.6 Day Book
- **Route:** `reports.financial.day_book` (`/reports/financial/day-book`)
- **Controller & Method:** `ReportController@dayBook`
- **Blade Views:** `resources/views/reports/financial/day_book.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Chronological journal of all financial vouchers entered on a chosen date.

### 11.7 Sales Register
- **Route:** `reports.sales.sales_register` (`/reports/sales/sales-register`)
- **Controller & Method:** `ReportController@salesRegister`
- **Blade Views:** `resources/views/reports/sales/sales_register.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Itemized log of all sales tax invoices issued, including taxable value and GST breakdown.

### 11.8 Purchase Register
- **Route:** `reports.purchase.purchase_register` (`/reports/purchase/purchase-register`)
- **Controller & Method:** `ReportController@purchaseRegister`
- **Blade Views:** `resources/views/reports/purchase/purchase_register.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Itemized log of all vendor bills received, verifying input tax credit (ITC) claims.

### 11.9 Stock Valuation Report
- **Route:** `reports.inventory.valuation` (`/reports/inventory/valuation`)
- **Controller & Method:** `ReportController@stockValuation`
- **Blade Views:** `resources/views/reports/inventory/valuation.blade.php`
- **Status:** **Implemented** (Batch unit cost valuation verified).
- **Purpose:** Quantifies total physical asset value of warehouse stock by item and category.

### 11.10 Stock Reconciliation Report
- **Route:** `reports.inventory.reconciliation` (`/reports/inventory/reconciliation`)
- **Controller & Method:** `ReportController@stockReconciliation`
- **Blade Views:** `resources/views/reports/inventory/reconciliation.blade.php`
- **Status:** **Implemented** (Audits Physical Batch Value vs. General Ledger Inventory Account).
- **Purpose:** Detects discrepancies between physical warehouse value and balance sheet stock ledger value.

### 11.11 Customer Outstanding
- **Route:** `reports.sales.customer_outstanding` (`/reports/sales/customer-outstanding`)
- **Controller & Method:** `ReportController@customerOutstanding`
- **Blade Views:** `resources/views/reports/sales/customer_outstanding.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Aging analysis of pending customer receivables to drive payment follow-ups.

### 11.12 Supplier Outstanding
- **Route:** `reports.purchase.supplier_outstanding` (`/reports/purchase/supplier-outstanding`)
- **Controller & Method:** `ReportController@supplierOutstanding`
- **Blade Views:** `resources/views/reports/purchase/supplier_outstanding.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Lists pending vendor liabilities to plan cash flow and payment disbursements.

### 11.13 Payroll Summary
- **Route:** `reports.payroll.summary` (`/reports/payroll/summary`)
- **Controller & Method:** `ReportController@payrollSummary`
- **Blade Views:** `resources/views/reports/payroll/summary.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Monthly departmental wage analysis, statutory PF/ESI liabilities, and payout totals.

### 11.14 Tax / GST Summary
- **Route:** `reports.tax.summary` (`/reports/tax/summary`)
- **Controller & Method:** `ReportController@taxSummary`
- **Blade Views:** `resources/views/reports/tax/summary.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Computes net GST liability: `Total Output GST (Sales) - Total Input GST Credit (Purchases)`.

---

## 12. Administration & Security

### 12.1 User Management
- **Route:** `settings.users.index`, `settings.users.create`, `settings.users.store`, `settings.users.edit` (`/settings/users`)
- **Controller & Method:** `UserController@index`, `create`, `store`, `edit`, `update`
- **Blade Views:** `resources/views/settings/users/index.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Creates system logins, email credentials, passwords, and assigned default company for employees.

### 12.2 Roles & Permissions
- **Route:** `settings.roles.index`, `settings.roles.edit`, `settings.roles.update` (`/settings/roles`)
- **Controller & Method:** `RoleController@index`, `edit`, `update`
- **Blade Views:** `resources/views/settings/roles/index.blade.php`
- **Status:** **Implemented** (Role-based access control with Spatie/Custom gate permissions).
- **Purpose:** Enforces security segregation of duties (e.g., warehouse staff cannot see financial profit reports; billing clerks cannot edit past journal entries).

### 12.3 Module Activation
- **Route:** `settings.modules.index`, `settings.modules.update` (`/settings/modules`)
- **Controller & Method:** `ModuleController@index`, `update`
- **Blade Views:** `resources/views/settings/modules/index.blade.php`
- **Status:** **Implemented** (Company-level feature toggling verified).
- **Purpose:** Enables or disables entire functional modules (e.g., enable Manufacturing for a spice factory, disable Manufacturing for a retail clothing boutique).

### 12.4 Audit Logs
- **Route:** `settings.audit_logs.index` (`/settings/audit-logs`)
- **Controller & Method:** `AuditLogController@index`
- **Blade Views:** `resources/views/settings/audit_logs/index.blade.php`
- **Status:** **Implemented**.
- **Purpose:** Immutable security log recording which user created, edited, or deleted records, along with IP address and timestamps.
"""

with open(os.path.join(OUTPUT_DIR, "01_COMPLETE_SIDEBAR_AND_SUBMENU_GUIDE.md"), "w", encoding="utf-8") as f:
    f.write(doc1_content)

print("01_COMPLETE_SIDEBAR_AND_SUBMENU_GUIDE.md written successfully.")

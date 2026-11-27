# COMMON ERP – COMPLETE SCREEN-BY-SCREEN USER MANUAL
## Comprehensive Operational Guide: Form Fields, Business Logic, Database Effects & Accounting Entries

---

### Navigation Index
- [1. Item Master Screen (Add / Edit Item)](#1-item-master-screen-add--edit-item)
- [2. Category Master Screen](#2-category-master-screen)
- [3. Product Master Screen](#3-product-master-screen)
- [4. Units of Measure (UOM) Screen](#4-units-of-measure-uom-screen)
- [5. Godowns / Warehouses Screen](#5-godowns--warehouses-screen)
- [6. Department Master Screen](#6-department-master-screen)
- [7. Vehicle & Fleet Master Screen](#7-vehicle--fleet-master-screen)
- [8. Route & Territory Master Screen](#8-route--territory-master-screen)
- [9. Supplier Master Screen (Add / Edit Vendor)](#9-supplier-master-screen-add--edit-vendor)
- [10. Customer Master Screen (Add / Edit Customer)](#10-customer-master-screen-add--edit-customer)
- [11. Staff Directory Screen (Add / Edit Employee)](#11-staff-directory-screen-add--edit-employee)
- [12. Daily Attendance Screen](#12-daily-attendance-screen)
- [13. Payroll Processing Screen](#13-payroll-processing-screen)
- [14. Bill of Materials (BOM) Screen](#14-bill-of-materials-bom-screen)
- [15. Daily Operations Entry Screen (Manufacturing Run)](#15-daily-operations-entry-screen-manufacturing-run)
- [16. Machine Maintenance Screen](#16-machine-maintenance-screen)
- [17. Godown Stock Transfer Screen](#17-godown-stock-transfer-screen)
- [18. Stock Adjustment Screen](#18-stock-adjustment-screen)
- [19. Bulk Opening Stock Entry Screen](#19-bulk-opening-stock-entry-screen)
- [20. Purchase Order (PO) Screen](#20-purchase-order-po-screen)
- [21. Goods Receipt Note (GRN) Screen](#21-goods-receipt-note-grn-screen)
- [22. Purchase Bill / Inward Screen](#22-purchase-bill--inward-screen)
- [23. Purchase Return (Debit Note) Screen](#23-purchase-return-debit-note-screen)
- [24. Supplier Payment Voucher Screen](#24-supplier-payment-voucher-screen)
- [25. Sales Order Screen](#25-sales-order-screen)
- [26. Sales Invoice / Tax Bill Screen](#26-sales-invoice--tax-bill-screen)
- [27. Sales Return (Credit Note) Screen](#27-sales-return-credit-note-screen)
- [28. Sales Route Delivery & Dispatch Screen](#28-sales-route-delivery--dispatch-screen)
- [29. Customer Collection Voucher Screen](#29-customer-collection-voucher-screen)
- [30. Chart of Accounts Screen](#30-chart-of-accounts-screen)
- [31. General Journal Voucher Screen](#31-general-journal-voucher-screen)
- [32. Bank Reconciliation Screen](#32-bank-reconciliation-screen)
- [33. Petty Cash Entry Screen](#33-petty-cash-entry-screen)
- [34. Company & Branch Profile Screen](#34-company--branch-profile-screen)
- [35. Module Activation & Toggles Screen](#35-module-activation--toggles-screen)

---

## 1. Item Master Screen (Add / Edit Item)

### A. Screen Identification
- **Sidebar Path:** Master Data -> Master Registry -> Items & Price Levels
- **Screen Name:** Add Item / Edit Item
- **Route URL:** `/master/items/create` (Create), `/master/items/{id}/edit` (Edit), `/master/items` (List)
- **Controller & Method:** `App\Http\Controllers\ItemController@create`, `@store`, `@edit`, `@update`
- **Blade File:** `resources/views/master/items/create.blade.php`, `resources/views/master/items/edit.blade.php`
- **Database Tables:** `items`, `products`, `categories`, `units`, `godowns`, `stock_batches`, `stock_movements`, `account_transactions`

### B. Purpose
- **What does it do?** Registers individual inventory items, packaging materials, raw ingredients, or finished resale goods.
- **Why does a business need it?** Standardizes item names, default buying/selling rates, GST HSN codes, and safety reorder points across purchasing, sales, and manufacturing.
- **Which department normally uses it?** Inventory / Store Department or Master Data Management.
- **What problem does it solve?** Eliminates duplicate item entries, inaccurate pricing, untracked inventory, and GST compliance errors.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Product (Select) | Brand or product family lineage | Choose umbrella brand from dropdown | Premium Spices Range |
| Item Name | Full unique description of the item | Complete commercial trade name | Coriander Powder 200g Pouch |
| Item Code / SKU | Unique stock keeping unit code | Internal alphanumeric code or barcode | RM-COR-200 |
| Category | Classification group | Choose appropriate category | Finished Spices |
| Unit of Measure (UOM) | Base stocking unit | Standard unit from UOM master | PKT (Packet) |
| Purchase Price | Standard vendor buying price | Cost price in INR before taxes | ₹45.00 |
| Selling Price | Standard customer selling price | Standard wholesale price in INR | ₹62.00 |
| Tax / HSN Code | GST classification code | 4 to 8 digit statutory HSN code | 0910 |
| GST Rate (%) | Tax rate slab | 0%, 5%, 12%, 18%, or 28% | 5% |
| Reorder Level | Minimum safety threshold | Quantity threshold triggering low-stock alert | 100 PKT |
| Initial Opening Stock | Physical units present at setup date | Quantity in warehouse during ERP onboarding | 500 PKT |
| Opening Stock Valuation Rate | Cost per unit of opening stock | Valuation rate per unit in INR | ₹45.00 |
| Opening Warehouse | Warehouse holding opening units | Warehouse location for opening stock | Central Finished Goods Godown |
| Item Image | Visual photograph of the product | JPG/PNG product photo (Max 2MB) | coriander_200g.png |
| Status | Master status toggle | Active or Inactive | Active |

### D. Practical Usage Steps
1. Navigate to **Master Data -> Items & Price Levels** and click **+ Add New Item**.
2. Select the parent **Product** from the dropdown (or leave blank if it is a raw commodity).
3. Type the **Item Name** and unique **Item Code**.
4. Select the **Category** and primary **Unit of Measure**.
5. Input the standard **Purchase Price** and **Selling Price**.
6. Provide the statutory **HSN Code** and applicable **GST Rate**.
7. If onboarding an existing warehouse, enter **Initial Opening Stock**, **Unit Valuation Rate**, and select the **Opening Warehouse**.
8. Upload a clear product image for warehouse identification.
9. Click **Save Item**.

### E. What Happens After Saving?
1. **Database:** New row inserted into `items` table with tenant isolation `company_id`.
2. **Inventory Stock:** If `opening_stock > 0`, a new batch is created in `stock_batches`, and an inward record is logged in `stock_movements`.
3. **Accounting:** If opening stock has value (`quantity * rate > 0`), a balanced journal voucher is posted in `account_vouchers` and `account_transactions`:
   - **Debit:** Inventory Asset Account (`10040 - Stock in Hand`)
   - **Credit:** Opening Balance Equity Account (`30020 - Opening Stock Equity`)
4. **Subsequent Impact:** The item immediately becomes available in Purchase Orders, Daily Operations BOM, and Sales Invoices.

### F. Practical Business Example
- **Scenario:** A spice company registers "Turmeric Powder 100g Pouch".
- **Entered Data:** Code: `TP-100G`, Category: `Spices`, UOM: `PKT`, Purchase Rate: ₹22, Selling Rate: ₹32, GST: 5%, Opening Stock: 250 PKT @ ₹22 in Main Godown.
- **Financial Result:** Inventory Asset increases by ₹5,500; Opening Balance Equity increases by ₹5,500.

### G. Common Mistakes
- Setting UOM to "KG" when the item is actually sold in "Packets", causing 1000x stock counting errors.
- Entering selling price inclusive of GST when the system expects basic pre-tax rate.
- Entering opening stock when goods were already counted in a prior bulk upload, causing double stock.

### H. Related Screens
- **Next:** Category Master, BOM Screen (if manufactured), Purchase Bill (if procured from vendor).

---

## 2. Category Master Screen

### A. Screen Identification
- **Sidebar Path:** Master Data -> Master Registry -> Categories
- **Screen Name:** Item Categories
- **Route URL:** `/master/categories`
- **Controller & Method:** `App\Http\Controllers\CategoryController@index`, `@store`, `@update`, `@destroy`
- **Blade File:** `resources/views/master/categories/index.blade.php`
- **Database Tables:** `categories`, `items`

### B. Purpose
- **What does it do?** Manages broad taxonomic classifications for items.
- **Why does a business need it?** Enables category-wise inventory valuation, purchase analysis, and sales margin reports.
- **Department:** Inventory Control & Accounts.
- **Problem solved:** Eliminates messy uncategorized inventory lists.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Category Name | Descriptive title of category | Standard group name | Whole Raw Spices |
| Category Code | Short uppercase code | 3-5 character code | RAW-SPICE |
| Parent Category | Hierarchical level | None (for root) or select parent group | Raw Materials |
| Description | Operational guidelines | Scope of items belonging to this category | Raw unground cumin, pepper, chilli |

### D. Practical Usage
1. Open Categories screen.
2. In the modal or inline form, enter Category Name and Code.
3. Select Parent Category if creating a nested sub-category.
4. Click Save.

### E. What Happens After Saving?
- Row added to `categories`. Instantly appears in Item Master category dropdown and Inventory Valuation filters. No accounting entry is created.

---

## 3. Product Master Screen

### A. Screen Identification
- **Sidebar Path:** Master Data -> Master Registry -> Products
- **Screen Name:** Product Lines
- **Route URL:** `/master/products`
- **Controller & Method:** `App\Http\Controllers\ProductController@index`, `@store`, `@update`
- **Blade File:** `resources/views/master/products/index.blade.php`
- **Database Tables:** `products`, `items`, `bill_of_materials`

### B. Purpose
- **What does it do?** Defines parent brand lines or generic product concepts that bundle multiple sellable SKUs.
- **Why needed?** Connects commercial marketing product lines to factory manufacturing BOM recipes.
- **Department:** Marketing, Production Planning.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Product Name | Commercial umbrella name | Brand or general product title | Garam Masala Blend |
| Product Code | System code | Short code | PRD-GM |
| Category | Associated category | Primary commodity group | Blended Spices |
| Description | Technical formulation details | Manufacturing or market positioning | 16-spice aromatic blend |

---

## 4. Units of Measure (UOM) Screen

### A. Screen Identification
- **Sidebar Path:** Master Data -> Master Registry -> Units of Measure
- **Screen Name:** Units of Measurement & Conversions
- **Route URL:** `/master/units`
- **Controller & Method:** `App\Http\Controllers\UnitController@index`, `@store`, `@update`
- **Blade File:** `resources/views/master/units/index.blade.php`
- **Database Tables:** `units`, `unit_conversions`

### B. Purpose
- **What does it do?** Manages metric and count measurement standards (KG, Gram, Liter, Box, Pouch).
- **Why needed?** Ensures mathematical consistency across manufacturing consumption and commercial retail packaging.
- **Department:** Stores, Engineering, Accounts.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Unit Name | Full formal metric name | Full name | Kilogram |
| Symbol / Code | Abbreviation | Standard short symbol | KG |
| Unit Type | Physical dimension | Weight, Volume, Length, Count | Weight |
| Allow Decimals | Can quantities be fractional? | Yes (for weight/volume), No (for discrete items) | Yes (e.g. 1.250 KG) |

---

## 5. Godowns / Warehouses Screen

### A. Screen Identification
- **Sidebar Path:** Master Data -> Master Registry -> Godowns / Warehouses
- **Screen Name:** Warehouses & Storage Godowns
- **Route URL:** `/master/godowns`
- **Controller & Method:** `App\Http\Controllers\GodownController@index`, `@store`, `@update`
- **Blade File:** `resources/views/master/godowns/index.blade.php`
- **Database Tables:** `godowns`, `stock_batches`, `stock_transfers`

### B. Purpose
- **What does it do?** Sets up physical locations where inventory is received, stored, or issued.
- **Why needed?** Allows multi-location stock tracking across factory sheds, transit depots, and showroom shelves.
- **Department:** Supply Chain / Warehousing.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Godown Name | Location title | Descriptive physical title | Raw Materials Shed 1 |
| Godown Code | Short code | Location identifier | WH-RM-01 |
| Branch | Controlling branch | Associated company branch | Head Office / Main Factory |
| Location Address | Physical address | Detailed site location | Gate 2, MIDC Bhosari, Pune |
| Supervisor Name | Storekeeper in charge | Name of employee responsible | Santosh Kadam |
| Contact Phone | Phone number | Storekeeper phone | +91 98223 44556 |

---

## 6. Department Master Screen

### A. Screen Identification
- **Sidebar Path:** Master Data -> Master Registry -> Departments
- **Screen Name:** Company Departments
- **Route URL:** `/master/departments`
- **Controller & Method:** `App\Http\Controllers\DepartmentController@index`, `@store`, `@update`
- **Blade File:** `resources/views/master/departments/index.blade.php`
- **Database Tables:** `departments`, `staff`, `daily_operations`

### B. Purpose
- Groups employees and operations into cost centers (Production, Quality, Sales, Accounts).
- Enables department-wise payroll expense allocation and manufacturing labor tracking.

---

## 7. Vehicle & Fleet Master Screen

### A. Screen Identification
- **Sidebar Path:** Master Data -> Master Registry -> Vehicles & Drivers
- **Screen Name:** Fleet & Logistics Management
- **Route URL:** `/master/vehicles`
- **Controller & Method:** `App\Http\Controllers\VehicleController@index`, `@store`, `@update`
- **Blade File:** `resources/views/master/vehicles/index.blade.php`
- **Database Tables:** `vehicles`, `sales_deliveries`

### B. Purpose
- Tracks company delivery vans, tempo trucks, and drivers used for order dispatch and raw material hauling.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Vehicle Registration Number | RTO license plate | Legal registration string | MH-14-GH-2341 |
| Vehicle Model / Type | Make and tonnage | Class of vehicle | Tata Ace 1.5T |
| Driver Name | Assigned primary driver | Name of authorized driver | Ramesh Gaikwad |
| Driver Mobile | Driver contact | 10-digit mobile number | +91 97654 32109 |
| Payload Capacity (KG) | Maximum weight limit | Safe cargo weight in KG | 1,500 KG |

---

## 8. Route & Territory Master Screen

### A. Screen Identification
- **Sidebar Path:** Master Data -> Master Registry -> Territory & Routes
- **Screen Name:** Distribution Routes
- **Route URL:** `/master/routes`
- **Controller & Method:** `App\Http\Controllers\RouteController@index`, `@store`, `@update`
- **Blade File:** `resources/views/master/routes/index.blade.php`
- **Database Tables:** `routes`, `customers`, `sales_deliveries`

### B. Purpose
- Defines fixed distribution beats/routes covering retail customer clusters for organized daily delivery dispatches.

---

## 9. Supplier Master Screen (Add / Edit Vendor)

### A. Screen Identification
- **Sidebar Path:** Stakeholders -> Suppliers
- **Screen Name:** Supplier Profile
- **Route URL:** `/suppliers/create` (Create), `/suppliers/{id}/edit` (Edit), `/suppliers` (List)
- **Controller & Method:** `App\Http\Controllers\SupplierController@create`, `@store`, `@edit`, `@update`, `@show`
- **Blade File:** `resources/views/suppliers/create.blade.php`, `resources/views/suppliers/edit.blade.php`, `resources/views/suppliers/show.blade.php`
- **Database Tables:** `suppliers`, `account_ledgers`, `account_transactions`, `purchase_invoices`

### B. Purpose
- **What does it do?** Registers vendors, raw material suppliers, packaging manufacturers, and service contractors.
- **Why needed?** Tracks credit terms, GSTIN compliance, purchasing history, and maintains Accounts Payable ledger.
- **Department:** Procurement & Accounts.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Supplier Name | Registered legal trade name | Vendor business name | Malwa Agricultural Spices |
| Contact Person | Key account representative | Sales executive or owner | Rajeshwar Patidar |
| Phone / Mobile | Contact phone number | Official mobile/telephone | +91 94250 88990 |
| Email Address | Billing email | Email for sending purchase orders | accounts@malwaspices.com |
| GSTIN | 15-digit GST registration | Supplier GST certificate number | 23AAAPM4567Q1Z4 |
| PAN | Income tax permanent number | 10-digit PAN | AAAPM4567Q |
| Address | Physical godown/office address | Complete postal address | 12 Mandi Road, Neemuch, MP 458441 |
| Credit Period (Days) | Agreed credit window | Number of days to pay invoices | 30 Days |
| Opening Balance | Unpaid historical dues | Amount owed to vendor at ERP start | ₹85,000.00 Cr |

### D. Practical Usage Steps
1. Navigate to **Stakeholders -> Suppliers** and click **+ Add Supplier**.
2. Enter business name, representative details, phone, and email.
3. Enter valid GSTIN and physical address.
4. Set agreed credit terms (e.g., 30 Days).
5. If migrating an existing balance, enter the Opening Balance.
6. Click **Save Supplier**.

### E. What Happens After Saving?
1. Record saved in `suppliers` table with `company_id`.
2. Dedicated ledger created automatically in `account_ledgers` under **Current Liabilities -> Sundry Creditors**.
3. If Opening Balance > 0, an initial opening journal entry is logged crediting the vendor's ledger and debiting Opening Balance Equity.

---

## 10. Customer Master Screen (Add / Edit Customer)

### A. Screen Identification
- **Sidebar Path:** Stakeholders -> Customers & Pricing
- **Screen Name:** Customer Profile
- **Route URL:** `/customers/create`, `/customers/{id}/edit`, `/customers`
- **Controller & Method:** `App\Http\Controllers\CustomerController@create`, `@store`, `@edit`, `@update`, `@show`
- **Blade File:** `resources/views/customers/create.blade.php`, `resources/views/customers/edit.blade.php`, `resources/views/customers/show.blade.php`
- **Database Tables:** `customers`, `account_ledgers`, `sales_invoices`, `routes`

### B. Purpose
- Registers wholesale buyers, distributors, modern retail stores, and end consumers.
- Controls credit limits, delivery beats/routes, wholesale price tiers, and maintains Accounts Receivable.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Customer Name | Retail shop or buyer name | Commercial name of customer | Sai Kirana Stores |
| Customer Code | Unique identification code | Alphanumeric customer ID | CUST-1045 |
| Contact Person | Shop owner / buyer name | Person handling payments | Santosh Pawar |
| Mobile Phone | Contact number | Mobile for dispatch SMS/bills | +91 98901 23456 |
| GSTIN | Buyer's GST number | 15-digit GSTIN (leave blank for un-registered consumer) | 27AACCS9988M1Z3 |
| Route / Territory | Assigned delivery beat | Select delivery route | Pune North Route |
| Price Level | Pricing tier | Wholesale Tier A / Retail / Super-Stockist | Wholesale Tier A |
| Credit Limit | Maximum allowable debt | Inward cap for unpaid sales in INR | ₹1,50,000.00 |
| Credit Period | Payment window in days | Due days after invoice | 15 Days |
| Opening Balance | Uncollected past invoices | Receivables owed to company at ERP setup | ₹24,000.00 Dr |

### D. What Happens After Saving?
- Customer saved in `customers`.
- Subsidiary ledger created in `account_ledgers` under **Current Assets -> Sundry Debtors**.
- Customer is now selectable in Sales Orders and Sales Invoices.

---

## 11. Staff Directory Screen (Add / Edit Employee)

### A. Screen Identification
- **Sidebar Path:** Staff & Human Resources -> Staff & Payroll -> Staff Directory
- **Screen Name:** Employee Profile
- **Route URL:** `/staff/create`, `/staff/{id}/edit`, `/staff`
- **Controller & Method:** `App\Http\Controllers\StaffController@create`, `@store`, `@edit`, `@update`, `@show`
- **Blade File:** `resources/views/staff/create.blade.php`, `resources/views/staff/edit.blade.php`, `resources/views/staff/show.blade.php`
- **Database Tables:** `staff`, `departments`, `shifts`

### B. Purpose
- Centralizes HR records, salary structures, shift assignments, and bank disbursement information.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Employee Code | Organization badge ID | Alphanumeric employee code | EMP-0205 |
| Full Name | Legal employee name | Name as per Aadhaar/Bank passbook | Nilesh Shivaji Mane |
| Department | Assigned department | Select from Department Master | Production / Grinding |
| Designation | Official job title | Designation title | Grinder Operator |
| Employment Type | Contract category | Permanent, Trainee, Daily Wage | Permanent |
| Basic Monthly Wage | Contracted basic salary | Monthly base amount in INR | ₹16,000.00 |
| Allowances | HRA, transport, special allowances | Total monthly allowance in INR | ₹4,000.00 |
| Bank Account Number | Salary deposit account | Verified employee bank account number | 50100234567890 |
| Bank IFSC Code | Bank branch code | 11-digit IFSC code | HDFC0000123 |
| Date of Joining | First working day | Date of hire | 2024-06-01 |

---

## 12. Daily Attendance Screen

### A. Screen Identification
- **Sidebar Path:** Staff & Human Resources -> Staff & Payroll -> Daily Attendance
- **Screen Name:** Daily Attendance Register
- **Route URL:** `/staff/attendance`
- **Controller & Method:** `App\Http\Controllers\StaffAttendanceController@index`, `@store`
- **Blade File:** `resources/views/staff/attendance/index.blade.php`
- **Database Tables:** `staff_attendances`, `staff`

### B. Purpose
- Records daily employee attendance (Present, Absent, Half Day, Leave, Overtime Hours) to accurately compute monthly payroll.

### C. Practical Usage Steps
1. Select the **Attendance Date** (defaults to today).
2. Filter by **Department** or view all employees.
3. For each employee, select status: `Present`, `Absent`, `Half Day`, or `On Leave`.
4. Enter `Overtime Hours` if applicable (e.g., 2.5 hours).
5. Click **Save Attendance Register**.

---

## 13. Payroll Processing Screen

### A. Screen Identification
- **Sidebar Path:** Staff & Human Resources -> Staff & Payroll -> Payroll Processing
- **Screen Name:** Monthly Payroll Generation
- **Route URL:** `/staff/payroll`, `/staff/payroll/create`, `/staff/payroll/{id}`
- **Controller & Method:** `App\Http\Controllers\StaffPayrollController@index`, `@generate`, `@process`, `@show`
- **Blade File:** `resources/views/staff/payroll/index.blade.php`, `create.blade.php`, `show.blade.php`
- **Database Tables:** `payrolls`, `payroll_items`, `staff`, `account_vouchers`, `account_transactions`

### B. Purpose
- Calculates monthly salary payouts based on attendance, applies statutory deductions (PF, ESI, Professional Tax), deducts salary advances, and posts the double-entry salary expense voucher into the General Ledger.

### C. What Happens After Processing?
- Monthly payroll finalized in `payrolls` and line items recorded in `payroll_items`.
- Balanced journal voucher posted:
  - **Debit:** Salary & Wages Expense (`50010 - Salaries Expense`)
  - **Credit:** Provident Fund (PF) Payable (`20030 - PF Payable`)
  - **Credit:** Employee State Insurance (ESI) Payable (`20040 - ESI Payable`)
  - **Credit:** Staff Advance Asset (Recovers outstanding employee loans)
  - **Credit:** Salaries Payable / Bank Clearing Account (`20020 - Salaries Payable`)

---

## 14. Bill of Materials (BOM) Screen

### A. Screen Identification
- **Sidebar Path:** Operations & Manufacturing -> Operations -> Bill of Materials (BOM)
- **Screen Name:** BOM Recipe Master
- **Route URL:** `/operations/bom/create`, `/operations/bom/{id}`, `/operations/bom`
- **Controller & Method:** `App\Http\Controllers\BOMController@create`, `@store`, `@edit`, `@show`
- **Blade File:** `resources/views/operations/bom/create.blade.php`, `resources/views/operations/bom/show.blade.php`
- **Database Tables:** `bill_of_materials`, `bom_items`, `products`, `items`

### B. Purpose
- Defines the exact standard raw material ingredients, packaging pouches, cartons, and estimated labor/machine hours required to manufacture a standard batch of finished product.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| BOM Code | Standard recipe identifier | Alphanumeric recipe code | BOM-CHICKEN-100G |
| Finished Item | Product being produced | Select finished SKU from Item Master | Chicken Masala 100g Pouch |
| Base Output Quantity | Batch yield quantity | Standard output quantity | 1,000 PKT |
| Component Items Grid | Raw materials required | Add rows: Item, Standard Qty, Scrap % | Chilli (35 KG), Coriander (40 KG), Pouch (1000 NOS) |
| Estimated Machine Hours | Expected grinding/packing time | Total machine run hours | 3.5 Hours |
| Estimated Direct Labor | Expected worker hours | Total labor man-hours | 8 Man-Hours |

---

## 15. Daily Operations Entry Screen (Manufacturing Run)

### A. Screen Identification
- **Sidebar Path:** Operations & Manufacturing -> Operations -> Daily Operations Entry
- **Screen Name:** Production Run Voucher
- **Route URL:** `/operations/daily/create`, `/operations/daily/{id}`, `/operations/daily`
- **Controller & Method:** `App\Http\Controllers\DailyOperationController@create`, `@store`, `@show`
- **Blade File:** `resources/views/operations/daily/create.blade.php`, `resources/views/operations/daily/show.blade.php`
- **Database Tables:** `daily_operations`, `daily_operation_items`, `stock_batches`, `stock_movements`, `account_transactions`

### B. Purpose
- Records actual factory production runs. Deducts raw material inventory, records machine and labor utilization, calculates actual unit manufacturing cost, and capitalizes new finished goods into the warehouse.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Operation Date | Date of production shift | Today's date | 2026-11-23 |
| Production Shift | Factory shift | Morning, Evening, Night | General Shift |
| BOM Formulation | Recipe template | Select approved BOM | BOM-CHICKEN-100G |
| Target Quantity | Planned production output | Quantity planned | 1,000 PKT |
| Actual Output Yield | Good units produced | Passed inspection count | 992 PKT |
| Scrap / Defective Yield | Rejected / torn pouches | Spoiled units | 8 PKT |
| Godown for Finished Goods | Storage destination | Warehouse where goods will be stacked | Central Finished Goods Godown |
| Raw Materials Consumed Grid | Actual materials used from warehouse | For each raw item: Godown, Batch, Actual Qty Consumed | Chilli: Batch B-01, 35.5 KG; Pouches: 1000 NOS |
| Machine Operating Hours | Machine run time | Running hours logged on meter | 3.8 Hours |
| Direct Labor Workers | Worker count | Total factory operators present | 4 Workers |

### D. What Happens After Saving?
1. Production voucher saved in `daily_operations`.
2. Raw materials deducted from source godown in `stock_batches` and outward logged in `stock_movements`.
3. New batch generated for finished goods (e.g., `BAT-20261123-01`) with capitalized unit cost:
   $$	ext{Unit Cost} = rac{	ext{Direct Material Cost} + 	ext{Direct Labor Cost} + 	ext{Allocated Machine Overhead} - 	ext{Scrap Recovery}}{	ext{Actual Output Yield}}$$
4. Balanced manufacturing journal voucher posted in General Ledger:
   - **Debit:** Finished Goods Inventory Asset (`10041 - Finished Goods Stock`)
   - **Debit:** Manufacturing Spoilage / Wastage Expense (`50090 - Production Loss`)
   - **Credit:** Raw Material Inventory Asset (`10040 - Raw Material Stock`)
   - **Credit:** Direct Labor Clearing Account (`50015 - Direct Labor Clearing`)
   - **Credit:** Factory Power / Machine Overhead Clearing (`50025 - Factory Overhead Clearing`)

---

## 16. Machine Maintenance Screen

### A. Screen Identification
- **Sidebar Path:** Operations & Manufacturing -> Operations -> Machine Maintenance
- **Screen Name:** Maintenance & Service Log
- **Route URL:** `/operations/maintenance/create`, `/operations/maintenance`
- **Controller & Method:** `App\Http\Controllers\MachineMaintenanceController@create`, `@store`
- **Blade File:** `resources/views/operations/maintenance/index.blade.php`
- **Database Tables:** `machine_maintenances`, `machines`, `account_transactions`

### B. Purpose
- Records machine servicing, oil replacement, spare parts usage, and downtime to prevent factory breakdowns.

---

## 17. Godown Stock Transfer Screen

### A. Screen Identification
- **Sidebar Path:** Inventory & Godown -> Inventory Management -> Godown Transfers
- **Screen Name:** Inter-Warehouse Stock Transfer
- **Route URL:** `/inventory/transfers/create`, `/inventory/transfers`
- **Controller & Method:** `App\Http\Controllers\InventoryTransferController@create`, `@store`, `@show`
- **Blade File:** `resources/views/inventory/transfers/create.blade.php`, `index.blade.php`
- **Database Tables:** `stock_transfers`, `stock_transfer_items`, `stock_batches`, `stock_movements`

### B. Purpose
- Relocates physical inventory from one warehouse to another (e.g., Factory Godown to City Retail Godown) without affecting company profit or overall stock valuation.

### C. Practical Usage Steps
1. Select **Source Godown** (where goods currently are).
2. Select **Destination Godown** (where goods are moving).
3. Select transfer date and optional delivery vehicle / tempo number.
4. Add items: select Item, specific Batch, and Transfer Quantity.
5. Click **Post Stock Transfer**.

### D. What Happens After Saving?
- Stock quantity deducted from Source Godown in `stock_batches` (Outward movement logged).
- Stock quantity added to Destination Godown under the same batch identifier (Inward movement logged).
- Total company stock quantity and total balance sheet asset value remain unchanged.

---

## 18. Stock Adjustment Screen

### A. Screen Identification
- **Sidebar Path:** Inventory & Godown -> Inventory Management -> Stock Adjustments
- **Screen Name:** Inventory Count Adjustment Voucher
- **Route URL:** `/inventory/adjustments/create`, `/inventory/adjustments`
- **Controller & Method:** `App\Http\Controllers\InventoryAdjustmentController@create`, `@store`
- **Blade File:** `resources/views/inventory/adjustments/create.blade.php`, `index.blade.php`
- **Database Tables:** `stock_adjustments`, `stock_batches`, `stock_movements`, `account_transactions`

### B. Purpose
- Aligns system stock counts with physical warehouse audit counts resulting from damage, expiry, theft, shrinkage, or unrecorded surplus.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Adjustment Date | Audit reconciliation date | Today's date | 2026-11-23 |
| Godown | Audited warehouse | Warehouse where variance occurred | Central Finished Goods Godown |
| Adjustment Type | Nature of discrepancy | Shortage, Surplus, Damaged, Expired | Shortage |
| Reason / Remarks | Justification for audit log | Explanation of discrepancy | Physical stock count variance during quarterly audit |
| Items Grid | Discrepant items | Item, Batch, Variance Quantity | Turmeric Powder 100g, Batch B-02, -15 PKT |

### D. Accounting Impact
- **Shortage / Damaged / Expired:**
  - **Debit:** Stock Adjustment / Loss Expense Account (`50080 - Inventory Variance Expense`)
  - **Credit:** Inventory Stock Asset (`10040 - Stock in Hand`)
- **Surplus / Found Stock:**
  - **Debit:** Inventory Stock Asset (`10040 - Stock in Hand`)
  - **Credit:** Stock Adjustment / Gain Income Account (`40050 - Inventory Variance Income`)

---

## 19. Bulk Opening Stock Entry Screen

### A. Screen Identification
- **Sidebar Path:** Inventory & Godown -> Inventory Management -> Bulk Opening Stock
- **Screen Name:** Initial Opening Inventory Setup Grid
- **Route URL:** `/inventory/opening-stock`
- **Controller & Method:** `App\Http\Controllers\OpeningStockController@index`, `@store`
- **Blade File:** `resources/views/inventory/opening_stock.blade.php`
- **Database Tables:** `items`, `stock_batches`, `stock_movements`, `account_transactions`

### B. Purpose
- High-efficiency batch spreadsheet entry screen used on Go-Live Day to input all opening quantities, unit valuation rates, and godown locations across all registered items simultaneously.

---

## 20. Purchase Order (PO) Screen

### A. Screen Identification
- **Sidebar Path:** Procurement -> Purchase Operations -> Purchase Orders (PO)
- **Screen Name:** Purchase Order Commitment
- **Route URL:** `/purchase/orders/create`, `/purchase/orders/{id}`, `/purchase/orders`
- **Controller & Method:** `App\Http\Controllers\PurchaseOrderController@create`, `@store`, `@show`
- **Blade File:** `resources/views/purchase/orders/create.blade.php`, `show.blade.php`
- **Database Tables:** `purchase_orders`, `purchase_order_items`, `suppliers`

### B. Purpose
- Formal commercial purchase order issued to an external supplier. Commits quantities and rates without altering warehouse inventory or general ledger accounts until goods are received.

---

## 21. Goods Receipt Note (GRN) Screen

### A. Screen Identification
- **Sidebar Path:** Procurement -> Purchase Operations -> Goods Receipts (GRN)
- **Screen Name:** Warehouse Goods Receipt Note
- **Route URL:** `/purchase/grns/create`, `/purchase/grns/{id}`, `/purchase/grns`
- **Controller & Method:** `App\Http\Controllers\PurchaseGRNController@create`, `@store`, `@show`
- **Blade File:** `resources/views/purchase/grns/create.blade.php`, `show.blade.php`
- **Database Tables:** `goods_receipt_notes`, `goods_receipt_items`, `stock_batches`, `stock_movements`

### B. Purpose
- Records physical truck delivery at the factory gate. Warehouse receiving clerk verifies delivered quantities against PO and checks for transit breakage before approving vendor bill.

---

## 22. Purchase Bill / Inward Screen

### A. Screen Identification
- **Sidebar Path:** Procurement -> Purchase Operations -> Purchase Bills / Inward
- **Screen Name:** Vendor Tax Invoice Entry
- **Route URL:** `/purchase/entries/create`, `/purchase/entries/{id}`, `/purchase/entries`
- **Controller & Method:** `App\Http\Controllers\PurchaseEntryController@create`, `@store`, `@show`
- **Blade File:** `resources/views/purchase/entries/create.blade.php`, `show.blade.php`
- **Database Tables:** `purchase_invoices`, `purchase_invoice_items`, `stock_batches`, `stock_movements`, `account_vouchers`, `account_transactions`, `suppliers`

### B. Purpose
- Records vendor's commercial tax invoice. Increases physical stock in warehouse, claims GST input tax credit (ITC), and increases liability to supplier in Accounts Payable.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Supplier | Vendor issuing invoice | Select supplier from dropdown | Malwa Agricultural Spices |
| Vendor Invoice Number | Supplier's tax bill number | Exact invoice string printed on bill | INV-2026-981 |
| Invoice Date | Supplier's bill date | Date of vendor invoice | 2026-11-20 |
| Receiving Godown | Target warehouse | Warehouse where goods were unloaded | Raw Materials Shed 1 |
| Payment Terms | Due days | Agreed credit period | 30 Days |
| Items Table | Billed items | Select Item, Batch No, Expiry Date, Quantity, Unit Rate, GST % | Whole Dry Red Chilli, 500 KG @ ₹180.00, GST 5% |

### D. What Happens After Saving?
1. Record inserted into `purchase_invoices`.
2. Physical stock increased in `stock_batches` and inward movement logged in `stock_movements`.
3. Balanced double-entry voucher posted in General Ledger:
   - **Debit:** Inventory Stock Asset (Pre-tax Base Cost: ₹90,000.00)
   - **Debit:** Input CGST Tax Asset (2.5%: ₹2,250.00)
   - **Debit:** Input SGST Tax Asset (2.5%: ₹2,250.00)
   - **Credit:** Supplier Subsidiary Ledger / Sundry Creditors (Total Bill: ₹94,500.00)

---

## 23. Purchase Return (Debit Note) Screen

### A. Screen Identification
- **Sidebar Path:** Procurement -> Purchase Operations -> Purchase Returns / Debit Notes
- **Screen Name:** Purchase Debit Note
- **Route URL:** `/purchase/returns/create`, `/purchase/returns`
- **Controller & Method:** `App\Http\Controllers\PurchaseReturnController@create`, `@store`
- **Blade File:** `resources/views/purchase/returns/create.blade.php`
- **Database Tables:** `purchase_returns`, `purchase_return_items`, `stock_batches`, `stock_movements`, `account_transactions`

### B. Purpose
- Reverses a purchase invoice when goods are rejected or returned to supplier, deducting warehouse stock and reducing the payable balance owed to the vendor.

---

## 24. Supplier Payment Voucher Screen

### A. Screen Identification
- **Sidebar Path:** Procurement -> Purchase Operations -> Supplier Payments
- **Screen Name:** Vendor Payment Voucher
- **Route URL:** `/purchase/payments/create`, `/purchase/payments`
- **Controller & Method:** `App\Http\Controllers\PurchasePaymentController@create`, `@store`
- **Blade File:** `resources/views/purchase/payments/create.blade.php`
- **Database Tables:** `account_vouchers`, `account_transactions`, `suppliers`

### B. Purpose
- Records cash, cheque, or bank NEFT/RTGS payments to vendors, reducing bank balance and clearing Accounts Payable liabilities.

### C. Accounting Impact
- **Debit:** Supplier Subsidiary Ledger / Sundry Creditors (Reduces liability)
- **Credit:** Bank Account or Cash in Hand (Reduces liquid assets)

---

## 25. Sales Order Screen

### A. Screen Identification
- **Sidebar Path:** Commercial & Sales -> Sales Operations -> Sales Orders (SO)
- **Screen Name:** Customer Sales Order
- **Route URL:** `/sales/orders/create`, `/sales/orders/{id}`, `/sales/orders`
- **Controller & Method:** `App\Http\Controllers\SalesOrderController@create`, `@store`, `@show`
- **Blade File:** `resources/views/sales/orders/create.blade.php`, `show.blade.php`
- **Database Tables:** `sales_orders`, `sales_order_items`, `customers`

### B. Purpose
- Books customer orders in advance. Allocates stock for picking without reducing warehouse balance or posting financial entries until invoiced.

---

## 26. Sales Invoice / Tax Bill Screen

### A. Screen Identification
- **Sidebar Path:** Commercial & Sales -> Sales Operations -> Sales Invoices
- **Screen Name:** Outward Tax Invoice
- **Route URL:** `/sales/invoices/create`, `/sales/invoices/{id}`, `/sales/invoices`
- **Controller & Method:** `App\Http\Controllers\SalesInvoiceController@create`, `@store`, `@show`
- **Blade File:** `resources/views/sales/invoices/create.blade.php`, `show.blade.php`
- **Database Tables:** `sales_invoices`, `sales_invoice_items`, `stock_batches`, `stock_movements`, `account_vouchers`, `account_transactions`, `customers`

### B. Purpose
- Core revenue engine. Issues GST tax invoice to buyer, relieves physical inventory from warehouse batches using FIFO, recognizes sales revenue, records output GST liability, and books customer receivables.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Customer | Buyer purchasing goods | Select customer from dropdown | Sai Kirana Stores |
| Invoice Date | Date of tax bill | Today's date | 2026-11-23 |
| Dispatch Godown | Warehouse shipping goods | Warehouse fulfilling order | Central Finished Goods Godown |
| Payment Due Date | Due date for payment | Automatically calculated based on customer credit days | 2026-12-08 (15 Days) |
| Items Table | Sold merchandise | Item, Batch, Billed Qty, Unit Selling Rate, Discount %, GST % | Chicken Masala 100g, 500 PKT @ ₹45.00, GST 5% |

### D. What Happens After Saving?
1. Record created in `sales_invoices` with sequential invoice number (`SAL-202611-0021`).
2. Physical stock relieved in `stock_batches` and outward movement logged in `stock_movements`.
3. Balanced double-entry voucher posted in General Ledger:
   - **Debit:** Customer Subsidiary Ledger / Sundry Debtors (Total Bill: ₹23,625.00)
   - **Credit:** Sales Revenue Account (`40010 - Sales Revenue`: ₹22,500.00)
   - **Credit:** Output CGST Tax Payable (`20050 - Output CGST`: ₹562.50)
   - **Credit:** Output SGST Tax Payable (`20060 - Output SGST`: ₹562.50)
   - *Cost of Goods Sold (COGS) Entry:*
   - **Debit:** Cost of Goods Sold (`50000 - Cost of Goods Sold`)
   - **Credit:** Finished Goods Inventory Asset (`10041 - Finished Goods Stock`)

---

## 27. Sales Return (Credit Note) Screen

### A. Screen Identification
- **Sidebar Path:** Commercial & Sales -> Sales Operations -> Sales Returns / Credit Notes
- **Screen Name:** Sales Credit Note
- **Route URL:** `/sales/returns/create`, `/sales/returns`
- **Controller & Method:** `App\Http\Controllers\SalesReturnController@create`, `@store`
- **Blade File:** `resources/views/sales/returns/create.blade.php`
- **Database Tables:** `sales_returns`, `sales_return_items`, `stock_batches`, `stock_movements`, `account_transactions`

### B. Purpose
- Issues credit note to customer for returned defective or expired goods. Restocks inventory and reduces customer receivable balance.

---

## 28. Sales Route Delivery & Dispatch Screen

### A. Screen Identification
- **Sidebar Path:** Commercial & Sales -> Sales Operations -> Dispatch & Route Delivery
- **Screen Name:** Vehicle Dispatch Trip Sheet
- **Route URL:** `/sales/deliveries/create`, `/sales/deliveries`
- **Controller & Method:** `App\Http\Controllers\SalesDeliveryController@create`, `@store`
- **Blade File:** `resources/views/sales/deliveries/index.blade.php`
- **Database Tables:** `sales_deliveries`, `sales_invoices`, `vehicles`, `routes`

### B. Purpose
- Assigns multiple pending customer invoices along a geographical route to a specific delivery vehicle and driver for organized dispatch.

---

## 29. Customer Collection Voucher Screen

### A. Screen Identification
- **Sidebar Path:** Commercial & Sales -> Sales Operations -> Customer Collections
- **Screen Name:** Customer Payment Receipt Voucher
- **Route URL:** `/sales/collections/create`, `/sales/collections`
- **Controller & Method:** `App\Http\Controllers\CustomerCollectionController@create`, `@store`
- **Blade File:** `resources/views/sales/collections/create.blade.php`
- **Database Tables:** `account_vouchers`, `account_transactions`, `customers`

### B. Purpose
- Records money collected from customers (Cash, Cheque, UPI, Bank Transfer), increasing liquid funds and clearing outstanding Accounts Receivable.

### C. Accounting Impact
- **Debit:** Bank Account or Cash in Hand (Increases liquid funds)
- **Credit:** Customer Subsidiary Ledger / Sundry Debtors (Clears receivable)

---

## 30. Chart of Accounts Screen

### A. Screen Identification
- **Sidebar Path:** Finance & Bookkeeping -> Double-Entry Accounts -> Chart of Accounts
- **Screen Name:** Chart of Accounts Master Tree
- **Route URL:** `/accounts/chart-of-accounts`
- **Controller & Method:** `App\Http\Controllers\AccountingController@chartOfAccounts`
- **Blade File:** `resources/views/accounts/chart_of_accounts.blade.php`
- **Database Tables:** `account_groups`, `account_ledgers`

### B. Purpose
- The master financial directory of all Asset, Liability, Equity, Revenue, and Expense accounts. Allows adding custom ledgers (e.g., "Electricity Expense", "Factory Rent", "HDFC Overdraft").

---

## 31. General Journal Voucher Screen

### A. Screen Identification
- **Sidebar Path:** Finance & Bookkeeping -> Double-Entry Accounts -> Journal Entries / Vouchers
- **Screen Name:** Double-Entry Journal Voucher
- **Route URL:** `/accounts/vouchers/create`, `/accounts/vouchers/{id}`, `/accounts/vouchers`
- **Controller & Method:** `App\Http\Controllers\AccountingController@createVoucher`, `@storeVoucher`, `@show`
- **Blade File:** `resources/views/accounts/vouchers/create.blade.php`, `show.blade.php`
- **Database Tables:** `account_vouchers`, `account_transactions`, `account_ledgers`

### B. Purpose
- Universal manual double-entry screen for non-routine accounting adjustments: posting monthly machinery depreciation, adjusting prepaid insurance, recording interest income, or correcting account misallocations.

### C. Field-by-Field Explanation
| Field | Meaning | What should I enter? | Example |
|---|---|---|---|
| Voucher Date | Date of transaction | Transaction date | 2026-11-23 |
| Reference / Memo | Audit description | Narration explaining the entry | Monthly depreciation on factory plant & machinery |
| Transaction Rows Grid | Debit and credit accounts | Select ledger, enter Debit amount or Credit amount | Line 1: Depreciation Expense (Dr: ₹12,000.00); Line 2: Accumulated Depreciation Machinery (Cr: ₹12,000.00) |

### D. System Validation Rule
- The screen strictly enforces mathematical balance:
  $$\sum 	ext{Debit Amounts} = \sum 	ext{Credit Amounts}$$
- If the difference exceeds ₹0.05, the ERP rejects the form with a validation alert, protecting ledger integrity.

---

## 32. Bank Reconciliation Screen

### A. Screen Identification
- **Sidebar Path:** Finance & Bookkeeping -> Double-Entry Accounts -> Bank Reconciliation
- **Screen Name:** Bank Passbook Reconciliation
- **Route URL:** `/accounts/bank-reconciliation`
- **Controller & Method:** `App\Http\Controllers\AccountingController@bankReconciliation`, `@updateBankReconciliation`
- **Blade File:** `resources/views/accounts/bank_reconciliation.blade.php`
- **Database Tables:** `account_transactions`, `account_ledgers`

### B. Purpose
- Reconciles ERP bank book balance against actual bank account statement by marking clearance dates for uncashed cheques and online transfers.

---

## 33. Petty Cash Entry Screen

### A. Screen Identification
- **Sidebar Path:** Finance & Bookkeeping -> Double-Entry Accounts -> Petty Cash
- **Screen Name:** Daily Petty Cash Register
- **Route URL:** `/accounts/petty-cash`
- **Controller & Method:** `App\Http\Controllers\AccountingController@pettyCash`, `@storePettyCash`
- **Blade File:** `resources/views/accounts/petty_cash.blade.php`
- **Database Tables:** `petty_cash_entries`, `account_transactions`

### B. Purpose
- Manages low-value daily office cash disbursements (office tea, stationery, minor courier, local transport) under an imprest petty cash system.

---

## 34. Company & Branch Profile Screen

### A. Screen Identification
- **Sidebar Path:** Enterprise Setup -> Companies & Branches
- **Screen Name:** Legal Entity Configuration
- **Route URL:** `/companies/{id}/edit`, `/companies`
- **Controller & Method:** `App\Http\Controllers\CompanyController@edit`, `@update`
- **Blade File:** `resources/views/companies/edit.blade.php`
- **Database Tables:** `companies`, `branches`, `company_settings`

### B. Purpose
- Configures company legal registration, registered address, GSTIN, PAN, functional currency symbol, invoice logo, and branch locations.

---

## 35. Module Activation & Toggles Screen

### A. Screen Identification
- **Sidebar Path:** Administration & Security -> Module Activation
- **Screen Name:** Enterprise Feature Toggles
- **Route URL:** `/settings/modules`
- **Controller & Method:** `App\Http\Controllers\ModuleController@index`, `@update`
- **Blade File:** `resources/views/settings/modules/index.blade.php`
- **Database Tables:** `company_settings`

### B. Purpose
- Dynamically enables or disables high-level ERP functional modules per company (e.g., turn OFF Manufacturing for a retail shop, turn ON Fleet Delivery for a distributor). Modifies sidebar menu visibility and route permissions instantly.

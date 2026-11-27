# COMMON ERP – DUMMY BUSINESS DATA & PRACTICAL EXERCISES
## 3 Complete Hands-On Industry Scenarios: Retail Boutique, Curry Masala Factory & Water Purification Plant

---

### Navigation Index
- [Industry Scenario 1: Retail Fashion Boutique (Shree Designer Studio)](#industry-scenario-1-retail-fashion-boutique-shree-designer-studio)
- [Industry Scenario 2: Food & Spice Manufacturing (Royal Spices Pvt Ltd)](#industry-scenario-2-food--spice-manufacturing-royal-spices-pvt-ltd)
- [Industry Scenario 3: Water Purification & Beverage Bottling (AquaPure Enterprises)](#industry-scenario-3-water-purification--beverage-bottling-aquapure-enterprises)
- [Practical Hands-On Learning Exercises](#practical-hands-on-learning-exercises)

---

## Industry Scenario 1: Retail Fashion Boutique (Shree Designer Studio)

### 1. Company Profile
- **Company Name:** Shree Designer Studio & Retail
- **Company Code:** SHREE
- **Business Vertical:** Retail Trading / Garments
- **Enabled Modules:** `items`, `sales`, `purchase`, `inventory`, `accounts` (Manufacturing & Fleet Delivery are DISABLED)
- **Currency:** INR (₹)
- **GSTIN:** 27AABCS1234D1Z8
- **Address:** Shop 14, City Center Mall, MG Road, Pune 411001

### 2. Master Data Setup

#### A. Warehouses / Godowns
1. **Main Showroom Floor** (`WH-SH-01`)
2. **Backroom Storage Godown** (`WH-BR-01`)

#### B. Units of Measure (UOM)
- `PCS` (Pieces - No decimals)

#### C. Suppliers
1. **Surat Silk Mills Pvt Ltd** (GSTIN: 24AAACS5566G1Z2, Address: Ring Road, Surat, Gujarat)
2. **Ludhiana Knitwear Apparel** (GSTIN: 03AABCL7788K1Z5, Address: Industrial Area, Ludhiana, Punjab)

#### D. Customers
1. **Walk-in Retail Cash Customers** (Unregistered / B2C)
2. **Pooja Deshmukh** (Bridal Client - Phone: +91 98220 12345)
3. **Anjali Sharma** (Loyalty Member - Phone: +91 98221 67890)

#### E. Item Master Catalog
| Item Code | Item Description | Category | Size / Variant | Buying Rate (₹) | Selling Rate (₹) | GST % | HSN |
|---|---|---|---|---|---|---|---|
| SAR-KAN-01 | Kanjeevaram Pure Silk Saree | Sarees | Free Size / Red-Gold | ₹6,500.00 | ₹12,500.00 | 5% | 5208 |
| SAR-BAN-01 | Banarasi Georgette Saree | Sarees | Free Size / Royal Blue | ₹4,200.00 | ₹8,200.00 | 5% | 5208 |
| SHT-LIN-M | Men Linen Casual Shirt | Shirts | M / Sky Blue | ₹950.00 | ₹1,850.00 | 5% | 6205 |
| SHT-LIN-L | Men Linen Casual Shirt | Shirts | L / White | ₹950.00 | ₹1,850.00 | 5% | 6205 |
| TRS-COT-32 | Chino Slim Cotton Trouser | Trousers | 32 / Khaki | ₹1,100.00 | ₹2,200.00 | 5% | 6203 |
| TRS-COT-34 | Chino Slim Cotton Trouser | Trousers | 34 / Olive | ₹1,100.00 | ₹2,200.00 | 5% | 6203 |

#### F. Opening Stock Onboarding
- `SAR-KAN-01`: 10 PCS @ ₹6,500 = ₹65,000.00 (Showroom)
- `SAR-BAN-01`: 15 PCS @ ₹4,200 = ₹63,000.00 (Showroom)
- `SHT-LIN-M`: 30 PCS @ ₹950 = ₹28,500.00 (Showroom)
- `TRS-COT-32`: 25 PCS @ ₹1,100 = ₹27,500.00 (Showroom)
- **Total Initial Opening Stock Value:** ₹1,84,000.00

### 3. Practical Retail Transactions

#### Transaction 1: Inter-State Purchase from Surat Silk Mills
- **Screen:** Procurement -> Purchase Bills / Inward
- **Invoice Ref:** `SSM-2026-441`
- **Items:**
  - 10 PCS `SAR-KAN-01` @ ₹6,500 = ₹65,000.00
  - 20 PCS `SAR-BAN-01` @ ₹4,200 = ₹84,000.00
- **Tax:** Inter-State IGST 5% on ₹1,49,000.00 = ₹7,450.00
- **Total Bill:** ₹1,56,450.00
- **Stock Impact:** Backroom Storage increases by 30 sarees.
- **Accounting Impact:**
  - Dr: Inventory - Merchandise (`10040`): ₹1,49,000.00
  - Dr: Input IGST Tax Asset (`10082`): ₹7,450.00
  - Cr: Surat Silk Mills (`20010`): ₹1,56,450.00

#### Transaction 2: Retail Boutique Sale to Bridal Customer Pooja Deshmukh
- **Screen:** Commercial & Sales -> Sales Invoices
- **Invoice Ref:** `SHR-SAL-001`
- **Items Sold:**
  - 2 PCS `SAR-KAN-01` @ ₹12,500 = ₹25,000.00
  - 1 PCS `SAR-BAN-01` @ ₹8,200 = ₹8,200.00
- **Tax:** Intra-State CGST 2.5% (₹830.00) + SGST 2.5% (₹830.00) = ₹1,660.00
- **Total Invoice Bill:** ₹34,860.00 (Paid via Credit Card / HDFC Bank Swipe Machine)
- **FIFO Cost of Goods Sold:** $(2 \times 6,500) + (1 \times 4,200) = ₹17,200.00$
- **Accounting Impact:**
  - Dr: HDFC Bank Current Account: ₹34,860.00
  - Cr: Sales Revenue: ₹33,200.00
  - Cr: Output CGST: ₹830.00
  - Cr: Output SGST: ₹830.00
  - Dr: Cost of Goods Sold (COGS): ₹17,200.00
  - Cr: Inventory - Merchandise: ₹17,200.00
- **Gross Profit Realized:** $33,200 - 17,200 = ₹16,000.00$ (Margin: 48.2%)

---

## Industry Scenario 2: Food & Spice Manufacturing (Royal Spices Pvt Ltd)

### 1. Company Profile
- **Company Name:** Royal Spices Private Limited
- **Company Code:** ROYAL
- **Business Vertical:** Food Processing & Packaging
- **Enabled Modules:** `items`, `sales`, `purchase`, `inventory`, `manufacturing`, `accounts`, `staff_hr`, `vehicle_route`
- **Currency:** INR (₹)
- **GSTIN:** 27AAACR1234F1Z5
- **Address:** Plot 42, MIDC Bhosari Industrial Estate, Pune 411026

### 2. Master Data Setup

#### A. Godowns
1. **Raw Spices Yard Shed 1** (`WH-SP-RAW`)
2. **Packaging Materials Store** (`WH-PKG`)
3. **Central Finished Goods Godown** (`WH-FG-MAIN`)

#### B. Units of Measure
- `KG` (Weight, 3 Decimals)
- `PKT` (Packets, 0 Decimals)
- `NOS` (Count, 0 Decimals)
- `BOX` (Carton Boxes, 0 Decimals - 1 BOX = 48 PKT)

#### C. Raw Materials & Packaging Items
- `RM-CHILLI-RAW`: Whole Guntur Red Chilli (UOM: KG, Rate: ₹180.00, HSN: 0904, GST: 5%)
- `RM-CORIANDER-RAW`: Coriander Seeds A-Grade (UOM: KG, Rate: ₹95.00, HSN: 0909, GST: 5%)
- `RM-TURMERIC-RAW`: Salem Turmeric Finger (UOM: KG, Rate: ₹130.00, HSN: 0910, GST: 5%)
- `PKG-POUCH-CHICKEN`: 100g Multi-layer Pouch (UOM: NOS, Rate: ₹2.20, GST: 18%)
- `PKG-CARTON-48`: 3-Ply Master Shipping Carton (UOM: NOS, Rate: ₹25.00, GST: 18%)

#### D. Finished Goods Items
- `FG-CHICKEN-100G`: Royal Chicken Masala 100g Pouch (Selling Rate: ₹48.00, GST: 5%)
- `FG-MEAT-200G`: Royal Meat Masala 200g Pouch (Selling Rate: ₹92.00, GST: 5%)

#### E. Bill of Materials (BOM Formulation)
**BOM Code:** `BOM-CHICKEN-100G`
- **Finished Item:** `FG-CHICKEN-100G`
- **Base Yield:** 1,000 PKT (100 KG Net Weight)
- **Raw Material Recipe:**
  - `RM-CHILLI-RAW`: 35.000 KG
  - `RM-CORIANDER-RAW`: 40.000 KG
  - `RM-TURMERIC-RAW`: 15.000 KG
  - Salt & Secret Condiments: 10.000 KG
  - `PKG-POUCH-CHICKEN`: 1,000 NOS
- **Standard Labor Hours:** 6 Man-Hours (3 Workers $\times$ 2 Hours)
- **Standard Machine Hours:** 2.5 Machine-Hours (Pulverizer Mill #1)

### 3. Practical Manufacturing Run Execution

#### Step 1: Procurement of Raw Spices
- Purchase 1,000 KG Chilli (@ ₹180) + 1,000 KG Coriander (@ ₹95) + 500 KG Turmeric (@ ₹130).
- Total Base Procurement: $1,80,000 + 95,000 + 65,000 = ₹3,40,000.00$.
- GST 5%: ₹17,000.00. Vendor: **Malwa Agricultural Spices**.

#### Step 2: Production Execution (Daily Operations Entry)
- **Screen:** Operations & Manufacturing -> Daily Operations Entry
- **Inputs Consumed:**
  - Chilli: 35.5 KG @ ₹180 = ₹6,390.00
  - Coriander: 40.0 KG @ ₹95 = ₹3,800.00
  - Turmeric: 15.0 KG @ ₹130 = ₹1,950.00
  - Packaging Pouches: 1,000 NOS @ ₹2.20 = ₹2,200.00
  - Total Direct Material Cost: ₹14,340.00
  - Allocated Direct Labor: 3 workers $\times$ 2 hrs @ ₹100/hr = ₹600.00
  - Allocated Machine Power: 2.5 hrs @ ₹120/hr = ₹300.00
  - **Total Batch Cost:** $14,340 + 600 + 300 = ₹15,240.00$
- **Yield Output:** 995 Sellable 100g Pouches; 5 defective pouches discarded.
- **Unit Production Cost Calculated:** $\frac{15,240}{995} = ₹15.316/\text{pouch}$.
- **Capitalized Finished Stock Added:** 995 PKT @ ₹15.316 = ₹15,240.00 in `Central Finished Goods Godown`.

#### Step 3: Wholesale Distributor Sale
- Sell 800 PKT `FG-CHICKEN-100G` to **Ganesh Wholesale Supermarket** at ₹38.00/PKT.
- Base Revenue: $800 \times 38 = ₹30,400.00$.
- GST 5%: ₹1,520.00. Total Bill: ₹31,920.00.
- COGS: $800 \times 15.316 = ₹12,253.00$.
- **Gross Profit Realized:** $30,400 - 12,253 = ₹18,147.00$ (Margin: 59.7%).

---

## Industry Scenario 3: Water Purification & Beverage Bottling (AquaPure Enterprises)

### 1. Company Profile
- **Company Name:** AquaPure Packaged Beverages
- **Company Code:** AQUA
- **Business Vertical:** Water Bottling & Distribution
- **Enabled Modules:** `items`, `sales`, `purchase`, `inventory`, `manufacturing`, `accounts`, `vehicle_route`
- **Currency:** INR (₹)
- **GSTIN:** 27AACPA9988B1Z1
- **Address:** Plot 18, Hinjawadi Phase 2, Pune 411057

### 2. Master Catalog & Products
- `RM-PET-PREFORM`: 20g PET Preforms (for blowing 1L bottles) - Rate: ₹1.40/NOS
- `RM-CAP-PLASTIC`: Plastic Screw Caps - Rate: ₹0.35/NOS
- `RM-LABEL-1L`: Shrink Wrap Labels - Rate: ₹0.25/NOS
- `RM-TREAT-CHEM`: Activated Carbon & Mineral Salts - Rate: ₹0.10/Litre
- `FG-WATER-1L`: AquaPure 1 Litre Packaged Drinking Water (Box of 12 Bottles)
- `FG-JAR-20L`: AquaPure 20 Litre Reusable Bubble Top Jar

### 3. Practical Operations Run

#### Production of 100 Boxes of 1 Litre Bottles (1,200 Bottles Total)
- Raw Materials: 1,200 Preforms (₹1,680) + 1,200 Caps (₹420) + 1,200 Labels (₹300) + Water Treatment Minerals (₹120) + 100 Corrugated Boxes (₹1,800).
- Direct Labor & RO Plant Power: ₹900.00.
- **Total Production Cost:** ₹5,220.00.
- **Yield:** 100 Boxes (1,200 bottles).
- **Unit Cost per Box (12 bottles):** ₹52.20 per box (or ₹4.35 per individual bottle).

#### Wholesale Sales & Route Delivery
- Deliver 50 Boxes to **Hotel Green Park** via Delivery Vehicle `MH-12-AQ-1100` on `Hinjawadi IT Route`.
- Selling Price: ₹110.00/Box.
- Total Sale: $50 \times 110 = ₹5,500.00$ (+ 18% GST ₹990.00 = ₹6,490.00).
- COGS: $50 \times 52.20 = ₹2,610.00$.
- **Gross Profit:** $5,500 - 2,610 = ₹2,890.00$ (Margin: 52.5%).

---

## Practical Hands-On Learning Exercises

Follow these step-by-step exercises in your Common ERP environment to verify end-to-end understanding:

### Exercise 1: Create a Vendor, Purchase Raw Materials, and Verify Stock
1. Create vendor `Gujarat Spices Hub` in **Stakeholders -> Suppliers**.
2. Create item `Black Pepper Whole` in **Master Data -> Items** with UOM `KG`, HSN `0904`, GST `5%`.
3. Post a Purchase Bill in **Procurement -> Purchase Bills / Inward** for 100 KG @ ₹450.00/KG.
4. Open **Inventory -> Current Stock** and confirm 100 KG exists in the warehouse.
5. Open **Reports -> Trial Balance** and confirm Sundry Creditors credit matches Inventory Asset debit.

### Exercise 2: Set up a BOM, Run a Daily Operations Production, and Inspect Unit Cost
1. Create a BOM for `Black Pepper Powder 100g` requiring 0.102 KG of whole pepper and 1 pouch.
2. In **Operations & Manufacturing -> Daily Operations Entry**, record production of 200 pouches.
3. Check the finished batch unit cost calculated by the ERP.
4. Verify in **Inventory -> Stock Movement Ledger** that whole pepper decreased by 20.4 KG and finished pouches increased by 200 PKT.

### Exercise 3: Issue a Customer Sales Invoice and Generate Financial Statements
1. In **Stakeholders -> Customers**, create `Mahesh Grocery Store`.
2. Issue a Sales Invoice in **Commercial & Sales -> Sales Invoices** for 150 pouches of Black Pepper Powder @ ₹85.00/PKT.
3. Verify the automatic stock reduction in warehouse.
4. Open **Reports -> Profit & Loss** and confirm the exact Gross Profit:
   $$\text{Sales Revenue} - \text{COGS} = \text{Gross Profit}$$
5. Open **Reports -> Balance Sheet** and confirm that Assets balance with Liabilities + Equity.

import os

OUTPUT_DIR = os.path.join("documentation", "COMMON_ERP_KNOWLEDGE_BASE")
os.makedirs(OUTPUT_DIR, exist_ok=True)

# ---------------------------------------------------------
# DOCUMENT 03: ACCOUNTING FOR DEVELOPERS
# ---------------------------------------------------------
print("Writing 03_ACCOUNTING_FOR_DEVELOPERS.md...")

doc3_content = """# COMMON ERP – ACCOUNTING FOR DEVELOPERS
## Complete Practical Accounting Guide for Software Engineers: Core Concepts, Debit/Credit Rules & Real Transactions

---

### Navigation Index
- [Part 1: The Golden Rules of Accounting for Software Developers](#part-1-the-golden-rules-of-accounting-for-software-developers)
- [Part 2: 40 Essential Accounting Concepts Explained Simply](#part-2-40-essential-accounting-concepts-explained-simply)
- [Part 3: Complete Real-World Accounting Walkthroughs with Full Debit/Credit Postings](#part-3-complete-real-world-accounting-walkthroughs-with-full-debitcredit-postings)

---

## Part 1: The Golden Rules of Accounting for Software Developers

As a software engineer, you understand databases, relations, and transactions. Accounting is simply a specialized, double-entry transactional ledger designed over 500 years ago by Luca Pacioli with one fundamental rule:

$$\\sum \\text{Debits} = \\sum \\text{Credits}$$

Every financial transaction must have at least two entries (one debit and one credit) that perfectly balance to zero.

### The Five Master Account Categories

All accounts in Common ERP belong to one of 5 fundamental groups:

| Account Type | What it represents | Normal Balance | To INCREASE it | To DECREASE it |
|---|---|---|---|---|
| **Asset** | Resources the company owns (Cash, Stock, Equipment, Debtors) | **Debit (Dr)** | **Debit** | **Credit** |
| **Expense** | Costs incurred to earn revenue (Rent, Wages, Electricity, COGS) | **Debit (Dr)** | **Debit** | **Credit** |
| **Liability** | Debts the company owes to external parties (Creditors, Loans, GST) | **Credit (Cr)** | **Credit** | **Debit** |
| **Equity / Capital** | Owner's investment and retained earnings in the business | **Credit (Cr)** | **Credit** | **Debit** |
| **Revenue / Income** | Money earned from selling products or services | **Credit (Cr)** | **Credit** | **Debit** |

### The Universal Accounting Equation

$$\\text{Assets} = \\text{Liabilities} + \\text{Equity} + (\\text{Revenue} - \\text{Expenses})$$

In Common ERP:
- **Balance Sheet** displays: $\\text{Assets} = \\text{Liabilities} + \\text{Equity} + \\text{Current Period Net Profit}$
- **Profit & Loss Account** displays: $\\text{Net Profit} = \\text{Total Revenue} - \\text{Total Expenses}$

---

## Part 2: 40 Essential Accounting Concepts Explained Simply

### 1. Assets
- **Meaning:** Everything of economic value that the company owns and can convert into cash.
- **Why needed:** Measures company wealth and capacity to produce goods.
- **Where in ERP:** Balance Sheet (Left/Top side), Chart of Accounts (`10000` series).
- **ERP Screen:** Accounts -> Chart of Accounts -> Assets.
- **Example:** Bank balance ₹2,50,000; Warehouse inventory ₹5,00,000; Delivery Van ₹4,00,000.
- **Accounting Effect:** Debit increases Assets; Credit decreases Assets.

### 2. Liabilities
- **Meaning:** Debts and legal obligations that the company owes to outside parties.
- **Why needed:** Shows how much of the company's assets are financed by creditors or lenders.
- **Where in ERP:** Balance Sheet, Chart of Accounts (`20000` series).
- **ERP Screen:** Accounts -> Chart of Accounts -> Liabilities.
- **Example:** Unpaid spice vendor bill ₹80,000; GST tax payable to government ₹15,000.
- **Accounting Effect:** Credit increases Liabilities; Debit decreases Liabilities.

### 3. Capital (Equity)
- **Meaning:** The net worth invested by the business owners/shareholders.
- **Why needed:** Represents the owners' financial claim after paying off all outside liabilities.
- **Where in ERP:** Balance Sheet (Equity section), Chart of Accounts (`30000` series).
- **ERP Screen:** Accounts -> Capital & Equity.
- **Example:** ₹10,00,000 deposited by founders into the business bank account on Day 1.

### 4. Income (Revenue)
- **Meaning:** Gross earnings generated from selling goods or delivering services.
- **Why needed:** Determines top-line commercial growth.
- **Where in ERP:** Profit & Loss Account (Credit side), Chart of Accounts (`40000` series).
- **ERP Screen:** Commercial & Sales -> Sales Invoices.
- **Example:** Selling 1,000 packets of curry masala at ₹50 each = ₹50,000 Sales Income.

### 5. Expenses
- **Meaning:** Costs incurred in running day-to-day business operations.
- **Why needed:** Deducted from Income to calculate true profitability.
- **Where in ERP:** Profit & Loss Account (Debit side), Chart of Accounts (`50000` series).
- **ERP Screen:** Accounts -> Journal Entries, Staff -> Payroll Processing.
- **Example:** Factory electricity bill ₹12,000; Monthly worker wages ₹45,000.

### 6. Revenue
- **Meaning:** Formal accounting term for primary operating income derived from the core business.
- **Where in ERP:** P&L Account header row "Operating Revenue".

### 7. Purchases
- **Meaning:** Procurement of raw materials, merchandise, and supplies required for production or resale.
- **Where in ERP:** Procurement -> Purchase Bills / Inward.
- **Example:** Purchasing 500 KG of whole dry red chilli for ₹90,000.

### 8. Sales
- **Meaning:** Outward commercial transaction transferring ownership of products to customers for payment.
- **Where in ERP:** Commercial & Sales -> Sales Invoices.

### 9. Accounts Receivable (Sundry Debtors)
- **Meaning:** Money legally owed to the company by customers who purchased on credit.
- **Why needed:** High receivables without cash collection can bankrupt a profitable business.
- **Where in ERP:** Balance Sheet -> Current Assets -> Sundry Debtors; Reports -> Customer Outstanding.
- **ERP Screen:** Stakeholders -> Customers & Pricing.

### 10. Accounts Payable (Sundry Creditors)
- **Meaning:** Money the company owes to suppliers for goods received on credit.
- **Where in ERP:** Balance Sheet -> Current Liabilities -> Sundry Creditors; Reports -> Supplier Outstanding.
- **ERP Screen:** Stakeholders -> Suppliers.

### 11. Debtors
- **Meaning:** Indian accounting term synonymous with Accounts Receivable (customers who owe you money).

### 12. Creditors
- **Meaning:** Indian accounting term synonymous with Accounts Payable (vendors you owe money to).

### 13. Cash Account (Cash in Hand)
- **Meaning:** Physical currency notes and coins kept in the office cash safe or shop register.
- **Where in ERP:** Accounts -> Ledgers -> Cash in Hand.

### 14. Bank Account
- **Meaning:** Commercial checking/current account balances held at institutional banks (e.g., HDFC, SBI).
- **Where in ERP:** Accounts -> Ledgers -> HDFC Current Account.

### 15. Ledger
- **Meaning:** A dedicated financial account containing a running record of all debits, credits, and the net balance for a specific entity or expense (e.g., "Electricity Expense Ledger").
- **Where in ERP:** Accounts -> Ledgers & Balances.

### 16. Chart of Accounts (COA)
- **Meaning:** The master catalog of all active ledgers organized into a tree hierarchy.
- **Where in ERP:** Accounts -> Chart of Accounts.

### 17. Account Groups
- **Meaning:** Categories that group related ledgers (e.g., "Current Assets", "Direct Expenses", "Bank Accounts").

### 18. Journal Entry (Voucher)
- **Meaning:** An individual accounting transaction consisting of balanced debits, credits, date, and narration.
- **Where in ERP:** Accounts -> Journal Entries / Vouchers.

### 19. Debit (Dr)
- **Meaning:** An entry on the left side of a ledger. Increases Assets and Expenses; Decreases Liabilities, Equity, and Revenue.

### 20. Credit (Cr)
- **Meaning:** An entry on the right side of a ledger. Increases Liabilities, Equity, and Revenue; Decreases Assets and Expenses.

### 21. Trial Balance
- **Meaning:** An internal auditing worksheet listing every ledger and its closing debit or credit balance. Total Debits MUST exactly equal Total Credits.
- **Where in ERP:** Reports -> Trial Balance.

### 22. Profit and Loss Account (Income Statement)
- **Meaning:** Financial report summarizing all Revenues and Expenses over a period to reveal Net Profit or Net Loss.
- **Where in ERP:** Reports -> Profit & Loss.

### 23. Balance Sheet
- **Meaning:** Financial snapshot showing what the business owns (Assets) and what it owes (Liabilities and Equity) on a specific calendar date.
- **Where in ERP:** Reports -> Balance Sheet.

### 24. Cash Flow Statement
- **Meaning:** Report tracking actual cash inflows and outflows across Operating, Investing, and Financing activities.
- **Where in ERP:** Reports -> Cash Flow Statement.

### 25. Opening Balance
- **Meaning:** The financial balance brought forward on the first day of an accounting period.

### 26. Closing Balance
- **Meaning:** The final net balance of an account at the end of an accounting period.

### 27. Outstanding Amount
- **Meaning:** The unpaid remainder of an invoice past its due date.

### 28. Advance Payment
- **Meaning:** Money paid or received before the delivery of goods or services.
- **Accounting Effect:** Customer advance is a Liability until invoiced; Supplier advance is an Asset until goods arrive.

### 29. Credit Note
- **Meaning:** A legal document issued to a customer reducing the invoice value due to goods returned, pricing error, or post-sale discount.
- **ERP Screen:** Commercial & Sales -> Sales Returns / Credit Notes.

### 30. Debit Note
- **Meaning:** A document sent to a supplier reducing the payable bill amount due to rejected goods or vendor overcharge.
- **ERP Screen:** Procurement -> Purchase Returns / Debit Notes.

### 31. GST Input (Input Tax Credit / ITC)
- **Meaning:** Goods & Services Tax paid by your company on purchases. Can be offset against tax collected on sales.
- **Accounting Status:** Current Asset (Receivable from government tax authorities).

### 32. GST Output
- **Meaning:** Goods & Services Tax collected by your company from customers on sales.
- **Accounting Status:** Current Liability (Payable to the government).

### 33. CGST (Central GST)
- **Meaning:** The 50% portion of GST collected on intra-state sales that goes to the Central Government.

### 34. SGST (State GST)
- **Meaning:** The 50% portion of GST collected on intra-state sales that goes to the State Government.

### 35. IGST (Integrated GST)
- **Meaning:** The 100% unified GST levied on inter-state sales (e.g., Maharashtra seller to Gujarat buyer).

### 36. Tax Liability
- **Meaning:** Net GST payable to the tax department:
  $$\\text{Net GST Liability} = \\text{Total Output GST} - \\text{Total Input GST Credit}$$

### 37. Stock Valuation
- **Meaning:** Calculating the total monetary worth of inventory sitting in warehouses (Common ERP uses Batch Actual Cost / FIFO).
- **Where in ERP:** Reports -> Stock Valuation.

### 38. Cost of Goods Sold (COGS)
- **Meaning:** The direct cost of producing or purchasing the physical goods that were sold during the period:
  $$\\text{COGS} = \\text{Opening Stock} + \\text{Purchases} + \\text{Direct Production Costs} - \\text{Closing Stock}$$

### 39. Inventory Valuation
- **Meaning:** Systematic accounting methods (FIFO, Moving Average) to determine how much inventory expense is recognized upon sale.

### 40. Depreciation
- **Meaning:** The systematic write-off of the cost of a long-term fixed asset (machinery, computers, vehicles) over its useful life due to wear and tear.
- **Accounting Effect:** Debit: Depreciation Expense (P&L); Credit: Accumulated Depreciation Asset (reduces asset book value).

---

## Part 3: Complete Real-World Accounting Walkthroughs with Full Debit/Credit Postings

### Scenario 1: Credit Purchase of Raw Materials with GST

#### Business Event
On 2026-11-20, **Royal Spices Pvt Ltd** purchases 500 KG of Whole Red Chilli at ₹180/KG from **Malwa Agricultural Spices**.
- Basic Purchase Amount: $500 \\times 180 = ₹90,000.00$
- GST Rate: 5% (Intra-state: CGST 2.5% + SGST 2.5%)
- CGST Input (2.5%): ₹2,250.00
- SGST Input (2.5%): ₹2,250.00
- Total Invoice Bill: ₹94,500.00 (Agreed Credit: 30 Days)

#### ERP Screen Used
Procurement -> Purchase Operations -> Purchase Bills / Inward

#### Accounting Journal Entries Generated
| Account Ledger | Account Group | Debit (₹) | Credit (₹) | Justification |
|---|---|---|---|---|
| **Inventory - Raw Materials** | Current Assets | 90,000.00 | - | Asset increases (goods arrived in godown at base cost) |
| **Input CGST Tax Asset** | Current Assets | 2,250.00 | - | Asset increases (tax credit reclaimable from govt) |
| **Input SGST Tax Asset** | Current Assets | 2,250.00 | - | Asset increases (tax credit reclaimable from govt) |
| **Malwa Agricultural Spices (Creditor)** | Current Liabilities | - | 94,500.00 | Liability increases (we owe supplier full bill amount) |
| **Total** | | **94,500.00** | **94,500.00** | **Balanced Entry** |

#### Financial Report Impact
- **Balance Sheet:** Total Assets increase by ₹94,500 (Inventory + GST ITC); Total Liabilities increase by ₹94,500 (Sundry Creditors).
- **Profit & Loss:** No immediate impact (inventory is an asset until consumed or sold).
- **Supplier Outstanding Report:** Malwa Spices shows ₹94,500 due in 30 days.

---

### Scenario 2: Supplier Payment via Bank Transfer

#### Business Event
On 2026-11-28, Royal Spices pays Malwa Agricultural Spices the full ₹94,500 via HDFC Bank NEFT.

#### ERP Screen Used
Procurement -> Purchase Operations -> Supplier Payments

#### Accounting Journal Entries Generated
| Account Ledger | Account Group | Debit (₹) | Credit (₹) | Justification |
|---|---|---|---|---|
| **Malwa Agricultural Spices (Creditor)** | Current Liabilities | 94,500.00 | - | Liability decreases (debt is cleared) |
| **HDFC Current Bank Account** | Current Assets | - | 94,500.00 | Asset decreases (cash left bank account) |
| **Total** | | **94,500.00** | **94,500.00** | **Balanced Entry** |

#### Financial Report Impact
- **Balance Sheet:** Current Liabilities decrease by ₹94,500; Current Assets (Bank) decrease by ₹94,500.
- **Supplier Outstanding Report:** Malwa Spices balance drops to ₹0.00.

---

### Scenario 3: Manufacturing Capitalization & Raw Material Consumption

#### Business Event
On 2026-11-22, the factory grinds and packs 500 KG of raw chilli into finished 500g pouches:
- Raw Chilli Consumed: 500 KG @ ₹180 = ₹90,000.00
- Packaging Pouches Consumed: 1,000 Pouches @ ₹3 = ₹3,000.00
- Direct Factory Labor Allocated: 4 workers $\\times$ 6 hrs = ₹2,400.00
- Machine Power & Overhead Allocated: 6 machine hrs = ₹1,800.00
- Total Production Cost: $90,000 + 3,000 + 2,400 + 1,800 = ₹97,200.00$
- Output Yield: 990 Sellable Pouches; 10 pouches spoiled during packaging test (scrap cost: ₹972.00).
- Capitalized Finished Goods Value: $990 \\times 97.20 = ₹96,228.00$

#### ERP Screen Used
Operations & Manufacturing -> Operations -> Daily Operations Entry

#### Accounting Journal Entries Generated
| Account Ledger | Account Group | Debit (₹) | Credit (₹) | Justification |
|---|---|---|---|---|
| **Inventory - Finished Goods** | Current Assets | 96,228.00 | - | New sellable inventory capitalized into godown |
| **Manufacturing Spoilage Expense** | Direct Expenses | 972.00 | - | Loss recognized on spoiled test pouches |
| **Inventory - Raw Materials** | Current Assets | - | 90,000.00 | Raw chilli stock deducted from warehouse |
| **Inventory - Packaging Supplies** | Current Assets | - | 3,000.00 | Plastic pouches deducted from warehouse |
| **Direct Labor Clearing Account** | Expense Clearing | - | 2,400.00 | Labor cost capitalized into finished stock |
| **Factory Overhead Clearing Account** | Expense Clearing | - | 1,800.00 | Machine power cost capitalized into finished stock |
| **Total** | | **97,200.00** | **97,200.00** | **Balanced Entry** |

---

### Scenario 4: Credit Sale of Finished Goods with GST

#### Business Event
On 2026-11-24, Royal Spices sells 500 pouches of Chilli Powder 500g to **Sai Kirana Stores** at ₹140/pouch.
- Base Sales Amount: $500 \\times 140 = ₹70,000.00$
- GST Rate: 5% (Intra-state: CGST 2.5% + SGST 2.5%)
- CGST Output (2.5%): ₹1,750.00
- SGST Output (2.5%): ₹1,750.00
- Total Invoice Bill: ₹73,500.00 (Customer Credit: 15 Days)
- Inventory Relieved: 500 pouches @ unit cost ₹97.20 = ₹48,600.00

#### ERP Screen Used
Commercial & Sales -> Sales Operations -> Sales Invoices

#### Accounting Journal Entries Generated
| Account Ledger | Account Group | Debit (₹) | Credit (₹) | Justification |
|---|---|---|---|---|
| **Sai Kirana Stores (Debtor)** | Current Assets | 73,500.00 | - | Asset increases (customer owes us full bill amount) |
| **Sales Revenue Account** | Revenue / Income | - | 70,000.00 | Income recognized (pre-tax sales value) |
| **Output CGST Tax Liability** | Current Liabilities | - | 1,750.00 | Tax debt owed to Central Government |
| **Output SGST Tax Liability** | Current Liabilities | - | 1,750.00 | Tax debt owed to State Government |
| **Cost of Goods Sold (COGS)** | Direct Expenses | 48,600.00 | - | Expense recognized for physical inventory sold |
| **Inventory - Finished Goods** | Current Assets | - | 48,600.00 | Finished goods stock asset relieved from warehouse |
| **Total** | | **1,22,100.00** | **1,22,100.00** | **Balanced Dual Entry** |

#### Financial Report Impact
- **Profit & Loss Account:**
  - Sales Revenue: ₹70,000.00
  - Less COGS: (₹48,600.00)
  - **Gross Profit:** ₹21,400.00 (Gross Margin: 30.57%)
- **Balance Sheet:**
  - Sundry Debtors increases by ₹73,500.00
  - Finished Goods Stock decreases by ₹48,600.00
  - GST Output Liability increases by ₹3,500.00
  - Net Profit (Equity) increases by ₹21,400.00
  - Accounting Equation strictly holds: $\\Delta \\text{Assets} (₹24,900) = \\Delta \\text{Liabilities} (₹3,500) + \\Delta \\text{Equity} (₹21,400)$

---

### Scenario 5: Customer Collection Receipt

#### Business Event
On 2026-11-27, Sai Kirana Stores pays ₹73,500 via UPI into the company's HDFC Bank Account.

#### ERP Screen Used
Commercial & Sales -> Sales Operations -> Customer Collections

#### Accounting Journal Entries Generated
| Account Ledger | Account Group | Debit (₹) | Credit (₹) | Justification |
|---|---|---|---|---|
| **HDFC Current Bank Account** | Current Assets | 73,500.00 | - | Liquid asset increases (funds deposited in bank) |
| **Sai Kirana Stores (Debtor)** | Current Assets | - | 73,500.00 | Asset decreases (customer receivable cleared) |
| **Total** | | **73,500.00** | **73,500.00** | **Balanced Entry** |

---

### Scenario 6: Monthly Payroll Disbursal

#### Business Event
On 2026-11-30, monthly salaries are processed:
- Gross Basic Salaries: ₹1,20,000.00
- Employee Provident Fund (PF) Deducted: ₹14,400.00
- Employee State Insurance (ESI) Deducted: ₹900.00
- Staff Salary Loan Recovery: ₹5,000.00
- Net Salary Payable to Employees: $1,20,000 - 14,400 - 900 - 5,000 = ₹99,700.00$

#### ERP Screen Used
Staff & Human Resources -> Staff & Payroll -> Payroll Processing

#### Accounting Journal Entries Generated
| Account Ledger | Account Group | Debit (₹) | Credit (₹) | Justification |
|---|---|---|---|---|
| **Salaries & Wages Expense** | Indirect Expenses | 1,20,000.00 | - | P&L expense recognized for entire gross salary |
| **PF Payable Account** | Current Liabilities | - | 14,400.00 | Statutory liability to EPFO |
| **ESI Payable Account** | Current Liabilities | - | 900.00 | Statutory liability to ESIC |
| **Staff Advance Asset** | Current Assets | - | 5,000.00 | Recovery of previous employee loan asset |
| **Salaries Payable (Bank Clearing)** | Current Liabilities | - | 99,700.00 | Net amount owed to staff on payout day |
| **Total** | | **1,20,000.00** | **1,20,000.00** | **Balanced Entry** |

---

### Scenario 7: Fixed Asset Depreciation Posting

#### Business Event
At the end of the month, factory machinery worth ₹12,00,000 is depreciated at 10% per annum (Straight Line Method):
$$\\text{Monthly Depreciation} = \\frac{12,00,000 \\times 10\\%}{12} = ₹10,000.00$$

#### ERP Screen Used
Finance & Bookkeeping -> Double-Entry Accounts -> Journal Entries / Vouchers

#### Accounting Journal Entries Generated
| Account Ledger | Account Group | Debit (₹) | Credit (₹) | Justification |
|---|---|---|---|---|
| **Depreciation Expense** | Indirect Expenses | 10,000.00 | - | Non-cash expense reduces current period profit |
| **Accumulated Depreciation - Plant & Machinery** | Fixed Asset Contra | - | 10,000.00 | Reduces the net carrying book value of machinery |
| **Total** | | **10,000.00** | **10,000.00** | **Balanced Entry** |

---

### Scenario 8: Physical Stock Shortage Adjustment

#### Business Event
A physical godown audit discovers that 10 packets of Chicken Masala 100g were water-damaged and destroyed:
- Quantity Damaged: 10 PKT
- Batch Unit Valuation Rate: ₹45.00
- Total Inventory Loss: ₹450.00

#### ERP Screen Used
Inventory & Godown -> Inventory Management -> Stock Adjustments

#### Accounting Journal Entries Generated
| Account Ledger | Account Group | Debit (₹) | Credit (₹) | Justification |
|---|---|---|---|---|
| **Stock Damage / Inventory Loss Expense** | Direct Expenses | 450.00 | - | P&L expense recognized for damaged inventory |
| **Inventory - Finished Goods Stock** | Current Assets | - | 450.00 | Warehouse physical stock asset written off |
| **Total** | | **450.00** | **450.00** | **Balanced Entry** |
"""

with open(os.path.join(OUTPUT_DIR, "03_ACCOUNTING_FOR_DEVELOPERS.md"), "w", encoding="utf-8") as f:
    f.write(doc3_content)

print("03_ACCOUNTING_FOR_DEVELOPERS.md written successfully.")

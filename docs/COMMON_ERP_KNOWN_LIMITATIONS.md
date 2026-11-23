# Common ERP – Known Limitations & Operational Boundaries

## 1. Purpose of this Document

In accordance with the Project Freeze and Strict Stopping Rules, this document outlines known architectural boundaries, non-critical limitations, and operational trade-offs of the completed Common ERP platform. Documenting these items provides transparency for system administrators, auditors, and deployment teams without indefinitely extending development cycles.

---

## 2. Accounting & Financial Limitations

### 2.1 Single-Currency Operating Ledger per Company
- **Current Behavior:** Each tenant company operates in a single primary currency (default `INR`).
- **Limitation:** There is no automated multi-currency revaluation engine or foreign exchange gain/loss accrual on open foreign currency receivables or payables.
- **Operational Recommendation:** International export/import bills should be entered in the tenant base currency using the realized exchange rate at the transaction date, with bank adjustment entries for forex differences.

### 2.2 Manual Fixed Asset Depreciation Run
- **Current Behavior:** Fixed assets and capital investments are capitalized into balance sheet asset accounts upon purchase or contribution.
- **Limitation:** The system does not feature an automated background cron job that periodically posts monthly straight-line or written-down-value (WDV) depreciation vouchers.
- **Operational Recommendation:** Accounting personnel should post a standard year-end or month-end depreciation Journal Voucher debiting `Depreciation Expense` and crediting `Accumulated Depreciation`.

### 2.3 Tax Regime Specializations
- **Current Behavior:** Fully models Indian GST (CGST, SGST, IGST), tax invoice numbering, and HSN/SAC codes.
- **Limitation:** International tax regimes (such as GCC VAT, US State Sales Tax with Avalara, or European EU-VAT reverse charge rules) are not natively parameterized out-of-the-box.
- **Operational Recommendation:** Configure tax groups and tax ledger accounts accordingly for domestic operations within the jurisdiction.

---

## 3. Inventory & Manufacturing Limitations

### 3.1 Batch-Specific vs Dynamic FIFO Queue Peeling
- **Current Behavior:** Inventory consumption requires specifying or selecting the source batch number (or uses the default batch). Landed cost is calculated at the batch level.
- **Limitation:** The inventory engine does not automatically peel across multiple older batches when a single dispatch line exceeds the quantity of the oldest batch.
- **Operational Recommendation:** Operators should split dispatch lines across individual batches when fulfilling large orders that span multiple lots.

### 3.2 Single-Stage BOM Execution
- **Current Behavior:** Daily operations model raw material consumption, craftsman labor, overhead absorption, and finished goods capitalization in a single process step.
- **Limitation:** Complex multi-level nested bills of materials (where Sub-Assembly A produces Component B, which feeds Assembly C) must be recorded as sequential daily operations rather than a single nested trigger.
- **Operational Recommendation:** Record sub-assembly operations first, inward the intermediate component into a semi-finished goods godown, and consume it in the subsequent final assembly operation.

### 3.3 Negative Stock Override
- **Current Behavior:** Negative inventory is strictly prohibited by default; dispatches failing stock availability throw exceptions.
- **Limitation:** If `$allowNegative = true` is enabled via configuration, physical stock reconciliations may show negative batch balances until inward entries catch up.

---

## 4. System & Integration Boundaries

### 4.1 Synchronous Document & PDF Generation
- **Current Behavior:** Printable invoices, salary payslips, and ledger statements are rendered synchronously via Blade and DomPDF within the HTTP request cycle.
- **Limitation:** Batch generation of 1,000+ payslips or bulk invoices simultaneously may cause request timeouts under constrained server memory.
- **Operational Recommendation:** Process bulk exports in batches or increase PHP `max_execution_time` and `memory_limit` (minimum 256M recommended).

### 4.2 Offline POS Counter Functionality
- **Current Behavior:** Point-of-Sale (POS) and counter sales require an active HTTP connection to the Laravel backend.
- **Limitation:** There is no offline Progressive Web App (PWA) local SQLite synchronization for sales counters experiencing intermittent internet connectivity.

### 4.3 Direct Bank Gateway Integration
- **Current Behavior:** Bank Reconciliation (BRS) is performed via standard CSV statement upload, manual ledger matching, and clearance tracking.
- **Limitation:** Direct Open Banking / NetBanking API protocols (such as RazorpayX, Cashfree, or ICICI Eazypay automated webhooks) are not natively baked in.

---

## 5. Security & Access Boundaries

### 5.1 Financial Year Lock Scope
- **Current Behavior:** Locking a financial year (`is_closed = true`) strictly rejects Purchases, Sales Invoices, Journal Vouchers, and Manufacturing Reversals attempted via the web interface and application API.
- **Limitation:** Direct database administrators with MySQL CLI / phpMyAdmin access can execute raw SQL updates on tables regardless of application lock state.
- **Operational Recommendation:** Restrict direct production MySQL root access and enforce audit logging on database server instances.

---

## 6. Summary Evaluation

None of the above limitations prevent reliable, day-to-day commercial and operational usage of Common ERP. They represent standard scope boundaries for an integrated Laravel ERP solution and are fully suitable for controlled enterprise adoption.

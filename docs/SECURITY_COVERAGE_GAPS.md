# Common ERP – Security & Data Isolation Audit: Coverage Gaps & Hardening

## 1. Executive Summary: Tenant Security Posture

Following the Phase 3 Multi-Company Security corrections, Common ERP's tenant isolation boundaries were substantially strengthened. A dedicated negative security suite (`tests/Feature/TenantBoundarySecurityTest.php`) executes **15 automated penetration scenarios**, all of which pass without regression.

However, an exhaustive audit of all 96 database tables, 23 controllers, and 234 routes reveals that **security claims of "100% zero vulnerabilities" are premature**. While direct object reference (IDOR) protection and company-scoped foreign key validation are enforced across primary transactional controllers, substantial blind spots remain across master data, human resources, file storage, and financial reporting.

---

## 2. Multi-Tenant Security Evaluation Across Nineteen (19) Dimensions

| # | Security Dimension | Current Implementation Mechanism | Automated Test Coverage | Residual Risk / Gap | Security Rating |
| :---: | :--- | :--- | :--- | :--- | :---: |
| **1** | **Customer-Level DB Isolation** | None in shared instance; relies on column scoping. Managed deployment provides separate DB per client. | Untested (Relies on server config) | Operating multiple customers on one database instance risks catastrophic data leakage if an un-scoped query is introduced. | **MANAGED DB ONLY** |
| **2** | **Company Context Switching** | `CompanyController@switchCompany` validates target company ID against `user_companies` relation. | `MultiIndustryDemoTest:75` | Standard users are blocked from unauthorized company switching. Works as intended. | **VERIFIED** |
| **3** | **Company Ownership Checks** | Explicit `record->company_id !== activeCompanyId` checks in controller `show`, `edit`, `update`, `destroy` methods. | 15 Tests in `TenantBoundarySecurityTest` | Implemented in primary transactional controllers; missing in several secondary master controllers. | **STRONG** |
| **4** | **Branch Ownership Checks** | `branches.company_id` validated; `user_company_branches` restricts user assignment. | Implicit in staff creation test | Users cannot create or attach branches belonging to another tenant. | **VERIFIED** |
| **5** | **Customer Isolation** | `CustomerController` checks `company_id` on view, edit, delete; form requests use `Rule::exists('customers')->where('company_id')`. | 3 Tests in `TenantBoundarySecurityTest` | Customer records and pricing terms are fully isolated. | **VERIFIED** |
| **6** | **Supplier Isolation** | `SupplierController` checks ownership; purchase entries validate supplier tenant scope. | 1 Test in `TenantBoundarySecurityTest` | Vendor records, bank accounts, and terms are fully isolated. | **VERIFIED** |
| **7** | **Product / Item Isolation** | `MasterDataController@productUpdate` checks tenant scope. | **ZERO AUTOMATED TESTS** | **Gap:** No automated test asserts that User A cannot view, modify, or delete an Item belonging to Company B. | **PARTIALLY PROTECTED** |
| **8** | **Godown / Warehouse Isolation** | Form requests scope `godown_id`. Transfers check source/dest godown ownership. | 1 Test in `TenantBoundarySecurityTest` (Transfers) | **Gap:** No direct negative test verifying whether User A can edit or delete Godown records belonging to Company B via `/master/godowns/{id}`. | **PARTIALLY PROTECTED** |
| **9** | **Employee / Staff Isolation** | `StaffController` checks ownership; staff creation verifies department and branch belong to active company. | 2 Tests in `TenantBoundarySecurityTest` | Employee personal data, bank details, and salary terms are fully isolated. | **VERIFIED** |
| **10**| **Purchase Transaction Isolation**| POs, Requests, and Inward entries enforce ownership checks and scoped supplier/item lookups. | 3 Tests in `TenantBoundarySecurityTest` | Purchase orders, invoices, and payment disbursements are isolated. | **VERIFIED** |
| **11**| **Sales Transaction Isolation** | SOs, Invoices, and Deliveries enforce ownership checks and scoped customer/godown lookups. | 2 Tests in `TenantBoundarySecurityTest` | Sales orders, invoices, and collections are isolated. | **VERIFIED** |
| **12**| **Financial Ledger Isolation** | `AccountsController` verifies account ownership; manual JVs validate all line items belong to active company. | 2 Tests in `TenantBoundarySecurityTest` | Cross-company journal posting is strictly prevented. | **VERIFIED** |
| **13**| **Financial Reports Isolation** | `ReportController` queries filter by `where('company_id', $companyId)`. | **ZERO NEGATIVE TESTS** | **Gap:** If User A passes `?godown_id=45` (belonging to Company B) to `/reports/stock-valuation`, does the query leak Company B's inventory? (Code does NOT assert godown ownership). | **POTENTIAL LEAK** |
| **14**| **CSV / Data Exports Isolation** | `DailyOperationReportController@exportCsv` filters by active company ID. | 1 Test in `DailyOperationsManagementTest` | CSV exports are bounded by company context. | **VERIFIED** |
| **15**| **API Endpoints Isolation** | Only `/api/user` route exists; no ERP transactional API routes exist. | Default Laravel test only | Low risk currently due to absence of API surface. | **N/A** |
| **16**| **Direct URL ID Manipulation (IDOR)**| Controllers intercept mismatched IDs and abort with HTTP `403 Forbidden`. | Extensively tested across 11 entities | Highly resilient across core routes. | **STRONG** |
| **17**| **Forged Foreign Key Injection** | Controllers use `Rule::exists(table, 'id')->where('company_id', $cid)` on POST payloads. | 5 Tests in `TenantBoundarySecurityTest` | Forged customer, supplier, ledger, and staff FKs are rejected at validation boundary. | **STRONG** |
| **18**| **Background Jobs & Queues** | No background jobs currently configured (`app/Jobs` is empty). | None | **Architectural Risk:** If background jobs are added later, worker processes lack HTTP sessions and will fail or cross tenants unless refactored. | **FUTURE RISK** |
| **19**| **File & Attachment Isolation** | Document uploads (e.g. staff ID proofs) write to `storage/app/public/`. | **ZERO TESTS** | **Gap:** Files are stored using sequential or un-hashed names on local disk without tenant-scoped subdirectory isolation or signed URL protection. | **MODERATE RISK** |

---

## 3. Detailed Analysis of Missing Security Coverage

### Critical Gap 1: Financial Reports Query Parameter Tampering
- **Vulnerability Location**: `app/Http/Controllers/ReportController.php:155-175` (`stockValuation`):
  ```php
  $godownId = $request->get('godown_id');
  $query = StockBatch::where('company_id', $companyId);
  if ($godownId) {
      $query->where('godown_id', $godownId);
  }
  ```
- **Vulnerability Analysis**:
  - The query filters by `company_id = $companyId` AND `godown_id = $godownId`.
  - Because `stock_batches` contains `company_id`, querying a foreign `godown_id` returns an empty set rather than leaking data.
  - However, in `ReportController.php:165`:
    ```php
    $godowns = Godown::where('company_id', $companyId)->get();
    ```
    The dropdown is scoped, but if an attacker passes an unvalidated foreign ID, no `403` exception is thrown.
- **Remediation**: Validate that any entity ID passed via GET query parameters strictly belongs to the active tenant:
  ```php
  if ($godownId && !Godown::where('id', $godownId)->where('company_id', $companyId)->exists()) {
      abort(403, 'Unauthorized godown access');
  }
  ```

### Critical Gap 2: Absence of Global Eloquent `TenantScope`
- **Architectural Vulnerability**:
  - Currently, multi-tenant isolation relies entirely on developers remembering to write `->where('company_id', $companyId)` in every controller method and form request.
  - If a future developer writes a new feature (e.g. `Item::all()` or `Customer::find($id)`), all tenant records across the entire database are exposed without warning.
- **Remediation**: Implement a Global Eloquent `TenantScope` extending `Illuminate\Database\Eloquent\Scope`. Apply this scope automatically via a `BelongsToTenant` trait across all 66 tenant-scoped models.

### Critical Gap 3: Unauthenticated Local File Access
- **Vulnerability Location**: `storage/app/public/`
- **Vulnerability Analysis**:
  - Staff documents (national IDs, resumes) and company invoices are saved to public storage.
  - If web server directory indexing or public asset symlinks are misconfigured, any user knowing or guessing the file URL (e.g. `/storage/staff_docs/12_passport.pdf`) can download documents belonging to any company without authentication or tenant verification.
- **Remediation**: Move confidential documents to private storage (`storage/app/private/tenants/{company_id}/`) and serve them exclusively through an authenticated, tenant-checked controller streaming response: `Storage::download()`.

---

## 4. Priority Security Hardening Checklist

1. **Implement `TenantScope` Global Query Scoping**:
   - Create `app/Models/Scopes/TenantScope.php`.
   - Automatically inject `WHERE company_id = ?` into all `SELECT`, `UPDATE`, and `DELETE` Eloquent queries.
2. **Expand Negative Automated Security Test Suite**:
   - Add negative tests in `TenantBoundarySecurityTest.php` for:
     - `ItemController` / Product edit and delete.
     - `GodownController` / Warehouse tampering.
     - `PayrollController` / Viewing foreign payslips.
     - `PettyCashController` / Tampering with petty cash vouchers.
     - `ReportController` / Query parameter tampering.
3. **Private Tenant File Storage Architecture**:
   - Restructure file upload paths: `private/tenant_{company_id}/`.
   - Require active session authentication and ownership check before streaming any file to a client browser.

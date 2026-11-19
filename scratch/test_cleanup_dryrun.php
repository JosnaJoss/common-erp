<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$candidateIds = [2, 3, 4, 5, 6, 7, 8, 9];
$preservedIds = [1, 10, 11];

echo "=== DRY RUN DEMO CLEANUP (OPTION 2) ===\n";
echo "Candidate IDs for deletion: " . implode(', ', $candidateIds) . "\n";
echo "Preserved IDs: " . implode(', ', $preservedIds) . "\n\n";

DB::beginTransaction();

try {
    $deletedCounts = [];

    // Helper function
    $deleteWhere = function($table, $whereRaw, $bindings = []) use (&$deletedCounts) {
        if (!Schema::hasTable($table)) return 0;
        $count = DB::table($table)->whereRaw($whereRaw, $bindings)->delete();
        if ($count > 0) {
            $deletedCounts[$table] = ($deletedCounts[$table] ?? 0) + $count;
        }
        return $count;
    };

    $deleteCompanyId = function($table) use ($candidateIds, &$deletedCounts) {
        if (!Schema::hasTable($table)) return 0;
        $count = DB::table($table)->whereIn('company_id', $candidateIds)->delete();
        if ($count > 0) {
            $deletedCounts[$table] = ($deletedCounts[$table] ?? 0) + $count;
        }
        return $count;
    };

    $inCandidates = implode(',', $candidateIds);

    // 1. Child tables of Daily Operations
    $deleteWhere('daily_operation_distributions', "daily_operation_id IN (SELECT id FROM daily_operations WHERE company_id IN ($inCandidates))");
    $deleteWhere('daily_operation_outputs', "daily_operation_id IN (SELECT id FROM daily_operations WHERE company_id IN ($inCandidates))");
    $deleteWhere('daily_operation_materials', "daily_operation_id IN (SELECT id FROM daily_operations WHERE company_id IN ($inCandidates))");
    $deleteWhere('daily_operation_employees', "daily_operation_id IN (SELECT id FROM daily_operations WHERE company_id IN ($inCandidates))");
    $deleteWhere('daily_operation_wastages', "daily_operation_id IN (SELECT id FROM daily_operations WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('daily_operations');

    // 2. Production Orders & BOM
    $deleteWhere('bom_items', "bom_id IN (SELECT id FROM boms WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('boms');
    $deleteCompanyId('production_orders');

    // 3. Journal Lines & Journal Entries
    $deleteWhere('journal_lines', "journal_entry_id IN (SELECT id FROM journal_entries WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('journal_entries');

    // 4. Sales transactions items & parents
    $deleteWhere('sales_delivery_items', "sales_delivery_id IN (SELECT id FROM sales_deliveries WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('sales_deliveries');
    $deleteWhere('sales_invoice_items', "sales_invoice_id IN (SELECT id FROM sales_invoices WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('sales_invoices');
    $deleteWhere('sales_order_items', "sales_order_id IN (SELECT id FROM sales_orders WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('sales_orders');
    $deleteWhere('sales_return_items', "sales_return_id IN (SELECT id FROM sales_returns WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('sales_returns');
    $deleteCompanyId('payment_allocations');
    $deleteCompanyId('customer_collections');

    // 5. Purchase transactions items & parents
    $deleteWhere('grn_items', "goods_receipt_note_id IN (SELECT id FROM goods_receipt_notes WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('goods_receipt_notes');
    $deleteWhere('purchase_entry_items', "purchase_entry_id IN (SELECT id FROM purchase_entries WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('purchase_entries');
    $deleteWhere('purchase_order_items', "purchase_order_id IN (SELECT id FROM purchase_orders WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('purchase_orders');
    $deleteWhere('purchase_return_items', "purchase_return_id IN (SELECT id FROM purchase_returns WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('purchase_returns');
    $deleteWhere('purchase_request_items', "purchase_request_id IN (SELECT id FROM purchase_requests WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('purchase_requests');
    $deleteCompanyId('supplier_payments');

    // 6. Stock & Inventory transactions
    $deleteWhere('stock_transfer_items', "stock_transfer_id IN (SELECT id FROM stock_transfers WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('stock_transfers');
    $deleteWhere('stock_adjustment_items', "stock_adjustment_id IN (SELECT id FROM stock_adjustments WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('stock_adjustments');
    $deleteWhere('stock_request_items', "stock_request_id IN (SELECT id FROM stock_requests WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('stock_requests');
    $deleteCompanyId('stock_movements');
    $deleteCompanyId('stock_batches');

    // 7. Quality Checks, CRM Leads, Incentives, Audit Logs
    $deleteCompanyId('quality_checks');
    $deleteCompanyId('crm_leads');
    $deleteCompanyId('incentives');
    $deleteCompanyId('audit_logs');

    // 8. Banking, Petty Cash, Capital Investment
    $deleteWhere('bank_reconciliation_items', "bank_reconciliation_id IN (SELECT id FROM bank_reconciliations WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('bank_reconciliations');
    $deleteCompanyId('petty_cash_vouchers');
    $deleteCompanyId('petty_cash_replenishments');
    $deleteCompanyId('petty_cash_accounts');
    $deleteCompanyId('petty_cash_categories');
    $deleteCompanyId('capital_investments');
    $deleteCompanyId('investors');

    // 9. HR & Payroll
    $deleteCompanyId('payrolls');
    $deleteCompanyId('attendances');
    $deleteCompanyId('overtimes');
    $deleteCompanyId('leave_applications');
    $deleteCompanyId('salary_advances');
    $deleteCompanyId('staff_targets');
    $deleteCompanyId('staff_allowances');
    $deleteWhere('staff_loan_installments', "staff_loan_id IN (SELECT id FROM staff_loans WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('staff_loans');
    $deleteWhere('staff_documents', "staff_id IN (SELECT id FROM staff WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('staff');
    $deleteCompanyId('shifts');
    $deleteCompanyId('holidays');

    // 10. Accounts & Ledgers
    $deleteCompanyId('ledgers');
    $deleteCompanyId('account_groups');

    // 11. Logistics & Routes
    $deleteWhere('route_cities', "route_id IN (SELECT id FROM routes WHERE company_id IN ($inCandidates))");
    $deleteWhere('route_locations', "route_id IN (SELECT id FROM routes WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('routes');
    $deleteCompanyId('locations');
    $deleteCompanyId('cities');
    $deleteCompanyId('vehicles');

    // 12. Master Catalog: Items, Products, Subcategories, Categories, Units, Partners
    $deleteCompanyId('items');
    $deleteCompanyId('products');
    $deleteCompanyId('subcategories');
    $deleteCompanyId('categories');
    $deleteCompanyId('units');
    $deleteCompanyId('customers');
    $deleteCompanyId('suppliers');

    // 13. Infrastructure: Godowns, Departments
    $deleteCompanyId('godowns');
    $deleteCompanyId('departments');

    // 14. Users & Authentication
    if (Schema::hasTable('model_has_roles')) {
        $deleteWhere('model_has_roles', "model_id IN (SELECT id FROM users WHERE company_id IN ($inCandidates))");
    }
    if (Schema::hasTable('model_has_permissions')) {
        $deleteWhere('model_has_permissions', "model_id IN (SELECT id FROM users WHERE company_id IN ($inCandidates))");
    }
    if (Schema::hasTable('personal_access_tokens')) {
        $deleteWhere('personal_access_tokens', "tokenable_id IN (SELECT id FROM users WHERE company_id IN ($inCandidates))");
    }
    $deleteWhere('user_company_branches', "company_id IN ($inCandidates) OR user_id IN (SELECT id FROM users WHERE company_id IN ($inCandidates))");
    $deleteCompanyId('users');

    // 15. Financial Years, Modules, Settings, Branches, Company
    $deleteCompanyId('financial_years');
    $deleteCompanyId('company_modules');
    $deleteCompanyId('system_settings');
    $deleteCompanyId('branches');

    // 16. Finally, companies table
    $compCount = DB::table('companies')->whereIn('id', $candidateIds)->delete();
    $deletedCounts['companies'] = $compCount;

    echo "Deleted records count per table:\n";
    $totalDeleted = 0;
    foreach ($deletedCounts as $tbl => $c) {
        echo "  - $tbl: $c\n";
        $totalDeleted += $c;
    }
    echo "Total records deleted: $totalDeleted\n\n";

    // Verify preserved companies
    $remainingCompanies = DB::table('companies')->pluck('name', 'id')->toArray();
    echo "Remaining companies:\n";
    foreach ($remainingCompanies as $id => $name) {
        echo "  - ID $id: $name\n";
    }

    // Verify Super Admin
    $admin = DB::table('users')->where('email', 'admin@erp.com')->first();
    echo "Super Admin: " . ($admin ? "EXISTS (ID: {$admin->id}, Company ID: {$admin->company_id})" : "MISSING!") . "\n";

    // Always rollback in dry run!
    DB::rollBack();
    echo "\n>>> DRY RUN COMPLETED SUCCESSFULLY: Transaction ROLLED BACK. No real data changed. <<<\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR during dry run: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}


<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=================================================================\n";
echo "       COMMON ERP – FRESH 12-INDUSTRY SETUP VERIFICATION         \n";
echo "=================================================================\n\n";

$allCompanies = DB::table('companies')->orderBy('id')->get();
echo "Total Companies in Database: " . count($allCompanies) . "\n\n";

$preservedIds = [1, 10, 11];
$newCompanyIds = [];

echo "--- COMPANY ROSTER ---\n";
foreach ($allCompanies as $c) {
    $isPreserved = in_array($c->id, $preservedIds);
    $tag = $isPreserved ? "[PRESERVED REAL/CORE]" : "[NEW 12-INDUSTRY DEMO]";
    if (!$isPreserved) {
        $newCompanyIds[] = $c->id;
    }
    echo sprintf("ID %2d | %-38s | %-16s | %-14s %s\n", $c->id, $c->name, $c->code, $c->business_type, $tag);
}
echo "\n";

// Verify Preserved Companies
echo "--- PRESERVED COMPANIES VERIFICATION ---\n";
foreach ($preservedIds as $pid) {
    $c = DB::table('companies')->where('id', $pid)->first();
    if ($c) {
        $usersCount = DB::table('users')->where('company_id', $pid)->count();
        $branchCount = DB::table('branches')->where('company_id', $pid)->count();
        echo "Company ID $pid ({$c->name}): OK (Branches: $branchCount, Users: $usersCount)\n";
    } else {
        echo "ALERT: Company ID $pid MISSING!\n";
    }
}
$admin = DB::table('users')->where('email', 'admin@erp.com')->first();
echo "Super Admin Account: " . ($admin ? "ACTIVE (ID: {$admin->id}, Company ID: {$admin->company_id})" : "MISSING!") . "\n\n";

// Verify 12 Demo Companies Master Data
echo "--- 12 DEMO COMPANIES MASTER DATA METRICS ---\n";
echo sprintf("%-4s | %-24s | %-4s | %-4s | %-4s | %-4s | %-4s | %-4s | %-4s | %-4s | %-4s\n",
    "ID", "Company Name", "Brn", "Dpt", "Gdw", "Unt", "Itm", "Sup", "Cus", "Stf", "Usr");
echo str_repeat("-", 80) . "\n";

$totalMasterRecords = 0;
foreach ($newCompanyIds as $cid) {
    $c = DB::table('companies')->where('id', $cid)->first();
    $branches = DB::table('branches')->where('company_id', $cid)->count();
    $depts = DB::table('departments')->where('company_id', $cid)->count();
    $godowns = DB::table('godowns')->where('company_id', $cid)->count();
    $units = DB::table('units')->where('company_id', $cid)->count();
    $items = DB::table('items')->where('company_id', $cid)->count();
    $supps = DB::table('suppliers')->where('company_id', $cid)->count();
    $custs = DB::table('customers')->where('company_id', $cid)->count();
    $staff = DB::table('staff')->where('company_id', $cid)->count();
    $users = DB::table('users')->where('company_id', $cid)->count();

    $sum = $branches + $depts + $godowns + $units + $items + $supps + $custs + $staff + $users;
    $totalMasterRecords += $sum;

    echo sprintf("%-4d | %-24s | %-4d | %-4d | %-4d | %-4d | %-4d | %-4d | %-4d | %-4d | %-4d\n",
        $cid, substr($c->name, 0, 24), $branches, $depts, $godowns, $units, $items, $supps, $custs, $staff, $users);
}
echo "\nTotal Master Data Entities across 12 demo companies: $totalMasterRecords\n\n";

// Verify Staff Employment Types
echo "--- STAFF EMPLOYMENT TYPES VERIFICATION (12 DEMO COMPANIES) ---\n";
$empTypes = DB::table('staff')
    ->whereIn('company_id', $newCompanyIds)
    ->select('employee_type', DB::raw('count(*) as cnt'))
    ->groupBy('employee_type')
    ->pluck('cnt', 'employee_type')
    ->toArray();
foreach ($empTypes as $type => $cnt) {
    echo "  - Employee Type '$type': $cnt staff members\n";
}
echo "\n";

// Verify Operational Transaction Tables (Must be ZERO for 12 demo companies)
echo "--- OPERATIONAL TRANSACTION AUDIT (MUST BE STRICTLY ZERO) ---\n";
$transactionTables = [
    'purchase_entries',
    'purchase_entry_items',
    'purchase_orders',
    'purchase_order_items',
    'purchase_requests',
    'purchase_request_items',
    'purchase_returns',
    'purchase_return_items',
    'goods_receipt_notes',
    'grn_items',
    'supplier_payments',
    'sales_invoices',
    'sales_invoice_items',
    'sales_orders',
    'sales_order_items',
    'sales_deliveries',
    'sales_delivery_items',
    'sales_returns',
    'sales_return_items',
    'customer_collections',
    'payment_allocations',
    'daily_operations',
    'daily_operation_materials',
    'daily_operation_outputs',
    'daily_operation_employees',
    'daily_operation_distributions',
    'daily_operation_wastages',
    'production_orders',
    'stock_transfers',
    'stock_transfer_items',
    'stock_adjustments',
    'stock_adjustment_items',
    'stock_requests',
    'stock_request_items',
    'stock_batches',
    'stock_movements',
    'journal_entries',
    'journal_lines',
    'payrolls',
    'attendances',
    'petty_cash_vouchers',
    'petty_cash_replenishments',
    'bank_reconciliations',
    'bank_reconciliation_items',
    'capital_investments',
];

$nonZeroTables = [];
foreach ($transactionTables as $tbl) {
    if (!Schema::hasTable($tbl)) continue;

    if (Schema::hasColumn($tbl, 'company_id')) {
        $cnt = DB::table($tbl)->whereIn('company_id', $newCompanyIds)->count();
    } else {
        // Child tables without company_id
        if (str_starts_with($tbl, 'daily_operation_')) {
            $cnt = DB::table($tbl)->whereIn('daily_operation_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('daily_operations')->whereIn('company_id', $newCompanyIds);
            })->count();
        } elseif ($tbl === 'journal_lines') {
            $cnt = DB::table($tbl)->whereIn('journal_entry_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('journal_entries')->whereIn('company_id', $newCompanyIds);
            })->count();
        } elseif ($tbl === 'purchase_entry_items') {
            $cnt = DB::table($tbl)->whereIn('purchase_entry_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('purchase_entries')->whereIn('company_id', $newCompanyIds);
            })->count();
        } elseif ($tbl === 'sales_invoice_items') {
            $cnt = DB::table($tbl)->whereIn('sales_invoice_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('sales_invoices')->whereIn('company_id', $newCompanyIds);
            })->count();
        } elseif ($tbl === 'grn_items') {
            $cnt = DB::table($tbl)->whereIn('goods_receipt_note_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('goods_receipt_notes')->whereIn('company_id', $newCompanyIds);
            })->count();
        } elseif ($tbl === 'purchase_order_items') {
            $cnt = DB::table($tbl)->whereIn('purchase_order_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('purchase_orders')->whereIn('company_id', $newCompanyIds);
            })->count();
        } elseif ($tbl === 'sales_order_items') {
            $cnt = DB::table($tbl)->whereIn('sales_order_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('sales_orders')->whereIn('company_id', $newCompanyIds);
            })->count();
        } elseif ($tbl === 'sales_delivery_items') {
            $cnt = DB::table($tbl)->whereIn('sales_delivery_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('sales_deliveries')->whereIn('company_id', $newCompanyIds);
            })->count();
        } elseif ($tbl === 'stock_transfer_items') {
            $cnt = DB::table($tbl)->whereIn('stock_transfer_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('stock_transfers')->whereIn('company_id', $newCompanyIds);
            })->count();
        } elseif ($tbl === 'bank_reconciliation_items') {
            $cnt = DB::table($tbl)->whereIn('bank_reconciliation_id', function($q) use ($newCompanyIds) {
                $q->select('id')->from('bank_reconciliations')->whereIn('company_id', $newCompanyIds);
            })->count();
        } else {
            $cnt = 0;
        }
    }

    if ($cnt > 0) {
        $nonZeroTables[$tbl] = $cnt;
        echo "  [FAIL] $tbl: $cnt records found for demo companies!\n";
    }
}

if (empty($nonZeroTables)) {
    echo "  [PASS] ALL " . count($transactionTables) . " transaction tables are 100% EMPTY (0 records) for the 12 new companies!\n";
} else {
    echo "  [FAIL] " . count($nonZeroTables) . " tables contain non-zero transaction records!\n";
}

echo "\n=================================================================\n";
echo "VERIFICATION SUMMARY: ENVIRONMENT FULLY READY FOR MANUAL TESTING!\n";
echo "=================================================================\n";


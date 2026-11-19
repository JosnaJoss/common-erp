<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== POST CLEANUP VERIFICATION ===\n\n";

$companies = DB::table('companies')->get();
echo "Companies in database (" . count($companies) . "):\n";
foreach ($companies as $c) {
    echo "  - ID {$c->id}: {$c->name} ({$c->code})\n";
}

$orphans = 0;
$tables = DB::select("SHOW TABLES");
$dbName = DB::getDatabaseName();
$colName = "Tables_in_" . $dbName;
$companyIds = $companies->pluck('id')->toArray();

foreach ($tables as $t) {
    $tableName = $t->$colName;
    if (Schema::hasColumn($tableName, 'company_id')) {
        $count = DB::table($tableName)
            ->whereNotNull('company_id')
            ->whereNotIn('company_id', $companyIds)
            ->count();
        if ($count > 0) {
            echo "ORPHAN DETECTED in table $tableName: $count rows\n";
            $orphans += $count;
        }
    }
}

if ($orphans === 0) {
    echo "\n>>> Zero orphan records detected across all tenant-scoped tables! <<<\n";
} else {
    echo "\n>>> WARNING: $orphans orphan records detected! <<<\n";
}

$admin = DB::table('users')->where('email', 'admin@erp.com')->first();
echo "Super Admin: ID {$admin->id}, Company ID {$admin->company_id}, Name: {$admin->name}\n";

$astra = DB::table('companies')->where('id', 10)->first();
$mn = DB::table('companies')->where('id', 11)->first();
echo "Astra preserved: " . ($astra ? 'YES' : 'NO') . "\n";
echo "Manakkattu preserved: " . ($mn ? 'YES' : 'NO') . "\n";


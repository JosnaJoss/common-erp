<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tables = DB::select("SHOW TABLES");
$dbName = DB::getDatabaseName();
$colName = "Tables_in_" . $dbName;

$withCompanyId = [];
$withoutCompanyId = [];

foreach ($tables as $t) {
    $tableName = $t->$colName;
    $cols = Schema::getColumnListing($tableName);
    if (in_array("company_id", $cols)) {
        $withCompanyId[] = $tableName;
    } else {
        $withoutCompanyId[] = $tableName;
    }
}

echo "WITH company_id (" . count($withCompanyId) . "):\n" . implode("\n", $withCompanyId) . "\n\n";
echo "WITHOUT company_id (" . count($withoutCompanyId) . "):\n" . implode("\n", $withoutCompanyId) . "\n";


<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$fks = DB::select("
    SELECT 
        TABLE_NAME, 
        COLUMN_NAME, 
        CONSTRAINT_NAME, 
        REFERENCED_TABLE_NAME, 
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
      AND REFERENCED_TABLE_NAME IS NOT NULL
");

$graph = [];
foreach ($fks as $fk) {
    $graph[$fk->TABLE_NAME][] = [
        'column' => $fk->COLUMN_NAME,
        'ref_table' => $fk->REFERENCED_TABLE_NAME,
        'ref_col' => $fk->REFERENCED_COLUMN_NAME,
        'constraint' => $fk->CONSTRAINT_NAME
    ];
}

echo "Found " . count($fks) . " foreign key constraints across " . count($graph) . " tables.\n";

// Check if any FK from preserved companies references candidate companies
$candidateIds = [2, 3, 4, 5, 6, 7, 8, 9];
echo "Checking candidate IDs: " . implode(', ', $candidateIds) . "\n";


<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

$tables = [
    'companies', 'branches', 'departments', 'godowns', 'units',
    'categories', 'subcategories', 'items', 'products', 'customers',
    'suppliers', 'staff', 'shifts', 'holidays', 'users',
    'account_groups', 'ledgers', 'financial_years'
];

foreach ($tables as $t) {
    echo "=== $t ===\n";
    $cols = Schema::getColumnListing($t);
    echo implode(', ', $cols) . "\n\n";
}


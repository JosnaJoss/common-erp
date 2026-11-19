<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

foreach (['vehicles', 'routes', 'locations'] as $t) {
    echo "=== $t ===\n" . implode(', ', Schema::getColumnListing($t)) . "\n";
}


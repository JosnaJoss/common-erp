<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['account_groups', 'ledgers'] as $tbl) {
    echo "=== $tbl ===\n";
    $cols = DB::select("DESCRIBE $tbl");
    foreach ($cols as $c) {
        if (str_contains($c->Type, 'enum')) {
            echo "{$c->Field}: {$c->Type}\n";
        }
    }
}


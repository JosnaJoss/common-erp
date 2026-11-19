<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (['cities', 'roles', 'industry_process_templates'] as $tbl) {
    echo "=== Table: $tbl ===\n";
    $sample = DB::table($tbl)->limit(5)->get();
    echo "Count: " . DB::table($tbl)->count() . "\n";
    echo "Distinct company_id: " . json_encode(DB::table($tbl)->pluck('company_id')->unique()->values()->toArray()) . "\n\n";
}


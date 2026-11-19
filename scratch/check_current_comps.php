<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$comps = DB::table('companies')->get();
foreach ($comps as $c) {
    echo "ID {$c->id}: {$c->name} ({$c->code}) - {$c->business_type}\n";
}


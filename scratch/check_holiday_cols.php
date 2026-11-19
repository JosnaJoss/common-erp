<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("DESCRIBE holidays");
foreach ($cols as $c) {
    if (in_array($c->Field, ['holiday_type'])) {
        echo "{$c->Field}: {$c->Type}\n";
    }
}


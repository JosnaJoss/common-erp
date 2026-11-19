<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$cols = DB::select("DESCRIBE items");
foreach ($cols as $c) {
    if (in_array($c->Field, ['item_type', 'unit_id', 'product_id', 'category_id', 'subcategory_id'])) {
        echo "{$c->Field}: {$c->Type}, Null: {$c->Null}, Default: {$c->Default}\n";
    }
}


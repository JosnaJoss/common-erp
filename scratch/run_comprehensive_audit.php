<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

echo "=== 1. VERSIONS ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Laravel Version: " . app()->version() . "\n\n";

echo "=== 2. DATABASE STATS ===\n";
$tables = DB::select("SHOW TABLES");
$dbName = DB::getDatabaseName();
$colName = "Tables_in_" . $dbName;
echo "Database Name: $dbName\n";
echo "Total Tables: " . count($tables) . "\n";

$companyScopedTables = [];
$otherTables = [];
foreach ($tables as $t) {
    $tbl = $t->$colName;
    if (Schema::hasColumn($tbl, 'company_id')) {
        $companyScopedTables[] = $tbl;
    } else {
        $otherTables[] = $tbl;
    }
}
echo "Company-scoped tables (has company_id): " . count($companyScopedTables) . "\n";
echo "Other tables: " . count($otherTables) . "\n\n";

echo "=== 3. CONTROLLERS ===\n";
$controllerFiles = glob(__DIR__ . '/../app/Http/Controllers/*.php');
$subControllers = glob(__DIR__ . '/../app/Http/Controllers/*/*.php');
$allControllers = array_merge($controllerFiles, $subControllers);
echo "Total Controller files: " . count($allControllers) . "\n";
foreach ($allControllers as $c) {
    echo "  - " . basename($c) . "\n";
}
echo "\n";

echo "=== 4. MODELS ===\n";
$modelFiles = glob(__DIR__ . '/../app/Models/*.php');
echo "Total Models: " . count($modelFiles) . "\n";
foreach ($modelFiles as $m) {
    echo "  - " . basename($m, '.php') . "\n";
}
echo "\n";

echo "=== 5. SERVICES, REPOSITORIES, JOBS, EVENTS ===\n";
foreach (['Services', 'Repositories', 'Jobs', 'Events', 'Listeners', 'Policies', 'Rules', 'Console'] as $dir) {
    $path = __DIR__ . '/../app/' . $dir;
    if (is_dir($path)) {
        $files = glob($path . '/*.php');
        echo "app/$dir (" . count($files) . "):\n";
        foreach ($files as $f) {
            echo "  - " . basename($f) . "\n";
        }
    } else {
        echo "app/$dir: Does not exist\n";
    }
}
echo "\n";

echo "=== 6. MIDDLEWARE ===\n";
$middlewareFiles = glob(__DIR__ . '/../app/Http/Middleware/*.php');
echo "Middleware files (" . count($middlewareFiles) . "):\n";
foreach ($middlewareFiles as $mw) {
    echo "  - " . basename($mw) . "\n";
}
echo "\n";

echo "=== 7. ROUTES ===\n";
$routeCollection = Route::getRoutes();
$webCount = 0;
$apiCount = 0;
foreach ($routeCollection as $route) {
    if (in_array('api', $route->middleware())) {
        $apiCount++;
    } else {
        $webCount++;
    }
}
echo "Total Routes: " . count($routeCollection) . " (Web: $webCount, API: $apiCount)\n\n";

echo "=== 8. VIEWS ===\n";
function countViews($dir) {
    $count = 0;
    $items = glob($dir . '/*');
    foreach ($items as $item) {
        if (is_dir($item)) {
            $count += countViews($item);
        } elseif (str_ends_with($item, '.blade.php')) {
            $count++;
        }
    }
    return $count;
}
$viewCount = countViews(__DIR__ . '/../resources/views');
echo "Total Blade Views: $viewCount\n";
$viewDirs = glob(__DIR__ . '/../resources/views/*', GLOB_ONLYDIR);
foreach ($viewDirs as $vd) {
    echo "  - " . basename($vd) . " (" . countViews($vd) . " views)\n";
}
echo "\n";


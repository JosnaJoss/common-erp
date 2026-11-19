<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::find(1);
echo "User 1: " . $user->name . ", role_id: " . $user->role_id . "\n";
echo "Role: " . ($user->role ? $user->role->name : 'none') . "\n";


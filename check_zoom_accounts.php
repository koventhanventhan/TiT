<?php
use App\Models\ZoomAccount;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Zoom Accounts in Database ===\n";
$accounts = ZoomAccount::all();
foreach ($accounts as $acc) {
    echo "ID: {$acc->id} | Email: {$acc->email} | Active: " . ($acc->is_active ? 'YES' : 'NO') . "\n";
}
if ($accounts->isEmpty()) {
    echo "NO ACCOUNTS FOUND!\n";
}
echo "================================\n";

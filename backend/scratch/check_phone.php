<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select('DESCRIBE users');
$found = false;
foreach ($columns as $col) {
    if (strpos($col->Field, 'phone') !== false) {
        echo "Found phone column: " . $col->Field . "\n";
        $found = true;
    }
}
if (!$found) echo "No phone columns found.\n";

$rows = DB::select('SELECT id, phone_number, parent_phone_number FROM users WHERE parent_phone_number IS NOT NULL LIMIT 5');
echo "Sample data:\n";
foreach ($rows as $row) {
    echo "ID: $row->id | phone_number: $row->phone_number | parent_phone_number: $row->parent_phone_number\n";
}

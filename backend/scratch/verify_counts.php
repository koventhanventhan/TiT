<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Student;

echo "=== USER AND STUDENT COUNTS ===\n";
echo "Total Users: " . User::count() . "\n";
echo "Total Students: " . Student::count() . "\n";

echo "\n=== PARENT_PHONE_NUMBER COLUMN CHECK ===\n";
$columns = DB::select('DESCRIBE users');
$hasParentPhone = false;
foreach ($columns as $col) {
    if ($col->Field === 'parent_phone_number') {
        $hasParentPhone = true;
    }
}
if ($hasParentPhone) {
    echo "❌ parent_phone_number still exists!\n";
} else {
    echo "✅ parent_phone_number successfully dropped from users table!\n";
}

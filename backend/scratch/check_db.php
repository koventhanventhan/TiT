<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ATTENDANCES TABLE STRUCTURE ===\n";
$columns = DB::select('DESCRIBE attendances');
foreach ($columns as $col) {
    echo "  {$col->Field} | {$col->Type} | Null:{$col->Null} | Key:{$col->Key} | Default:{$col->Default}\n";
}

echo "\n=== SAMPLE ATTENDANCES DATA ===\n";
$rows = DB::table('attendances')->limit(10)->get();
if ($rows->isEmpty()) {
    echo "  (empty table - no rows)\n";
} else {
    foreach ($rows as $row) {
        echo "  " . json_encode($row) . "\n";
    }
}

echo "\n=== ATTENDANCES INDEXES & FOREIGN KEYS ===\n";
$indexes = DB::select('SHOW INDEX FROM attendances');
foreach ($indexes as $idx) {
    echo "  Key:{$idx->Key_name} | Column:{$idx->Column_name} | Unique:" . ($idx->Non_unique ? 'No' : 'Yes') . "\n";
}

echo "\n=== ATTENDANCES FOREIGN KEYS ===\n";
$fks = DB::select("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'attendances'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
foreach ($fks as $fk) {
    echo "  {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
}

echo "\n=== ASSIGNMENT_SUBMISSIONS TABLE STRUCTURE ===\n";
$columns2 = DB::select('DESCRIBE assignment_submissions');
foreach ($columns2 as $col) {
    echo "  {$col->Field} | {$col->Type} | Null:{$col->Null} | Key:{$col->Key} | Default:{$col->Default}\n";
}

echo "\n=== ASSIGNMENT_SUBMISSIONS FOREIGN KEYS ===\n";
$fks2 = DB::select("
    SELECT CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'assignment_submissions'
    AND REFERENCED_TABLE_NAME IS NOT NULL
");
foreach ($fks2 as $fk) {
    echo "  {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME} -> {$fk->REFERENCED_TABLE_NAME}.{$fk->REFERENCED_COLUMN_NAME}\n";
}

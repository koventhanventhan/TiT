<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$student = User::find(78);
if (!$student) { die("Student 78 not found.\n"); }

echo "Before: " . $student->selected_subjects . "\n";

$decoded = json_decode($student->selected_subjects, true);
if (is_array($decoded)) {
    $unique = array_values(array_unique($decoded));
    $student->selected_subjects = json_encode($unique, JSON_UNESCAPED_UNICODE);
    $student->save();
    echo "After (deduped): " . $student->selected_subjects . "\n";
    echo "Count: " . count($unique) . "\n";
} else {
    echo "Not valid JSON, skipping.\n";
}

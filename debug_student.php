<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$studentId = 78;
$student = User::find($studentId);

if ($student) {
    echo "Dumping data for student ID: " . $studentId . "\n";
    print_r($student->toArray());
} else {
    echo "Student with ID " . $studentId . " not found.\n";
}

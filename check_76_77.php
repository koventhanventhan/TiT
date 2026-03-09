<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Check if sasikanth (76/77) also has duplicate issue
$s76 = User::find(76);
$s77 = User::find(77);

if ($s76 && $s77) {
    echo "Student 76:\n";
    echo "  Name: {$s76->name} | Email: {$s76->email}\n";
    echo "  DOB: {$s76->date_of_birth} | Gender: {$s76->gender} | Grade: {$s76->current_grade}\n";
    echo "  Subjects: " . ($s76->selected_subjects ?: 'EMPTY') . "\n\n";
    
    echo "Student 77:\n";
    echo "  Name: {$s77->name} | Email: {$s77->email}\n";
    echo "  DOB: {$s77->date_of_birth} | Gender: {$s77->gender} | Grade: {$s77->current_grade}\n";
    echo "  Subjects: " . ($s77->selected_subjects ?: 'EMPTY') . "\n\n";
}

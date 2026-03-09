<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Merge sasikanth: ID 77 (has details) → ID 76 (empty shell)
$source = User::find(77);
$target = User::find(76);

if (!$source || !$target) {
    die("Source or Target student not found.\n");
}

echo "Merging data from student ID 77 to 76...\n";

$fields = [
    'full_name', 'phone_number', 'date_of_birth', 'gender', 
    'school_name', 'medium', 'online_experience', 'device_used', 
    'current_grade', 'stream', 'selected_subjects'
];

foreach ($fields as $field) {
    if (!empty($source->$field) || $source->$field === '0' || $source->$field === 0) {
        $target->$field = $source->$field;
    }
}

$target->registration_status = $source->registration_status;

if ($target->save()) {
    echo "Successfully merged data to student 76.\n";
    $source->delete();
    echo "Deleted duplicate student 77.\n";
    
    // Verify
    $verify = User::find(76);
    echo "\nVerification - Student 76:\n";
    echo "  Name: {$verify->name}\n";
    echo "  DOB: {$verify->date_of_birth}\n";
    echo "  Gender: {$verify->gender}\n";
    echo "  Grade: {$verify->current_grade}\n";
    echo "  Subjects count: " . count(json_decode($verify->selected_subjects, true)) . "\n";
} else {
    echo "Failed to save merged data.\n";
}

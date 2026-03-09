<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sourceId = 79;
$targetId = 78;

$source = User::find($sourceId);
$target = User::find($targetId);

if (!$source || !$target) {
    die("Source or Target student not found.\n");
}

echo "Merging data from student ID {$sourceId} to {$targetId}...\n";

$fields = [
    'full_name', 'phone_number', 'date_of_birth', 'gender', 
    'school_name', 'medium', 'online_experience', 'device_used', 
    'current_grade', 'stream', 'selected_subjects'
];

foreach ($fields as $field) {
    if (!empty($source->$field) || $source->$field === 0 || $source->$field === false) {
        $target->$field = $source->$field;
    }
}

// Also ensure registration status is updated
$target->registration_status = $source->registration_status;

if ($target->save()) {
    echo "Successfully merged data to student 78.\n";
    // Delete the duplicate
    $source->delete();
    echo "Deleted duplicate student 79.\n";
} else {
    echo "Failed to save merged data.\n";
}

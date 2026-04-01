<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ZoomSchedule;
use Carbon\Carbon;

echo "--- User 144 (aaa) Profile ---\n";
$u = User::find(144);
if ($u) {
    echo "ID: " . $u->id . "\n";
    echo "Name: " . $u->name . "\n";
    echo "Grade: " . $u->current_grade . "\n";
    echo "Medium: " . $u->medium . "\n";
    echo "Selected Subjects: " . json_encode($u->selected_subjects) . "\n";
    echo "Has Paid for 2026-03: " . ($u->hasPaidForMonth('2026-03') ? 'Yes' : 'No') . "\n";
} else {
    echo "User 144 not found.\n";
}

echo "\n--- Zoom Schedules for Today (2026-03-30) ---\n";
$schedules = ZoomSchedule::whereDate('scheduled_at', '2026-03-30')->get();
foreach ($schedules as $s) {
    echo "ID: " . $s->id . " | Title: " . $s->title . " | Grade: " . $s->grade . " | Subject: " . $s->subject . " | Time: " . $s->scheduled_at . "\n";
}

if ($schedules->isEmpty()) {
    echo "No schedules found for today.\n";
}

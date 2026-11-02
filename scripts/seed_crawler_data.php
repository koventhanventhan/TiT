<?php

require __DIR__ . '/../laravel_api/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../laravel_api/backend/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ZoomSchedule;
use App\Models\Attendance;
use App\Models\AdminMessage;
use App\Models\ExamResult;
use App\Models\User;

echo "Seeding crawler demo records if missing...\n";

$firstUser = User::first();
$firstUserId = $firstUser ? $firstUser->id : null;

// 1. ZoomSchedule & Attendance
if (ZoomSchedule::count() === 0) {
    $schedule = ZoomSchedule::create([
        'institute_id' => 1,
        'title' => 'Grade 11 Mathematics Live Class',
        'zoom_link' => 'https://zoom.us/j/1234567890',
        'meeting_id' => '1234567890',
        'start_url' => 'https://zoom.us/s/1234567890',
        'join_url' => 'https://zoom.us/j/1234567890',
        'password' => '123456',
        'duration' => 60,
        'scheduled_at' => now(),
        'subject' => 'Mathematics',
        'grade' => 'Grade 11',
        'medium' => 'English',
        'created_by' => $firstUserId,
    ]);
    echo "Created ZoomSchedule ID: {$schedule->id}\n";

    $student = User::where('role', 'student')->first() ?: $firstUser;
    if ($student) {
        $att = Attendance::create([
            'institute_id' => 1,
            'zoom_schedule_id' => $schedule->id,
            'user_id' => $student->id,
            'role' => 'student',
            'status' => 'present',
            'marked_at' => now(),
            'source' => 'system'
        ]);
        echo "Created Attendance ID: {$att->id}\n";
    }
}

// 2. AdminMessage
if (AdminMessage::count() === 0) {
    $msg = AdminMessage::create([
        'institute_id' => 1,
        'title' => 'Welcome to the New Academic Year',
        'body' => 'Please review the schedule and prepare course materials.',
        'target_type' => 'all'
    ]);
    echo "Created AdminMessage ID: {$msg->id}\n";
}

// 3. ExamResult
if (ExamResult::count() === 0) {
    $res = ExamResult::create([
        'institute_id' => 1,
        'student_name' => 'Kajanthan S.',
        'index_no' => 'TIT-2024-001',
        'term' => '1st Term',
        'grade' => 'Grade 11',
        'subject' => 'Combined Maths',
        'marks' => 88.5,
        'result_grade' => 'A',
        'rank' => 1,
        'year' => 2024
    ]);
    echo "Created ExamResult ID: {$res->id}\n";
}

echo "Done seeding crawler demo records.\n";

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$schedule = App\Models\ZoomSchedule::latest()->first();
if ($schedule) {
    echo json_encode([
        'id' => $schedule->id,
        'title' => $schedule->title,
        'grade' => $schedule->grade,
        'subject' => $schedule->subject,
        'scheduled_at' => $schedule->scheduled_at,
        'created_at' => $schedule->created_at,
        'reminded_at' => $schedule->reminded_at
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "No schedules found";
}

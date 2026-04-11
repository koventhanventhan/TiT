<?php
use App\Models\ZoomSchedule;
use Carbon\Carbon;

$s = new ZoomSchedule();
$s->title = 'Test Class';
$s->scheduled_at = Carbon::now()->addMinutes(16);
$s->grade = 'Grade 10';
$s->subject = 'English';
$s->meeting_id = '123456789';
$s->zoom_link = 'https://zoom.us/j/123456789';
$s->save();

echo "Created test schedule ID: " . $s->id . "\n";
echo "Scheduled for: " . $s->scheduled_at . "\n";
echo "Current time: " . Carbon::now() . "\n";

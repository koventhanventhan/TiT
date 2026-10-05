<?php
use App\Models\ZoomSchedule;
use Carbon\Carbon;

$s = ZoomSchedule::where('title', 'Test Class')->first();
if ($s) {
    echo "ID: " . $s->id . "\n";
    echo "Title: " . $s->title . "\n";
    echo "Scheduled At: " . $s->scheduled_at . "\n";
    echo "Current Now: " . Carbon::now() . "\n";
    echo "Diff In Minutes: " . Carbon::now()->diffInMinutes($s->scheduled_at, false) . "\n";
} else {
    echo "Test Class schedule NOT found.\n";
    // Check all recent schedules
    echo "Recent schedules:\n";
    foreach (ZoomSchedule::orderBy('id', 'desc')->take(5)->get() as $rs) {
        echo "- ID: {$rs->id}, Title: {$rs->title}, Scheduled: {$rs->scheduled_at}\n";
    }
}

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$schedules = DB::table('zoom_schedules')->where('title', 'maths_haja')->limit(5)->get();
echo "Found " . $schedules->count() . " records for maths_haja:\n";
foreach ($schedules as $s) {
    echo "ID: " . $s->id . " | Raw scheduled_at: " . $s->scheduled_at . "\n";
}

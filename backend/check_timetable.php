<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$t = DB::table('timetables')->where('title', 'maths_haja')->first();
if ($t) {
    echo "Timetable start_time: " . $t->start_time . "\n";
} else {
    echo "Timetable not found\n";
}

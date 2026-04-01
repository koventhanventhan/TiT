<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
$s = DB::table('zoom_schedules')->where('title', 'maths_haja')->first();
if ($s) {
    echo "Raw scheduled_at: " . $s->scheduled_at . "\n";
} else {
    echo "Not found\n";
}

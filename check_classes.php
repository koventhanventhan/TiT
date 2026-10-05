<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classes = App\Models\ZoomSchedule::with('teachers')->orderBy('id', 'desc')->take(2)->get();
echo json_encode($classes, JSON_PRETTY_PRINT);

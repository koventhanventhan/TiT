<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$subjects = \App\Models\Subject::all();
foreach($subjects as $s) {
    echo $s->name . " - " . $s->category . "\n";
}

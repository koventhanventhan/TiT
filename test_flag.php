<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::find(336);
if ($u) {
    $u->needs_subject_review = true;
    $u->save();
    echo "Flagged student #{$u->id} ({$u->full_name}) for subject review.\n";
} else {
    echo "Student not found.\n";
}
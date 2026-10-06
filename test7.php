<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$users = \App\Models\User::where('role', 'user')->whereNotNull('parent_id')->get();
foreach ($users as $u) {
    echo 'ID: ' . $u->id . ' Subjects: ' . $u->selected_subjects . ' Grade: ' . $u->current_grade . ' Stream: ' . $u->stream . "\n";
}

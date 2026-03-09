<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$students = User::where('role', 'user')->whereNotNull('selected_subjects')->get(['id', 'name', 'selected_subjects']);

foreach ($students as $s) {
    $decoded = json_decode($s->selected_subjects, true);
    if (is_array($decoded)) {
        $count = count($decoded);
    } else {
        $arr = array_filter(array_map('trim', explode(',', $s->selected_subjects)));
        $count = count($arr);
    }
    echo "ID: {$s->id} | Name: {$s->name} | Count: {$count} | Raw: " . substr($s->selected_subjects, 0, 80) . "\n";
}

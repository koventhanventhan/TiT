<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Users Created in February 2026 ===\n\n";

$users = User::withoutGlobalScopes()
    ->whereYear('created_at', 2026)
    ->whereMonth('created_at', 2)
    ->orderBy('created_at', 'desc')
    ->get();

foreach ($users as $user) {
    echo "ID: " . $user->id . " | Role: " . $user->role . " | Email: " . $user->email . " | Created: " . $user->created_at . "\n";
}

<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Searching for all 'koventhanventhan' users ===\n\n";

$users = User::withoutGlobalScopes()
    ->where('email', 'LIKE', 'koventhanventhan%')
    ->get();

if ($users->count() === 0) {
    echo "No matching users found!\n";
} else {
    foreach ($users as $user) {
        echo "ID: " . $user->id . " | Email: " . $user->email . " | Role: " . $user->role . "\n";
    }
}

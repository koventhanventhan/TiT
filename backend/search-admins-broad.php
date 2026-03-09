<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Broad Search for Admin Accounts ===\n\n";

$users = User::withoutGlobalScopes()
    ->where(function($query) {
        $query->where('role', 'LIKE', '%admin%')
              ->orWhere('email', 'LIKE', '%admin%')
              ->orWhere('name', 'LIKE', '%admin%');
    })
    ->get();

if ($users->count() === 0) {
    echo "No matching admins found!\n";
} else {
    foreach ($users as $user) {
        echo "ID: " . $user->id . "\n";
        echo "Name: " . $user->name . "\n";
        echo "Email: " . $user->email . "\n";
        echo "Role: " . $user->role . "\n";
        echo "Created At: " . $user->created_at . "\n";
        echo "---------------------------\n";
    }
}

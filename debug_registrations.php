<?php
require 'backend/vendor/autoload.php';
$app = require_once 'backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$latest_users = User::latest()->take(5)->get();
$admin = User::where('role', 'admin')->first();

echo "--- LATEST 5 USERS ---\n";
foreach ($latest_users as $u) {
    echo "ID: " . $u->id . " | Name: " . $u->name . " | Email: " . $u->email . " | Created At: " . $u->created_at . "\n";
}

echo "\n--- ADMIN SETTINGS ---\n";
if ($admin) {
    echo "Email: " . $admin->email . "\n";
    echo "Settings: " . json_encode($admin->profile_settings, JSON_PRETTY_PRINT) . "\n";
    echo "Unread Notifications: " . $admin->unreadNotifications()->count() . "\n";
    
    echo "\n--- RECENT NOTIFICATIONS ---\n";
    foreach ($admin->notifications()->latest()->take(5)->get() as $n) {
        echo "[" . $n->created_at . "] " . json_encode($n->data) . "\n";
    }
} else {
    echo "Admin not found.\n";
}

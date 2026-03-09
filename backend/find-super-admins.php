<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "=== Searching for Super Admins ===\n\n";

$superAdmins = User::withoutGlobalScopes()->where('role', 'super_admin')->get();

if ($superAdmins->count() === 0) {
    echo "No Super Admins found!\n";
} else {
    foreach ($superAdmins as $admin) {
        echo "ID: " . $admin->id . "\n";
        echo "Name: " . $admin->name . "\n";
        echo "Email: " . $admin->email . "\n";
        echo "---------------------------\n";
    }
}

<?php
// backend/scratch/check_latest_user.php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::orderBy('created_at', 'desc')->first();
if ($user) {
    echo "Latest User ID: {$user->id}\n";
    echo "Username: {$user->username}\n";
    echo "Email: {$user->email}\n";
    echo "Registration Status: {$user->registration_status}\n";
    echo "Created At: {$user->created_at}\n";
} else {
    echo "No users found.\n";
}

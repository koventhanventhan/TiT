<?php

use Illuminate\Support\Facades\Hash;
use App\Models\User;

define('LARAVEL_START', microtime(true));

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'koventhanventhan153@gmail.com';
$password = 'admin123';

echo "Updating password for $email...\n";

$user = User::where('email', $email)->first();

if ($user) {
    if ($user->role !== 'admin' && $user->role !== 'super_admin') {
         $user->role = 'admin'; // Ensure they have admin access
    }
    $user->password = Hash::make($password);
    $user->save();
    echo "SUCCESS: Password updated to '$password'. You can now login at /super-admin/login\n";
} else {
    echo "ERROR: User with email $email not found.\n";
}

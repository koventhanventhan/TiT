<?php
// SUPER ADMIN PASSWORD RESET SCRIPT (Public Folder Version)
// URL: https://titjaffna.lk/backend/public/reset_admin.php OR https://titjaffna.lk/api/reset_admin.php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$newPassword = 'superadmin123';
$email = 'superadmin@lenova.lk';

$user = User::where('email', $email)->first();

if ($user) {
    $user->password = Hash::make($newPassword);
    $user->plain_password = $newPassword;
    $user->save();
    echo "<h1>SUCCESS!</h1>";
    echo "<p>Password for <b>$email</b> has been reset to: <b>$newPassword</b></p>";
} else {
    echo "<h1>ERROR</h1>";
    echo "<p>User $email not found in database.</p>";
}
echo "<p style='color:red;'><b>IMPORTANT: Delete this file (backend/public/reset_admin.php) immediately!</b></p>";

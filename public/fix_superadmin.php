<?php
// Debug script to check what's happening with the super admin password
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'superadmin@lenova.lk';
$password = 'superadmin123';

$user = User::where('email', $email)->first();

if (!$user) {
    echo "<h1>User NOT found</h1>";
    exit;
}

echo "<h2>User Found</h2>";
echo "<p>ID: {$user->id} | Email: {$user->email} | Role: {$user->role}</p>";
echo "<p>Password hash: " . substr($user->password, 0, 30) . "...</p>";
echo "<p>Password check 'superadmin123': " . (Hash::check($password, $user->password) ? '<b style="color:green">MATCH</b>' : '<b style="color:red">NO MATCH</b>') . "</p>";

// Fix: Use the query builder to bypass the 'hashed' cast
echo "<hr><h2>Fixing password (bypassing cast)...</h2>";
$newHash = Hash::make($password);
\DB::table('users')->where('email', $email)->update([
    'password' => $newHash,
    'plain_password' => $password,
]);

// Re-check
$user->refresh();
$check = Hash::check($password, $user->password);
echo "<p>After fix - Password check: " . ($check ? '<b style="color:green">MATCH ✅</b>' : '<b style="color:red">NO MATCH ❌</b>') . "</p>";

echo "<p style='color:red;'><b>DELETE THIS FILE IMMEDIATELY!</b></p>";

<?php
/**
 * SUPER ADMIN PASSWORD RESET SCRIPT
 * Upload to production backend folder, run once, then DELETE immediately.
 * URL: https://titjaffna.lk/backend/reset_production_superadmin.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$newPassword = 'superadmin123';

$user = User::where('email', 'superadmin@lenova.lk')->first();

if (!$user) {
    echo "<h2 style='color:red;'>ERROR: Super admin user (superadmin@lenova.lk) NOT found in database!</h2>";
    echo "<p>Creating new super admin user...</p>";
    
    $user = User::create([
        'name' => 'Super Admin',
        'email' => 'superadmin@lenova.lk',
        'role' => 'super_admin',
        'password' => Hash::make($newPassword),
        'plain_password' => $newPassword,
        'institute_id' => null,
        'email_verified_at' => now(),
    ]);
    
    echo "<h2 style='color:green;'>Super Admin CREATED successfully!</h2>";
} else {
    $user->password = Hash::make($newPassword);
    $user->plain_password = $newPassword;
    $user->save();
    
    echo "<h2 style='color:green;'>Super Admin password RESET successfully!</h2>";
}

// Verify
$user->refresh();
$match = Hash::check($newPassword, $user->password);

echo "<p><strong>Email:</strong> superadmin@lenova.lk</p>";
echo "<p><strong>Password:</strong> {$newPassword}</p>";
echo "<p><strong>Verification:</strong> " . ($match ? '<span style="color:green;">✅ SUCCESS</span>' : '<span style="color:red;">❌ FAILED</span>') . "</p>";
echo "<br><p style='color:red; font-weight:bold;'>⚠️ DELETE THIS FILE IMMEDIATELY AFTER USE!</p>";

<?php
// Diagnostic script to debug Filament super admin login
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

echo "<h2>Filament Super Admin Login Diagnostic</h2>";

$email = 'superadmin@lenova.lk';
$password = 'superadmin123';

// 1. Check user exists
$user = User::where('email', $email)->first();
echo "<h3>1. User Check</h3>";
if ($user) {
    echo "<p style='color:green'>✅ User found: ID={$user->id}, Role={$user->role}</p>";
} else {
    echo "<p style='color:red'>❌ User NOT found!</p>";
    exit;
}

// 2. Check password
echo "<h3>2. Password Check</h3>";
$rawHash = \DB::table('users')->where('email', $email)->value('password');
echo "<p>Hash starts with: " . substr($rawHash, 0, 10) . "</p>";
echo "<p>Hash length: " . strlen($rawHash) . "</p>";
$match = password_verify($password, $rawHash);
echo "<p>password_verify result: " . ($match ? '<span style="color:green">✅ MATCH</span>' : '<span style="color:red">❌ NO MATCH</span>') . "</p>";

// 3. Check Auth::attempt
echo "<h3>3. Auth::attempt Check</h3>";
$attemptResult = Auth::attempt(['email' => $email, 'password' => $password]);
echo "<p>Auth::attempt result: " . ($attemptResult ? '<span style="color:green">✅ SUCCESS</span>' : '<span style="color:red">❌ FAILED</span>') . "</p>";
if ($attemptResult) {
    Auth::logout();
}

// 4. Check canAccessPanel
echo "<h3>4. Filament Panel Access Check</h3>";
try {
    $panels = \Filament\Facades\Filament::getPanels();
    foreach ($panels as $panelId => $panel) {
        $canAccess = $user->canAccessPanel($panel);
        echo "<p>Panel '{$panelId}': " . ($canAccess ? '<span style="color:green">✅ CAN ACCESS</span>' : '<span style="color:red">❌ CANNOT ACCESS</span>') . "</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color:red'>Error checking panels: " . $e->getMessage() . "</p>";
}

// 5. Check APP_URL and session config
echo "<h3>5. Environment Config</h3>";
echo "<p>APP_URL: " . config('app.url') . "</p>";
echo "<p>APP_ENV: " . config('app.env') . "</p>";
echo "<p>SESSION_DRIVER: " . config('session.driver') . "</p>";
echo "<p>SESSION_DOMAIN: " . (config('session.domain') ?: 'null') . "</p>";
echo "<p>SANCTUM_STATEFUL_DOMAINS: " . config('sanctum.stateful') . "</p>";

// 6. Check Filament auth guard
echo "<h3>6. Filament Auth Guard</h3>";
try {
    $superAdminPanel = $panels['super-admin'] ?? null;
    if ($superAdminPanel) {
        $guardName = $superAdminPanel->getAuthGuard();
        echo "<p>Super Admin panel auth guard: " . ($guardName ?: 'default (web)') . "</p>";
    }
} catch (\Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr><p style='color:red'><b>DELETE THIS FILE IMMEDIATELY!</b></p>";

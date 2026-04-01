<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

// Find student 'aaa'
$user = User::where('name', 'aaa')->first();
if (!$user) {
    echo "User 'aaa' not found\n";
    exit;
}

// 1. Deactivate user
echo "Deactivating user: " . $user->email . "\n";
$user->update(['deactivated_at' => now()]);

// 2. Mock a login request to AuthController
$request = Illuminate\Http\Request::create('/api/auth/login', 'POST', [
    'usernameOrEmail' => $user->email,
    'password' => $user->plain_password, // Using plain_password stored for debugging
]);

$controller = new App\Http\Controllers\AuthController();
$response = $controller->login($request);

echo "Login Response Status: " . $response->getStatusCode() . "\n";
echo "Login Response Data: " . $response->getContent() . "\n";

// 3. Reactivate user for final state
// $user->update(['deactivated_at' => null]);
echo "User left deactivated for manual verification if needed.\n";

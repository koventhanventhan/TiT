<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'superadmin@lenova.lk';
$password = 'kudu';

if (Illuminate\Support\Facades\Auth::attempt(['email' => $email, 'password' => $password])) {
    $user = Illuminate\Support\Facades\Auth::user();
    echo "Login successful for {$user->email}!\n";
    echo "User role: {$user->role}\n";
} else {
    echo "Login FAILED for {$email}.\n";
    
    // Check if user exists first
    $user = App\Models\User::where('email', $email)->first();
    if ($user) {
        echo "User exists in DB.\n";
        echo "Status: " . ($user->registration_status ?? 'N/A') . "\n";
        if (password_verify($password, $user->password)) {
            echo "Password VERIFIED via script, but Auth::attempt failed.\n";
        } else {
            echo "Password NOT verified via script.\n";
        }
    } else {
        echo "User NOT found in DB.\n";
    }
}

<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'superadmin@lenova.lk';
$user = App\Models\User::where('email', $email)->first();

if ($user) {
    $user->password = Illuminate\Support\Facades\Hash::make('kudu');
    $user->role = 'super_admin';
    $user->institute_id = 1;
    $user->registration_status = 'confirmed';
    $user->save();
    echo "Superadmin updated successfully for $email\n";
} else {
    echo "User $email not found.\n";
}

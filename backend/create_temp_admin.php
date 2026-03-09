<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'tempadmin@lenova.lk';
$user = App\Models\User::where('email', $email)->first() ?: new App\Models\User();

$user->name = 'Temp Admin';
$user->email = $email;
$user->password = Illuminate\Support\Facades\Hash::make('kudu');
$user->role = 'super_admin';
$user->institute_id = 1;
$user->registration_status = 'confirmed';
$user->save();

echo "Temp admin created/updated successfully with email: $email and password: kudu\n";
echo "Role: {$user->role}, Status: {$user->registration_status}, Institute: {$user->institute_id}\n";

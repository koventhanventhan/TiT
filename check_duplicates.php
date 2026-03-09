<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';

use App\Models\User;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Total Users: " . User::count() . "\n";
$users = User::where('name', 'like', '%sasi%')
    ->orWhere('full_name', 'like', '%sasi%')
    ->get(['id', 'name', 'full_name', 'email', 'created_at']);

foreach ($users as $user) {
    echo "ID: {$user->id} | Name: {$user->name} | Full Name: {$user->full_name} | Email: {$user->email} | Created: {$user->created_at}\n";
}

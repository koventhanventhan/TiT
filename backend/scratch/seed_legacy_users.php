<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Create a legacy user (which represents a single child)
$user = User::create([
    'name' => 'legacy_karthik',
    'email' => 'karthik@legacy.com',
    'password' => Hash::make('password123'),
    'role' => 'user',
    'full_name' => 'Karthik Kumar',
    'first_name' => 'Karthik',
    'last_name' => 'Kumar',
    'phone_number' => '0771234567',
    'current_grade' => 'Grade 5',
    'selected_subjects' => 'Maths,Science',
    'medium' => 'english',
    'gender' => 'male',
    'registration_status' => 'approved',
]);

echo "Created Legacy User ID: " . $user->id . "\n";

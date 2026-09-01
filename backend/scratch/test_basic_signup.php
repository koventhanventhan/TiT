<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use App\Models\User;
use App\Models\Student;

$email = 'testnewregisterfix@example.com';

echo "Testing Basic Signup for $email...\n";

$requestData = [
    'email' => $email,
    'password' => 'Password@123',
    // No other fields! Basic signup.
];

$request = Request::create('/api/auth/register', 'POST', $requestData);
$controller = app(AuthController::class);

try {
    $response = $controller->register($request);
    echo "Status Code: " . $response->getStatusCode() . "\n";
    
    // Verify in DB
    $user = User::where('email', $email)->first();
    if ($user) {
        echo "✅ User created successfully with ID: {$user->id}\n";
        
        $studentCount = Student::where('user_id', $user->id)->count();
        if ($studentCount === 0) {
            echo "✅ SUCCESS: No blank student record was created! Bug is fixed.\n";
        } else {
            echo "❌ FAILED: Found $studentCount student records for this user!\n";
        }
    } else {
        echo "❌ User not found in DB!\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

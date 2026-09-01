<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Http\Request;
use App\Http\Controllers\RegistrationController;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

$user = User::where('role', 'user')->first();
if (!$user) {
    die("No user found to test with.\n");
}

echo "Testing End-to-End 'Add Child & Payment' for User: {$user->name} (ID: {$user->id})\n";

// Authenticate
Sanctum::actingAs($user, ['*']);

$controller = app(RegistrationController::class);

// --- STEP 1 ---
echo "--- Executing Step 1 ---\n";
$request1 = Request::create('/api/register/step1', 'POST', [
    'full_name' => 'End to End Child',
    'date_of_birth' => '2015-01-01',
    'gender' => 'female',
    'school_name' => 'Test School',
    'medium' => 'english',
    'current_grade' => 'Grade 7',
    'online_experience' => false,
    'device_used' => 'Tablet',
    'selected_subjects' => 'Science',
]);
$request1->setUserResolver(fn() => $user);

try {
    $response1 = $controller->step1($request1);
    echo "Step 1 Status: " . $response1->getStatusCode() . "\n";
    if ($response1->getStatusCode() !== 201) {
        die("Step 1 Failed: " . $response1->getContent() . "\n");
    }
} catch (\Exception $e) {
    die("Step 1 Error: " . $e->getMessage() . "\n");
}

// --- STEP 2 ---
echo "\n--- Executing Step 2 (Offline Payment) ---\n";
$request2 = Request::create('/api/register/step2', 'POST', [
    'payment_method' => 'offline',
    'amount' => 500,
]);
$request2->setUserResolver(fn() => $user);

try {
    $response2 = $controller->step2($request2);
    echo "Step 2 Status: " . $response2->getStatusCode() . "\n";
    echo "Step 2 Content: " . $response2->getContent() . "\n";
    if ($response2->getStatusCode() === 200) {
        echo "✅ SUCCESS! End-to-End flow works perfectly.\n";
    } else {
        echo "❌ FAILED at Step 2.\n";
    }
} catch (\Exception $e) {
    echo "❌ Step 2 Error: " . $e->getMessage() . "\n";
}

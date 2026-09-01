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

echo "Testing 'Add Child' for User: {$user->name} (ID: {$user->id})\n";

// Authenticate via Sanctum for the current request
Sanctum::actingAs($user, ['*']);

$requestData = [
    'full_name' => 'thevas test',
    'date_of_birth' => '2014-05-10',
    'gender' => 'male',
    'school_name' => 'vembadi school',
    'medium' => 'tamil',
    'current_grade' => 'Grade 6',
    'online_experience' => true,
    'device_used' => 'Mobile',
    'selected_subjects' => 'Maths, Science',
    // NO phone_number included!
];

$request = Request::create('/api/register/step1', 'POST', $requestData);

// Set the user on the request resolver
$request->setUserResolver(function () use ($user) {
    return $user;
});

$controller = app(RegistrationController::class);

try {
    $response = $controller->step1($request);
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Response Content: " . $response->getContent() . "\n";
    
    // Verify it was saved in DB
    $student = \App\Models\Student::where('full_name', 'thevas test')->first();
    if ($student) {
        echo "✅ Student 'thevas test' successfully saved in DB for user_id {$student->user_id}!\n";
    } else {
        echo "❌ Student not found in DB!\n";
    }
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "❌ Validation Error: \n";
    print_r($e->errors());
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\NotificationService;
use App\Models\User;

$user = new User([
    'name' => 'Test Student',
    'id' => 9999,
    'full_name' => 'Local Test Student',
    'email' => 'koventhanventhan153@gmail.com', // Sending to the owner's email
    'phone_number' => '+94701234567' 
]);

echo "Attempting to send Notification (Fallback Test)...\n";

$notifier = app(NotificationService::class);
$success = $notifier->notifyUser(
    $user, 
    'welcome', 
    'tit_welcome',
    ['Local Test Student', 'teststudent123'],
    ['student_name' => 'Local Test Student', 'username' => 'teststudent123']
);

if ($success) {
    echo "Fallback successfully executed! Check your email inbox.\n";
} else {
    echo "Something failed.\n";
}

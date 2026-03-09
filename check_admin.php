<?php
require 'backend/vendor/autoload.php';
$app = require_once 'backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$admins = User::where('role', 'admin')->get();
if ($admins->isEmpty()) {
    echo "NO_ADMINS_FOUND\n";
    exit;
}

foreach ($admins as $admin) {
    echo "ADMIN_EMAIL: " . $admin->email . "\n";
    $settings = $admin->profile_settings['notifications'] ?? [];
    echo "  SETTING_ADMISSION_NEW: " . ($settings['admission_new'] ?? 'NOT_SET') . "\n";
    echo "  SETTING_ADMISSION_PAYMENTS: " . ($settings['admission_payments'] ?? 'NOT_SET') . "\n";
}

$todayStart = now()->startOfDay();
$studentCount = User::where('role', 'user')->where('created_at', '>=', $todayStart)->count();
echo "STUDENTS_REGISTERED_TODAY: " . $studentCount . "\n";

$latest = User::where('role', 'user')->latest()->first();
if ($latest) {
    echo "LATEST_STUDENT_NAME: " . $latest->full_name . "\n";
    echo "LATEST_STUDENT_CREATED: " . $latest->created_at . "\n";
}

<?php
// Ensure we always connect to the actual local database, even if invoked from phpunit testing environment
putenv('APP_ENV=local');
unset($_ENV['APP_ENV'], $_SERVER['APP_ENV']);
putenv('DB_CONNECTION=mysql');
unset($_ENV['DB_CONNECTION'], $_SERVER['DB_CONNECTION']);
putenv('DB_DATABASE');
unset($_ENV['DB_DATABASE'], $_SERVER['DB_DATABASE']);

require __DIR__ . '/../laravel_api/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/../laravel_api/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$action = $argv[1] ?? '';

if ($action === 'get_token') {
    $user = App\Models\User::where('role', 'admin')->first();
    echo $user->createToken('admin-crawler')->plainTextToken;
    exit(0);
}

if ($action === 'set_preset') {
    $preset = $argv[2] ?? 'royal';
    App\Models\SiteSetting::setForInstitute('theme_preset', $preset, 1, 'appearance');
    Illuminate\Support\Facades\Cache::forget('site_settings_1');
    Illuminate\Support\Facades\Cache::forget('site_settings_all');
    echo "Preset set to {$preset}";
    exit(0);
}

if ($action === 'get_student_id') {
    $student = App\Models\User::where('role', 'user')->first();
    echo $student ? $student->id : 'none';
    exit(0);
}

if ($action === 'seed_crawler_data') {
    if (App\Models\Subject::count() < 2) {
        App\Models\Subject::firstOrCreate(['name' => 'Mathematics', 'category' => 'grade_6_to_11'], ['price' => 600]);
        App\Models\Subject::firstOrCreate(['name' => 'Science', 'category' => 'grade_6_to_11'], ['price' => 650]);
    }
    if (App\Models\Package::count() < 2) {
        App\Models\Package::firstOrCreate(['name' => 'Grade 10 Full Package'], [
            'category' => 'grade_6_to_11',
            'medium' => 'tamil',
            'package_price' => 2500,
            'original_price' => 3000,
            'applicable_grades' => ['10'],
            'is_active' => true,
        ]);
        App\Models\Package::firstOrCreate(['name' => 'Grade 11 Full Package'], [
            'category' => 'grade_6_to_11',
            'medium' => 'tamil',
            'package_price' => 2800,
            'original_price' => 3200,
            'applicable_grades' => ['11'],
            'is_active' => true,
        ]);
    }
    $studentId = 363;
    $paidPayment = App\Models\Payment::where('user_id', $studentId)->where('status', 'paid')->first();
    if (!$paidPayment) {
        App\Models\Payment::create([
            'user_id' => $studentId,
            'amount' => 1500,
            'status' => 'paid',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'TXN_TEST_1001',
            'paid_at' => now(),
            'year_month' => '2026-03',
            'institute_id' => 1,
        ]);
    }
    $pendingPayment = App\Models\Payment::where('user_id', $studentId)->where('status', 'pending')->first();
    if (!$pendingPayment) {
        App\Models\Payment::create([
            'user_id' => $studentId,
            'amount' => 1500,
            'status' => 'pending',
            'payment_method' => 'cash',
            'year_month' => '2026-04',
            'institute_id' => 1,
        ]);
    }
    echo "Crawler data seeded";
    exit(0);
}

echo "Unknown action";
exit(1);

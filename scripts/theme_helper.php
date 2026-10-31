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

echo "Unknown action";
exit(1);

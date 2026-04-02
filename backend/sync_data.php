<?php

use Illuminate\Support\Facades\DB;
use Database\Seeders\SyncSettingsSeeder;

define('LARAVEL_START', microtime(true));

// 1. Try to find vendor/autoload.php in parent or current directory
$possibleAutoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php'
];

foreach ($possibleAutoloadPaths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $baseUrl = dirname($path);
        break;
    }
}

if (!isset($baseUrl)) {
    die("ERROR: Could not find vendor/autoload.php. Please check your folder structure.");
}

$app = require_once $baseUrl . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Force Data Sync...\n";

// 2. Manually include the seeder file to bypass autoloader issues
$seederPath = __DIR__ . '/database/seeders/SyncSettingsSeeder.php';
if (file_exists($seederPath)) {
    require_once $seederPath;
    echo "Seeder file loaded successfully.\n";
    
    try {
        $seeder = new SyncSettingsSeeder();
        $seeder->run();
        echo "SUCCESS: All site settings synchronized successfully!\n";
    } catch (\Exception $e) {
        echo "ERROR running seeder: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: SyncSettingsSeeder.php not found at $seederPath\n";
}

// 3. Fix the migration error (timetables table)
try {
    $exists = DB::table('migrations')->where('migration', '2026_03_09_161138_create_timetables_table')->exists();
    if (!$exists) {
        DB::table('migrations')->insert([
            'migration' => '2026_03_09_161138_create_timetables_table',
            'batch' => 1
        ]);
        echo "SUCCESS: Timetables migration marked as completed (Fake migration fix).\n";
    }
} catch (\Exception $e) {
    echo "Wait: Migration fix skipped (already fixed or DB error: " . $e->getMessage() . ")\n";
}

echo "\nSync Complete! Please refresh your admin dashboard.\n";

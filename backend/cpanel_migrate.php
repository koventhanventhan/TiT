<?php
/**
 * cPanel Migration Helper Script - Upload this to /home/titjaffn/public_html/laravel_api/
 * Access it via: https://titjaffna.lk/api/cpanel_migrate.php
 * After running, DELETE this file for security!
 */

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Find Laravel path
$laravelPath = __DIR__ . '/laravel_api';
if (!is_dir($laravelPath)) {
    $laravelPath = __DIR__; // Fallback to current dir
}

echo "<pre>";
echo "Using Laravel at: $laravelPath\n";

// Bootstrap Laravel
require $laravelPath . '/vendor/autoload.php';
$app = require_once $laravelPath . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "<pre>";
echo "=== cPanel Migration Fix ===\n\n";

try {
    // 1. Run migrations
    echo "Running artisan migrate...\n";
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output() . "\n";

    // 2. Check zoom_accounts table
    echo "Verifying 'zoom_accounts' table structure...\n";
    if (Schema::hasTable('zoom_accounts')) {
        if (!Schema::hasColumn('zoom_accounts', 'is_active')) {
            echo "Column 'is_active' MISSING in 'zoom_accounts'. Adding it manually...\n";
            DB::statement("ALTER TABLE zoom_accounts ADD COLUMN is_active BOOLEAN DEFAULT 1 AFTER max_concurrent");
            echo "✅ Column 'is_active' added.\n";
        } else {
            echo "✅ Column 'is_active' already exists.\n";
        }
    } else {
        echo "❌ Table 'zoom_accounts' does NOT exist. Migration might have failed.\n";
    }

    // 3. Clear cache
    echo "\nClearing application cache...\n";
    Artisan::call('cache:clear');
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    echo "✅ Cache cleared.\n";

    echo "\n\n🎉 Done! Now try visiting: https://titjaffna.lk/admin/timetables\n";
    echo "⚠️  REMINDER: Delete this script (cpanel_migrate.php) immediately for security!\n";

} catch (Exception $e) {
    echo "❌ Error occurred: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";

<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "Sanctum Model: " . \Laravel\Sanctum\Sanctum::personalAccessTokenModel() . "\n";
echo "Config Model: " . config('sanctum.models.personal_access_token') . "\n";

try {
    \DB::connection()->getPdo();
    echo "DB Connection: OK\n";
} catch (\Exception $e) {
    echo "DB Connection: FAILED - " . $e->getMessage() . "\n";
}

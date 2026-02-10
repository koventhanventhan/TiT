<?php

/**
 * Simple script to clear all sessions from the database
 * Run: php clear-sessions.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $deleted = DB::table('sessions')->delete();
    echo "✅ Successfully cleared {$deleted} session(s) from database.\n";
} catch (\Exception $e) {
    echo "❌ Error clearing sessions: " . $e->getMessage() . "\n";
    echo "💡 Make sure the sessions table exists. Run: php artisan migrate\n";
}











<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$phone = '94767206279';
$whatsApp = app(\App\Services\WhatsAppService::class);

echo "Testing tit_zoom_reminder with 'en_US'...\n";
$res1 = $whatsApp->sendTemplate($phone, 'tit_zoom_reminder', 'en_US', ['Maths', '12:50']);
echo "Result (en_US): " . ($res1 ? "SUCCESS" : "FAILED") . "\n";

if (!$res1) {
    echo "\nTesting tit_zoom_reminder with 'en' (again to confirm)...\n";
    $res2 = $whatsApp->sendTemplate($phone, 'tit_zoom_reminder', 'en', ['Maths', '12:50']);
    echo "Result (en): " . ($res2 ? "SUCCESS" : "FAILED") . "\n";
}

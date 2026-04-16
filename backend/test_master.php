<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$phone = '94767206279';
$whatsApp = app(\App\Services\WhatsAppService::class);

$langs = ['en', 'en_US', 'en_GB'];

foreach ($langs as $lang) {
    echo "Testing tit_zoom_reminder with '$lang'...\n";
    $res = $whatsApp->sendTemplate($phone, 'tit_zoom_reminder', $lang, ['Maths', '12:50']);
    echo "Result ($lang): " . ($res ? "SUCCESS" : "FAILED") . "\n";
    if ($res) break;
}

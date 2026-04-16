<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$phone = '94767206279';
$whatsApp = app(\App\Services\WhatsAppService::class);

echo "Testing tit_welcome (Known working template)...\n";
$res = $whatsApp->sendTemplate($phone, 'tit_welcome', 'en', ['Test Name', 'Test Username']);
echo "Result (tit_welcome): " . ($res ? "SUCCESS" : "FAILED") . "\n";

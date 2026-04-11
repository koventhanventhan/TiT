<?php
use App\Services\WhatsAppService;

$whatsApp = app(WhatsAppService::class);
$phone = '0767206279';
$template = 'tit_zoom_reminder';
$vars = ['Manual Test', date('H:i')];

echo "Attempting to send test WhatsApp to $phone...\n";
$success = $whatsApp->sendTemplate($phone, $template, 'en', $vars);

if ($success) {
    echo "SUCCESS: Meta Cloud API accepted the message.\n";
} else {
    echo "FAILED: Check laravel.log for errors.\n";
}

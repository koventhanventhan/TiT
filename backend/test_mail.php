<?php

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Starting Mail Test...\n";
echo "Mailer: " . config('mail.default') . "\n";
echo "Host: " . config('mail.mailers.smtp.host') . "\n";
echo "Port: " . config('mail.mailers.smtp.port') . "\n";
echo "Encryption: " . config('mail.mailers.smtp.encryption') . "\n";
echo "Username: " . config('mail.mailers.smtp.username') . "\n";
echo "Password set: " . (config('mail.mailers.smtp.password') ? 'YES' : 'NO') . "\n";

try {
    $to = 'koventhanventhan153@gmail.com';
    echo "Attempting to send test email to $to...\n";
    
    Mail::raw('This is a test email from titjaffna.lk server to verify SMTP settings.', function ($message) use ($to) {
        $message->to($to)
                ->subject('TiT Server Mail Test');
    });

    echo "SUCCESS: Email sent successfully!\n";
} catch (\Exception $e) {
    echo "ERROR: Failed to send email.\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

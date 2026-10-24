<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Mail\PaymentReceiptMail;
use App\Services\PaymentReceiptService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class DiagnosePaymentReceiptsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'receipts:diagnose 
                            {payment_id? : Optional ID of a payment to test} 
                            {--to= : Test email address to send receipt/mail to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose payment receipt PDF generation, font availability, server permissions, and mail configuration';

    public function handle(PaymentReceiptService $receiptService): int
    {
        $hasError = false;
        $this->info("==================================================");
        $this->info("      Payment Receipt Diagnostic (cPanel Check)   ");
        $this->info("==================================================");

        // 1. PHP Version & Memory Limit
        $phpVersion = PHP_VERSION;
        $memoryLimit = ini_get('memory_limit');
        $this->line("PHP Version  : {$phpVersion}");
        $this->line("Memory Limit : {$memoryLimit}");

        // 2. mPDF Autoload Check
        $this->newLine();
        $this->info("--- 1. mPDF Class Autoload ---");
        if (class_exists(\Mpdf\Mpdf::class)) {
            $this->info(" [OK] \Mpdf\Mpdf class is loaded.");
        } else {
            $hasError = true;
            $this->error(" [FAIL] Class \Mpdf\Mpdf NOT FOUND!");
            $this->warn(" >>> CRITICAL: Composer vendor dependencies are missing or incomplete. <<<");
            $this->warn(" >>> RUN THIS COMMAND ON THE CPANEL SERVER TERMINAL:                    <<<");
            $this->warn(" >>>   composer install --no-dev --optimize-autoloader                 <<<");
        }

        // 3. Extensions (mbstring, gd)
        $this->newLine();
        $this->info("--- 2. Required PHP Extensions ---");
        if (extension_loaded('mbstring')) {
            $this->info(" [OK] mbstring extension is loaded.");
        } else {
            $hasError = true;
            $this->error(" [FAIL] mbstring extension is MISSING. Enable mbstring in cPanel PHP Selector.");
        }

        if (extension_loaded('gd')) {
            $this->info(" [OK] gd extension is loaded.");
        } else {
            $hasError = true;
            $this->error(" [FAIL] gd extension is MISSING. Enable gd in cPanel PHP Selector.");
        }

        // 4. storage/app/mpdf directory
        $this->newLine();
        $this->info("--- 3. Temporary Storage Directory (storage/app/mpdf) ---");
        $mpdfDir = storage_path('app/mpdf');
        if (!File::exists($mpdfDir)) {
            $this->line(" Directory does not exist. Creating {$mpdfDir}...");
            try {
                File::makeDirectory($mpdfDir, 0775, true, true);
            } catch (\Throwable $e) {
                // Ignore failure here, is_writable check below will report it
            }
        }

        if (File::isDirectory($mpdfDir) && is_writable($mpdfDir)) {
            $this->info(" [OK] {$mpdfDir} exists and is writable.");
        } else {
            $hasError = true;
            $this->error(" [FAIL] {$mpdfDir} is NOT writable or could not be created.");
            $this->warn(" Fix with: chmod -R 775 storage/app/mpdf (or ensure webserver user owns storage/)");
        }

        // 5. Font files in resources/fonts
        $this->newLine();
        $this->info("--- 4. Font Files (resources/fonts) ---");
        $requiredFonts = [
            'Montserrat-ExtraBold.ttf',
            'NotoSansTamil-Bold.ttf',
            'NotoSansTamil-Regular.ttf',
            'Poppins-Bold.ttf',
        ];

        foreach ($requiredFonts as $font) {
            $fontPath = resource_path('fonts/' . $font);
            if (File::exists($fontPath)) {
                $sizeKb = round(File::size($fontPath) / 1024, 1);
                $this->info(" [OK] {$font} found ({$sizeKb} KB).");
            } else {
                $hasError = true;
                $this->error(" [FAIL] {$font} MISSING at {$fontPath}!");
            }
        }

        // 6. Mail & Queue configuration
        $this->newLine();
        $this->info("--- 5. Mail & Queue Configuration ---");
        $mailer = config('mail.default', 'unknown');
        $mailHost = config("mail.mailers.{$mailer}.host", config('mail.mailers.smtp.host', 'none'));
        $mailFrom = config('mail.from.address', 'none');
        $queueDriver = config('queue.default', 'sync');

        $this->line(" MAIL_MAILER       : {$mailer}");
        $this->line(" MAIL_HOST         : {$mailHost}");
        $this->line(" MAIL_FROM_ADDRESS : {$mailFrom}");
        $this->line(" QUEUE_CONNECTION  : {$queueDriver}");
        $this->line(" (Mail password is masked and never displayed)");

        // 7. Payment test if payment_id provided
        $paymentId = $this->argument('payment_id');
        if ($paymentId) {
            $this->newLine();
            $this->info("--- 6. Test Payment PDF & Mail Delivery (Payment #{$paymentId}) ---");

            $payment = Payment::with(['student', 'institute'])->find($paymentId);
            if (!$payment) {
                $this->error(" [FAIL] Payment #{$paymentId} not found in database.");
                return Command::FAILURE;
            }

            $this->line(" Student: " . ($payment->student ? $payment->student->name . " ({$payment->student->email})" : 'No student linked'));
            $this->line(" Amount : LKR " . number_format($payment->amount, 2) . " [{$payment->status}]");

            $pdfContent = null;
            try {
                $pdfContent = $receiptService->pdf($payment);
                $outPath = storage_path('app/receipt-diagnose.pdf');
                File::put($outPath, $pdfContent);
                $this->info(" [OK] PDF generated successfully (" . round(strlen($pdfContent) / 1024, 1) . " KB).");
                $this->info("      Saved to: {$outPath}");
            } catch (\Throwable $e) {
                $hasError = true;
                $this->error(" [FAIL] PDF Generation Error: " . $e->getMessage());
                $this->error("        Class: " . get_class($e));
                $this->error("        Location: " . $e->getFile() . ':' . $e->getLine());
            }

            // Test Mail Delivery
            $toEmail = $this->option('to') ?: ($payment->student ? $payment->student->email : null);
            if ($toEmail && filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                $this->line(" Sending test receipt email to: {$toEmail}...");
                try {
                    $signedUrl = URL::temporarySignedRoute(
                        'receipts.download',
                        now()->addDays(30),
                        ['payment' => $payment->id]
                    );
                    Mail::to($toEmail)->send(new PaymentReceiptMail($payment, $pdfContent, $signedUrl));
                    $this->info(" [OK] Email sent successfully to {$toEmail}.");
                } catch (\Throwable $e) {
                    $hasError = true;
                    $this->error(" [FAIL] Email Delivery Failed: " . $e->getMessage());
                    $this->error("        Location: " . $e->getFile() . ':' . $e->getLine());
                }
            } else {
                $this->line(" [INFO] No valid recipient email specified. Pass --to=user@example.com to test email sending.");
            }
        }

        $this->newLine();
        $this->info("==================================================");
        if ($hasError) {
            $this->error(" DIAGNOSIS: ISSUES DETECTED. Review errors above.");
            return Command::FAILURE;
        }

        $this->info(" DIAGNOSIS: ALL CHECKS PASSED HEALTHY.");
        return Command::SUCCESS;
    }
}

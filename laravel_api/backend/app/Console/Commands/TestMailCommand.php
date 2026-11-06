<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email} {--institute=1 : Institute ID whose mail configuration to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify mail configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $instituteId = (int) ($this->option('institute') ?: 1);
        $configured = \App\Support\InstituteMail::configureMailer($instituteId);
        $creds = $configured['creds'];

        $this->info("Sending test email to: {$email} (institute #{$instituteId})");
        $this->info("Source: " . ($creds?->source ?? 'none'));
        $this->info("Mailer: " . ($configured['mailer'] ?? 'none'));
        $this->info("Host: " . ($creds?->host ?? 'N/A'));
        $this->info("Port: " . ($creds?->port ?? 'N/A'));

        $result = \App\Support\InstituteMail::sendTestEmail($instituteId, $email);
        if ($result['success']) {
            $this->info("✅ " . $result['message']);
            return self::SUCCESS;
        }

        $this->error("❌ Failed to send email.");
        $this->error($result['message']);
        return self::FAILURE;
    }
}

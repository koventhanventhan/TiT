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
    protected $signature = 'mail:test {email}';

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
        $this->info("Sending test email to: {$email}");
        $this->info("Mailer: " . config('mail.default'));
        $this->info("Host: " . config('mail.mailers.smtp.host'));
        $this->info("Port: " . config('mail.mailers.smtp.port'));

        try {
            Mail::raw('This is a test email from TiT Education.', function (Message $message) use ($email) {
                $message->to($email)
                        ->subject('TiT Education - Test Email');
            });
            $this->info("✅ Test email sent successfully.");
        } catch (\Exception $e) {
            $this->error("❌ Failed to send email.");
            $this->error($e->getMessage());
        }
    }
}

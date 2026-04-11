<?php

namespace App\Console\Commands;

use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class TestWhatsApp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:test-whatsapp {phone} {template} {vars?*}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Send a test WhatsApp message';

    /**
     * Execute the console command.
     */
    public function handle(WhatsAppService $whatsapp)
    {
        $phone = $this->argument('phone');
        $template = $this->argument('template');
        $vars = $this->argument('vars') ?? [];

        $this->info("Sending template '$template' to $phone...");

        if ($whatsapp->sendTemplate($phone, $template, 'en', $vars)) {
            $this->info('Message sent successfully!');
        } else {
            $this->error('Failed to send message. Check storage/logs/laravel.log for details.');
        }
    }
}

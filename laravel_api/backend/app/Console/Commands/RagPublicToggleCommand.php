<?php

namespace App\Console\Commands;

use App\Models\Institute;
use App\Models\SiteSetting;
use App\Services\PublicKnowledgeService;
use Illuminate\Console\Command;

class RagPublicToggleCommand extends Command
{
    protected $signature = 'rag:public {id : The ID of the institute} {state : on or off}';
    protected $description = 'Enable or disable public website assistant for an institute';

    public function handle(PublicKnowledgeService $knowledgeService): int
    {
        $id = (int) $this->argument('id');
        $state = strtolower((string) $this->argument('state'));

        if (!in_array($state, ['on', 'off'])) {
            $this->error("Invalid state '{$state}'. Must be 'on' or 'off'.");
            return 1;
        }

        $institute = Institute::find($id);
        if (!$institute) {
            $this->error("Institute #{$id} not found.");
            return 1;
        }

        if ($state === 'off') {
            $institute->update(['public_assistant_enabled' => false]);
            $knowledgeService->clearCache($id);
            $this->info("Public assistant disabled for institute #{$id} ({$institute->name}).");
            return 0;
        }

        // Safety gate before enabling 'on':
        // Refuse to enable unless the helpline/phone and institute contact details are configured!
        $phone = trim((string) SiteSetting::get('assistant_public_contact_phone', ''));
        $email = trim((string) SiteSetting::get('assistant_public_contact_email', ''));
        $address = trim((string) SiteSetting::get('assistant_public_contact_address', ''));
        $whatsapp = trim((string) SiteSetting::get('assistant_public_contact_whatsapp', ''));

        $missing = [];
        if (empty($phone) && empty($whatsapp)) {
            $missing[] = 'assistant_public_contact_phone or assistant_public_contact_whatsapp';
        }
        if (empty($email) && empty($address)) {
            $missing[] = 'assistant_public_contact_email or assistant_public_contact_address';
        }

        if (!empty($missing)) {
            $this->error("Refusing to enable public assistant for institute #{$id} ({$institute->name}).");
            $this->warn("Required contact settings are missing:");
            foreach ($missing as $m) {
                $this->line("  - {$m}");
            }
            $this->line("Please configure these in the Admin Settings panel before enabling the public assistant.");
            return 1;
        }

        $institute->update(['public_assistant_enabled' => true]);
        $knowledgeService->clearCache($id);
        $this->info("Public assistant successfully enabled for institute #{$id} ({$institute->name}).");
        return 0;
    }
}

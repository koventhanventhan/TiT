<?php

namespace App\Console\Commands;

use App\Models\Institute;
use App\Services\ActivityLogService;
use App\Services\PublicAssistantStatusService;
use App\Services\PublicKnowledgeService;
use Illuminate\Console\Command;

class RagPublicToggleCommand extends Command
{
    protected $signature = 'rag:public {id : The ID of the institute} {state : on or off}';
    protected $description = 'Enable or disable public website assistant for an institute';

    public function handle(PublicKnowledgeService $knowledgeService, PublicAssistantStatusService $statusService): int
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
            ActivityLogService::log(
                'assistant_public_toggle',
                "Public assistant disabled for institute #{$id} ({$institute->name})",
                ['state' => 'off', 'public_assistant_enabled' => false],
                $id
            );
            $knowledgeService->clearCache($id);
            $this->info("Public assistant disabled for institute #{$id} ({$institute->name}).");
            return 0;
        }

        // Shared safety gate before enabling 'on':
        $refusalReasons = [];
        if (!$statusService->canTurnOn($institute, $refusalReasons)) {
            $this->error("Refusing to enable public assistant for institute #{$id} ({$institute->name}).");
            $this->error("Refusing to activate Public Assistant without RECAPTCHA_SECRET_KEY / reCAPTCHA in production.");
            $this->warn("Required prerequisites are missing:");
            foreach ($refusalReasons as $m) {
                $this->line("  - {$m}");
            }
            $this->line("Please configure these in the Admin Settings panel before enabling the public assistant.");
            return 1;
        }

        $institute->update(['public_assistant_enabled' => true]);
        ActivityLogService::log(
            'assistant_public_toggle',
            "Public assistant successfully enabled for institute #{$id} ({$institute->name})",
            ['state' => 'on', 'public_assistant_enabled' => true],
            $id
        );
        $knowledgeService->clearCache($id);
        $this->info("Public assistant successfully enabled for institute #{$id} ({$institute->name}).");
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Institute;
use Illuminate\Console\Command;

class RagInstituteCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rag:institute {id? : Institute ID} {status? : "on" or "off"}';

    /**
     * The console command description.
     */
    protected $description = 'Toggle RAG study assistant for an institute and report enabled institutes';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $id = $this->argument('id');
        $statusArg = $this->argument('status');

        if ($id !== null && $statusArg !== null) {
            $statusNormalized = strtolower($statusArg);
            if (!in_array($statusNormalized, ['on', 'off'], true)) {
                $this->error("Invalid status '{$statusArg}'. Must be 'on' or 'off'.");
                return self::FAILURE;
            }

            $institute = Institute::find($id);
            if (!$institute) {
                $this->error("Institute #{$id} not found.");
                return self::FAILURE;
            }

            $enable = ($statusNormalized === 'on');

            if ($enable) {
                $helplines = config('rag.safety.helplines', []);
                $primaryHelpline = $helplines[0] ?? [];
                $primaryName = trim((string) ($primaryHelpline['name'] ?? ''));
                $primaryNumber = trim((string) ($primaryHelpline['number'] ?? ''));

                $instituteHelpline = $helplines[2] ?? [];
                $configuredInstituteNumber = trim((string) ($instituteHelpline['number'] ?? ''));
                $directInstituteContact = trim((string) (
                    $institute->theme_settings['contact_number'] ??
                    $institute->theme_settings['phone'] ??
                    $institute->phone_number ??
                    $institute->admins()->whereNotNull('phone_number')->where('phone_number', '!=', '')->value('phone_number') ??
                    ''
                ));

                $missing = [];
                if (empty($primaryName) || empty($primaryNumber)) {
                    $missing[] = 'Primary crisis helpline name and phone number (RAG_HELPLINE_PRIMARY_NAME & RAG_HELPLINE_PRIMARY_NUMBER in .env)';
                }
                if (empty($configuredInstituteNumber) && empty($directInstituteContact)) {
                    $missing[] = 'Institute emergency contact number (RAG_HELPLINE_INSTITUTE_NUMBER in .env or institute contact settings)';
                }

                if (!empty($missing)) {
                    $this->error("Cannot enable RAG Study Assistant for institute #{$id} ({$institute->name}).");
                    $this->line("<comment>Student safety guardrails require configured emergency support contacts before enabling the assistant:</comment>");
                    foreach ($missing as $item) {
                        $this->line("  ✗ Missing: {$item}");
                    }
                    $this->line("\nPlease configure these contact details before turning on the study assistant.\n");
                    return self::FAILURE;
                }
            }

            $institute->update(['rag_enabled' => $enable]);
            $label = $enable ? '<info>ENABLED (ON)</info>' : '<comment>DISABLED (OFF)</comment>';
            $this->info("Institute #{$id} ({$institute->name}) RAG Study Assistant is now {$label}.");
            $this->newLine();
        }

        // Report all institutes and their RAG status
        $institutes = Institute::select('id', 'name', 'slug', 'status', 'rag_enabled')
            ->orderBy('id')
            ->get();

        if ($institutes->isEmpty()) {
            $this->warn('No institutes found in database.');
            return self::SUCCESS;
        }

        $rows = [];
        $enabledCount = 0;

        foreach ($institutes as $inst) {
            $isEnabled = (bool) $inst->rag_enabled;
            if ($isEnabled) {
                $enabledCount++;
            }

            $rows[] = [
                $inst->id,
                $inst->name,
                $inst->slug ?? 'default',
                $inst->status ?? 'active',
                $isEnabled ? '<info>ON (Enabled)</info>' : '<comment>OFF (Disabled)</comment>',
            ];
        }

        $this->info("=== Institute RAG Status Report ===");
        $this->table(['ID', 'Name', 'Slug', 'Status', 'RAG Assistant'], $rows);
        $this->info("Summary: {$enabledCount} of " . $institutes->count() . " institute(s) have RAG enabled (Default is OFF / false).\n");

        return self::SUCCESS;
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ProductionFixesPartCTest extends TestCase
{
    /**
     * 1. Test that the schedule list contains NO command-type events (Schedule::events() - none may use shell commands).
     * This protects against host environments where proc_open is disabled.
     */
    public function test_schedule_contains_no_shell_command_events(): void
    {
        $schedule = app(Schedule::class);
        $events = $schedule->events();

        $this->assertNotEmpty($events, 'Scheduled events list must not be empty.');

        foreach ($events as $index => $event) {
            // Must be CallbackEvent (in-process PHP callable execution)
            $this->assertInstanceOf(
                CallbackEvent::class,
                $event,
                "Event at index {$index} is not a CallbackEvent. It uses an external shell command which fails when proc_open is disabled."
            );

            // Command string must be null on CallbackEvents (no proc_open shell call)
            $this->assertNull(
                $event->command,
                "Event at index {$index} has a shell command defined ('{$event->command}'). It must use Schedule::call(...) instead."
            );
        }
    }

    /**
     * 2. Verify that RAG schedule tasks exist and retain their expected cron timings.
     */
    public function test_rag_schedules_exist_with_correct_timings(): void
    {
        $schedule = app(Schedule::class);
        $events = $schedule->events();

        $cronExpressions = array_map(function ($event) {
            return $event->expression;
        }, $events);

        // Daily at 03:00 -> "0 3 * * *"
        $this->assertContains('0 3 * * *', $cronExpressions, 'rag:prune-queries dailyAt 03:00 missing');

        // Every five minutes -> "*/5 * * * *"
        $this->assertContains('*/5 * * * *', $cronExpressions, 'rag:cleanup-stuck-queries everyFiveMinutes missing');

        // Every ten minutes -> "*/10 * * * *"
        $this->assertContains('*/10 * * * *', $cronExpressions, 'rag:cleanup-stuck-materials everyTenMinutes missing');
    }

    /**
     * 3. Verify repo root .htaccess does not have the dead-code safety net rule and documents live maintenance.
     */
    public function test_repo_root_htaccess_has_dead_rule_removed_and_has_documentation(): void
    {
        $htaccessPath = base_path('../../.htaccess');
        $this->assertFileExists($htaccessPath);

        $content = file_get_contents($htaccessPath);

        // Dead safety-net rule must be gone
        $this->assertStringNotContainsString(
            '^laravel_api/backend/public/(admin|api|super-panel|livewire)',
            $content,
            'Dead safety-net rewrite rule was found in root .htaccess and must be removed.'
        );

        // Documentation notice must be present
        $this->assertStringContainsString('LIVE .htaccess Configuration Notice', $content);
        $this->assertStringContainsString('docs/DEPLOYMENT.md', $content);
    }

    /**
     * 4. Verify docs/DEPLOYMENT.md exists and documents deployment rules.
     */
    public function test_deployment_docs_exists(): void
    {
        $deployDocPath = base_path('../../docs/DEPLOYMENT.md');
        $this->assertFileExists($deployDocPath);

        $docContent = file_get_contents($deployDocPath);
        $this->assertStringContainsString('titjaffna.lk', $docContent);
        $this->assertStringContainsString('proc_open', $docContent);
        $this->assertStringContainsString('institutes:index-config', $docContent);
        $this->assertStringContainsString('students:index-counter', $docContent);
    }
}

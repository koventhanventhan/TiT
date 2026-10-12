<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\StudentAiQuery;
use Illuminate\Support\Carbon;

class PruneStudentAiQueries extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rag:prune-queries {--days= : Override configured retention days}';

    /**
     * The console command description.
     */
    protected $description = 'Redact student AI question and answer text older than retention days while preserving telemetry';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('rag.privacy.retention_days', 90));
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Pruning student AI queries older than {$days} days (before {$cutoffDate->toDateTimeString()})...");

        $affected = StudentAiQuery::withoutGlobalScopes()
            ->where('created_at', '<', $cutoffDate)
            ->where(function ($query) {
                $query->where('question', '!=', '[REDACTED]')
                      ->orWhere('answer', '!=', '[REDACTED]');
            })
            ->update([
                'question' => '[REDACTED]',
                'answer' => '[REDACTED]',
            ]);

        $this->info("Successfully redacted text content for {$affected} expired query record(s).");

        return Command::SUCCESS;
    }
}

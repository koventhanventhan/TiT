<?php

namespace App\Console\Commands;

use App\Models\StudentAiQuery;
use Illuminate\Console\Command;

class CleanupStuckAiQueries extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rag:cleanup-stuck-queries {--minutes=5 : Mark processing queries older than this threshold as error}';

    /**
     * The console command description.
     */
    protected $description = 'Mark AI study assistant queries stuck in processing state for over 5 minutes as error';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) ($this->option('minutes') ?: 5);
        $cutoff = now()->subMinutes($minutes);

        $this->info("Scanning for student AI queries stuck in 'processing' for > {$minutes} minutes (before {$cutoff->toDateTimeString()})...");

        $affected = StudentAiQuery::withoutGlobalScopes()
            ->where('status', 'processing')
            ->where('created_at', '<', $cutoff)
            ->update([
                'status' => 'error',
                'answer' => 'Query timed out during processing.',
            ]);

        $this->info("Marked {$affected} stuck query record(s) as 'error'.");

        return Command::SUCCESS;
    }
}

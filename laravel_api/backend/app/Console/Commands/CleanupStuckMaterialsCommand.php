<?php

namespace App\Console\Commands;

use App\Models\LearningMaterial;
use Illuminate\Console\Command;

class CleanupStuckMaterialsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rag:cleanup-stuck-materials {--minutes=15 : Mark processing materials older than this threshold as failed}';

    /**
     * The console command description.
     */
    protected $description = 'Move learning materials stuck in processing state for over 15 minutes to failed with a clear error';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $minutes = (int) ($this->option('minutes') ?: 15);
        $cutoff = now()->subMinutes($minutes);

        $this->info("Scanning for learning materials stuck in 'processing' for > {$minutes} minutes (before {$cutoff->toDateTimeString()})...");

        $affected = LearningMaterial::withoutGlobalScopes()
            ->where('rag_status', 'processing')
            ->where(function ($q) use ($cutoff) {
                $q->where('updated_at', '<', $cutoff)
                  ->orWhereNull('updated_at');
            })
            ->update([
                'rag_status' => 'failed',
                'rag_error' => "Processing timed out after {$minutes} minutes, retry later",
            ]);

        $this->info("Marked {$affected} stuck material(s) as 'failed'.");

        return Command::SUCCESS;
    }
}

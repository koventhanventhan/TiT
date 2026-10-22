<?php

namespace App\Console\Commands;

use App\Models\AssistantVisitorLog;
use Illuminate\Console\Command;

class PruneAssistantVisitorLogsCommand extends Command
{
    protected $signature = 'assistant:prune-visitor-logs';
    protected $description = 'Prune old redacted visitor logs according to privacy retention policy';

    public function handle(): int
    {
        $retentionDays = (int) config('rag.assistant.visitor_log_retention_days', 7);
        $cutoff = now()->subDays($retentionDays);

        $deleted = AssistantVisitorLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} visitor log records older than {$retentionDays} days.");
        return 0;
    }
}

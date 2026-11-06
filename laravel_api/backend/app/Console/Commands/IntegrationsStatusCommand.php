<?php

namespace App\Console\Commands;

use App\Models\Institute;
use App\Support\Integrations;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class IntegrationsStatusCommand extends Command
{
    protected $signature = 'integrations:status {--institute= : Optional institute ID or slug filter}';

    protected $description = 'Display per-institute integration status, credential source (db|env|none), and masked secret summary';

    public function handle(): int
    {
        if (!Schema::hasTable('institutes')) {
            $this->warn('The institutes table does not exist yet.');
            return self::SUCCESS;
        }

        $query = Institute::withoutGlobalScopes()->orderBy('id');

        $filter = $this->option('institute');
        if ($filter !== null && $filter !== '') {
            if (ctype_digit((string) $filter)) {
                $query->where('id', (int) $filter);
            } else {
                $query->where('slug', (string) $filter);
            }
        }

        $institutes = $query->get();

        if ($institutes->isEmpty()) {
            $this->warn('No matching institutes found.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($institutes as $institute) {
            $summary = Integrations::for((int) $institute->id)->statusSummary();
            foreach ($summary as $provider => $info) {
                $maskedPairs = [];
                foreach ($info['masked'] as $k => $v) {
                    $maskedPairs[] = "{$k}={$v}";
                }

                $rows[] = [
                    sprintf('#%d %s', $institute->id, $institute->name),
                    $provider,
                    $info['status'],
                    $info['source'],
                    empty($maskedPairs) ? '—' : implode(', ', $maskedPairs),
                    $info['last_verified_at'] ?? '—',
                ];
            }
        }

        $this->table(
            ['Institute', 'Provider', 'Status', 'Source', 'Credentials (Masked)', 'Last Verified'],
            $rows
        );

        return self::SUCCESS;
    }
}

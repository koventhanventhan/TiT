<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Institute;
use Illuminate\Console\Command;

class InstituteIndexConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'institutes:index-config
                            {institute=1 : Institute ID (default 1)}
                            {--format= : Index format: grade or batch}
                            {--prefix= : Index number prefix (e.g. TIT)}
                            {--start= : Start sequence number for new series (e.g. 1 or 100)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure institute index format, prefix, and starting sequence';

    public function handle(): int
    {
        $instituteId = (int) $this->argument('institute');
        $institute = Institute::find($instituteId);

        if (!$institute) {
            $this->error("Institute with ID {$instituteId} not found.");
            return 1;
        }

        $beforeFormat = $institute->getIndexFormat();
        $beforePrefix = $institute->getIndexPrefix();
        $beforeStart = $institute->index_seq_start ?? ($beforeFormat === 'grade' ? 1 : 100);

        $hasUpdates = false;

        if ($this->option('format') !== null) {
            $format = strtolower((string) $this->option('format'));
            if (!in_array($format, ['grade', 'batch'], true)) {
                $this->error("Invalid format '{$format}'. Allowed values: grade, batch.");
                return 1;
            }
            $institute->index_format = $format;
            $hasUpdates = true;
        }

        if ($this->option('prefix') !== null) {
            $prefix = trim((string) $this->option('prefix'));
            if ($prefix === '') {
                $this->error("Prefix cannot be empty.");
                return 1;
            }
            $institute->index_prefix = $prefix;
            $hasUpdates = true;
        }

        if ($this->option('start') !== null) {
            $start = (int) $this->option('start');
            if ($start < 1) {
                $this->error("Start number must be at least 1.");
                return 1;
            }
            $institute->index_seq_start = $start;
            $hasUpdates = true;
        }

        if ($hasUpdates) {
            $institute->save();

            ActivityLog::create([
                'institute_id' => $institute->id,
                'user_id' => null,
                'action' => 'index_config_updated',
                'description' => "CLI updated index config for Institute #{$institute->id}",
                'metadata' => [
                    'before' => ['format' => $beforeFormat, 'prefix' => $beforePrefix, 'start' => $beforeStart],
                    'after' => [
                        'format' => $institute->getIndexFormat(),
                        'prefix' => $institute->getIndexPrefix(),
                        'start' => $institute->index_seq_start,
                    ],
                ],
            ]);
        }

        $afterFormat = $institute->getIndexFormat();
        $afterPrefix = $institute->getIndexPrefix();
        $afterStart = $institute->index_seq_start ?? ($afterFormat === 'grade' ? 1 : 100);

        $this->info("Institute #{$institute->id} ({$institute->name}) Index Configuration:");
        $this->table(
            ['Setting', 'Before', 'After'],
            [
                ['Index Format', $beforeFormat, $afterFormat],
                ['Prefix', $beforePrefix, $afterPrefix],
                ['Start Number', (string) $beforeStart, (string) $afterStart],
            ]
        );

        if ($hasUpdates) {
            $this->info("Configuration successfully updated.");
        } else {
            $this->line("No options provided. Displaying current configuration.");
        }

        return 0;
    }
}

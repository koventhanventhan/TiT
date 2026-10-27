<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\StudentIndexCounter;
use App\Models\User;
use Illuminate\Console\Command;

class StudentIndexCounterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'students:index-counter
                            {series : Series number (01-99)}
                            {next : Next automatic running sequence number to issue}
                            {institute=1 : Institute ID (default 1)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set the next automatic running number for a specific student index series';

    public function handle(): int
    {
        $seriesRaw = $this->argument('series');
        $seriesInt = (int) $seriesRaw;
        if (!is_numeric($seriesRaw) || $seriesInt < 1 || $seriesInt > 99) {
            $this->error("Series must be a number 01–99. Provided: '{$seriesRaw}'.");
            return 1;
        }

        $nextRaw = $this->argument('next');
        $next = (int) $nextRaw;
        if (!is_numeric($nextRaw) || $next < 1) {
            $this->error("Next sequence number must be a positive integer >= 1. Provided: '{$nextRaw}'.");
            return 1;
        }

        $instituteId = (int) $this->argument('institute');
        $institute = Institute::find($instituteId);
        if (!$institute) {
            $this->error("Institute with ID {$instituteId} not found.");
            return 1;
        }

        $seriesStr = str_pad((string) $seriesInt, 2, '0', STR_PAD_LEFT);

        // Validation against highest issued number in DB for this series
        $highestIssued = 0;
        $users = User::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where(function ($q) use ($seriesInt, $seriesStr) {
                $q->where('batch_year', $seriesInt)
                  ->orWhere('index_number', 'LIKE', "%/{$seriesStr}/%")
                  ->orWhere('index_number', 'LIKE', "%/{$seriesInt}/%");
            })
            ->get(['id', 'index_seq', 'index_number']);

        foreach ($users as $u) {
            if ($u->index_seq && (int) $u->index_seq > $highestIssued) {
                $highestIssued = (int) $u->index_seq;
            }
            if ($u->index_number && preg_match('/\/(\d{3,})$/', $u->index_number, $m)) {
                $seqVal = (int) $m[1];
                if ($seqVal > $highestIssued) {
                    $highestIssued = $seqVal;
                }
            }
        }

        if ($highestIssued > 0 && $next <= $highestIssued) {
            $this->error("Next number must be greater than highest number issued in series {$seriesStr} ({$highestIssued}). Provided: {$next}.");
            return 1;
        }

        $counter = StudentIndexCounter::where('institute_id', $instituteId)
            ->where('batch_year', $seriesInt)
            ->first();

        $beforeLastSeq = $counter ? $counter->last_seq : null;
        $beforeNextSeq = $counter ? ((int) $counter->last_seq + 1) : null;

        if (!$counter) {
            $counter = new StudentIndexCounter([
                'institute_id' => $instituteId,
                'batch_year' => $seriesInt,
            ]);
        }

        $counter->last_seq = $next - 1;
        $counter->save();

        ActivityLog::create([
            'institute_id' => $instituteId,
            'user_id' => null,
            'action' => 'index_counter_updated',
            'description' => "CLI updated series {$seriesStr} next sequence from " . ($beforeNextSeq ?? '(none)') . " to {$next}",
            'metadata' => [
                'series' => $seriesStr,
                'before_last_seq' => $beforeLastSeq,
                'before_next_seq' => $beforeNextSeq,
                'after_last_seq' => $counter->last_seq,
                'after_next_seq' => $next,
                'highest_issued' => $highestIssued,
            ],
        ]);

        $this->info("Student Index Counter for Institute #{$instituteId}, Series {$seriesStr}:");
        $this->table(
            ['Attribute', 'Before', 'After'],
            [
                ['Series', $seriesStr, $seriesStr],
                ['Last Sequence', $beforeLastSeq !== null ? (string)$beforeLastSeq : '(none)', (string)$counter->last_seq],
                ['Next Automatic Number', $beforeNextSeq !== null ? (string)$beforeNextSeq : '(default)', (string)$next],
                ['Highest Number in DB', (string)$highestIssued, (string)$highestIssued],
            ]
        );

        $this->info("Counter successfully updated.");
        return 0;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\LearningMaterial;
use App\Models\StudentAiQuery;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RagStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rag:status
                            {--institute= : Optional Institute ID to filter}
                            {--days=14 : Days of history to analyze}
                            {--list : List materials with ID, title, type, grade, medium, status, and error}';

    /**
     * The console command description.
     */
    protected $description = 'Display operational telemetry: materials by rag_status, failed materials, queries per day, error rates, and flagged queries';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $instituteId = $this->option('institute') ? (int) $this->option('institute') : null;
        $days = (int) ($this->option('days') ?: 14);

        $this->info("=== RAG Operational Telemetry & Health Status ===");
        if ($instituteId) {
            $this->comment("Filtered by Institute #{$instituteId}");
        }
        $this->newLine();

        // 1. Learning Materials by rag_status
        $materialsQuery = LearningMaterial::withoutGlobalScopes()
            ->whereNotNull('file_path')
            ->where(function ($q) {
                $q->where('type', '!=', 'recording')
                  ->orWhereNull('zoom_schedule_id');
            });

        if ($instituteId) {
            $materialsQuery->where('institute_id', $instituteId);
        }

        $materialCounts = (clone $materialsQuery)
            ->selectRaw('COALESCE(rag_status, "pending") as status_key, count(*) as count')
            ->groupBy('status_key')
            ->pluck('count', 'status_key')
            ->toArray();

        $totalMaterials = array_sum($materialCounts);
        $materialStatusRows = [];
        foreach (['ready', 'processing', 'pending', 'quality_pending', 'low_quality', 'skipped', 'failed', 'unsupported'] as $status) {
            $cnt = $materialCounts[$status] ?? 0;
            $pct = $totalMaterials > 0 ? round(($cnt / $totalMaterials) * 100, 1) : 0;
            $formattedStatus = match ($status) {
                'ready' => '<info>ready</info>',
                'failed' => '<error>failed</error>',
                'quality_pending' => '<comment>quality_pending</comment>',
                'low_quality' => '<comment>low_quality</comment>',
                'skipped' => '<comment>skipped</comment>',
                'processing' => '<comment>processing</comment>',
                default => $status,
            };
            $materialStatusRows[] = [$formattedStatus, $cnt, "{$pct}%"];
        }

        $this->info("1. Learning Materials by RAG Status (Total: {$totalMaterials})");
        $this->table(['Status', 'Count', 'Share'], $materialStatusRows);

        $ocrWarningCount = (clone $materialsQuery)
            ->where('rag_status', 'ready')
            ->where('rag_error', 'like', 'Warning: OCR uncertainty%')
            ->count();
        if ($ocrWarningCount > 0) {
            $this->warn(" • OCR Uncertainty Warnings: {$ocrWarningCount} material(s) have OCR discrepancy warnings");
        }
        $this->newLine();

        // Optional Detailed Material Listing
        if ($this->option('list')) {
            $allMaterials = (clone $materialsQuery)
                ->select('id', 'title', 'type', 'grade', 'medium', 'rag_status', 'rag_error')
                ->orderBy('id')
                ->get();

            $listRows = $allMaterials->map(function ($m) {
                return [
                    $m->id,
                    mb_substr($m->title ?? '-', 0, 25),
                    $m->type ?? '-',
                    $m->grade ?? '-',
                    $m->medium ?? '-',
                    $m->rag_status ?? 'pending',
                    mb_substr($m->rag_error ?? '-', 0, 60),
                ];
            });

            $this->info("Detailed Material Listing (Total: " . $allMaterials->count() . ")");
            $this->table(
                ['ID', 'Title', 'Type', 'Grade', 'Medium', 'Status', 'Error'],
                $listRows
            );
            $this->newLine();
        }

        // 2. Failed and Low Quality Materials with rag_error
        $failedMaterials = (clone $materialsQuery)
            ->whereIn('rag_status', ['failed', 'low_quality', 'quality_pending'])
            ->select('id', 'title', 'institute_id', 'rag_status', 'rag_error', 'updated_at')
            ->limit(20)
            ->get();

        $this->info("2. Failed, Low-Quality & Quality-Pending Materials (showing up to 20)");
        if ($failedMaterials->isEmpty()) {
            $this->line(" <info>No failed or low-quality materials found.</info>");
        } else {
            $failedRows = $failedMaterials->map(function ($m) {
                return [
                    $m->id,
                    mb_substr($m->title, 0, 30),
                    $m->institute_id,
                    $m->rag_status,
                    mb_substr($m->rag_error ?? 'Unknown error', 0, 50),
                    $m->updated_at?->diffForHumans() ?? '-',
                ];
            });
            $this->table(['ID', 'Title', 'Institute', 'Status', 'RAG Error', 'Updated At'], $failedRows);
        }
        $this->newLine();

        // 3. Query Statistics & Rate Metrics (Last N days)
        $queriesQuery = StudentAiQuery::withoutGlobalScopes()
            ->where('created_at', '>=', now()->subDays($days));

        if ($instituteId) {
            $queriesQuery->where('institute_id', $instituteId);
        }

        $totalQueries = (clone $queriesQuery)->count();
        $noNotesFoundCount = (clone $queriesQuery)->where('status', 'no_notes_found')->count();
        $answeredCount = (clone $queriesQuery)->where('status', 'answered')->count();
        $errorCount = (clone $queriesQuery)->where('status', 'error')->count();
        $abortedCount = (clone $queriesQuery)->where('status', 'aborted')->count();
        $distressCount = (clone $queriesQuery)->where('is_flagged', true)->count();
        $safetyBlockCount = (clone $queriesQuery)->where(function ($q) {
            $q->where('status', 'blocked_safety')->orWhere('flag_reason', 'safety_block');
        })->count();

        $ratedQueries = (clone $queriesQuery)->whereNotNull('feedback_rating')->count();
        $thumbsDownCount = (clone $queriesQuery)->where('feedback_rating', 'down')->count();
        $thumbsUpCount = (clone $queriesQuery)->where('feedback_rating', 'up')->count();

        $noNotesFoundRate = $totalQueries > 0 ? round(($noNotesFoundCount / $totalQueries) * 100, 1) : 0.0;
        $thumbsDownRate = $ratedQueries > 0 ? round(($thumbsDownCount / $ratedQueries) * 100, 1) : 0.0;
        $thumbsUpRate = $ratedQueries > 0 ? round(($thumbsUpCount / $ratedQueries) * 100, 1) : 0.0;

        $this->info("3. Overall Query Quality Rates (Last {$days} Days)");
        $this->table(
            ['Metric', 'Count', 'Rate'],
            [
                ['Total Student Queries', $totalQueries, '100%'],
                ['Answered (Grounded in Notes)', $answeredCount, $totalQueries > 0 ? round(($answeredCount / $totalQueries) * 100, 1) . '%' : '0%'],
                ['No Notes Found Rate', $noNotesFoundCount, "{$noNotesFoundRate}%"],
                ['Aborted / Stopped by Student', $abortedCount, $totalQueries > 0 ? round(($abortedCount / $totalQueries) * 100, 1) . '%' : '0%'],
                ['Server / Processing Error Rate', $errorCount, $totalQueries > 0 ? round(($errorCount / $totalQueries) * 100, 1) . '%' : '0%'],
                ['Total Rated Queries', $ratedQueries, $totalQueries > 0 ? round(($ratedQueries / $totalQueries) * 100, 1) . '% of total' : '0%'],
                ['Thumbs Down (Negative Feedback)', $thumbsDownCount, "{$thumbsDownRate}% of rated"],
                ['Thumbs Up (Positive Feedback)', $thumbsUpCount, "{$thumbsUpRate}% of rated"],
                ['Flagged Distress Queries', $distressCount, $totalQueries > 0 ? round(($distressCount / $totalQueries) * 100, 1) . '%' : '0%'],
                ['Blocked Safety Queries', $safetyBlockCount, $totalQueries > 0 ? round(($safetyBlockCount / $totalQueries) * 100, 1) . '%' : '0%'],
            ]
        );
        $this->newLine();

        // 4. Queries per Day (Past 7 Days)
        $dailyRows = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $dayQueries = (clone $queriesQuery)->whereBetween('created_at', [$dayStart, $dayEnd]);
            $dayTotal = (clone $dayQueries)->count();
            $dayAnswered = (clone $dayQueries)->where('status', 'answered')->count();
            $dayNoNotes = (clone $dayQueries)->where('status', 'no_notes_found')->count();
            $dayFlagged = (clone $dayQueries)->where('is_flagged', true)->count();
            $dayError = (clone $dayQueries)->where('status', 'error')->count();

            $dailyRows[] = [
                $date->format('Y-m-d (D)'),
                $dayTotal,
                $dayAnswered,
                $dayNoNotes,
                $dayFlagged,
                $dayError,
            ];
        }

        $this->info("4. Daily Queries Breakdown (Past 7 Days)");
        $this->table(['Date', 'Total', 'Answered', 'No Notes', 'Distress', 'Errors'], $dailyRows);
        $this->newLine();

        // 5. Flagged Distress Queries (excluding safety blocks which are count-only)
        $distressQueries = (clone $queriesQuery)
            ->where('is_flagged', true)
            ->where('flag_reason', '!=', 'safety_block')
            ->select('id', 'institute_id', 'user_id', 'flag_reason', 'question', 'created_at')
            ->latest()
            ->limit(10)
            ->get();

        $this->info("5. Recent Distress Queries for Review (Past {$days} Days, showing up to 10)");
        if ($distressQueries->isEmpty()) {
            $this->line(" <info>No distress queries recorded.</info>");
        } else {
            $distressRows = $distressQueries->map(function ($q) {
                return [
                    $q->id,
                    $q->institute_id,
                    $q->user_id,
                    $q->flag_reason ?? 'Personal distress',
                    mb_substr($q->question ?? '', 0, 40) . '...',
                    $q->created_at?->diffForHumans() ?? '-',
                ];
            });
            $this->table(['ID', 'Institute', 'Student ID', 'Distress Reason', 'Question Preview', 'Logged At'], $distressRows);
        }
        $this->newLine();

        return self::SUCCESS;
    }
}

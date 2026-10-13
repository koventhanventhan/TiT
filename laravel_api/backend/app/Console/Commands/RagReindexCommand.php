<?php

namespace App\Console\Commands;

use App\Jobs\IngestLearningMaterialJob;
use App\Models\LearningMaterial;
use Illuminate\Console\Command;
use Throwable;

class RagReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'rag:reindex
                            {--institute= : Filter materials by Institute ID}
                            {--grade= : Filter materials by grade (e.g. grade-10)}
                            {--type= : Filter materials by type (e.g. note, paper, revision)}
                            {--only-missing : Only reindex materials that are not in "ready" status or lack chunks}
                            {--dry-run : Simulate and estimate OCR and embedding API calls without indexing}
                            {--limit= : Limit the number of materials to process}
                            {--delay=1 : Seconds to pause between materials to respect rate limits}';

    /**
     * The console command description.
     */
    protected $description = 'Backfill and reindex learning materials into the RAG vector store';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $instituteId = $this->option('institute');
        $grade = $this->option('grade');
        $type = $this->option('type');
        $onlyMissing = (bool) $this->option('only-missing');
        $isDryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $delay = max(0, (int) $this->option('delay'));

        $query = LearningMaterial::withoutGlobalScopes()
            ->whereNotNull('file_path')
            ->where(function ($q) {
                // Ignore zoom recordings without file path or audio
                $q->where('type', '!=', 'recording')
                  ->orWhereNull('zoom_schedule_id');
            });

        if (!empty($instituteId)) {
            $query->where('institute_id', (int) $instituteId);
        }

        if (!empty($grade)) {
            $query->where('grade', $grade);
        }

        if (!empty($type)) {
            $query->where('type', $type);
        }

        if ($onlyMissing) {
            $query->where(function ($q) {
                $q->where('rag_status', '!=', 'ready')
                  ->orWhereNull('rag_status')
                  ->orWhereDoesntHave('chunks');
            });
        }

        if ($limit !== null) {
            $query->limit($limit);
        }

        $materials = $query->orderBy('id')->get();
        $totalCount = $materials->count();

        $this->info("=== RAG Reindex Backfill " . ($isDryRun ? "[DRY RUN]" : "[LIVE EXECUTION]") . " ===");
        $this->line("Filters Applied:");
        $this->line(" • Institute:    " . ($instituteId ?: 'All'));
        $this->line(" • Grade:        " . ($grade ?: 'All'));
        $this->line(" • Type:         " . ($type ?: 'All (excluding recordings)'));
        $this->line(" • Only Missing: " . ($onlyMissing ? 'Yes' : 'No'));
        $this->line(" • Matched:      {$totalCount} material(s)\n");

        if ($totalCount === 0) {
            $this->comment("No materials matched the specified filters.");
            return self::SUCCESS;
        }

        // Calculate pages and estimates
        $knownPages = 0;
        $materialsWithPages = 0;
        foreach ($materials as $m) {
            if (!empty($m->page_count) && $m->page_count > 0) {
                $knownPages += $m->page_count;
                $materialsWithPages++;
            }
        }

        // If page_count is not yet populated, use an average of 5 pages per document
        $avgPages = $materialsWithPages > 0 ? ($knownPages / $materialsWithPages) : 5.0;
        $estimatedTotalPages = $knownPages + (int) round(($totalCount - $materialsWithPages) * $avgPages);

        // OCR estimate: ~25% of pages in notes/past papers require Vision OCR
        $estimatedOcrPages = (int) ceil($estimatedTotalPages * 0.25);

        // Embedding estimate: ~2 chunks per page, batched at 25 chunks per API call
        $batchSize = (int) config('rag.chunking.embedding_batch_size', 25);
        $estimatedChunks = $estimatedTotalPages * 2;
        $estimatedEmbeddingCalls = (int) ceil($estimatedChunks / $batchSize);

        if ($isDryRun) {
            $this->table(
                ['Metric', 'Estimated Value'],
                [
                    ['Materials to Process', number_format($totalCount)],
                    ['Total Estimated Document Pages', number_format($estimatedTotalPages)],
                    ['Estimated Gemini Vision OCR Pages (~25%)', number_format($estimatedOcrPages)],
                    ['Estimated Text Chunks (~2 per page)', number_format($estimatedChunks)],
                    ['Estimated Gemini batchEmbedContents Calls', number_format($estimatedEmbeddingCalls)],
                    ['Batch Size per Embedding Call', $batchSize],
                    ['Configured Delay Between Materials', "{$delay} second(s)"],
                    ['Estimated Runtime', gmdate('H:i:s', $totalCount * ($delay + 2))],
                ]
            );

            $this->info("Dry run completed. No files were modified or sent to Gemini API.");
            $this->line("To run actual backfill, remove the --dry-run option.");
            return self::SUCCESS;
        }

        // Live Execution
        $this->info("Starting live backfill of {$totalCount} materials with {$delay}s rate-limit delay...\n");
        $bar = $this->output->createProgressBar($totalCount);
        $bar->start();

        $successCount = 0;
        $failCount = 0;
        $skippedCount = 0;

        foreach ($materials as $material) {
            try {
                // If only-missing is set and material is already ready with chunks, skip
                if ($onlyMissing && $material->rag_status === 'ready' && $material->chunks()->exists()) {
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }

                // Ingest synchronously
                IngestLearningMaterialJob::dispatchSync($material->id);

                $material->refresh();
                if ($material->rag_status === 'ready') {
                    $successCount++;
                } else {
                    $failCount++;
                }

                if ($delay > 0) {
                    sleep($delay);
                }

            } catch (Throwable $e) {
                $failCount++;
                $this->newLine();
                $this->error("Failed to ingest Material #{$material->id} ('{$material->title}'): " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("=== Backfill Complete ===");
        $this->line(" • Succeeded: {$successCount}");
        $this->line(" • Failed:    {$failCount}");
        $this->line(" • Skipped:   {$skippedCount}");
        $this->line("Note: The backfill is resumable. Re-running with --only-missing will process any remaining or failed items.\n");

        return self::SUCCESS;
    }
}

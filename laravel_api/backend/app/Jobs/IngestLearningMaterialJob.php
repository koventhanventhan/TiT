<?php

namespace App\Jobs;

use App\Contracts\Rag\GeminiClientInterface;
use App\Contracts\Rag\VectorStoreInterface;
use App\Models\LearningMaterial;
use App\Services\Rag\FileTooLargeException;
use App\Services\Rag\MultilingualTextChunker;
use App\Services\Rag\OcrPageLimitExceededException;
use App\Services\Rag\OcrTimeLimitException;
use App\Services\Rag\OcrUnavailableException;
use App\Services\Rag\PageLimitExceededException;
use App\Services\Rag\PdfTextExtractor;
use App\Services\Rag\QualityCheckUnavailableException;
use App\Services\Rag\TextReadabilityChecker;
use App\Services\Rag\UnsupportedFileTypeException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IngestLearningMaterialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $materialId;
    public int $tries = 3;
    public int $timeout = 120;
    public array $backoff = [10, 30, 60];

    public function __construct(int $materialId)
    {
        $this->materialId = $materialId;
    }

    /**
     * Execute the job.
     */
    public function handle(
        VectorStoreInterface $vectorStore,
        PdfTextExtractor $extractor,
        ?MultilingualTextChunker $chunker = null,
        ?GeminiClientInterface $geminiClient = null,
        ?TextReadabilityChecker $readabilityChecker = null
    ): void {
        $material = LearningMaterial::withoutGlobalScopes()->find($this->materialId);

        if (!$material) {
            Log::info("IngestLearningMaterialJob: Material #{$this->materialId} not found, skipping.");
            return;
        }

        // Fail-closed security rule: Material must have an explicit institute_id
        if (empty($material->institute_id)) {
            Log::error("IngestLearningMaterialJob: Material #{$material->id} has no institute_id assigned.");
            $material->updateQuietly([
                'rag_status' => 'failed',
                'rag_error' => 'Material has no institute_id assigned.',
            ]);
            return;
        }

        // 1. Skip non-document materials (recordings, external link-only)
        if ($material->type === 'recording' || empty($material->file_path)) {
            Log::info("IngestLearningMaterialJob: Material #{$material->id} is a recording or URL-only. Marking skipped.");
            $vectorStore->deleteByMaterial($material->id);
            $material->updateQuietly([
                'rag_status' => 'skipped',
                'rag_error' => null,
            ]);
            return;
        }

        $material->updateQuietly([
            'rag_status' => 'processing',
            'rag_error' => null,
        ]);

        $tempDir = "rag_temp/material_{$material->id}";

        try {
            // 2. Inspect document and classify pages into native vs OCR
            $inspection = $extractor->inspectPagesForIngestion($material);
            if (!is_array($inspection) || !isset($inspection['total_pages'])) {
                $extracted = $extractor->extract($material);
                $inspection = [
                    'total_pages' => count($extracted),
                    'native_pages' => $extracted,
                    'pages_needing_ocr' => [],
                ];
            }
            $totalPages = $inspection['total_pages'];
            $nativePages = $inspection['native_pages'];
            $pagesNeedingOcr = $inspection['pages_needing_ocr'];

            // Store native pages in temporary stage for finalizer
            foreach ($nativePages as $nativePage) {
                $pageNum = $nativePage['page_number'];
                Storage::disk('local')->put("{$tempDir}/page_{$pageNum}.json", json_encode([
                    'page_number' => $pageNum,
                    'text' => $nativePage['text'],
                    'used_ocr' => false,
                    'word_count' => $nativePage['word_count'] ?? 0,
                    'completed_at' => now()->timestamp,
                ]));
            }

            $ocrMaxPages = (int) config('rag.ingestion.ocr_max_pages', 20);
            if (count($pagesNeedingOcr) > $ocrMaxPages) {
                throw new OcrPageLimitExceededException("Document exceeds maximum OCR limit of {$ocrMaxPages} pages (requires: " . count($pagesNeedingOcr) . ").");
            }

            // 3. Prepare pipeline jobs: one ProcessMaterialOcrPageJob per OCR page, then FinalizeMaterialIngestionJob
            $jobs = [];
            foreach ($pagesNeedingOcr as $pageNum) {
                $jobs[] = new ProcessMaterialOcrPageJob($material->id, $pageNum, $totalPages);
            }
            $finalizeJob = new FinalizeMaterialIngestionJob($material->id, $totalPages);
            $allPipelineJobs = array_merge($jobs, [$finalizeJob]);

            // Synchronous vs Asynchronous execution
            // If running without a queued job context (e.g. dispatchSync from rag:reindex), run each step synchronously
            $isSync = ($this->job === null || $this->job instanceof \Illuminate\Queue\Jobs\SyncJob);
            if ($isSync) {
                foreach ($allPipelineJobs as $pipelineJob) {
                    app(\Illuminate\Contracts\Bus\Dispatcher::class)->dispatchSync($pipelineJob);
                }
            } else {
                // When executing inside a queue worker, chain the page-by-page OCR jobs and finalizer
                Bus::chain($allPipelineJobs)->dispatch();
            }

        } catch (OcrUnavailableException $e) {
            $shortMsg = $e->getFormattedRagError();
            Log::warning("IngestLearningMaterialJob: OCR unavailable for material #{$material->id}: {$shortMsg}");
            $vectorStore->deleteByMaterial($material->id);
            $material->updateQuietly([
                'rag_status' => 'failed',
                'rag_error' => $shortMsg,
            ]);
            Storage::disk('local')->deleteDirectory($tempDir);
            return;

        } catch (OcrTimeLimitException $e) {
            $shortMsg = 'OCR time limit, retry later';
            Log::warning("IngestLearningMaterialJob: OCR time limit exceeded for material #{$material->id}: {$shortMsg}");
            $vectorStore->deleteByMaterial($material->id);
            $material->updateQuietly([
                'rag_status' => 'failed',
                'rag_error' => $shortMsg,
            ]);
            Storage::disk('local')->deleteDirectory($tempDir);
            return;

        } catch (QualityCheckUnavailableException $e) {
            $shortMsg = 'quality check unavailable, retry later';
            Log::warning("IngestLearningMaterialJob: Readability check unavailable for material #{$material->id}: {$shortMsg}");
            $vectorStore->deleteByMaterial($material->id);
            $material->updateQuietly([
                'rag_status' => 'quality_pending',
                'rag_error' => $shortMsg,
            ]);
            return;

        } catch (UnsupportedFileTypeException $e) {
            $cleanMsg = $this->sanitizeErrorMessage($e->getMessage());
            Log::warning("IngestLearningMaterialJob: Unsupported file type for material #{$material->id}: " . $cleanMsg);
            $vectorStore->deleteByMaterial($material->id);
            $material->updateQuietly([
                'rag_status' => 'unsupported',
                'rag_error' => $cleanMsg,
            ]);
            Storage::disk('local')->deleteDirectory($tempDir);
            return;

        } catch (FileTooLargeException|PageLimitExceededException|OcrPageLimitExceededException|\RuntimeException $e) {
            $cleanMsg = $this->sanitizeErrorMessage($e->getMessage());
            Log::warning("IngestLearningMaterialJob: Document processing failed for material #{$material->id}: " . $cleanMsg);
            $vectorStore->deleteByMaterial($material->id);
            $material->updateQuietly([
                'rag_status' => 'failed',
                'rag_error' => $cleanMsg,
            ]);
            Storage::disk('local')->deleteDirectory($tempDir);
            return;

        } catch (Throwable $e) {
            $cleanMsg = $this->sanitizeErrorMessage($e->getMessage());
            Log::error("IngestLearningMaterialJob: Failed for material #{$material->id}: " . $cleanMsg, [
                'exception' => $e,
            ]);

            $vectorStore->deleteByMaterial($material->id);
            $material->updateQuietly([
                'rag_status' => 'failed',
                'rag_error' => $cleanMsg,
            ]);
            Storage::disk('local')->deleteDirectory($tempDir);

            // Re-throw so Laravel queue worker retries transient failures
            throw $e;
        }
    }

    /**
     * Handle job failure when retries exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $material = LearningMaterial::withoutGlobalScopes()->find($this->materialId);
        if ($material) {
            $cleanMsg = match (true) {
                $exception instanceof OcrUnavailableException => $exception->getFormattedRagError(),
                $exception instanceof OcrTimeLimitException => 'OCR time limit, retry later',
                $exception instanceof QualityCheckUnavailableException => 'quality check unavailable, retry later',
                default => $this->sanitizeErrorMessage($exception ? $exception->getMessage() : 'Ingestion failed permanently.'),
            };
            $status = ($exception instanceof QualityCheckUnavailableException) ? 'quality_pending' : 'failed';
            $material->updateQuietly([
                'rag_status' => $status,
                'rag_error' => $cleanMsg,
            ]);
        }

        Storage::disk('local')->deleteDirectory("rag_temp/material_{$this->materialId}");
        Log::error("IngestLearningMaterialJob: Permanently failed for material #{$this->materialId}.");
    }

    protected function sanitizeErrorMessage(string $message): string
    {
        $sanitized = preg_replace('/key=[A-Za-z0-9._-]+/i', 'key=***', $message);
        $sanitized = strip_tags($sanitized);
        return mb_substr(trim($sanitized), 0, 500);
    }
}

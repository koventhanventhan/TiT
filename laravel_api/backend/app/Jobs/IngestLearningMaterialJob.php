<?php

namespace App\Jobs;

use App\Contracts\Rag\GeminiClientInterface;
use App\Contracts\Rag\VectorStoreInterface;
use App\Models\LearningMaterial;
use App\Services\Rag\MultilingualTextChunker;
use App\Services\Rag\PdfTextExtractor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class IngestLearningMaterialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $materialId;
    public int $tries = 3;
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
        MultilingualTextChunker $chunker,
        GeminiClientInterface $geminiClient
    ): void {
        $material = LearningMaterial::withoutGlobalScopes()->find($this->materialId);

        if (!$material) {
            Log::info("IngestLearningMaterialJob: Material #{$this->materialId} not found, skipping.");
            return;
        }

        // 1. Skip non-document materials (recordings, external link-only)
        if ($material->type === 'recording' || empty($material->file_path)) {
            Log::info("IngestLearningMaterialJob: Material #{$material->id} is a recording or URL-only. Marking skipped.");
            $vectorStore->deleteByMaterial($material->id);
            $material->update([
                'rag_status' => 'skipped',
                'rag_error' => null,
            ]);
            return;
        }

        $material->update([
            'rag_status' => 'processing',
            'rag_error' => null,
        ]);

        try {
            // 2. Extract text from PDF document
            $pages = $extractor->extract($material);

            if (empty($pages)) {
                $vectorStore->deleteByMaterial($material->id);
                $material->update([
                    'rag_status' => 'skipped',
                    'rag_error' => 'No extractable text found in file (empty or unsupported format).',
                ]);
                return;
            }

            // 3. Chunk page text preserving Unicode words
            $chunks = $chunker->chunkPages($pages);

            if (empty($chunks)) {
                $vectorStore->deleteByMaterial($material->id);
                $material->update([
                    'rag_status' => 'skipped',
                    'rag_error' => 'Document generated 0 text chunks.',
                ]);
                return;
            }

            $embeddingModel = (string) config('rag.models.embedding', 'gemini-embedding-2');
            $dimensions = (int) config('rag.models.embedding_dimensions', 768);

            // 4. Generate embeddings and save chunks inside an idempotent database transaction
            DB::transaction(function () use ($vectorStore, $geminiClient, $material, $chunks, $embeddingModel, $dimensions) {
                // Idempotency: clean up any existing chunks for this material first
                $vectorStore->deleteByMaterial($material->id);

                foreach ($chunks as $chunkData) {
                    $content = $chunkData['content'];
                    if (empty(trim($content))) {
                        continue;
                    }

                    // Generate embedding for document chunk
                    $floatVector = $geminiClient->embedContent(
                        $content,
                        'RETRIEVAL_DOCUMENT',
                        $material->title
                    );

                    $vectorStore->saveChunk([
                        'institute_id' => $material->institute_id ?? 1,
                        'material_id' => $material->id,
                        'grade' => $material->grade,
                        'medium' => $material->medium,
                        'subject' => $material->subject,
                        'chunk_type' => 'page',
                        'page_number' => $chunkData['page_number'] ?? 1,
                        'content' => $content,
                        'embedding_model' => $embeddingModel,
                        'dimensions' => $dimensions,
                        'word_count' => $chunkData['word_count'] ?? 0,
                    ], $floatVector);
                }

                $material->update([
                    'rag_status' => 'ready',
                    'rag_error' => null,
                    'rag_indexed_at' => now(),
                ]);
            });

            Log::info("IngestLearningMaterialJob: Successfully indexed material #{$material->id} (" . count($chunks) . " chunks).");

        } catch (Throwable $e) {
            Log::error("IngestLearningMaterialJob: Failed for material #{$material->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Re-throw so Laravel's queue retries with backoff up to $tries
            throw $e;
        }
    }

    /**
     * Handle a job failure after all retries are exhausted.
     */
    public function failed(?Throwable $exception): void
    {
        $material = LearningMaterial::withoutGlobalScopes()->find($this->materialId);
        if ($material) {
            $material->update([
                'rag_status' => 'failed',
                'rag_error' => substr($exception ? $exception->getMessage() : 'Ingestion failed.', 0, 1000),
            ]);
        }

        Log::error("IngestLearningMaterialJob: Permanently failed for material #{$this->materialId}. Marked as failed.");
    }
}

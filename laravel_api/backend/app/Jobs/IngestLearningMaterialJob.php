<?php

namespace App\Jobs;

use App\Contracts\Rag\GeminiClientInterface;
use App\Contracts\Rag\VectorStoreInterface;
use App\Models\LearningMaterial;
use App\Services\Rag\FileTooLargeException;
use App\Services\Rag\MultilingualTextChunker;
use App\Services\Rag\OcrPageLimitExceededException;
use App\Services\Rag\PageLimitExceededException;
use App\Services\Rag\PdfTextExtractor;
use App\Services\Rag\UnsupportedFileTypeException;
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
    public int $timeout = 110;
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

        try {
            // 2. Extract text from document (enforces max_pages, max_file_size, OCR limits)
            $pages = $extractor->extract($material);

            if (empty($pages)) {
                $vectorStore->deleteByMaterial($material->id);
                $material->updateQuietly([
                    'rag_status' => 'skipped',
                    'rag_error' => 'No extractable text found in file (empty or unsupported format).',
                ]);
                return;
            }

            // 3. Chunk page text preserving sentence and Unicode boundaries
            $chunks = $chunker->chunkPages($pages);

            $validChunks = array_values(array_filter($chunks, function ($c) {
                return !empty(trim($c['content'] ?? ''));
            }));

            if (empty($validChunks)) {
                $vectorStore->deleteByMaterial($material->id);
                $material->updateQuietly([
                    'rag_status' => 'skipped',
                    'rag_error' => 'Document generated 0 text chunks.',
                ]);
                return;
            }

            $embeddingModel = (string) config('rag.models.embedding', 'gemini-embedding-2');
            $dimensions = (int) config('rag.models.embedding_dimensions', 768);

            // 4. Generate ALL embeddings outside of database transactions using batchEmbedContents
            $chunkTexts = array_column($validChunks, 'content');
            $allEmbeddings = $geminiClient->batchEmbedContents(
                $chunkTexts,
                'RETRIEVAL_DOCUMENT',
                $material->title
            );

            if (count($allEmbeddings) !== count($validChunks)) {
                throw new \RuntimeException('Mismatch between chunk count and generated embeddings count.');
            }

            // 5. Short, atomic transaction: delete old chunks, insert new ones, and mark ready
            DB::transaction(function () use ($vectorStore, $material, $validChunks, $allEmbeddings, $embeddingModel, $dimensions) {
                $vectorStore->deleteByMaterial($material->id);

                foreach ($validChunks as $index => $chunkData) {
                    $vectorStore->saveChunk([
                        'institute_id' => $material->institute_id ?? 1,
                        'material_id' => $material->id,
                        'grade' => $material->grade,
                        'medium' => $material->medium,
                        'subject' => $material->subject,
                        'chunk_type' => 'page',
                        'page_number' => $chunkData['page_number'] ?? 1,
                        'content' => $chunkData['content'],
                        'embedding_model' => $embeddingModel,
                        'dimensions' => $dimensions,
                        'word_count' => $chunkData['word_count'] ?? 0,
                    ], $allEmbeddings[$index]);
                }

                $material->updateQuietly([
                    'rag_status' => 'ready',
                    'rag_error' => null,
                    'rag_indexed_at' => now(),
                ]);
            });

            Log::info("IngestLearningMaterialJob: Successfully indexed material #{$material->id} (" . count($validChunks) . " chunks).");

        } catch (FileTooLargeException|PageLimitExceededException|OcrPageLimitExceededException|UnsupportedFileTypeException $e) {
            // Guardrail limit or unsupported format exceeded: mark failed/skipped without retrying
            Log::warning("IngestLearningMaterialJob: Guardrail limit triggered for material #{$material->id}: " . $e->getMessage());
            $vectorStore->deleteByMaterial($material->id);
            $material->updateQuietly([
                'rag_status' => 'failed',
                'rag_error' => substr($e->getMessage(), 0, 1000),
            ]);
            return;

        } catch (Throwable $e) {
            Log::error("IngestLearningMaterialJob: Failed for material #{$material->id}: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Re-throw so Laravel's queue retries transient errors with backoff up to $tries
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
            $material->updateQuietly([
                'rag_status' => 'failed',
                'rag_error' => substr($exception ? $exception->getMessage() : 'Ingestion failed.', 0, 1000),
            ]);
        }

        Log::error("IngestLearningMaterialJob: Permanently failed for material #{$this->materialId}. Marked as failed.");
    }
}

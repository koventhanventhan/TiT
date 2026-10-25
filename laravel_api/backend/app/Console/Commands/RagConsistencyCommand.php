<?php

namespace App\Console\Commands;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\MaterialChunk;
use App\Services\Rag\GeminiRagClient;
use Illuminate\Console\Command;

class RagConsistencyCommand extends Command
{
    protected $signature = 'rag:consistency {chunk_id : The chunk ID to inspect} {query : The search query to compare against}';

    protected $description = 'Inspect chunk embedding consistency, stored vs fresh re-embedded vector norms, and cosine similarities with query vector';

    public function handle(): int
    {
        $chunkId = (int) $this->argument('chunk_id');
        $query = (string) $this->argument('query');

        $chunk = MaterialChunk::withoutGlobalScopes()
            ->with('material')
            ->find($chunkId);

        if (!$chunk) {
            $this->error("Chunk #{$chunkId} not found in database.");
            return self::FAILURE;
        }

        $material = $chunk->material;
        $materialId = $chunk->material_id;
        $materialTitle = $material?->title ?? 'Untitled Material';
        $questionRange = $chunk->question_number ?: 'None';
        $content = (string) $chunk->content;
        $charCount = mb_strlen($content, 'UTF-8');

        $prefixExplanation = "No text prefix prepended to chunk content. Gemini API request body sets 'title' => '{$materialTitle}' for RETRIEVAL_DOCUMENT.";

        $storedModel = $chunk->embedding_model ?? 'unknown';
        $storedDimensions = (int) ($chunk->dimensions ?: 768);
        $storedVector = $chunk->getEmbeddingVector();
        $storedNorm = $this->computeNorm($storedVector);

        app()->bind(GeminiClientInterface::class, GeminiRagClient::class);
        $geminiClient = app(GeminiClientInterface::class);

        $apiCallsMade = 0;

        $this->info("=== Running RAG Embedding Consistency Inspection ===");
        $this->line("Target Chunk: #{$chunkId} | Material #{$materialId} ('{$materialTitle}')");
        $this->line("Query: '{$query}'\n");

        // Freshly re-embed chunk text with RETRIEVAL_DOCUMENT
        $freshResults = $geminiClient->batchEmbedContents(
            [$content],
            'RETRIEVAL_DOCUMENT',
            $materialTitle
        );
        $apiCallsMade++;
        $freshVector = $freshResults[0] ?? [];
        $freshNorm = $this->computeNorm($freshVector);
        $freshDimensions = count($freshVector);
        $freshModel = (string) config('rag.models.embedding', 'gemini-embedding-2');
        $freshTaskType = 'RETRIEVAL_DOCUMENT';

        // Embed query with RETRIEVAL_QUERY
        $queryVector = $geminiClient->embedContent($query, 'RETRIEVAL_QUERY');
        $apiCallsMade++;
        $queryNorm = $this->computeNorm($queryVector);
        $queryDimensions = count($queryVector);
        $queryModel = (string) config('rag.models.embedding', 'gemini-embedding-2');
        $queryTaskType = 'RETRIEVAL_QUERY';

        // Compute Cosine Similarities
        $cosStoredFresh = $this->cosineSimilarity($storedVector, $freshVector);
        $cosQueryStored = $this->cosineSimilarity($queryVector, $storedVector);
        $cosQueryFresh = $this->cosineSimilarity($queryVector, $freshVector);

        // Print Report
        $this->info("--- 1. Chunk Overview ---");
        $this->table(
            ['Property', 'Value'],
            [
                ['Chunk ID', $chunk->id],
                ['Material ID', $materialId],
                ['Material Title', $materialTitle],
                ['Question Range', $questionRange],
                ['Characters in Chunk', $charCount],
                ['Title / Metadata Prefix', $prefixExplanation],
            ]
        );

        $this->newLine();
        $this->info("--- 2. Vector Specifications ---");
        $this->table(
            ['Vector Source', 'Model', 'Dimensions', 'Task Type', 'L2 Norm'],
            [
                ['Stored Vector (Database)', $storedModel, $storedDimensions, 'RETRIEVAL_DOCUMENT', number_format($storedNorm, 6)],
                ['Fresh Re-embedded Chunk', $freshModel, $freshDimensions, $freshTaskType, number_format($freshNorm, 6)],
                ['Query Vector', $queryModel, $queryDimensions, $queryTaskType, number_format($queryNorm, 6)],
            ]
        );

        $this->newLine();
        $this->info("--- 3. Cosine Similarities ---");
        $this->table(
            ['Comparison', 'Cosine Similarity', 'Interpretation'],
            [
                ['Stored-vs-Fresh Chunk Vector', number_format($cosStoredFresh, 6), $cosStoredFresh >= 0.9999 ? 'Exact match (deterministic)' : 'Minor float variance'],
                ['Query-vs-Stored Chunk Vector', number_format($cosQueryStored, 6), 'Production retrieval score'],
                ['Query-vs-Fresh Chunk Vector', number_format($cosQueryFresh, 6), 'Fresh re-embedded retrieval score'],
            ]
        );

        $this->newLine();
        $this->info("--- 4. API Telemetry ---");
        $this->line("Total API Calls Made: {$apiCallsMade}");
        $this->line(" - Call 1: batchEmbedContents (chunk content, taskType: RETRIEVAL_DOCUMENT, title: '{$materialTitle}')");
        $this->line(" - Call 2: embedContent (query text, taskType: RETRIEVAL_QUERY)");

        return self::SUCCESS;
    }

    private function computeNorm(array $vector): float
    {
        $sum = 0.0;
        foreach ($vector as $val) {
            $sum += $val * $val;
        }
        return sqrt($sum);
    }

    private function cosineSimilarity(array $vecA, array $vecB): float
    {
        $normA = $this->computeNorm($vecA);
        $normB = $this->computeNorm($vecB);

        if ($normA <= 1e-12 || $normB <= 1e-12) {
            return 0.0;
        }

        $dot = 0.0;
        $dim = min(count($vecA), count($vecB));
        for ($i = 0; $i < $dim; $i++) {
            $dot += $vecA[$i] * $vecB[$i];
        }

        return $dot / ($normA * $normB);
    }
}
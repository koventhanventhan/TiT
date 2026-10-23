<?php

namespace App\Services\Rag;

use App\Contracts\Rag\VectorStoreInterface;
use App\Models\MaterialChunk;

class DatabaseVectorStore implements VectorStoreInterface
{
    /**
     * Store a single chunk and its embedding vector as a packed-float BLOB.
     * Automatically L2-normalizes the vector before packing.
     */
    public function saveChunk(array $attributes, array $floatVector): ?MaterialChunk
    {
        $norm = $this->computeNorm($floatVector);
        if ($norm <= 1e-12) {
            \Illuminate\Support\Facades\Log::warning('Skipping chunk save: embedding vector has zero norm.', [
                'material_id' => $attributes['material_id'] ?? null,
                'page_number' => $attributes['page_number'] ?? null,
            ]);
            return null;
        }

        // L2 normalize
        $normalizedVector = array_map(fn($v) => $v / $norm, $floatVector);

        $chunk = new MaterialChunk($attributes);
        $chunk->setEmbeddingVector($normalizedVector);
        $chunk->save();

        return $chunk;
    }

    /**
     * Delete all chunks associated with a specific learning material.
     */
    public function deleteByMaterial(int $materialId): int
    {
        return MaterialChunk::withoutGlobalScopes()
            ->where('material_id', $materialId)
            ->delete();
    }

    /**
     * Retrieve the most similar chunks strictly bounded by the student's allowed material IDs.
     * Streams candidates via cursor() to prevent memory spikes on large sets of BLOBs.
     */
    public function search(
        int $instituteId,
        array $allowedMaterialIds,
        array $queryVector,
        int $limit = 5,
        float $threshold = 0.70,
        ?string $subject = null
    ): array {
        if (empty($allowedMaterialIds) || empty($queryVector)) {
            return [];
        }

        $queryNorm = $this->computeNorm($queryVector);
        if ($queryNorm <= 1e-12) {
            \Illuminate\Support\Facades\Log::warning('RAG search skipped: query vector has zero norm.');
            return [];
        }

        // L2-normalize query vector
        $normalizedQuery = array_map(fn($v) => $v / $queryNorm, $queryVector);
        $dim = count($normalizedQuery);

        $query = MaterialChunk::with('material')
            ->where('institute_id', $instituteId)
            ->whereIn('material_id', $allowedMaterialIds);

        if (!empty($subject)) {
            $query->where(function ($q) use ($subject) {
                $q->where('subject', $subject)
                  ->orWhereNull('subject');
            });
        }

        $scoredResults = [];

        // Stream via cursor() to keep memory low
        foreach ($query->cursor() as $chunk) {
            $chunkVector = $chunk->getEmbeddingVector();
            if (empty($chunkVector) || count($chunkVector) !== $dim) {
                continue;
            }

            // Ensure chunk vector is L2-normalized so dot product is true cosine similarity in [-1, 1]
            $chunkNorm = $this->computeNorm($chunkVector);
            if ($chunkNorm <= 1e-12) {
                continue;
            }
            if (abs($chunkNorm - 1.0) > 1e-4) {
                $chunkVector = array_map(fn($v) => $v / $chunkNorm, $chunkVector);
            }

            // Since both vectors are L2-normalized, cosine similarity is exactly the dot product
            $similarity = min(1.0, max(-1.0, $this->dotProduct($normalizedQuery, $chunkVector)));

            if ($similarity >= $threshold) {
                $scoredResults[] = [
                    'chunk' => $chunk,
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort descending by similarity score
        usort($scoredResults, function ($a, $b) {
            return $b['similarity'] <=> $a['similarity'];
        });

        return array_slice($scoredResults, 0, $limit);
    }

    /**
     * Compute vector L2 norm.
     */
    public function computeNorm(array $vector): float
    {
        $sum = 0.0;
        foreach ($vector as $val) {
            $sum += $val * $val;
        }
        return sqrt($sum);
    }

    /**
     * Compute dot product between two normalized float vectors.
     */
    public function dotProduct(array $vecA, array $vecB): float
    {
        $dot = 0.0;
        $dim = count($vecA);

        for ($i = 0; $i < $dim; $i++) {
            $dot += $vecA[$i] * ($vecB[$i] ?? 0.0);
        }

        return $dot;
    }
}

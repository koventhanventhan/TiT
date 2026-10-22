<?php

namespace App\Contracts\Rag;

use App\Models\MaterialChunk;

interface VectorStoreInterface
{
    /**
     * Store a single chunk and its embedding vector as a packed-float BLOB.
     * Skips and returns null if the vector norm is zero.
     *
     * @param array $attributes
     * @param array<int, float> $floatVector
     * @return MaterialChunk|null
     */
    public function saveChunk(array $attributes, array $floatVector): ?MaterialChunk;

    /**
     * Delete all chunks associated with a specific learning material.
     *
     * @param int $materialId
     * @return int Number of deleted chunks
     */
    public function deleteByMaterial(int $materialId): int;

    /**
     * Retrieve the most similar chunks strictly bounded by the student's allowed material IDs.
     *
     * @param int $instituteId
     * @param array<int> $allowedMaterialIds
     * @param array<int, float> $queryVector
     * @param int $limit
     * @param float $threshold
     * @param string|null $subject
     * @return array<int, array{chunk: MaterialChunk, similarity: float}>
     */
    public function search(
        int $instituteId,
        array $allowedMaterialIds,
        array $queryVector,
        int $limit = 5,
        float $threshold = 0.70,
        ?string $subject = null
    ): array;
}

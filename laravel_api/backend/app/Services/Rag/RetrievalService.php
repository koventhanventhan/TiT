<?php

namespace App\Services\Rag;

use App\Contracts\Rag\GeminiClientInterface;
use App\Contracts\Rag\VectorStoreInterface;
use App\Models\LearningMaterial;
use App\Models\User;
use App\Support\GradeMatcher;

class RetrievalService
{
    protected VectorStoreInterface $vectorStore;
    protected GeminiClientInterface $geminiClient;
    protected LanguageDetector $languageDetector;
    protected int $topK;

    public function __construct(
        VectorStoreInterface $vectorStore,
        GeminiClientInterface $geminiClient,
        LanguageDetector $languageDetector
    ) {
        $this->vectorStore = $vectorStore;
        $this->geminiClient = $geminiClient;
        $this->languageDetector = $languageDetector;
        $this->topK = (int) config('rag.retrieval.top_k', 5);
    }

    /**
     * Compute allowed material IDs for the authenticated student using
     * InstituteScope, medium, GradeMatcher, and optional subject preference.
     *
     * @param User $student
     * @param string|null $subject
     * @return array<int, int>
     */
    public function getAllowedMaterialIds(User $student, ?string $subject = null): array
    {
        $instituteId = (int) ($student->institute_id ?? 1);

        // 1. Explicitly filter by student's institute to guarantee isolation across web & console
        $query = LearningMaterial::where('institute_id', $instituteId)
            ->where('type', '!=', 'recording')
            ->where('rag_status', 'ready');

        // 2. Medium filtering (same rules as student's Notes page)
        if (!empty($student->medium)) {
            $query->where(function ($q) use ($student) {
                $q->where('medium', $student->medium)
                  ->orWhere('medium', 'both')
                  ->orWhere('medium', 'all')
                  ->orWhereNull('medium');
            });
        }

        $allMaterials = $query->get();

        // 3. Filter using GradeMatcher
        $matchedMaterials = $allMaterials->filter(function ($m) use ($student) {
            return GradeMatcher::matches($student->current_grade, $m->grade);
        });

        // 4. Subject preference: If a subject is given, prefer materials with that subject OR null subject
        if (!empty($subject)) {
            $subjectFiltered = $matchedMaterials->filter(function ($m) use ($subject) {
                return $m->subject === $subject || is_null($m->subject);
            });

            if ($subjectFiltered->isNotEmpty()) {
                $matchedMaterials = $subjectFiltered;
            }
        }

        return $matchedMaterials->pluck('id')->values()->all();
    }

    /**
     * Retrieve top-k relevant chunks for a student query.
     *
     * @param User $student
     * @param string $question
     * @param string|null $subject
     * @param int|null $topK
     * @return array{
     *   language: string,
     *   allowed_material_count: int,
     *   chunks: array<int, array{
     *     chunk_id: int,
     *     material_id: int,
     *     material_title: string,
     *     page_number: int,
     *     content: string,
     *     similarity: float
     *   }>
     * }
     */
    public function retrieve(User $student, string $question, ?string $subject = null, ?int $topK = null): array
    {
        $language = $this->languageDetector->detect($question);
        $k = $topK ?? $this->topK;
        $instituteId = (int) ($student->institute_id ?? 1);

        $allowedMaterialIds = $this->getAllowedMaterialIds($student, $subject);

        if (empty($allowedMaterialIds)) {
            return [
                'language' => $language,
                'allowed_material_count' => 0,
                'chunks' => [],
            ];
        }

        // Generate query embedding with taskType RETRIEVAL_QUERY
        $queryVector = $this->geminiClient->embedContent($question, 'RETRIEVAL_QUERY');

        // Search in vector store restricted strictly to allowedMaterialIds
        $hits = $this->vectorStore->search(
            $instituteId,
            $allowedMaterialIds,
            $queryVector,
            $k,
            0.0, // Return top K regardless of threshold so AnswerService can evaluate & log score
            $subject
        );

        if (empty($hits)) {
            return [
                'language' => $language,
                'allowed_material_count' => count($allowedMaterialIds),
                'chunks' => [],
            ];
        }

        $chunks = [];
        foreach ($hits as $hit) {
            $chunk = $hit['chunk'];
            $chunks[] = [
                'chunk_id' => $chunk->id,
                'material_id' => $chunk->material_id,
                'material_title' => $chunk->material?->title ?? 'Notes',
                'page_number' => $chunk->page_number ?? 1,
                'content' => $chunk->content,
                'similarity' => (float) $hit['similarity'],
            ];
        }

        return [
            'language' => $language,
            'allowed_material_count' => count($allowedMaterialIds),
            'chunks' => $chunks,
        ];
    }
}

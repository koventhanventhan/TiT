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
        // Fail closed: Student without an institute_id has zero access
        if (empty($student->institute_id)) {
            return [];
        }

        $instituteId = (int) $student->institute_id;

        // 1. Explicitly filter by student's institute to guarantee isolation across web & console
        $query = LearningMaterial::select(['id', 'institute_id', 'grade', 'medium', 'subject'])
            ->where('institute_id', $instituteId)
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

        // 4. Subject handling: "all subjects" is default.
        // If a specific subject is chosen, search ONLY materials with that subject or null subject (NO FALLBACK).
        $isAllSubjects = empty($subject) || strtolower($subject) === 'all' || strtolower($subject) === 'all subjects';
        if (!$isAllSubjects) {
            $matchedMaterials = $matchedMaterials->filter(function ($m) use ($subject) {
                return $m->subject === $subject || is_null($m->subject);
            });
        }

        return $matchedMaterials->pluck('id')->values()->all();
    }

    /**
     * Retrieve top-k relevant chunks for a student query with duplicate collapse.
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
     *     material_type: string,
     *     file_hash: string|null,
     *     chunk_type: string,
     *     question_number: string|null,
     *     page_number: int|null,
     *     content: string,
     *     similarity: float,
     *     is_ocr: bool,
     *     ocr_used: bool
     *   }>
     * }
     */
    public function retrieve(User $student, string $question, ?string $subject = null, ?int $topK = null): array
    {
        $language = $this->languageDetector->detect($question);
        $k = $topK ?? $this->topK;

        // Fail-closed rule: student must have an institute_id
        if (empty($student->institute_id)) {
            return [
                'language' => $language,
                'allowed_material_count' => 0,
                'chunks' => [],
            ];
        }

        $instituteId = (int) $student->institute_id;
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

        // Search in vector store fetching extra candidates to allow duplicate collapse
        $searchLimit = max($k * 2, 10);
        $hits = $this->vectorStore->search(
            $instituteId,
            $allowedMaterialIds,
            $queryVector,
            $searchLimit,
            0.0, // Return candidate hits so AnswerService can evaluate & log score
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
        $seenHashes = [];

        foreach ($hits as $hit) {
            $chunk = $hit['chunk'];
            $fileHash = $chunk->material?->file_hash;

            // Duplicate collapse: if multiple materials have the identical file_hash,
            // collapse duplicates by (file_hash, page_number, question_number, md5(content))
            // keeping only the highest scoring hit (first encountered in similarity-sorted hits).
            if (!empty($fileHash)) {
                $dedupKey = $fileHash . ':' . ($chunk->page_number ?? 0) . ':' . ($chunk->question_number ?? '');
                if (isset($seenHashes[$dedupKey])) {
                    continue;
                }
                $seenHashes[$dedupKey] = true;
            }

            $chunks[] = [
                'chunk_id' => $chunk->id,
                'material_id' => $chunk->material_id,
                'material_title' => $chunk->material?->title ?? 'Notes',
                'material_type' => $chunk->material?->type ?? 'notes',
                'file_hash' => $fileHash,
                'chunk_type' => $chunk->chunk_type ?? 'page',
                'question_number' => $chunk->question_number ?? null,
                'page_number' => $chunk->page_number ?? 1,
                'content' => $chunk->content,
                'similarity' => (float) $hit['similarity'],
                'is_ocr' => (bool) ($chunk->is_ocr ?? false),
                'ocr_used' => (bool) ($chunk->is_ocr ?? false),
            ];

            if (count($chunks) >= $k) {
                break;
            }
        }

        return [
            'language' => $language,
            'allowed_material_count' => count($allowedMaterialIds),
            'chunks' => $chunks,
        ];
    }
}
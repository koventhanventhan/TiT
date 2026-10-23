<?php

namespace App\Http\Controllers\Api;

use App\Contracts\Rag\GeminiClientInterface;
use App\Contracts\Rag\VectorStoreInterface;
use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use App\Models\MaterialPage;
use App\Services\Rag\MultilingualTextChunker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaterialPageCorrectionController extends Controller
{
    /**
     * Retrieve all extracted pages for a learning material.
     * Accessible by material teacher owner or admin.
     */
    public function index(Request $request, int $materialId): JsonResponse
    {
        $material = LearningMaterial::findOrFail($materialId);
        $user = $request->user();

        $isOwnerTeacher = ($user->role === 'teacher' && (int) $material->teacher_id === (int) $user->id);
        $isAdmin = in_array($user->role, ['admin', 'super-admin', 'super_admin']);

        if (!$isAdmin && !$isOwnerTeacher) {
            return response()->json([
                'message' => 'Unauthorized. Only the material owner or an administrator can review extracted text.'
            ], 403);
        }

        $pages = MaterialPage::where('material_id', $material->id)
            ->orderBy('page_number', 'asc')
            ->get([
                'id',
                'material_id',
                'page_number',
                'text',
                'is_ocr',
                'ocr_uncertain',
                'teacher_verified',
                'last_edited_by',
                'last_edited_at',
            ]);

        return response()->json([
            'material_id' => $material->id,
            'material_title' => $material->title,
            'pages' => $pages,
        ]);
    }

    /**
     * Update and verify extracted page text.
     * Re-chunks and re-embeds without calling OCR.
     */
    public function update(
        Request $request,
        int $materialId,
        int $pageNumber,
        MultilingualTextChunker $chunker,
        VectorStoreInterface $vectorStore,
        GeminiClientInterface $geminiClient
    ): JsonResponse {
        $material = LearningMaterial::findOrFail($materialId);
        $user = $request->user();

        $isOwnerTeacher = ($user->role === 'teacher' && (int) $material->teacher_id === (int) $user->id);
        $isAdmin = in_array($user->role, ['admin', 'super-admin', 'super_admin']);

        if (!$isAdmin && !$isOwnerTeacher) {
            return response()->json([
                'message' => 'Unauthorized. Only the material owner or an administrator can edit extracted text.'
            ], 403);
        }

        $validated = $request->validate([
            'text' => ['required', 'string', 'max:50000'],
        ]);

        $correctedText = $validated['text'];

        // 1. Find or initialize the page record
        $page = MaterialPage::firstOrNew([
            'material_id' => $material->id,
            'page_number' => (int) $pageNumber,
        ]);

        $page->text = $correctedText;
        $page->teacher_verified = true;
        $page->ocr_uncertain = false;
        $page->is_ocr = false; // Verified by teacher, drop raw OCR flag
        $page->last_edited_by = $user->id;
        $page->last_edited_at = now();
        $page->save();

        // 2. Audit log: log WHO edited and WHEN (NEVER the text)
        Log::info('Material extracted page text reviewed and corrected', [
            'material_id' => $material->id,
            'page_number' => (int) $pageNumber,
            'user_id' => $user->id,
            'user_role' => $user->role,
            'edited_at' => now()->toIso8601String(),
        ]);

        // 3. Re-chunk without OCR
        MaterialChunk::withoutGlobalScopes()
            ->where('material_id', $material->id)
            ->where('page_number', (int) $pageNumber)
            ->delete();

        $pageItems = [
            [
                'page_number' => (int) $pageNumber,
                'text' => $correctedText,
                'used_ocr' => false,
            ]
        ];

        $rawChunks = $chunker->chunkPages($pageItems, $material->type);
        $validChunks = array_values(array_filter($rawChunks, fn($c) => !empty(trim($c['content'] ?? ''))));

        $createdChunksCount = 0;
        if (!empty($validChunks)) {
            $embeddingModel = (string) config('rag.models.embedding', 'gemini-embedding-2');
            $dimensions = (int) config('rag.models.embedding_dimensions', 768);

            $chunkTexts = array_column($validChunks, 'content');
            $embeddings = $geminiClient->batchEmbedContents($chunkTexts, 'RETRIEVAL_DOCUMENT', $material->title);

            foreach ($validChunks as $index => $c) {
                $vectorStore->saveChunk([
                    'institute_id' => (int) $material->institute_id,
                    'material_id' => $material->id,
                    'grade' => $material->grade,
                    'medium' => $material->medium,
                    'subject' => $material->subject,
                    'chunk_type' => $c['chunk_type'] ?? 'page',
                    'page_number' => (int) $pageNumber,
                    'question_number' => $c['question_number'] ?? null,
                    'content' => $c['content'],
                    'embedding_model' => $embeddingModel,
                    'dimensions' => $dimensions,
                    'word_count' => $c['word_count'] ?? 0,
                    'is_ocr' => false, // Verified: drop OCR flag
                ], $embeddings[$index]);
                $createdChunksCount++;
            }
        }

        // 4. Update material uncertainty warning
        $hasUncertain = MaterialPage::where('material_id', $material->id)
            ->where('ocr_uncertain', true)
            ->exists();
        $ragWarning = $hasUncertain
            ? 'Warning: OCR uncertainty detected on one or more pages'
            : null;

        $material->updateQuietly([
            'rag_status' => 'ready',
            'rag_error' => $ragWarning,
            'rag_indexed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Page text updated, re-chunked and re-indexed successfully.',
            'page' => $page,
            'chunks_reindexed' => $createdChunksCount,
        ]);
    }
}

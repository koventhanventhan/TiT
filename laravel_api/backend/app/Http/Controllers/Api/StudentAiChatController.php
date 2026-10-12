<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\StudentAiQuery;
use App\Services\Rag\AnswerService;
use App\Services\Rag\DistressDetector;
use App\Services\Rag\LanguageDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentAiChatController extends Controller
{
    protected AnswerService $answerService;
    protected DistressDetector $distressDetector;
    protected LanguageDetector $languageDetector;

    public function __construct(
        AnswerService $answerService,
        DistressDetector $distressDetector,
        LanguageDetector $languageDetector
    ) {
        $this->answerService = $answerService;
        $this->distressDetector = $distressDetector;
        $this->languageDetector = $languageDetector;
    }

    /**
     * Stream grounded answers from teacher notes via Server-Sent Events (SSE).
     */
    public function chat(Request $request): StreamedResponse|JsonResponse
    {
        $user = $request->user();

        // 1. Fail closed: user must have an assigned institute
        if (empty($user?->institute_id)) {
            return response()->json([
                'message' => 'Unauthorized: User is not assigned to an institute.',
            ], 403);
        }

        // 2. Global feature switch
        if (!config('rag.enabled', true)) {
            return response()->json([
                'message' => 'The AI Study Assistant is temporarily disabled.',
            ], 503);
        }

        // 3. Per-institute feature switch
        $institute = Institute::find($user->institute_id);
        if (!$institute || !$institute->rag_enabled) {
            return response()->json([
                'message' => 'The AI Study Assistant is disabled for your institute.',
            ], 403);
        }

        // 4. Input validation
        $validated = $request->validate([
            'question' => 'required|string|min:3|max:500',
            'subject' => 'nullable|string|max:100',
        ]);

        $question = trim($validated['question']);
        $subject = !empty($validated['subject']) ? trim($validated['subject']) : null;

        // 5. Per-minute rate limiting
        $throttleKey = 'rag_min_' . $user->id;
        $perMinuteLimit = (int) config('rag.limits.per_minute', 10);
        if (RateLimiter::tooManyAttempts($throttleKey, $perMinuteLimit)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => "Too many questions. Please wait {$seconds} seconds before asking again.",
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        // 6. Daily question quota per student (counts only 'answered' and 'no_notes_found')
        $dailyLimit = (int) config('rag.limits.daily_questions_per_student', 30);
        $usedToday = StudentAiQuery::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->whereIn('status', ['answered', 'no_notes_found'])
            ->count();

        if ($usedToday >= $dailyLimit) {
            return response()->json([
                'message' => "You have reached your daily limit of {$dailyLimit} questions for today.",
                'daily_limit' => $dailyLimit,
                'used_today' => $usedToday,
            ], 429);
        }

        // 7. Personal Distress & Self-Harm Safety Guardrail
        if ($this->distressDetector->isDistress($question)) {
            $language = $this->languageDetector->detect($question);
            $caringMessage = $this->distressDetector->getCaringMessage($language);

            $aiQuery = StudentAiQuery::create([
                'institute_id' => $user->institute_id,
                'user_id' => $user->id,
                'subject' => $subject,
                'question' => $question,
                'question_language' => $language,
                'answer' => $caringMessage,
                'status' => 'distress_blocked',
                'is_flagged' => true,
                'flag_reason' => 'Personal distress / self-harm detected',
            ]);

            return response()->stream(function () use ($caringMessage, $aiQuery, $language) {
                echo "event: message\ndata: " . json_encode(['text' => $caringMessage]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();

                echo "event: end\ndata: " . json_encode([
                    'query_id' => $aiQuery->id,
                    'status' => 'distress_blocked',
                    'language' => $language,
                    'citations' => [],
                    'sources' => [],
                ]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache, no-transform',
                'X-Accel-Buffering' => 'no',
                'Connection' => 'keep-alive',
            ]);
        }

        // 8. Streamed Grounded RAG Answering
        return response()->stream(function () use ($user, $question, $subject) {
            $fullAnswer = '';
            $queryRecord = null;

            try {
                $result = $this->answerService->streamAnswer($user, $question, $subject, function (string $textChunk) {
                    echo "event: message\ndata: " . json_encode(['text' => $textChunk]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                });

                $status = $result['llm_called'] ? 'answered' : 'no_notes_found';
                $retrievedMaterialIds = array_values(array_unique(array_column($result['sources'], 'material_id')));

                $queryRecord = StudentAiQuery::create([
                    'institute_id' => $user->institute_id,
                    'user_id' => $user->id,
                    'subject' => $subject,
                    'question' => $question,
                    'question_language' => $result['language'],
                    'answer' => $result['answer'],
                    'retrieved_material_ids' => $retrievedMaterialIds,
                    'top_similarity_score' => $result['top_similarity_score'],
                    'status' => $status,
                ]);

                echo "event: end\ndata: " . json_encode([
                    'query_id' => $queryRecord->id,
                    'status' => $status,
                    'language' => $result['language'],
                    'citations' => $result['citations'],
                    'sources' => $result['sources'],
                    'top_similarity_score' => $result['top_similarity_score'],
                    'has_valid_citations' => $result['has_valid_citations'],
                ]) . "\n\n";

                if (ob_get_level() > 0) ob_flush();
                flush();

            } catch (\Throwable $e) {
                Log::error('StudentAiChatController Stream Exception: ' . $e->getMessage(), [
                    'exception' => $e,
                    'user_id' => $user->id,
                ]);

                StudentAiQuery::create([
                    'institute_id' => $user->institute_id,
                    'user_id' => $user->id,
                    'subject' => $subject,
                    'question' => $question,
                    'question_language' => $this->languageDetector->detect($question),
                    'answer' => null,
                    'status' => 'error',
                ]);

                $errorMessage = 'An error occurred while generating your answer. Please try again.';
                echo "event: error\ndata: " . json_encode(['message' => $errorMessage]) . "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Submit student thumbs up / down feedback on an AI response.
     */
    public function feedback(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'rating' => 'required|in:up,down',
            'comment' => 'nullable|string|max:500',
        ]);

        $query = StudentAiQuery::where('id', $id)->first();

        if (!$query) {
            return response()->json(['message' => 'Query not found.'], 404);
        }

        // Ownership enforcement: students can only rate their own queries
        if ($query->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query->update([
            'feedback_rating' => $validated['rating'],
            'feedback_comment' => $validated['comment'] ?? null,
            'feedback_at' => now(),
        ]);

        return response()->json([
            'message' => 'Thank you for your feedback.',
            'query_id' => $query->id,
            'feedback_rating' => $query->feedback_rating,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\StudentAiQuery;
use App\Models\User;
use App\Services\EventNotifier;
use App\Services\Rag\AnswerService;
use App\Services\Rag\DistressDetector;
use App\Services\Rag\LanguageDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        // 2. Block deactivated users
        if (!empty($user->deactivated_at)) {
            return response()->json([
                'message' => 'Your account has been deactivated.',
            ], 403);
        }

        // 3. Global feature switch
        if (!config('rag.enabled', true)) {
            return response()->json([
                'message' => 'The AI Study Assistant is temporarily disabled.',
            ], 503);
        }

        // 4. Per-institute feature switch
        $institute = Institute::find($user->institute_id);
        if (!$institute || !$institute->rag_enabled) {
            return response()->json([
                'message' => 'The AI Study Assistant is disabled for your institute.',
            ], 403);
        }

        // 5. Input validation (MUST happen before rate limiter hit)
        $validated = $request->validate([
            'question' => 'required|string|min:3|max:500',
            'subject' => 'nullable|string|max:100',
        ]);

        $question = trim($validated['question']);
        $subject = !empty($validated['subject']) ? trim($validated['subject']) : null;
        $language = $this->languageDetector->detect($question);

        // 6. Per-minute rate limiting (consistent config: rag.limits.per_minute)
        $throttleKey = 'rag_min_' . $user->id;
        $perMinuteLimit = (int) config('rag.limits.per_minute', 10);
        if (RateLimiter::tooManyAttempts($throttleKey, $perMinuteLimit)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return response()->json([
                'message' => "Too many questions. Please wait {$seconds} seconds before asking again.",
            ], 429);
        }
        RateLimiter::hit($throttleKey, 60);

        // 7. Daily question quota calculation & processing row creation
        // Count processing, answered, no_notes_found, and aborted toward the daily limit.
        $dailyLimit = (int) config('rag.limits.daily_questions_per_student', 30);
        $startOfDay = now()->startOfDay();
        $endOfDay = now()->endOfDay();

        // Atomically enforce daily limit and create 'processing' row at the start of request
        // Uses User row-level lock (FOR UPDATE) to serialize concurrent check-and-create per student
        $aiQuery = DB::transaction(function () use ($user, $startOfDay, $endOfDay, $dailyLimit, $question, $language, $subject) {
            User::where('id', $user->id)->lockForUpdate()->first();

            $usedToday = StudentAiQuery::where('user_id', $user->id)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->whereIn('status', ['processing', 'answered', 'no_notes_found', 'aborted'])
                ->count();

            if ($usedToday >= $dailyLimit) {
                return null;
            }

            return StudentAiQuery::create([
                'institute_id' => $user->institute_id,
                'user_id' => $user->id,
                'subject' => $subject,
                'question' => $question,
                'question_language' => $language,
                'status' => 'processing',
            ]);
        });

        if (!$aiQuery) {
            return response()->json([
                'message' => "You have reached your daily limit of {$dailyLimit} questions for today.",
                'daily_limit' => $dailyLimit,
            ], 429);
        }

        // 8. Personal Distress & Self-Harm Safety Guardrail
        if ($this->distressDetector->isDistress($question)) {
            $instituteContact = $institute->phone_number ?? null;
            $caringMessage = $this->distressDetector->getCaringMessage($language, $instituteContact);

            $aiQuery->update([
                'answer' => $caringMessage,
                'status' => 'distress_blocked',
                'is_flagged' => true,
                'flag_reason' => 'Personal distress / self-harm detected',
            ]);

            // Notify institute admin
            EventNotifier::studentDistressAlert($aiQuery, $user);

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

        // 9. Streamed Grounded RAG Answering with ignore_user_abort and connection_aborted
        return response()->stream(function () use ($user, $question, $subject, $aiQuery, $language) {
            ignore_user_abort(true);
            $accumulatedAnswer = '';
            $isAborted = false;

            try {
                $result = $this->answerService->streamAnswer($user, $question, $subject, function (string $textChunk) use (&$accumulatedAnswer, &$isAborted, $aiQuery) {
                    if (connection_aborted()) {
                        $isAborted = true;
                        return;
                    }

                    $accumulatedAnswer .= $textChunk;
                    echo "event: message\ndata: " . json_encode(['text' => $textChunk]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();

                    if (connection_aborted()) {
                        $isAborted = true;
                    }
                });

                if ($isAborted || connection_aborted()) {
                    $aiQuery->update([
                        'status' => 'aborted',
                        'answer' => $accumulatedAnswer ?: null,
                    ]);
                    return;
                }

                $status = $result['llm_called'] ? 'answered' : 'no_notes_found';
                $retrievedMaterialIds = array_values(array_unique(array_column($result['sources'], 'material_id')));

                $aiQuery->update([
                    'answer' => $result['answer'],
                    'retrieved_material_ids' => $retrievedMaterialIds,
                    'top_similarity_score' => $result['top_similarity_score'],
                    'status' => $status,
                ]);

                echo "event: end\ndata: " . json_encode([
                    'query_id' => $aiQuery->id,
                    'status' => $status,
                    'language' => $result['language'],
                    'citations' => $result['citations'],
                    'sources' => $result['sources'],
                    'top_similarity_score' => $result['top_similarity_score'],
                    'has_valid_citations' => $result['has_valid_citations'],
                ]) . "\n\n";

                if (ob_get_level() > 0) ob_flush();
                flush();

            } catch (\App\Services\Rag\GeminiSafetyBlockedException $e) {
                $blockReason = $e->getBlockReason() ?? $e->getFinishReason() ?? 'SAFETY';
                $safetyMessage = match ($language) {
                    'ta' => "இந்தக் கேள்வி அல்லது பதில் பாதுகாப்பு வழிகாட்டுதல்களின்படி தடுக்கப்பட்டுள்ளது. தயவுசெய்து உங்கள் கேள்வியை மாற்றி கேளுங்கள் அல்லது உங்கள் ஆசிரியரின் வழிகாட்டலைப் பெறுங்கள்.",
                    'si' => "මෙම ප්‍රශ්නය හෝ පිළිතුර අපගේ ළමා ආරක්ෂණ පෙරහන් මගින් අවහිර කර ඇත. කරුණාකර ඔබේ ප්‍රශ්නය වෙනත් ආකාරයකින් අසන්න හෝ ඔබේ ගුරුවරයාගෙන් මඟ පෙන්වීම ලබා ගන්න.",
                    default => "This question or answer was blocked by our child safety filters. Please rephrase your question or ask your teacher for guidance.",
                };

                $aiQuery->update([
                    'status' => 'blocked_safety',
                    'answer' => $safetyMessage,
                    'is_flagged' => false,
                    'flag_reason' => 'safety_block',
                ]);

                // Stream friendly translated safety guidance to student
                echo "event: message\ndata: " . json_encode(['text' => $safetyMessage]) . "\n\n";
                echo "event: end\ndata: " . json_encode([
                    'query_id' => $aiQuery->id,
                    'status' => 'blocked_safety',
                    'language' => $language,
                    'citations' => [],
                    'sources' => [],
                    'has_valid_citations' => false,
                ]) . "\n\n";

                if (ob_get_level() > 0) ob_flush();
                flush();

            } catch (\Throwable $e) {
                Log::error('StudentAiChatController Stream Exception: ' . $e->getMessage(), [
                    'exception_class' => get_class($e),
                    'user_id' => $user->id,
                    'query_id' => $aiQuery->id,
                ]);

                if (connection_aborted()) {
                    $aiQuery->update([
                        'status' => 'aborted',
                        'answer' => $accumulatedAnswer ?: null,
                    ]);
                } else {
                    $aiQuery->update([
                        'status' => 'error',
                    ]);

                    $errorMessage = 'An error occurred while generating your answer. Please try again.';
                    echo "event: error\ndata: " . json_encode(['message' => $errorMessage]) . "\n\n";
                    if (ob_get_level() > 0) ob_flush();
                    flush();
                }
            } finally {
                if (connection_aborted()) {
                    $aiQuery->refresh();
                    if ($aiQuery->status === 'processing') {
                        $aiQuery->update([
                            'status' => 'aborted',
                            'answer' => $accumulatedAnswer ?: null,
                        ]);
                    }
                }
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

        // Returns 404 for non-existent queries or queries belonging to other students
        $query = StudentAiQuery::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$query) {
            return response()->json(['message' => 'Query not found.'], 404);
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

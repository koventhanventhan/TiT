<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\IngestLearningMaterialJob;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\SiteSetting;
use App\Models\StudentAiQuery;
use App\Services\PublicKnowledgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminRagController extends Controller
{
    /**
     * Return operational telemetry & status metrics for the admin dashboard.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        // Enforce admin privileges
        if (!$user || !in_array($user->role, ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $instituteId = $user->role === 'super_admin' ? ($request->query('institute_id') ?: $user->institute_id ?: 1) : ($user->institute_id ?: 1);
        $days = (int) ($request->query('days', 14));

        $institute = Institute::find($instituteId);

        // 1. Materials by rag_status
        $materialsQuery = LearningMaterial::withoutGlobalScopes()
            ->whereNotNull('file_path')
            ->where(function ($q) {
                $q->where('type', '!=', 'recording')
                  ->orWhereNull('zoom_schedule_id');
            });

        if ($instituteId) {
            $materialsQuery->where('institute_id', $instituteId);
        }

        $statusCounts = (clone $materialsQuery)
            ->selectRaw('COALESCE(rag_status, "pending") as status_key, count(*) as count')
            ->groupBy('status_key')
            ->pluck('count', 'status_key')
            ->toArray();

        // 2. Failed materials with rag_error
        $failedMaterials = (clone $materialsQuery)
            ->where('rag_status', 'failed')
            ->select('id', 'title', 'subject', 'grade', 'institute_id', 'rag_error', 'updated_at')
            ->latest('updated_at')
            ->limit(20)
            ->get();

        // 3. Query telemetry (last N days)
        $queriesQuery = StudentAiQuery::withoutGlobalScopes()
            ->where('created_at', '>=', now()->subDays($days));

        if ($instituteId) {
            $queriesQuery->where('institute_id', $instituteId);
        }

        $totalQueries = (clone $queriesQuery)->count();
        $answeredCount = (clone $queriesQuery)->where('status', 'answered')->count();
        $noNotesFoundCount = (clone $queriesQuery)->where('status', 'no_notes_found')->count();
        $errorCount = (clone $queriesQuery)->where('status', 'error')->count();
        $abortedCount = (clone $queriesQuery)->where('status', 'aborted')->count();
        $flaggedCount = (clone $queriesQuery)->where('is_flagged', true)->count();

        $ratedCount = (clone $queriesQuery)->whereNotNull('feedback_rating')->count();
        $thumbsDownCount = (clone $queriesQuery)->where('feedback_rating', 'down')->count();
        $thumbsUpCount = (clone $queriesQuery)->where('feedback_rating', 'up')->count();

        // Rates
        $noNotesFoundRate = $totalQueries > 0 ? round(($noNotesFoundCount / $totalQueries) * 100, 1) : 0.0;
        $thumbsDownRate = $ratedCount > 0 ? round(($thumbsDownCount / $ratedCount) * 100, 1) : 0.0;
        $thumbsUpRate = $ratedCount > 0 ? round(($thumbsUpCount / $ratedCount) * 100, 1) : 0.0;

        // 4. Queries per Day (Past 7 Days)
        $dailyQueries = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dayStart = $date->copy()->startOfDay();
            $dayEnd = $date->copy()->endOfDay();

            $dayQ = (clone $queriesQuery)->whereBetween('created_at', [$dayStart, $dayEnd]);
            $dailyQueries[] = [
                'date' => $date->format('Y-m-d'),
                'total' => (clone $dayQ)->count(),
                'answered' => (clone $dayQ)->where('status', 'answered')->count(),
                'no_notes_found' => (clone $dayQ)->where('status', 'no_notes_found')->count(),
                'flagged' => (clone $dayQ)->where('is_flagged', true)->count(),
                'error' => (clone $dayQ)->where('status', 'error')->count(),
            ];
        }

        // 5. Recent Flagged Distress Queries (Restricted access - admins and super admins only)
        $flaggedQueries = (clone $queriesQuery)
            ->where('is_flagged', true)
            ->where('flag_reason', '!=', 'safety_block')
            ->with(['user:id,name,phone_number,email'])
            ->latest()
            ->limit(30)
            ->get(['id', 'institute_id', 'user_id', 'flag_reason', 'question', 'answer', 'created_at']);

        $safetyBlockCount = (clone $queriesQuery)->where(function ($q) {
            $q->where('status', 'blocked_safety')->orWhere('flag_reason', 'safety_block');
        })->count();

        $ocrWarningCount = (clone $materialsQuery)
            ->where('rag_status', 'ready')
            ->where('rag_error', 'like', 'Warning: OCR uncertainty%')
            ->count();

        return response()->json([
            'institute_id' => $instituteId,
            'switches' => [
                'rag_enabled' => (bool) ($institute?->rag_enabled ?? false),
                'public_assistant_enabled' => (bool) ($institute?->public_assistant_enabled ?? false),
            ],
            'materials_status' => [
                'ready' => $statusCounts['ready'] ?? 0,
                'ocr_uncertain_warnings' => $ocrWarningCount,
                'processing' => $statusCounts['processing'] ?? 0,
                'pending' => $statusCounts['pending'] ?? 0,
                'failed' => $statusCounts['failed'] ?? 0,
                'unsupported' => $statusCounts['unsupported'] ?? 0,
                'total' => array_sum($statusCounts),
            ],
            'failed_materials' => $failedMaterials,
            'rates' => [
                'total_queries' => $totalQueries,
                'answered_count' => $answeredCount,
                'no_notes_found_count' => $noNotesFoundCount,
                'no_notes_found_rate_percent' => $noNotesFoundRate,
                'error_count' => $errorCount,
                'aborted_count' => $abortedCount,
                'rated_queries_count' => $ratedCount,
                'thumbs_down_count' => $thumbsDownCount,
                'thumbs_down_rate_percent' => $thumbsDownRate,
                'thumbs_up_count' => $thumbsUpCount,
                'thumbs_up_rate_percent' => $thumbsUpRate,
                'flagged_queries_count' => $flaggedCount,
                'safety_block_count' => $safetyBlockCount,
                'ocr_warnings_count' => $ocrWarningCount,
            ],
            'daily_queries' => $dailyQueries,
            'flagged_queries' => $flaggedQueries,
        ]);
    }

    /**
     * Trigger re-indexing of a single learning material.
     */
    public function reindex(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $query = LearningMaterial::withoutGlobalScopes()->where('id', $id);
        if ($user->role !== 'super_admin') {
            $query->where('institute_id', $user->institute_id);
        }

        $material = $query->firstOrFail();

        $material->update([
            'rag_status' => 'pending',
            'rag_error' => null,
        ]);

        IngestLearningMaterialJob::dispatch($material->id);

        return response()->json([
            'message' => "Material #{$material->id} ('{$material->title}') queued for re-indexing.",
            'material' => $material,
        ]);
    }

    /**
     * Toggle institute RAG and Public Assistant switches.
     */
    public function toggleAssistant(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $instituteId = $user->role === 'super_admin' ? ($request->input('institute_id') ?: $user->institute_id ?: 1) : ($user->institute_id ?: 1);
        $institute = Institute::findOrFail($instituteId);

        $validated = $request->validate([
            'rag_enabled' => 'nullable|boolean',
            'public_assistant_enabled' => 'nullable|boolean',
        ]);

        $updates = [];
        if (array_key_exists('rag_enabled', $validated)) {
            $updates['rag_enabled'] = (bool) $validated['rag_enabled'];
        }
        if (array_key_exists('public_assistant_enabled', $validated)) {
            $updates['public_assistant_enabled'] = (bool) $validated['public_assistant_enabled'];
        }

        if (!empty($updates)) {
            $institute->update($updates);
        }

        return response()->json([
            'message' => 'Institute assistant settings updated.',
            'institute_id' => $institute->id,
            'switches' => [
                'rag_enabled' => (bool) $institute->rag_enabled,
                'public_assistant_enabled' => (bool) $institute->public_assistant_enabled,
            ],
        ]);
    }

    /**
     * Question-quality feedback loop: thumbs-down and no_notes questions grouped by subject.
     * Accessible by admins and teachers.
     * Strictly redacted: NO personal data (no user_id, student name, email, or phone).
     * Respects retention rules (last 60 days).
     */
    public function feedbackLoop(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'teacher', 'super_admin'], true)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $instituteId = $user->role === 'super_admin' ? ($request->query('institute_id') ?: $user->institute_id ?: 1) : ($user->institute_id ?: 1);
        $days = (int) ($request->query('days', 60));

        // Fetch thumbs-down or no_notes_found queries
        // Exclude flagged/distress queries for strict privacy
        $queries = StudentAiQuery::withoutGlobalScopes()
            ->where('is_flagged', false)
            ->where('created_at', '>=', now()->subDays($days))
            ->where(function ($q) {
                $q->where('status', 'no_notes_found')
                  ->orWhere('feedback_rating', 'down');
            });

        if ($instituteId) {
            $queries->where('institute_id', $instituteId);
        }

        $results = $queries->latest()->limit(200)->get();

        // Group by subject
        $groups = [];
        foreach ($results as $item) {
            $subject = trim((string) ($item->subject ?: 'General / Unspecified'));
            if (!isset($groups[$subject])) {
                $groups[$subject] = [
                    'subject' => $subject,
                    'total_count' => 0,
                    'no_notes_count' => 0,
                    'thumbs_down_count' => 0,
                    'questions' => [],
                ];
            }

            $groups[$subject]['total_count']++;
            if ($item->status === 'no_notes_found') {
                $groups[$subject]['no_notes_count']++;
            }
            if ($item->feedback_rating === 'down') {
                $groups[$subject]['thumbs_down_count']++;
            }

            // REDACTED record: absolutely NO student personal information
            $groups[$subject]['questions'][] = [
                'id' => $item->id,
                'question' => $item->question,
                'status' => $item->status,
                'feedback_rating' => $item->feedback_rating,
                'created_at' => $item->created_at->format('Y-m-d H:i'),
            ];
        }

        // Sort groups by total_count descending
        uasort($groups, fn($a, $b) => $b['total_count'] <=> $a['total_count']);

        return response()->json([
            'institute_id' => $instituteId,
            'days' => $days,
            'feedback_groups' => array_values($groups),
        ]);
    }

    /**
     * Get assistant_public_contact settings.
     */
    public function getContactSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        return response()->json([
            'phone' => SiteSetting::get('assistant_public_contact_phone', ''),
            'whatsapp' => SiteSetting::get('assistant_public_contact_whatsapp', ''),
            'email' => SiteSetting::get('assistant_public_contact_email', ''),
            'address' => SiteSetting::get('assistant_public_contact_address', ''),
            'hours' => SiteSetting::get('assistant_public_contact_hours', ''),
        ]);
    }

    /**
     * Update assistant_public_contact settings.
     */
    public function updateContactSettings(Request $request, PublicKnowledgeService $knowledgeService): JsonResponse
    {
        $user = $request->user();
        if (!$user || !in_array($user->role, ['admin', 'super_admin'], true)) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'phone' => 'nullable|string|max:100',
            'whatsapp' => 'nullable|string|max:100',
            'email' => 'nullable|string|max:150',
            'address' => 'nullable|string|max:500',
            'hours' => 'nullable|string|max:200',
        ]);

        foreach ($validated as $field => $value) {
            $key = "assistant_public_contact_{$field}";
            SiteSetting::set($key, trim((string) $value), 'assistant_public_contact');
        }

        $instituteId = $user->institute_id ?? 1;
        $knowledgeService->clearCache($instituteId);

        return response()->json([
            'message' => 'Assistant public contact settings updated successfully.',
            'settings' => [
                'phone' => SiteSetting::get('assistant_public_contact_phone', ''),
                'whatsapp' => SiteSetting::get('assistant_public_contact_whatsapp', ''),
                'email' => SiteSetting::get('assistant_public_contact_email', ''),
                'address' => SiteSetting::get('assistant_public_contact_address', ''),
                'hours' => SiteSetting::get('assistant_public_contact_hours', ''),
            ],
        ]);
    }
}

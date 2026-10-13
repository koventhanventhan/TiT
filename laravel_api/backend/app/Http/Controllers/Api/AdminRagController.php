<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningMaterial;
use App\Models\StudentAiQuery;
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

        $instituteId = $user->role === 'super_admin' ? $request->query('institute_id') : $user->institute_id;
        $days = (int) ($request->query('days', 14));

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
            ->select('id', 'title', 'institute_id', 'rag_error', 'updated_at')
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

        // 5. Recent Flagged Distress Queries (excluding safety blocks which are count-only)
        $flaggedQueries = (clone $queriesQuery)
            ->where('is_flagged', true)
            ->where('flag_reason', '!=', 'safety_block')
            ->with(['user:id,name,phone_number,email'])
            ->latest()
            ->limit(20)
            ->get(['id', 'institute_id', 'user_id', 'flag_reason', 'question', 'answer', 'created_at']);

        $safetyBlockCount = (clone $queriesQuery)->where(function ($q) {
            $q->where('status', 'blocked_safety')->orWhere('flag_reason', 'safety_block');
        })->count();

        return response()->json([
            'institute_id' => $instituteId,
            'materials_status' => [
                'ready' => $statusCounts['ready'] ?? 0,
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
            ],
            'daily_queries' => $dailyQueries,
            'flagged_queries' => $flaggedQueries,
        ]);
    }
}

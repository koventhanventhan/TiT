<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subject;
use App\Models\ActivityLog;
use App\Services\NotificationService;
use App\Services\StudentPromotionService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminPromotionController extends Controller
{
    protected StudentPromotionService $promotionService;

    public function __construct(StudentPromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }

    public function promoteStudents(Request $request)
    {
        $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'integer|exists:users,id',
            'force' => 'boolean', // To override the 6-month safeguard
        ]);

        $studentIds = array_unique($request->student_ids);
        $force = $request->input('force', false);

        try {
            $summary = $this->promotionService->promoteStudents(
                $studentIds, 
                $force, 
                auth()->id(), 
                $request->ip(), 
                $request->userAgent()
            );

            return response()->json([
                'success' => true,
                'message' => "Promotion complete.",
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during promotion: ' . $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LearningMaterial;

class StudentMaterialController extends Controller
{
    public function index(Request $request)
    {
        $parent = $request->user();
        if (!$parent || $parent->role !== 'user' || $parent->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $studentId = $request->header('X-Selected-Child-Id') ?: $request->header('X-Student-Id');
        if ($studentId) {
            $student = $parent->students()->where('id', $studentId)->first();
            if (!$student) {
                return response()->json(['message' => 'Forbidden: You do not have access to this student profile.'], 403);
            }
        } else {
            $student = $parent->students()->first();
        }

        if (!$student) {
            return response()->json([]);
        }

        $query = LearningMaterial::orderBy('created_at', 'desc');

        // Filter materials based on the student's grade
        if ($student->current_grade) {
            $query->where(function($q) use ($student) {
                $q->where('grade', $student->current_grade)
                  ->orWhereNull('grade')
                  ->orWhere('grade', ''); // Also include materials specifically not assigned to a grade (Global use)
            });
        }

        // Filter by student's medium (English/Tamil)
        if ($student->medium) {
            $query->where(function($q) use ($student) {
                $q->where('medium', $student->medium)
                  ->orWhere('medium', 'both');
            });
        }

        // Apply 2-day limit for automated recordings
        $query->where(function ($q) {
            $q->where('type', '!=', 'recording')
              ->orWhereNull('zoom_schedule_id')
              ->orWhere('created_at', '>=', now()->subDays(2));
        });

        // Skip disabled grades for recordings
        $disabledGrades = json_decode(\App\Models\SiteSetting::get('zoom_recordings_disabled_grades', '[]'), true);
        if (is_array($disabledGrades) && count($disabledGrades) > 0) {
            $query->where(function ($q) use ($disabledGrades) {
                $q->where('type', '!=', 'recording')
                  ->orWhereNotIn('grade', $disabledGrades);
            });
        }

        $materials = $query->get();
        return response()->json($materials);
    }
}

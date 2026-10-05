<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LearningMaterial;

class StudentMaterialController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = LearningMaterial::orderBy('created_at', 'desc');

        // Filter materials based on the student's grade
        if ($user && $user->current_grade) {
            $query->where(function($q) use ($user) {
                $q->where('grade', $user->current_grade)
                  ->orWhereNull('grade')
                  ->orWhere('grade', ''); // Also include materials specifically not assigned to a grade (Global use)
            });
        }

        // Filter by student's medium (English/Tamil)
        if ($user && $user->medium) {
            $query->where(function($q) use ($user) {
                $q->where('medium', $user->medium)
                  ->orWhere('medium', 'both');
            });
        }

        // Apply configurable day-limit for automated recordings
        $visibilityDays = (int) \App\Models\SiteSetting::get('recording_visibility_days', 2);
        $query->where(function ($q) use ($visibilityDays) {
            $q->where('type', '!=', 'recording')
              ->orWhereNull('zoom_schedule_id')
              ->orWhere('created_at', '>=', now()->subDays($visibilityDays));
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

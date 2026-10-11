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

        $materials = $materials->filter(function ($m) use ($user) {
            $userGrade = $user ? $user->current_grade : null;
            $matGrade = $m->grade;

            // Keep if student has no current_grade or material has no grade
            if (empty($userGrade) || empty($matGrade)) {
                return true;
            }

            preg_match('/(\d+)/', $userGrade, $userMatch);
            $userRef = isset($userMatch[1]) ? $userMatch[1] : strtoupper(trim($userGrade));

            preg_match('/(\d+)/', $matGrade, $matMatch);
            $matRef = isset($matMatch[1]) ? $matMatch[1] : strtoupper(trim($matGrade));

            return $userRef === $matRef;
        });

        return response()->json($materials->values());
    }
}

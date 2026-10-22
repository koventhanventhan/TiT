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

        // Filter by student's medium (Tamil / English / Sinhala / all / both)
        if ($user && $user->medium) {
            $query->where(function ($q) use ($user) {
                $q->where('medium', $user->medium)
                  ->orWhere('medium', 'both')
                  ->orWhere('medium', 'all')
                  ->orWhereNull('medium');
            });
        }

        // Apply configurable day-limit for automated recordings
        $visibilityDays = (int) \App\Models\SiteSetting::get('recording_visibility_days', 2);
        $query->where(function ($q) use ($visibilityDays) {
            $q->where('type', '!=', 'recording')
              ->orWhereNull('zoom_schedule_id')
              ->orWhere('created_at', '>=', now()->subDays($visibilityDays));
        });

        $materials = $query->get()->reject(function ($m) {
            return $m->type === 'recording' && \App\Models\SiteSetting::isRecordingGradeDisabled($m->grade);
        });

        $materials = $materials->filter(function ($m) use ($user) {
            return \App\Support\GradeMatcher::matches($user?->current_grade, $m->grade);
        });

        return response()->json($materials->values());
    }
}

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

        // Direct subject query access check
        if ($request->has('subject') && !empty($request->input('subject'))) {
            $requestedSubject = trim((string) $request->input('subject'));
            if ($user && in_array($requestedSubject, $user->lockedSubjects())) {
                return response()->json([
                    'error' => 'subject_locked',
                    'subject' => $requestedSubject,
                    'message' => "Subject {$requestedSubject} is locked pending top-up payment.",
                ], 403);
            }
        }

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

        $lockedSubjects = $user ? $user->lockedSubjects() : [];
        if (!empty($lockedSubjects)) {
            $materials = $materials->reject(function ($m) use ($lockedSubjects) {
                return !empty($m->subject) && in_array($m->subject, $lockedSubjects);
            });
        }

        return response()->json($materials->values());
    }
}

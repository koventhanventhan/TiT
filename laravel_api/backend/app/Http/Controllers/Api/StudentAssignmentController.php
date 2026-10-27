<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Support\GradeMatcher;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class StudentAssignmentController extends Controller
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
        
        $lockedSubjects = $user ? $user->lockedSubjects() : [];

        $assignments = Assignment::with(['teacher:id,name,full_name', 'submissions' => fn($q) => $q->where('student_id', $user->id)])
            ->orderBy('due_date', 'asc')
            ->get()
            ->filter(function ($assignment) use ($user, $lockedSubjects) {
                if (!empty($assignment->subject) && in_array($assignment->subject, $lockedSubjects)) {
                    return false;
                }
                $assignGrade = $assignment->grade;
                if (empty($assignGrade)) {
                    return true;
                }
                $userGrade = $user?->current_grade;
                if (empty($userGrade)) {
                    return false;
                }
                return GradeMatcher::normalize($assignGrade) === GradeMatcher::normalize($userGrade);
            })
            ->values();

        $mapped = $assignments->map(function ($a) {
            $dueDateIso = null;
            if ($a->due_date instanceof Carbon) {
                $dueDateIso = $a->due_date->toIso8601String();
            } elseif ($a->due_date) {
                $dueDateIso = Carbon::parse($a->due_date)->toIso8601String();
            }

            return [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                'due_date' => $dueDateIso,
                'grade' => $a->grade,
                'subject' => $a->subject,
                'file_path' => $a->file_path,
                'file_url' => $a->file_path ? asset('storage/' . $a->file_path) : null,
                'teacher_id' => $a->teacher_id,
                'teacher' => $a->teacher ? [
                    'id' => $a->teacher->id,
                    'name' => $a->teacher->name,
                    'full_name' => $a->teacher->full_name ?? $a->teacher->name,
                ] : null,
                'submissions' => $a->submissions->map(function ($sub) {
                    $submittedAtIso = null;
                    $submitted = $sub->submitted_at ?? $sub->created_at;
                    if ($submitted instanceof Carbon) {
                        $submittedAtIso = $submitted->toIso8601String();
                    } elseif ($submitted) {
                        $submittedAtIso = Carbon::parse($submitted)->toIso8601String();
                    }

                    return [
                        'id' => $sub->id,
                        'assignment_id' => $sub->assignment_id,
                        'student_id' => $sub->student_id,
                        'file_path' => $sub->file_path,
                        'file_url' => $sub->file_path ? asset('storage/' . $sub->file_path) : null,
                        'marks' => $sub->marks,
                        'feedback' => $sub->feedback,
                        'status' => $sub->status,
                        'submitted_at' => $submittedAtIso,
                        'created_at' => $sub->created_at ? $sub->created_at->toIso8601String() : null,
                        'updated_at' => $sub->updated_at ? $sub->updated_at->toIso8601String() : null,
                    ];
                })->values(),
                'created_at' => $a->created_at ? $a->created_at->toIso8601String() : null,
                'updated_at' => $a->updated_at ? $a->updated_at->toIso8601String() : null,
            ];
        });
            
        return response()->json($mapped->all());
    }

    public function submit(Request $request, $assignmentId)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,zip|max:10240', // 10MB
        ]);

        $user = $request->user();
        $assignment = Assignment::findOrFail($assignmentId);

        $assignGrade = $assignment->grade;
        if (!empty($assignGrade)) {
            $userGrade = $user?->current_grade;
            if (empty($userGrade) || GradeMatcher::normalize($assignGrade) !== GradeMatcher::normalize($userGrade)) {
                return response()->json(['message' => 'Unauthorized or assignment not visible.'], 403);
            }
        }

        if ($user && !empty($assignment->subject) && in_array($assignment->subject, $user->lockedSubjects())) {
            return response()->json([
                'error' => 'subject_locked',
                'subject' => $assignment->subject,
                'message' => "Subject {$assignment->subject} is locked pending top-up payment.",
            ], 403);
        }

        $existing = AssignmentSubmission::where('assignment_id', $assignmentId)
            ->where('student_id', $user->id)
            ->first();

        if ($existing && $existing->status === 'graded') {
            return response()->json(['message' => 'Already graded, resubmission is not allowed'], 422);
        }

        $filePath = $request->file('file')->store('submissions', 'public');

        $submission = AssignmentSubmission::updateOrCreate(
            ['assignment_id' => $assignmentId, 'student_id' => $user->id],
            [
                'file_path' => $filePath,
                'status' => 'pending',
                'submitted_at' => now(),
            ]
        );

        return response()->json([
            'id' => $submission->id,
            'assignment_id' => $submission->assignment_id,
            'student_id' => $submission->student_id,
            'file_path' => $submission->file_path,
            'file_url' => asset('storage/' . $submission->file_path),
            'status' => $submission->status,
            'marks' => $submission->marks,
            'feedback' => $submission->feedback,
            'submitted_at' => $submission->submitted_at ? $submission->submitted_at->toIso8601String() : null,
            'created_at' => $submission->created_at ? $submission->created_at->toIso8601String() : null,
            'updated_at' => $submission->updated_at ? $submission->updated_at->toIso8601String() : null,
        ]);
    }
}
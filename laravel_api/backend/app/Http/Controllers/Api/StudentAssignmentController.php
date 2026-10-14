<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Support\Facades\Storage;

class StudentAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $assignments = Assignment::with(['teacher:id,name,full_name', 'submissions' => fn($q) => $q->where('student_id', $user->id)])
            ->orderBy('due_date', 'asc')
            ->get()
            ->filter(function ($assignment) use ($user) {
                $assignGrade = $assignment->grade;
                if (empty($assignGrade)) {
                    return true;
                }
                $userGrade = $user?->current_grade;
                if (empty($userGrade)) {
                    return false;
                }
                return \App\Support\GradeMatcher::normalize($assignGrade) === \App\Support\GradeMatcher::normalize($userGrade);
            })
            ->values();
            
        return response()->json($assignments);
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
            if (empty($userGrade) || \App\Support\GradeMatcher::normalize($assignGrade) !== \App\Support\GradeMatcher::normalize($userGrade)) {
                return response()->json(['message' => 'Unauthorized or assignment not visible.'], 403);
            }
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

        return response()->json($submission);
    }
}

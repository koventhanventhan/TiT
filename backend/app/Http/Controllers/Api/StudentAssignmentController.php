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
        $parent = $request->user();
        if (!$parent || $parent->role !== 'user' || $parent->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $studentId = $request->header('X-Selected-Child-Id') ?: $request->header('X-Student-Id');
        
        $user = $studentId 
            ? $parent->students()->where('id', $studentId)->first() 
            : $parent->students()->first();
            
        if (!$user) {
            return response()->json(['message' => 'Forbidden: You do not have access to this student profile.'], 403);
        }
        
        $assignments = Assignment::where(function($query) use ($user) {
                $query->where('grade', $user->current_grade)
                      ->orWhereNull('grade')
                      ->orWhere('grade', '');
            })
            ->with(['teacher', 'submissions' => function($q) use ($user) {
                $q->where('student_id', $user->id);
            }])
            ->orderBy('due_date', 'asc')
            ->get();
            
        return response()->json($assignments);
    }

    public function submit(Request $request, $assignmentId)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB
        ]);

        $parent = $request->user();
        if (!$parent || $parent->role !== 'user' || $parent->deactivated_at) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $studentId = $request->header('X-Selected-Child-Id') ?: $request->header('X-Student-Id');
        
        $user = $studentId 
            ? $parent->students()->where('id', $studentId)->first() 
            : $parent->students()->first();
            
        if (!$user) {
            return response()->json(['message' => 'Forbidden: You do not have access to this student profile.'], 403);
        }

        $assignment = Assignment::findOrFail($assignmentId);

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

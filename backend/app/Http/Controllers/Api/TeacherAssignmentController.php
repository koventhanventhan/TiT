<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $assignments = Assignment::where('teacher_id', $request->user()->id)
            ->withCount('submissions')
            ->withCount(['submissions as ungraded_count' => function($query) {
                $query->where('status', 'pending');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($assignments);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'subject' => 'nullable|string',
            'grade' => 'nullable|string',
            'due_date' => 'nullable|date',
            'file' => 'nullable|file|mimes:pdf,doc,docx,zip|max:10240',
        ]);

        $filePath = null;
        if ($request->hasFile('file')) {
            $filePath = $request->file('file')->store('assignments', 'public');
        }

        $assignment = Assignment::create([
            'teacher_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'subject' => $validated['subject'] ?? null,
            'grade' => $validated['grade'] ?? null,
            'due_date' => $validated['due_date'] ?? null,
            'file_path' => $filePath,
        ]);

        return response()->json($assignment, 201);
    }

    public function submissions(Assignment $assignment)
    {
        $this->authorizeTeacher($assignment);
        
        $submissions = $assignment->submissions()->with('student')->get();
        return response()->json($submissions);
    }

    public function gradeSubmission(Request $request, AssignmentSubmission $submission)
    {
        $this->authorizeTeacher($submission->assignment);

        $validated = $request->validate([
            'marks' => 'required|numeric|min:0|max:100',
            'feedback' => 'nullable|string',
        ]);

        $submission->update([
            'marks' => $validated['marks'],
            'feedback' => $validated['feedback'],
            'status' => 'graded',
        ]);

        return response()->json($submission);
    }

    private function authorizeTeacher(Assignment $assignment)
    {
        if ($assignment->teacher_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
    }
}

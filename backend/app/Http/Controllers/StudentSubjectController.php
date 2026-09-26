<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentSubjectController extends Controller
{
    public function updateSubjects(Request $request)
    {
        $request->validate([
            'subjects' => 'required|array',
            'subjects.*' => 'string',
            'medium' => 'nullable|in:tamil,english',
        ]);

        $user = Auth::user();

        if ($user->role !== 'user') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($request->has('medium') && in_array($request->medium, ['tamil', 'english'])) {
            $user->medium = $request->medium;
        }

        $user->selected_subjects = json_encode(array_values(array_unique($request->subjects)));
        $user->needs_subject_review = false; // Clear the flag upon update
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Subjects updated successfully.',
            'user' => $user
        ]);
    }
}

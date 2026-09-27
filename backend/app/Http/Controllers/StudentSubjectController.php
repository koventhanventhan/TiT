<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\NotificationService;

class StudentSubjectController extends Controller
{
    protected NotificationService $notifier;

    public function __construct(NotificationService $notifier)
    {
        $this->notifier = $notifier;
    }

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

        $mediumChangeRequested = false;

        if ($request->has('medium') && in_array($request->medium, ['tamil', 'english'])) {
            if ($user->medium !== $request->medium) {
                \App\Models\MediumChangeRequest::create([
                    'user_id' => $user->id,
                    'current_medium' => $user->medium ?? 'unknown',
                    'requested_medium' => $request->medium,
                    'status' => 'pending'
                ]);
                $mediumChangeRequested = true;

                // Notify admin about the pending medium change request
                try {
                    $this->notifier->notifyAdmin(
                        'admin_alert', 'tit_general_update',
                        ['Medium Change Request', "New medium change request from {$user->full_name ?? $user->name} ({$user->phone_number}). Requested: {$request->medium}."],
                        ['alert_title' => 'Medium Change Request', 'alert_message' => "{$user->full_name ?? $user->name} ({$user->phone_number}) requested a medium change to {$request->medium}."]
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to notify admin about medium change: ' . $e->getMessage());
                }
            }
        }

        $user->selected_subjects = json_encode(array_values(array_unique($request->subjects)));
        $user->needs_subject_review = false; // Clear the flag upon update
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Subjects updated successfully.',
            'mediumChangeRequested' => $mediumChangeRequested,
            'user' => $user
        ]);
    }
}

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
                    $whatsappService = app(\App\Services\WhatsAppService::class);
                    $adminPhone = config('services.whatsapp.admin_phone', '94770000000');
                    $whatsappService->sendMessage($adminPhone, "New Medium Change Request from {$user->name} ({$user->phone}). Requested: {$request->medium}.");
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

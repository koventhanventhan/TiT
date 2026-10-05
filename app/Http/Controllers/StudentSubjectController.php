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

        // Make sure every selected subject really belongs to this student's grade / stream
        $category = $user->getSubjectCategory();
        if ($category) {
            $validNames = \App\Models\Subject::where('category', $category)->pluck('name')->all();
            $invalid = array_values(array_diff($request->subjects, $validNames));
            if (!empty($validNames) && !empty($invalid)) {
                return response()->json([
                    'message' => 'Invalid subject(s) for your grade/stream: ' . implode(', ', $invalid),
                ], 422);
            }
        }

        $mediumChangeRequested = false;

        if ($request->has('medium') && in_array($request->medium, ['tamil', 'english'])) {
            if ($user->medium !== $request->medium) {
                $alreadyPending = \App\Models\MediumChangeRequest::where('user_id', $user->id)
                    ->where('requested_medium', $request->medium)
                    ->where('status', 'pending')
                    ->exists();

                if ($alreadyPending) {
                    // Don't flood the admin with duplicate requests
                    $mediumChangeRequested = true;
                } else {
                \App\Models\MediumChangeRequest::create([
                    'user_id' => $user->id,
                    'current_medium' => $user->medium ?? 'unknown',
                    'requested_medium' => $request->medium,
                    'status' => 'pending'
                ]);
                $mediumChangeRequested = true;

                // Notify admin about the pending medium change request
                $studentName = $user->full_name ?? $user->name;
                $contactInfo = $user->phone_number;
                if (empty($contactInfo) && $user->parent_id && $user->parent) {
                    $contactInfo = 'via Parent: ' . ($user->parent->full_name ?? $user->parent->name);
                } elseif (empty($contactInfo)) {
                    // Hide child.local emails if possible, but fallback to it if nothing else
                    $contactInfo = str_ends_with($user->email, '@child.local') ? 'No Contact' : $user->email;
                }

                try {
                    $this->notifier->notifyAdmin(
                        'admin_alert', 'tit_general_update',
                        ['Medium Change Request', "New medium change request from {$studentName} ({$contactInfo}). Requested: {$request->medium}."],
                        ['alert_title' => 'Medium Change Request', 'alert_message' => "{$studentName} ({$contactInfo}) requested a medium change to {$request->medium}."]
                    );
                } catch (\Exception $e) {
                    \Log::error('Failed to notify admin about medium change: ' . $e->getMessage());
                }
                }
            }
        }

        $user->selected_subjects = json_encode(array_values(array_unique($request->subjects)));
        $user->needs_subject_review = false; // Clear the flag upon update
        $user->save();

        // Authoritative fee + whether this month is already paid
        $yearMonth = now()->format('Y-m');
        $alreadyPaid = \App\Models\Payment::where('user_id', $user->id)
            ->where('year_month', $yearMonth)
            ->where('status', 'paid')
            ->exists();

        // Get the last paid month
        $lastPaidPayment = \App\Models\Payment::where('user_id', $user->id)
            ->where('status', 'paid')
            ->orderBy('year_month', 'desc')
            ->first();
            
        $paidUntil = null;
        if ($lastPaidPayment) {
            $paidUntil = \Carbon\Carbon::createFromFormat('Y-m', $lastPaidPayment->year_month)->format('F Y');
        }

        $totalAmount = $user->calculateMonthlyFee();
        $admissionFee = $user->getAdmissionFeeAmount();

        return response()->json([
            'success' => true,
            'message' => 'Subjects updated successfully.',
            'mediumChangeRequested' => $mediumChangeRequested,
            'already_paid' => $alreadyPaid,
            'paid_until' => $paidUntil,
            'amount' => $totalAmount,
            'admission_fee' => $admissionFee,
            'monthly_fee' => $totalAmount - $admissionFee,
            'user' => $user
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\SubjectTopup;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PayHereService;
use App\Services\PaymentReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StudentTopupController extends Controller
{
    protected ?NotificationService $notifier = null;
    protected ?PayHereService $payHere = null;

    public function __construct()
    {
        try {
            $this->notifier = app(NotificationService::class);
        } catch (\Throwable $e) {
            Log::warning('NotificationService init failed in StudentTopupController: ' . $e->getMessage());
        }

        try {
            $this->payHere = app(PayHereService::class);
        } catch (\Throwable $e) {
            Log::warning('PayHereService init failed in StudentTopupController: ' . $e->getMessage());
        }
    }

    /**
     * Initiate online payment for a pending subject top-up.
     * POST /api/student/subject-topups/{id}/pay-online
     */
    public function payOnline(Request $request, $id)
    {
        $user = $request->user();
        $topup = SubjectTopup::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $orderId = 'TOPUP_' . $topup->id . '_' . time();

        $payment = Payment::updateOrCreate(
            [
                'user_id' => $user->id,
                'year_month' => $topup->year_month,
                'kind' => 'topup',
                'status' => 'pending',
            ],
            [
                'institute_id' => $user->institute_id ?? 1,
                'amount' => $topup->amount,
                'payment_method' => 'online',
                'gateway_ref' => $orderId,
                'meta' => [
                    'topup_id' => $topup->id,
                    'added_subjects' => $topup->added_subjects,
                ],
            ]
        );

        $topup->update([
            'payment_id' => $payment->id,
            'payment_method' => 'online',
        ]);

        // If simulate_success is passed (e.g. in test environment or mock)
        if ($request->boolean('simulate_success') || app()->environment('testing')) {
            $payment->update([
                'status' => 'paid',
                'paid_at' => now(),
                'transaction_id' => 'SIM_' . $orderId,
            ]);
            $topup->markPaid($payment->id, 'online');

            try {
                $receiptService = app(PaymentReceiptService::class);
                $receiptService->ensureReceipt($payment);
                $receiptService->sendReceiptNotifications($payment);
            } catch (\Throwable $e) {
                Log::error('Receipt trigger failed in simulated payOnline: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Top-up payment successful. Your new subjects are now unlocked!',
                'topup' => $topup->fresh(),
                'user' => $user->fresh(),
            ]);
        }

        // Build PayHere checkout params
        $payhereData = null;
        if ($this->payHere && $this->payHere->isConfigured()) {
            $payhereData = [
                'payhere_url' => $this->payHere->getCheckoutUrl(),
                'params' => $this->payHere->buildCheckoutParams($payment, $user),
            ];
        }

        return response()->json([
            'success' => true,
            'order_id' => $orderId,
            'amount' => (float) $topup->amount,
            'topup' => $topup,
            'payhere' => $payhereData,
        ]);
    }

    /**
     * Initiate offline payment for a pending subject top-up.
     * POST /api/student/subject-topups/{id}/pay-offline
     */
    public function payOffline(Request $request, $id)
    {
        $user = $request->user();
        $topup = SubjectTopup::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Deduplication: if requested offline within last 10 minutes
        if ($topup->payment_method === 'offline' && $topup->updated_at && $topup->updated_at->diffInMinutes(now()) < 10) {
            return response()->json([
                'message' => 'Top-up offline payment already requested. Admin will confirm shortly.',
                'topup' => $topup,
                'deduped' => true,
            ]);
        }

        $orderId = 'TOPUP_OFF_' . $topup->id . '_' . time();
        $payment = Payment::updateOrCreate(
            [
                'user_id' => $user->id,
                'year_month' => $topup->year_month,
                'kind' => 'topup',
                'status' => 'pending',
            ],
            [
                'institute_id' => $user->institute_id ?? 1,
                'amount' => $topup->amount,
                'payment_method' => 'offline',
                'gateway_ref' => $orderId,
                'meta' => [
                    'topup_id' => $topup->id,
                    'added_subjects' => $topup->added_subjects,
                ],
            ]
        );

        $topup->update([
            'payment_method' => 'offline',
            'payment_id' => $payment->id,
        ]);

        // Send admin notification
        $studentName = $user->full_name ?? $user->name;
        $contactInfo = $user->phone_number ?: ($user->email ?: 'No contact');
        $subjectsStr = implode(', ', $topup->added_subjects ?: []);
        $amountStr = number_format((float) $topup->amount, 2);

        if ($this->notifier) {
            try {
                $this->notifier->notifyAdmin(
                    'admin_alert', 'tit_admin_approved',
                    ["Subject Top-up: {$studentName} added {$subjectsStr} (Rs {$amountStr})", "admin"],
                    [
                        'alert_title' => 'Subject Top-up Request',
                        'alert_message' => "Subject Top-up: Student {$studentName} ({$contactInfo}) added {$subjectsStr} (Rs {$amountStr}) for {$topup->year_month}.",
                    ]
                );
            } catch (\Throwable $e) {
                Log::error('Top-up offline notification failed: ' . $e->getMessage());
            }
        }

        return response()->json([
            'message' => 'Top-up offline payment requested. Added subjects will unlock once admin confirms.',
            'topup' => $topup->fresh(),
            'deduped' => false,
        ]);
    }

    /**
     * Cancel pending top-up and revert added subjects.
     * POST /api/student/subject-topups/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $topup = SubjectTopup::where('id', $id)
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->firstOrFail();

        // Revert user's selected_subjects to previous list
        $current = $user->selected_subjects ?: [];
        if (is_string($current)) {
            $current = json_decode($current, true) ?: [];
        }

        $revert = $topup->previous_subjects ?: array_values(array_diff($current, $topup->added_subjects ?: []));
        $user->selected_subjects = json_encode(array_values($revert));
        $user->save();

        $topup->markCancelled();

        // Also cancel associated pending payment record if exists
        if ($topup->payment_id) {
            Payment::where('id', $topup->payment_id)->where('status', 'pending')->update(['status' => 'cancelled']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Top-up cancelled and added subjects removed.',
            'topup' => $topup->fresh(),
            'user' => $user->fresh(),
        ]);
    }

    /**
     * Admin mark top-up as paid.
     * POST /admin/students-requests/subject-topups/{id}/mark-paid
     */
    public function markPaidByAdmin(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin' && auth()->user()->role !== 'super_admin') {
            return redirect()->back()->with('error', 'Admin access required.');
        }

        $topup = SubjectTopup::where('id', $id)
            ->where('status', 'pending')
            ->firstOrFail();

        $payment = Payment::updateOrCreate(
            [
                'id' => $topup->payment_id,
            ],
            [
                'user_id' => $topup->user_id,
                'institute_id' => $topup->institute_id,
                'amount' => $topup->amount,
                'year_month' => $topup->year_month,
                'status' => 'paid',
                'payment_method' => $topup->payment_method ?: 'offline',
                'paid_at' => now(),
                'kind' => 'topup',
                'meta' => [
                    'topup_id' => $topup->id,
                    'added_subjects' => $topup->added_subjects,
                ],
                'gateway_ref' => 'TOPUP_ADMIN_' . $topup->id . '_' . time(),
            ]
        );

        $topup->markPaid($payment->id, $topup->payment_method ?: 'offline');

        // Issue receipt
        try {
            $receiptService = app(PaymentReceiptService::class);
            $receiptService->ensureReceipt($payment);
            $receiptService->sendReceiptNotifications($payment);
        } catch (\Throwable $e) {
            Log::error('Receipt trigger failed in markPaidByAdmin: ' . $e->getMessage());
        }

        // Notify student
        if ($this->notifier && $topup->user) {
            try {
                $this->notifier->notifyUser(
                    $topup->user, 'general_update', 'tit_general_update',
                    [$topup->user->full_name ?? $topup->user->name, 'admin'],
                    ['update_details' => 'Your subject top-up has been approved. Your new subjects are now unlocked!']
                );
            } catch (\Throwable $e) {
                Log::error('Student top-up approval notification failed: ' . $e->getMessage());
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Top-up approved and marked paid. Subjects are now unlocked.',
                'topup' => $topup->fresh(),
            ]);
        }

        return redirect()->back()->with('success', 'Top-up approved and marked paid.');
    }
}

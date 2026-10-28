<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentPaymentController extends Controller
{
    /**
     * Check authorization for admin access.
     */
    private function authorizeAdmin(): void
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(redirect()->route('admin.login')->with('error', 'Admin access required'));
        }
    }

    /**
     * Scope student and payment by student ID and tenant.
     * Ensures payment belongs to the student (404 otherwise).
     */
    private function resolveStudentAndPayment($studentId, $paymentId): array
    {
        $this->authorizeAdmin();

        $student = User::where('role', 'user')
            ->where('id', $studentId)
            ->firstOrFail();

        $payment = Payment::where('id', $paymentId)
            ->where('user_id', $student->id)
            ->firstOrFail();

        return [$student, $payment];
    }

    /**
     * Update an existing payment row.
     * PUT /admin/students/{id}/payments/{payment}
     */
    public function update(Request $request, $studentId, $paymentId)
    {
        [$student, $payment] = $this->resolveStudentAndPayment($studentId, $paymentId);

        $request->validate([
            'year_month' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['paid', 'pending', 'cancelled', 'failed'])],
            'payment_method' => ['nullable', 'string', Rule::in(['offline', 'online', 'bank_transfer', 'cash'])],
            'received_by' => ['nullable', Rule::in(array_keys(Payment::RECEIVED_BY_OPTIONS))],
            'paid_date' => ['nullable', 'date'],
        ]);

        $yearMonth = $request->year_month;
        $status = $request->status;
        $amount = (float) $request->amount;
        $paymentMethod = $request->payment_method ?: ($payment->payment_method ?: 'offline');

        // Handle paid_date: required when status is paid (default to today if empty), null otherwise
        if ($status === 'paid') {
            $paidAt = $request->filled('paid_date') ? $request->paid_date : now()->format('Y-m-d');
        } else {
            $paidAt = null;
        }

        if ($payment->kind === 'topup' && $payment->status !== 'pending') {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Top-up payment amount can only be edited while pending.');
        }

        return DB::transaction(function () use ($request, $student, $payment, $yearMonth, $status, $amount, $paymentMethod, $paidAt) {
            $isTopup = ($payment->kind === 'topup');

            // Lock relevant rows for update within the same kind group
            $existingRows = Payment::where('user_id', $student->id)
                ->where('year_month', $yearMonth)
                ->when($isTopup, function ($q) {
                    $q->where('kind', 'topup');
                }, function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereNull('kind')->orWhereIn('kind', ['monthly', 'advance']);
                    });
                })
                ->lockForUpdate()
                ->get();

            // Never allow two PAID rows for the same student+month in the same kind group
            if ($status === 'paid') {
                $otherPaid = $existingRows->where('status', 'paid')->where('id', '!=', $payment->id);
                if ($otherPaid->isNotEmpty()) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['year_month' => 'A paid payment already exists for that month.'])
                        ->with('error', 'A paid payment already exists for that month.');
                }
            }

            // Changing the month of a row to a month that already has a PENDING row cancels that older pending row in the same kind group
            $otherPending = $existingRows->where('status', 'pending')->where('id', '!=', $payment->id);
            if ($otherPending->isNotEmpty()) {
                Payment::whereIn('id', $otherPending->pluck('id'))->update(['status' => 'cancelled']);
            }

            $oldSnapshot = $payment->toArray();

            $updates = [
                'year_month' => $yearMonth,
                'amount' => $amount,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'paid_at' => $paidAt,
            ];

            // Explicitly changing the method invalidates an older "received by" choice
            if ($request->filled('payment_method') && $request->payment_method !== $payment->payment_method) {
                $meta = (array) ($payment->meta ?? []);
                unset($meta['received_by']);
                $updates['meta'] = $meta ?: null;
            }

            // A paid row can only be labelled online when the gateway confirmed it
            if ($status === 'paid') {
                $payment->payment_method = $paymentMethod;
                if (isset($updates['meta'])) {
                    $payment->meta = $updates['meta'];
                }
                $updates = array_merge($updates, $payment->adminPaidAttributes($request->input('received_by'), auth()->id()));
            }

            $payment->update($updates);

            if ($payment->kind === 'topup') {
                $topup = \App\Models\SubjectTopup::where('payment_id', $payment->id)->first()
                    ?? (!empty($payment->meta['topup_id']) ? \App\Models\SubjectTopup::find($payment->meta['topup_id']) : null);
                if ($topup) {
                    $topup->update(['amount' => $amount]);
                }
            }

            ActivityLogService::log(
                'payment_updated',
                "Updated payment ID {$payment->id} for student {$student->id}",
                [
                    'student_id' => $student->id,
                    'old' => $oldSnapshot,
                    'new' => $payment->fresh()->toArray(),
                    'admin_id' => auth()->id(),
                ]
            );

            // If the edit turns a row into PAID and the student is deactivated -> reactivate
            if ($status === 'paid' && $student->deactivated_at) {
                $student->update(['deactivated_at' => null]);
            }

            // If the edit turned a previously unpaid row into PAID -> trigger receipt
            $preflightWarning = null;
            if ($status === 'paid' && ($oldSnapshot['status'] ?? null) !== 'paid') {
                try {
                    $receiptService = app(\App\Services\PaymentReceiptService::class);
                    $receiptService->ensureReceipt($payment);
                    $preflightWarning = $receiptService->preflightCheck($payment);
                    $receiptService->sendReceiptNotifications($payment);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Failed to trigger receipt on payment update: ' . $e->getMessage());
                    $preflightWarning = $e->getMessage();
                }
            }

            $successMsg = ($status === 'paid' && $student->deactivated_at)
                ? 'Payment updated for ' . $yearMonth . ' and student account reactivated.'
                : 'Payment updated.';

            $response = redirect()->back()->with('success', $successMsg);
            if ($request->payment_method === 'online' && $status === 'paid' && $payment->payment_method !== 'online') {
                $response->with('info', 'The payment gateway never confirmed this payment, so it was saved as Offline instead of Online.');
            }

            if ($preflightWarning) {
                $response->with('warning', "Payment saved, but the receipt could not be sent: {$preflightWarning}. Use Resend on the student's page.");
            }
            return $response;
        });
    }

    /**
     * Mark an existing pending/cancelled/failed payment row as paid.
     * POST /admin/students/{id}/payments/{payment}/mark-paid
     */
    public function markPaid(Request $request, $studentId, $paymentId)
    {
        [$student, $payment] = $this->resolveStudentAndPayment($studentId, $paymentId);

        $request->validate([
            'paid_date' => ['nullable', 'date'],
            'received_by' => ['nullable', Rule::in(array_keys(Payment::RECEIVED_BY_OPTIONS))],
        ]);

        $paidAt = $request->filled('paid_date') ? $request->paid_date : now()->format('Y-m-d');
        $receivedBy = $request->input('received_by');

        return DB::transaction(function () use ($student, $payment, $paidAt, $receivedBy) {
            $isTopup = ($payment->kind === 'topup');

            $existingRows = Payment::where('user_id', $student->id)
                ->where('year_month', $payment->year_month)
                ->when($isTopup, function ($q) {
                    $q->where('kind', 'topup');
                }, function ($q) {
                    $q->where(function ($sq) {
                        $sq->whereNull('kind')->orWhereIn('kind', ['monthly', 'advance']);
                    });
                })
                ->lockForUpdate()
                ->get();

            // Cannot mark paid if a paid row already exists for that month within same kind group
            $otherPaid = $existingRows->where('status', 'paid')->where('id', '!=', $payment->id);
            if ($otherPaid->isNotEmpty()) {
                return redirect()->back()->with('error', 'Payment for ' . $payment->year_month . ' is already marked as paid!');
            }

            // Cancel any other pending rows for that month within same kind group
            $otherPending = $existingRows->where('status', 'pending')->where('id', '!=', $payment->id);
            if ($otherPending->isNotEmpty()) {
                Payment::whereIn('id', $otherPending->pluck('id'))->update(['status' => 'cancelled']);
            }

            $oldSnapshot = $payment->toArray();

            $payment->update(array_merge([
                'status' => 'paid',
                'paid_at' => $paidAt,
            ], $payment->adminPaidAttributes($receivedBy, auth()->id())));

            // adminPaidAttributes() already ran against the pre-update status, refresh the model
            $payment->refresh();

            if ($payment->kind === 'topup') {
                \App\Models\SubjectTopup::markPaid($payment->id, $payment->payment_method ?: 'offline');
                try {
                    $notifier = app(\App\Services\NotificationService::class);
                    $notifier->notifyUser(
                        $student, 'general_update', 'tit_general_update',
                        [$student->full_name ?? $student->name, 'admin'],
                        ['update_details' => 'Your subject top-up has been approved. Your new subjects are now unlocked!']
                    );
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Top-up approval notification to student failed: ' . $e->getMessage());
                }
            }

            ActivityLogService::log(
                'payment_marked_paid',
                "Marked payment ID {$payment->id} ({$payment->year_month}) as paid for student {$student->id}",
                [
                    'student_id' => $student->id,
                    'payment' => $payment->fresh()->toArray(),
                    'admin_id' => auth()->id(),
                ]
            );

            // If student was deactivated, reactivate
            if ($student->deactivated_at) {
                $student->update(['deactivated_at' => null]);
            }

            // Assign receipt number & dispatch receipt notifications (Email + WhatsApp)
            $preflightWarning = null;
            try {
                $receiptService = app(\App\Services\PaymentReceiptService::class);
                $receiptService->ensureReceipt($payment);
                $preflightWarning = $receiptService->preflightCheck($payment);
                $receiptService->sendReceiptNotifications($payment);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to trigger receipt on markPaid: ' . $e->getMessage());
                $preflightWarning = $e->getMessage();
            }

            $successMsg = $student->deactivated_at
                ? 'Payment marked for ' . $payment->year_month . ' and student account reactivated.'
                : 'Payment marked for ' . $payment->year_month . '.';

            $response = redirect()->back()->with('success', $successMsg);
            if ($preflightWarning) {
                $response->with('warning', "Payment saved, but the receipt could not be sent: {$preflightWarning}. Use Resend on the student's page.");
            }
            return $response;
        });
    }

    /**
     * Fallback for 3rd button when receipt service is not present: Revert a paid payment to pending.
     * POST /admin/students/{id}/payments/{payment}/revert
     */
    public function revert(Request $request, $studentId, $paymentId)
    {
        [$student, $payment] = $this->resolveStudentAndPayment($studentId, $paymentId);

        return DB::transaction(function () use ($student, $payment) {
            $oldSnapshot = $payment->toArray();

            $payment->update([
                'status' => 'pending',
                'paid_at' => null,
            ]);

            if ($payment->kind === 'topup') {
                $topup = \App\Models\SubjectTopup::where('payment_id', $payment->id)->first()
                    ?? (!empty($payment->meta['topup_id']) ? \App\Models\SubjectTopup::find($payment->meta['topup_id']) : null);
                if ($topup) {
                    $topup->update([
                        'status' => 'pending',
                        'paid_at' => null,
                    ]);
                }
            }

            ActivityLogService::log(
                'payment_reverted',
                "Reverted payment ID {$payment->id} ({$payment->year_month}) to pending for student {$student->id}",
                [
                    'student_id' => $student->id,
                    'old' => $oldSnapshot,
                    'new' => $payment->fresh()->toArray(),
                    'admin_id' => auth()->id(),
                ]
            );

            return redirect()->back()->with('success', 'Payment reverted to pending.');
        });
    }

    /**
     * Download payment receipt (Admin view).
     * GET /admin/students/{id}/payments/{payment}/receipt
     */
    public function downloadReceipt(Request $request, $studentId, $paymentId)
    {
        [$student, $payment] = $this->resolveStudentAndPayment($studentId, $paymentId);

        try {
            $receiptService = app(\App\Services\PaymentReceiptService::class);
            $pdfBinary = $receiptService->pdf($payment);
            $receiptNo = $payment->receipt_no ?: "payment-{$payment->id}";

            return response($pdfBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="receipt-' . $receiptNo . '.pdf"',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Receipt download failed for payment #{$payment->id}: " . $e->getMessage());
            return redirect()->back()->with('error', 'Receipt PDF engine problem: ' . $e->getMessage());
        }
    }

    /**
     * Resend receipt email & WhatsApp message synchronously and report exact outcome.
     * POST /admin/students/{id}/payments/{payment}/resend-receipt
     */
    public function resendReceipt(Request $request, $studentId, $paymentId)
    {
        [$student, $payment] = $this->resolveStudentAndPayment($studentId, $paymentId);

        if ($payment->status !== 'paid') {
            return redirect()->back()->with('error', 'Cannot send receipt for an unpaid payment.');
        }

        $receiptService = app(\App\Services\PaymentReceiptService::class);
        $result = $receiptService->deliverDirect($payment, force: true);

        if ($result['delivered_any']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()->with('error', $result['message']);
    }

    /**
     * Hard delete a payment row.
     * DELETE /admin/students/{id}/payments/{payment}
     */
    public function destroy(Request $request, $studentId, $paymentId)
    {
        [$student, $payment] = $this->resolveStudentAndPayment($studentId, $paymentId);

        return DB::transaction(function () use ($student, $payment) {
            $snapshot = $payment->toArray();

            if ($payment->kind === 'topup') {
                $topup = \App\Models\SubjectTopup::where('payment_id', $payment->id)->first()
                    ?? (!empty($payment->meta['topup_id']) ? \App\Models\SubjectTopup::find($payment->meta['topup_id']) : null);
                if ($topup) {
                    $topup->update([
                        'status' => 'pending',
                        'payment_id' => null,
                        'payment_method' => null,
                        'paid_at' => null,
                    ]);
                }
            }

            ActivityLogService::log(
                'payment_deleted',
                "Deleted payment ID {$payment->id} ({$payment->year_month}) for student {$student->id}",
                [
                    'student_id' => $student->id,
                    'payment' => $snapshot,
                    'admin_id' => auth()->id(),
                ]
            );

            $payment->delete();

            return redirect()->back()->with('success', 'Payment deleted.');
        });
    }
}

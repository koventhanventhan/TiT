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

        return DB::transaction(function () use ($request, $student, $payment, $yearMonth, $status, $amount, $paymentMethod, $paidAt) {
            // Lock relevant rows for update
            $existingRows = Payment::where('user_id', $student->id)
                ->where('year_month', $yearMonth)
                ->lockForUpdate()
                ->get();

            // Never allow two PAID rows for the same student+month
            if ($status === 'paid') {
                $otherPaid = $existingRows->where('status', 'paid')->where('id', '!=', $payment->id);
                if ($otherPaid->isNotEmpty()) {
                    return redirect()->back()
                        ->withInput()
                        ->withErrors(['year_month' => 'A paid payment already exists for that month.'])
                        ->with('error', 'A paid payment already exists for that month.');
                }
            }

            // Changing the month of a row to a month that already has a PENDING row cancels that older pending row
            $otherPending = $existingRows->where('status', 'pending')->where('id', '!=', $payment->id);
            if ($otherPending->isNotEmpty()) {
                Payment::whereIn('id', $otherPending->pluck('id'))->update(['status' => 'cancelled']);
            }

            $oldSnapshot = $payment->toArray();

            $payment->update([
                'year_month' => $yearMonth,
                'amount' => $amount,
                'status' => $status,
                'payment_method' => $paymentMethod,
                'paid_at' => $paidAt,
            ]);

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
                return redirect()->back()->with('success', 'Payment updated for ' . $yearMonth . ' and student account reactivated.');
            }

            return redirect()->back()->with('success', 'Payment updated.');
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
        ]);

        $paidAt = $request->filled('paid_date') ? $request->paid_date : now()->format('Y-m-d');

        return DB::transaction(function () use ($student, $payment, $paidAt) {
            $existingRows = Payment::where('user_id', $student->id)
                ->where('year_month', $payment->year_month)
                ->lockForUpdate()
                ->get();

            // Cannot mark paid if a paid row already exists for that month
            $otherPaid = $existingRows->where('status', 'paid')->where('id', '!=', $payment->id);
            if ($otherPaid->isNotEmpty()) {
                return redirect()->back()->with('error', 'Payment for ' . $payment->year_month . ' is already marked as paid!');
            }

            // Cancel any other pending rows for that month
            $otherPending = $existingRows->where('status', 'pending')->where('id', '!=', $payment->id);
            if ($otherPending->isNotEmpty()) {
                Payment::whereIn('id', $otherPending->pluck('id'))->update(['status' => 'cancelled']);
            }

            $oldSnapshot = $payment->toArray();

            $payment->update([
                'status' => 'paid',
                'paid_at' => $paidAt,
                'payment_method' => $payment->payment_method ?: 'offline',
            ]);

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
                return redirect()->back()->with('success', 'Payment marked for ' . $payment->year_month . ' and student account reactivated.');
            }

            return redirect()->back()->with('success', 'Payment marked for ' . $payment->year_month . '.');
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
     * Hard delete a payment row.
     * DELETE /admin/students/{id}/payments/{payment}
     */
    public function destroy(Request $request, $studentId, $paymentId)
    {
        [$student, $payment] = $this->resolveStudentAndPayment($studentId, $paymentId);

        return DB::transaction(function () use ($student, $payment) {
            $snapshot = $payment->toArray();

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

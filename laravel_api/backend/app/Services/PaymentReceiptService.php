<?php

namespace App\Services;

use App\Mail\PaymentReceiptMail;
use App\Models\Payment;
use App\Models\PaymentReceiptCounter;
use App\Models\Timetable;
use App\Models\User;
use App\Traits\ValidatesEmail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class PaymentReceiptService
{
    use ValidatesEmail;

    /**
     * Generate or return existing sequential receipt number for a payment.
     * Format: RC-YYYY-XXXXXX (e.g. RC-2026-000001), safe with lockForUpdate.
     *
     * @param Payment $payment
     * @return string
     */
    public function generateReceiptNumber(Payment $payment): string
    {
        if (!empty($payment->receipt_no)) {
            return $payment->receipt_no;
        }

        return DB::transaction(function () use ($payment) {
            // Re-check inside transaction lock
            $freshPayment = Payment::where('id', $payment->id)->lockForUpdate()->first();
            if ($freshPayment && !empty($freshPayment->receipt_no)) {
                $payment->receipt_no = $freshPayment->receipt_no;
                return $freshPayment->receipt_no;
            }

            $instituteId = (int) ($payment->institute_id ?: 1);
            $year = (int) ($payment->paid_at ? $payment->paid_at->format('Y') : date('Y'));

            $counter = PaymentReceiptCounter::where('institute_id', $instituteId)
                ->where('receipt_year', $year)
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                $counter = PaymentReceiptCounter::create([
                    'institute_id' => $instituteId,
                    'receipt_year' => $year,
                    'last_seq' => 1,
                ]);
                $nextSeq = 1;
            } else {
                $nextSeq = $counter->last_seq + 1;
                $counter->update(['last_seq' => $nextSeq]);
            }

            $seqPadded = sprintf('%06d', $nextSeq);
            $receiptNo = "RC-{$year}-{$seqPadded}";

            $payment->receipt_no = $receiptNo;
            $payment->save();

            return $receiptNo;
        });
    }

    /**
     * Ensure a payment has an assigned receipt number.
     *
     * @param Payment $payment
     * @return Payment
     */
    public function ensureReceipt(Payment $payment): Payment
    {
        if (empty($payment->receipt_no)) {
            $this->generateReceiptNumber($payment);
        }
        return $payment;
    }

    /**
     * Build all view parameters for the PDF template.
     *
     * @param Payment $payment
     * @return array
     */
    public function getReceiptData(Payment $payment): array
    {
        $this->ensureReceipt($payment);
        $student = $payment->user;

        // Student Name
        $studentName = $student?->full_name ?: ($student?->name ?: 'Student');

        // Student Index Number
        $indexNumber = $student?->index_number ?: 'Pending';

        // Grade formatting
        $grade = $student?->current_grade ?: 'N/A';
        if (preg_match('/(\d+)/', $grade, $matches)) {
            $grade = sprintf('%02d', (int) $matches[1]);
        }

        // Classes / Teachers breakdown
        $classes = $this->resolveStudentClassesWithTeachers($student, (int) ($payment->institute_id ?: 1));

        // Payment Month
        $paymentMonth = $payment->year_month;
        if (!empty($payment->year_month)) {
            try {
                $paymentMonth = Carbon::createFromFormat('Y-m', $payment->year_month)->format('F Y');
            } catch (\Throwable $e) {
                $paymentMonth = $payment->year_month;
            }
        } else {
            $paymentMonth = ($payment->paid_at ?? now())->format('F Y');
        }

        // Payment Date
        $paymentDate = ($payment->paid_at ?? now())->format('d-m-Y');

        // Payment Method
        $paymentMethod = $this->formatPaymentMethod($payment->payment_method);

        // Amount
        $amountFormatted = 'LKR ' . number_format((float) $payment->amount, 2);

        // Transaction ID / Gateway Ref
        $transactionId = $payment->transaction_id ?: $payment->gateway_ref;

        // Base64 Logo (No network request needed for Dompdf)
        $logoBase64 = $this->getLogoBase64();

        return [
            'receiptNo' => $payment->receipt_no,
            'student' => $student,
            'studentName' => $studentName,
            'indexNumber' => $indexNumber,
            'grade' => $grade,
            'classes' => $classes,
            'paymentMonth' => $paymentMonth,
            'paymentDate' => $paymentDate,
            'paymentMethod' => $paymentMethod,
            'amountFormatted' => $amountFormatted,
            'transactionId' => $transactionId,
            'logoBase64' => $logoBase64,
        ];
    }

    /**
     * Resolve student's subjects mapped to their timetable teachers.
     *
     * @param User|null $student
     * @param int $instituteId
     * @return array
     */
    protected function resolveStudentClassesWithTeachers(?User $student, int $instituteId): array
    {
        if (!$student) {
            return [];
        }

        $selectedSubjects = [];
        $raw = $student->selected_subjects;
        if (is_array($raw)) {
            $selectedSubjects = $raw;
        } elseif (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $selectedSubjects = $decoded;
            } else {
                $selectedSubjects = array_map('trim', explode(',', $raw));
            }
        }
        $selectedSubjects = array_values(array_filter($selectedSubjects));

        if (empty($selectedSubjects)) {
            return [];
        }

        // Query timetable for teacher assignments
        $timetables = Timetable::where('institute_id', $instituteId)
            ->where(function ($q) use ($student) {
                if ($student->current_grade) {
                    $q->where('grade', $student->current_grade)
                      ->orWhere('grade', 'Grade ' . $student->current_grade);
                }
            })
            ->with(['subject', 'teacher'])
            ->get();

        $teacherMap = [];
        foreach ($timetables as $tt) {
            if ($tt->subject && $tt->teacher) {
                $key = strtolower(trim($tt->subject->name));
                $teacherMap[$key] = $tt->teacher->full_name ?: $tt->teacher->name;
            }
        }

        $result = [];
        $count = 0;
        foreach ($selectedSubjects as $subj) {
            $count++;
            if ($count <= 6) {
                $lower = strtolower(trim($subj));
                if (isset($teacherMap[$lower])) {
                    $result[] = "{$subj} – {$teacherMap[$lower]}";
                } else {
                    $result[] = "{$subj}";
                }
            }
        }

        if ($count > 6) {
            $extra = $count - 6;
            $result[] = "+ {$extra} more";
        }

        return $result;
    }

    /**
     * Format payment method string.
     *
     * @param string|null $method
     * @return string
     */
    protected function formatPaymentMethod(?string $method): string
    {
        return match (strtolower(trim((string) $method))) {
            'online' => 'Online Payment (PayHere)',
            'offline' => 'Bank Transfer / Cash',
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash Payment',
            'card' => 'Credit/Debit Card',
            default => !empty($method) ? ucfirst($method) : 'Paid Offline',
        };
    }

    /**
     * Retrieve base64 embedded logo.
     *
     * @return string
     */
    public function getLogoBase64(): string
    {
        $possiblePaths = [
            public_path('admin-theme/images/logo.png'),
            public_path('admin-theme/images/logo-full.png'),
            base_path('logo.png'),
            public_path('logo.png'),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $data = file_get_contents($path);
                $mime = mime_content_type($path) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        }

        return '';
    }

    /**
     * Generate PDF binary string for a payment.
     *
     * @param Payment $payment
     * @return string
     */
    public function pdf(Payment $payment): string
    {
        $data = $this->getReceiptData($payment);
        $pdf = Pdf::loadView('pdf.receipt', $data)->setPaper('a4', 'portrait');
        return $pdf->output();
    }

    /**
     * Generate a 30-day temporary signed URL for public download.
     *
     * @param Payment $payment
     * @return string
     */
    public function signedUrl(Payment $payment): string
    {
        return URL::temporarySignedRoute(
            'receipts.download',
            now()->addDays(30),
            ['payment' => $payment->id]
        );
    }

    /**
     * Dispatch receipt delivery (Email + WhatsApp) after response.
     * Idempotent: does not resend if receipt_sent_at is already set unless $force is true.
     *
     * @param Payment $payment
     * @param bool $force
     * @return bool
     */
    public function sendReceiptNotifications(Payment $payment, bool $force = false): bool
    {
        if (!$force && !empty($payment->receipt_sent_at)) {
            Log::info("PaymentReceiptService: Receipt already sent for Payment #{$payment->id} at {$payment->receipt_sent_at}. Skipping.");
            return true;
        }

        $this->ensureReceipt($payment);
        $student = $payment->user;
        if (!$student) {
            return false;
        }

        // Handle siblings: if student has placeholder email or no phone, fallback to parent
        $targetUser = $student;
        if ($student->parent_id && $student->parent) {
            $isPlaceholderEmail = str_contains($student->email ?? '', '@child.local');
            if (empty($student->phone_number) || $isPlaceholderEmail) {
                $targetUser = $student->parent;
            }
        }

        // Asynchronously dispatch delivery so response to admin / student is instantaneous
        dispatch(function () use ($payment, $student, $targetUser) {
            try {
                $service = app(PaymentReceiptService::class);
                $service->deliverNotifications($payment, $student, $targetUser);
            } catch (\Throwable $e) {
                Log::error("PaymentReceiptService: Error delivering receipt for Payment #{$payment->id}: " . $e->getMessage());
            }
        })->afterResponse();

        return true;
    }

    /**
     * Actual delivery execution of Email with PDF and WhatsApp notification.
     *
     * @param Payment $payment
     * @param User $student
     * @param User $targetUser
     * @return void
     */
    public function deliverNotifications(Payment $payment, User $student, User $targetUser): void
    {
        $pdfContent = $this->pdf($payment);
        $signedUrl = $this->signedUrl($payment);
        $receiptNo = $payment->receipt_no;

        $monthName = $payment->year_month;
        if (!empty($payment->year_month)) {
            try {
                $monthName = Carbon::createFromFormat('Y-m', $payment->year_month)->format('F Y');
            } catch (\Throwable $e) {
                $monthName = $payment->year_month;
            }
        } else {
            $monthName = ($payment->paid_at ?? now())->format('F Y');
        }

        $studentName = $student->full_name ?: ($student->name ?: 'Student');
        $deliveredAny = false;

        // 1. Send Email with PDF attachment
        $targetEmail = $targetUser->email;
        if ($targetEmail && !str_contains($targetEmail, '@child.local') && $this->isValidEmailForSending($targetEmail)) {
            try {
                Mail::to($targetEmail)->send(new PaymentReceiptMail(
                    $student,
                    $payment,
                    $pdfContent,
                    $receiptNo,
                    $monthName,
                    $signedUrl
                ));
                $deliveredAny = true;
                Log::info("PaymentReceiptService: Sent receipt email with PDF to {$targetEmail} for Payment #{$payment->id}");
            } catch (\Throwable $e) {
                Log::error("PaymentReceiptService: Failed to send receipt email to {$targetEmail}: " . $e->getMessage());
            }
        }

        // 2. Send WhatsApp message with secure signed link
        $targetPhone = $targetUser->phone_number;
        if ($targetPhone) {
            try {
                $whatsApp = app(WhatsAppService::class);
                // Template: tit_payment_receipt with variables [student_name, month_name, signed_receipt_url]
                $sent = $whatsApp->sendTemplate(
                    $targetPhone,
                    'tit_payment_receipt',
                    'en',
                    [$studentName, $monthName, $signedUrl]
                );
                if ($sent) {
                    $deliveredAny = true;
                    Log::info("PaymentReceiptService: Sent receipt WhatsApp notification to {$targetPhone} for Payment #{$payment->id}");
                }
            } catch (\Throwable $e) {
                Log::error("PaymentReceiptService: Failed to send receipt WhatsApp to {$targetPhone}: " . $e->getMessage());
            }
        }

        if ($deliveredAny) {
            $payment->update(['receipt_sent_at' => now()]);
        }
    }
}

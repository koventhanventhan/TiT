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
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

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
        // Transaction ID / Gateway Ref
        $transactionId = $payment->transaction_id ?: $payment->gateway_ref;

        // Base64 Logo (overridable via public/images/receipt-logo.png or institute setting receipt_logo)
        $logoBase64 = $this->getLogoBase64($payment);

        // Institute Contact Line
        $contactLine = $this->resolveInstituteContactLine($payment);

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
            'contactLine' => $contactLine,
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

        if (empty($selectedSubjects) && $timetables->isNotEmpty()) {
            foreach ($timetables as $tt) {
                if ($tt->subject) {
                    $subjName = $tt->subject->name;
                    $teacherName = $tt->teacher ? ($tt->teacher->full_name ?: $tt->teacher->name) : null;
                    $line = $teacherName ? "{$subjName} – {$teacherName}" : $subjName;
                    if (!in_array($line, $result) && count($result) < 6) {
                        $result[] = $line;
                    }
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
     * offline -> "Bank Transfer / Cash"
     * bank / bank_transfer -> "Bank Transfer"
     * cash -> "Cash"
     * online / payhere -> "Online Payment (PayHere)"
     * unknown / empty -> "—"
     *
     * @param string|null $method
     * @return string
     */
    public function formatPaymentMethod(?string $method): string
    {
        return match (strtolower(trim((string) $method))) {
            'bank', 'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            'online', 'payhere' => 'Online Payment (PayHere)',
            'offline' => 'Bank Transfer / Cash',
            default => '—',
        };
    }

    /**
     * Retrieve base64 embedded logo if overridden.
     * Uses public/images/receipt-logo.png or institute setting receipt_logo.
     * Otherwise returns null to let template render text wordmark.
     *
     * @param Payment|null $payment
     * @return string|null
     */
    public function getLogoBase64(?Payment $payment = null): ?string
    {
        $customPaths = [
            public_path('images/receipt-logo.png'),
        ];

        if ($payment && $payment->institute) {
            $settingLogo = $payment->institute->theme_settings['receipt_logo'] ?? null;
            if (!empty($settingLogo)) {
                $customPaths[] = public_path($settingLogo);
                $customPaths[] = storage_path('app/public/' . $settingLogo);
            }
        }

        foreach ($customPaths as $path) {
            if (!empty($path) && file_exists($path)) {
                $data = file_get_contents($path);
                $mime = mime_content_type($path) ?: 'image/png';
                return 'data:' . $mime . ';base64,' . base64_encode($data);
            }
        }

        return null;
    }

    /**
     * Resolve institute contact line for footer.
     *
     * @param Payment $payment
     * @return string
     */
    protected function resolveInstituteContactLine(Payment $payment): string
    {
        $institute = $payment->institute;
        $parts = [];

        if ($institute && !empty($institute->theme_settings['phone'])) {
            $parts[] = $institute->theme_settings['phone'];
        }
        if ($institute && !empty($institute->theme_settings['email'])) {
            $parts[] = $institute->theme_settings['email'];
        }

        if (empty($parts)) {
            $mail = config('mail.from.address');
            if ($mail) {
                $parts[] = $mail;
            }
        }

        return !empty($parts) ? implode(' • ', $parts) : 'TiT Institute • Jaffna';
    }

    /**
     * Generate PDF binary string for a payment using mPDF with Indic OpenType shaping.
     *
     * @param Payment $payment
     * @return string
     */
    public function pdf(Payment $payment): string
    {
        $data = $this->getReceiptData($payment);
        $html = view('pdf.receipt', $data)->render();

        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];
        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A5',
            'orientation' => 'P',
            'tempDir' => $tempDir,
            'fontDir' => array_merge($fontDirs, [resource_path('fonts')]),
            'fontdata' => $fontData + [
                'notosanstamil' => [
                    'R' => 'NotoSansTamil-Regular.ttf',
                    'B' => 'NotoSansTamil-Bold.ttf',
                    'useOTL' => 0xFF,
                    'useKashida' => 75,
                ],
                'montserrat' => [
                    'B' => 'Montserrat-ExtraBold.ttf',
                ],
            ],
            'default_font' => 'notosanstamil',
            'autoScriptToLang' => false,
            'autoLangToFont' => false,
            'useSubstitutions' => true,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->WriteHTML($html);
        return $mpdf->Output('', 'S');
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
    /**
     * Cheap synchronous preflight check before delivery or response.
     * Returns null if everything looks healthy, or a specific error message.
     *
     * @param Payment $payment
     * @return string|null
     */
    public function preflightCheck(Payment $payment): ?string
    {
        if (!class_exists(\Mpdf\Mpdf::class)) {
            return 'mPDF library is missing (run composer install --no-dev on server)';
        }

        if (!extension_loaded('mbstring')) {
            return 'PHP mbstring extension is not loaded';
        }

        if (!extension_loaded('gd')) {
            return 'PHP gd extension is not loaded';
        }

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0777, true);
        }
        if (!is_dir($tempDir) || !is_writable($tempDir)) {
            return 'storage/app/mpdf directory is missing or not writable';
        }

        $requiredFonts = [
            'NotoSansTamil-Regular.ttf',
            'NotoSansTamil-Bold.ttf',
            'Montserrat-ExtraBold.ttf',
        ];
        foreach ($requiredFonts as $fontFile) {
            if (!file_exists(resource_path('fonts/' . $fontFile))) {
                return "Required font file resources/fonts/{$fontFile} is missing";
            }
        }

        $student = $payment->user;
        if (!$student) {
            return 'Payment has no associated student';
        }

        $targetUser = $this->resolveTargetUser($student);
        $hasEmail = $targetUser->email && !str_contains($targetUser->email, '@child.local') && $this->isValidEmailForSending($targetUser->email);
        $hasPhone = !empty($targetUser->phone_number);

        if (!$hasEmail && !$hasPhone) {
            return 'Student/parent has no deliverable email or phone number';
        }

        return null;
    }

    /**
     * Resolve target user for notifications (handling sibling parent fallback).
     *
     * @param User $student
     * @return User
     */
    public function resolveTargetUser(User $student): User
    {
        $targetUser = $student;
        if ($student->parent_id && $student->parent) {
            $isPlaceholderEmail = str_contains($student->email ?? '', '@child.local');
            if (empty($student->phone_number) || $isPlaceholderEmail) {
                $targetUser = $student->parent;
            }
        }
        return $targetUser;
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

        $targetUser = $this->resolveTargetUser($student);

        // Preflight check synchronously: record failure if any
        $preflight = $this->preflightCheck($payment);
        if ($preflight !== null) {
            $payment->update([
                'receipt_attempted_at' => now(),
                'receipt_last_error' => $preflight,
            ]);
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
     * Synchronous direct receipt delivery (used by resend and internal delivery).
     * Never silently succeeds; returns detailed delivery report.
     *
     * @param Payment $payment
     * @param bool $force
     * @return array
     */
    public function deliverDirect(Payment $payment, bool $force = false): array
    {
        if (!$force && !empty($payment->receipt_sent_at)) {
            return [
                'success' => true,
                'delivered_any' => true,
                'message' => "Receipt #{$payment->receipt_no} was already sent on " . $payment->receipt_sent_at->format('Y-m-d H:i') . '.',
            ];
        }

        $this->ensureReceipt($payment);
        $student = $payment->user;
        if (!$student) {
            $err = 'Payment has no associated student';
            $payment->update([
                'receipt_attempted_at' => now(),
                'receipt_last_error' => $err,
            ]);
            return [
                'success' => false,
                'delivered_any' => false,
                'message' => $err,
            ];
        }

        $targetUser = $this->resolveTargetUser($student);

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
        $signedUrl = $this->signedUrl($payment);
        $receiptNo = $payment->receipt_no;

        $payment->update(['receipt_attempted_at' => now()]);

        // 1. Build PDF safely
        $pdfContent = null;
        $pdfError = null;
        try {
            $pdfContent = $this->pdf($payment);
        } catch (\Throwable $e) {
            $pdfError = get_class($e) . ': ' . $e->getMessage();
            Log::error('Receipt PDF failed', [
                'payment_id' => $payment->id,
                'exception' => $e->getMessage(),
                'class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            $payment->update([
                'receipt_last_error' => 'Receipt PDF failed: ' . $e->getMessage(),
            ]);
        }

        $deliveredAny = false;
        $emailSent = false;
        $emailError = null;
        $whatsappSent = false;
        $whatsappError = null;

        // 2. Send Email (with or without PDF)
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
                $emailSent = true;
                Log::info("PaymentReceiptService: Sent receipt email to {$targetEmail} for Payment #{$payment->id}" . ($pdfContent ? ' with PDF' : ' without PDF attachment'));
            } catch (\Throwable $e) {
                $emailError = $e->getMessage();
                Log::error("PaymentReceiptService: Failed to send receipt email to {$targetEmail}: " . $e->getMessage());
            }
        } else {
            $emailError = $targetEmail ? 'Email format invalid or placeholder' : 'No email address registered';
        }

        // 3. Send WhatsApp
        $targetPhone = $targetUser->phone_number;
        if ($targetPhone) {
            try {
                $whatsApp = app(WhatsAppService::class);
                $sent = $whatsApp->sendTemplate(
                    $targetPhone,
                    'tit_payment_receipt',
                    'en',
                    [$studentName, $monthName, $signedUrl]
                );
                if ($sent) {
                    $deliveredAny = true;
                    $whatsappSent = true;
                    Log::info("PaymentReceiptService: Sent receipt WhatsApp notification to {$targetPhone} for Payment #{$payment->id}");
                } else {
                    $whatsappError = 'WhatsApp template not approved or delivery failed';
                }
            } catch (\Throwable $e) {
                $whatsappError = $e->getMessage();
                Log::error("PaymentReceiptService: Failed to send receipt WhatsApp to {$targetPhone}: " . $e->getMessage());
            }
        } else {
            $whatsappError = 'No phone number registered';
        }

        // Update payment record based on delivery outcome
        if ($deliveredAny) {
            $payment->update([
                'receipt_sent_at' => now(),
                'receipt_last_error' => $pdfError ? ('Receipt PDF failed: ' . $pdfError) : null,
            ]);
        } else {
            $combinedError = implode(' | ', array_filter([
                $pdfError ? 'PDF engine problem: ' . $pdfError : null,
                'Email: ' . ($emailError ?: 'failed'),
                'WhatsApp: ' . ($whatsappError ?: 'failed'),
            ]));
            $payment->update([
                'receipt_last_error' => $combinedError,
            ]);
        }

        // Build clear, specific outcome message
        if ($emailSent && $whatsappSent) {
            $msg = "Receipt #{$receiptNo} sent via email to {$targetEmail} and WhatsApp to {$targetPhone}.";
        } elseif ($emailSent) {
            $waSkipReason = $targetPhone ? ($whatsappError ?? 'template not approved') : 'no phone number registered';
            $msg = "Receipt #{$receiptNo} sent via email to {$targetEmail}. WhatsApp skipped ({$waSkipReason}).";
        } elseif ($whatsappSent) {
            $emailSkipReason = $targetEmail ? ($emailError ?? 'delivery error') : 'no email address registered';
            $msg = "Receipt #{$receiptNo} sent via WhatsApp to {$targetPhone}. Email skipped ({$emailSkipReason}).";
        } else {
            $failReason = $emailError ?: ($whatsappError ?: ($pdfError ?: 'all delivery channels failed'));
            $msg = "Receipt delivery failed: {$failReason}.";
        }

        if ($pdfError && $deliveredAny) {
            $msg .= " (Note: PDF generation failed; receipt link included instead: {$pdfError})";
        }

        return [
            'success' => $deliveredAny,
            'delivered_any' => $deliveredAny,
            'email_sent' => $emailSent,
            'email_to' => $targetEmail,
            'email_error' => $emailError,
            'whatsapp_sent' => $whatsappSent,
            'whatsapp_to' => $targetPhone,
            'whatsapp_error' => $whatsappError,
            'pdf_error' => $pdfError,
            'message' => $msg,
        ];
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
        $this->deliverDirect($payment, true);
    }
}

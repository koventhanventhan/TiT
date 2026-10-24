<?php

namespace Tests\Feature;

use App\Mail\PaymentReceiptMail;
use App\Models\Institute;
use App\Models\Payment;
use App\Models\PaymentReceiptCounter;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\User;
use App\Services\PaymentReceiptService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

class PaymentReceiptTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private PaymentReceiptService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit',
            'status' => 'active',
            'index_prefix' => 'TiT',
            'index_seq_start' => 100,
        ]);

        $this->service = app(PaymentReceiptService::class);
    }

    public function test_sequential_receipt_numbering_starts_at_1_and_increments(): void
    {
        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);

        $payment1 = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1500,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => now()->format('Y-m'),
        ]);

        $payment2 = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 2000,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => now()->addMonth()->format('Y-m'),
        ]);

        $year = date('Y');
        $no1 = $this->service->generateReceiptNumber($payment1);
        $no2 = $this->service->generateReceiptNumber($payment2);

        $this->assertEquals("RC-{$year}-000001", $no1);
        $this->assertEquals("RC-{$year}-000002", $no2);

        // Calling generateReceiptNumber again returns cached number without incrementing counter
        $no1Repeat = $this->service->generateReceiptNumber($payment1);
        $this->assertEquals("RC-{$year}-000001", $no1Repeat);

        $counter = PaymentReceiptCounter::where('institute_id', $this->institute->id)
            ->where('receipt_year', (int) $year)
            ->first();
        $this->assertEquals(2, $counter->last_seq);
    }

    public function test_signed_download_url_returns_200_with_pdf_header(): void
    {
        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'John Doe',
            'current_grade' => '10',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
        ]);

        $this->service->ensureReceipt($payment);
        $signedUrl = $this->service->signedUrl($payment);

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_invalid_or_expired_signed_url_returns_403(): void
    {
        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
        ]);

        // Access route directly without signature
        $response = $this->get("/receipts/{$payment->id}/download");
        $response->assertStatus(403);

        // Access route with tampered signature
        $responseTampered = $this->get("/receipts/{$payment->id}/download?signature=invalidhash&expires=9999999999");
        $responseTampered->assertStatus(403);
    }

    public function test_student_authenticated_download_allows_own_receipt_and_blocks_other_students(): void
    {
        $studentA = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Student Alice',
        ]);

        $studentB = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Student Bob',
        ]);

        $paymentA = Payment::create([
            'user_id' => $studentA->id,
            'institute_id' => $this->institute->id,
            'amount' => 1500,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
        ]);

        $paymentB = Payment::create([
            'user_id' => $studentB->id,
            'institute_id' => $this->institute->id,
            'amount' => 1500,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
        ]);

        // Alice accessing her own receipt -> 200
        $responseA = $this->actingAs($studentA)->get("/api/student/payments/{$paymentA->id}/receipt");
        $responseA->assertStatus(200);
        $responseA->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $responseA->getContent());

        // Alice accessing Bob's receipt -> 403
        $responseUnauthorized = $this->actingAs($studentA)->get("/api/student/payments/{$paymentB->id}/receipt");
        $responseUnauthorized->assertStatus(403);
    }

    public function test_parent_can_download_child_sibling_receipt(): void
    {
        $parent = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Parent User',
        ]);

        $child = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'parent_id' => $parent->id,
            'full_name' => 'Child Sibling',
        ]);

        $childPayment = Payment::create([
            'user_id' => $child->id,
            'institute_id' => $this->institute->id,
            'amount' => 1800,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
        ]);

        // Parent accessing child's receipt -> 200
        $response = $this->actingAs($parent)->get("/api/student/payments/{$childPayment->id}/receipt");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_admin_mark_paid_triggers_receipt_generation_and_notifications(): void
    {
        Mail::fake();

        $whatsAppMock = Mockery::mock(WhatsAppService::class);
        $whatsAppMock->shouldReceive('sendTemplate')
            ->once()
            ->with(
                '0771234567',
                'tit_payment_receipt',
                'en',
                Mockery::on(function ($args) {
                    return is_array($args) && count($args) === 3 && $args[0] === 'Kasun Silva';
                })
            )
            ->andReturn(true);
        $this->app->instance(WhatsAppService::class, $whatsAppMock);

        $admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Kasun Silva',
            'email' => 'kasun@example.com',
            'phone_number' => '0771234567',
            'current_grade' => '11',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200,
            'status' => 'pending',
            'year_month' => '2026-10',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.students.payments.mark-paid', ['id' => $student->id, 'payment' => $payment->id])
        );

        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertNotNull($payment->receipt_no);
        $this->assertStringStartsWith('RC-', $payment->receipt_no);

        Mail::assertSent(PaymentReceiptMail::class, function ($mail) use ($student) {
            return $mail->hasTo($student->email);
        });

        $this->assertNotNull($payment->receipt_sent_at);
    }

    public function test_receipt_notifications_are_idempotent_unless_forced(): void
    {
        Mail::fake();

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'email' => 'idempotent@example.com',
            'phone_number' => '0770001122',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200,
            'status' => 'paid',
            'receipt_no' => 'RC-2026-000099',
            'receipt_sent_at' => now(),
            'year_month' => '2026-10',
        ]);

        // Calling sendReceiptNotifications without force -> skipped
        $dispatched = $this->service->sendReceiptNotifications($payment, false);
        $this->assertTrue($dispatched);

        Mail::assertNothingSent();
    }

    public function test_sibling_with_placeholder_email_routes_receipt_to_parent(): void
    {
        Mail::fake();

        $whatsAppMock = Mockery::mock(WhatsAppService::class);
        $whatsAppMock->shouldReceive('sendTemplate')
            ->once()
            ->with('0778888888', 'tit_payment_receipt', 'en', Mockery::any())
            ->andReturn(true);
        $this->app->instance(WhatsAppService::class, $whatsAppMock);

        $parent = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'name' => 'parent_user',
            'full_name' => 'Parent Full Name',
            'email' => 'realparent@example.com',
            'phone_number' => '0778888888',
        ]);

        $child = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'parent_id' => $parent->id,
            'full_name' => 'Child Student',
            'email' => 'sibling_123_456@child.local',
            'phone_number' => null,
            'current_grade' => '08',
        ]);

        $payment = Payment::create([
            'user_id' => $child->id,
            'institute_id' => $this->institute->id,
            'amount' => 1500,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
        ]);

        $this->service->ensureReceipt($payment);
        $this->service->deliverNotifications($payment, $child, $parent);

        Mail::assertSent(PaymentReceiptMail::class, function ($mail) use ($parent) {
            return $mail->hasTo($parent->email);
        });

        $payment->refresh();
        $this->assertNotNull($payment->receipt_sent_at);
    }

    public function test_receipt_pdf_renders_with_tamil_characters_and_embedded_font(): void
    {
        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'கவின் கந்தசாமி குணரத்தினம்',
            'index_number' => 'TiT/28/105',
            'current_grade' => '04',
            'selected_subjects' => json_encode(['Mathematics', 'Science', 'English']),
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1200,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
            'payment_method' => 'bank',
        ]);

        $pdfBinary = $this->service->pdf($payment);

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF-', $pdfBinary);
        $this->assertMatchesRegularExpression('/\/FontName\s*\/[^\s\/]*NotoSansTamil/', $pdfBinary);

        // Rasterise with pdftoppm -r 80 when available to catch tofu
        $pdftoppm = $this->resolvePdftoppmPath();
        if (!$pdftoppm) {
            $this->markTestSkipped('pdftoppm is not available in environment');
        }

        $tmpPdf = tempnam(sys_get_temp_dir(), 'rcpt_') . '.pdf';
        $tmpPngPrefix = tempnam(sys_get_temp_dir(), 'rcpt_png_');
        file_put_contents($tmpPdf, $pdfBinary);

        exec("\"$pdftoppm\" -png -r 80 " . escapeshellarg($tmpPdf) . " " . escapeshellarg($tmpPngPrefix), $out, $ret);

        @unlink($tmpPdf);
        $pngFile = $tmpPngPrefix . '-1.png';
        $this->assertEquals(0, $ret, 'pdftoppm exited with error code ' . $ret);
        $this->assertFileExists($pngFile);
        $this->assertGreaterThan(1000, filesize($pngFile));
        @unlink($pngFile);
    }

    public function test_receipt_pdf_renders_mixed_english_tamil_and_sixty_char_name(): void
    {
        $mixedStudent = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'கவின் Kavin கந்தசாமி Kandasamy',
            'index_number' => 'TiT/28/106',
            'current_grade' => '10',
        ]);

        $payment1 = Payment::create([
            'user_id' => $mixedStudent->id,
            'institute_id' => $this->institute->id,
            'amount' => 2000,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
            'payment_method' => 'cash',
        ]);

        $pdfMixed = $this->service->pdf($payment1);
        $this->assertStringStartsWith('%PDF-', $pdfMixed);

        // 60-character name must wrap, not overflow
        $longNameStudent = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'கவின் கந்தசாமி குணரத்தினம் சிவசுப்பிரமணியம் பாலசுப்பிரமணியம்',
            'index_number' => 'TiT/28/107',
            'current_grade' => '10',
        ]);

        $payment2 = Payment::create([
            'user_id' => $longNameStudent->id,
            'institute_id' => $this->institute->id,
            'amount' => 2000,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
            'payment_method' => 'online',
        ]);

        $pdfLong = $this->service->pdf($payment2);
        $this->assertStringStartsWith('%PDF-', $pdfLong);
    }

    public function test_teacher_resolution_from_timetables_and_eight_subjects_cap(): void
    {
        $teacher = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'teacher',
            'name' => 'ghajanan',
            'full_name' => 'G. Hajanan',
        ]);

        $math = Subject::create([
            'name' => 'Mathematics',
            'category' => 'secondary_6_9',
            'price' => 500,
        ]);

        Timetable::create([
            'institute_id' => $this->institute->id,
            'title' => 'Grade 10 Mathematics',
            'grade' => 'Grade 10',
            'day_of_week' => 'Monday',
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'subject_id' => $math->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Student Eight Subjects',
            'index_number' => 'TiT/28/108',
            'current_grade' => '10',
            'selected_subjects' => [
                'Mathematics',
                'Science',
                'English',
                'Tamil Language',
                'History',
                'Commerce',
                'ICT',
                'Art',
            ],
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 5000,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
            'payment_method' => 'bank',
        ]);

        $data = $this->service->getReceiptData($payment);
        $classes = $data['classes'];

        // Mathematics line should have teacher full_name with en dash
        $this->assertContains('Mathematics – G. Hajanan', $classes);

        // Exactly 6 lines plus "+ 2 more"
        $this->assertCount(7, $classes);
        $this->assertEquals('+ 2 more', $classes[6]);
    }

    public function test_payment_method_wording(): void
    {
        $this->assertEquals('Bank Transfer', $this->service->formatPaymentMethod('bank'));
        $this->assertEquals('Bank Transfer', $this->service->formatPaymentMethod('bank_transfer'));
        $this->assertEquals('Cash', $this->service->formatPaymentMethod('cash'));
        $this->assertEquals('Online Payment (PayHere)', $this->service->formatPaymentMethod('online'));
        $this->assertEquals('Online Payment (PayHere)', $this->service->formatPaymentMethod('payhere'));
        $this->assertEquals('Bank Transfer / Cash', $this->service->formatPaymentMethod('offline'));
        $this->assertEquals('—', $this->service->formatPaymentMethod('unknown'));
        $this->assertEquals('—', $this->service->formatPaymentMethod(null));
        $this->assertEquals('—', $this->service->formatPaymentMethod(''));
    }

    public function test_pdf_failure_still_sends_email_with_signed_link_and_records_error(): void
    {
        Mail::fake();

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Failing PDF Student',
            'email' => 'pdf_fail@example.com',
            'phone_number' => null,
            'current_grade' => '09',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1500,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
        ]);

        $serviceMock = Mockery::mock(PaymentReceiptService::class)->makePartial();
        $serviceMock->shouldReceive('pdf')->andThrow(new \RuntimeException('Simulated mPDF failure on live server'));
        $this->app->instance(PaymentReceiptService::class, $serviceMock);

        $serviceMock->ensureReceipt($payment);
        $serviceMock->deliverNotifications($payment, $student, $student);

        $payment->refresh();

        // 1. Mail must still have been sent to student
        Mail::assertSent(PaymentReceiptMail::class, function ($mail) use ($student) {
            return $mail->hasTo($student->email) && empty($mail->rawAttachments);
        });

        // 2. Error must be logged on payment
        $this->assertNotNull($payment->receipt_last_error);
        $this->assertStringContainsString('Simulated mPDF failure on live server', $payment->receipt_last_error);
        $this->assertNotNull($payment->receipt_attempted_at);

        // 3. receipt_sent_at must still be set because email succeeded
        $this->assertNotNull($payment->receipt_sent_at);
    }

    public function test_resend_receipt_runs_synchronously_and_reports_real_outcome(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Resend Test Student',
            'email' => 'resend_student@example.com',
            'phone_number' => null,
            'current_grade' => '10',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1800,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.students.payments.resend-receipt', ['id' => $student->id, 'payment' => $payment->id])
        );

        $response->assertSessionHas('success');
        $flash = session('success');
        $this->assertStringContainsString('Receipt #', $flash);
        $this->assertStringContainsString('email to resend_student@example.com', $flash);

        Mail::assertSent(PaymentReceiptMail::class, function ($mail) use ($student) {
            return $mail->hasTo($student->email);
        });
    }

    public function test_download_receipt_catches_exception_and_redirects_with_error(): void
    {
        $admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Download Exception Student',
            'email' => 'download_error@example.com',
            'current_grade' => '10',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 2000,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
        ]);

        $serviceMock = Mockery::mock(PaymentReceiptService::class)->makePartial();
        $serviceMock->shouldReceive('pdf')->andThrow(new \RuntimeException('Missing font NotoSansTamil'));
        $this->app->instance(PaymentReceiptService::class, $serviceMock);

        $response = $this->actingAs($admin)->get(
            route('admin.students.payments.receipt', ['id' => $student->id, 'payment' => $payment->id])
        );

        $response->assertStatus(302);
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Receipt PDF engine problem: Missing font NotoSansTamil', session('error'));
    }

    public function test_admin_payment_update_to_paid_triggers_receipt(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Update Paid Student',
            'email' => 'update_paid@example.com',
            'phone_number' => null,
            'current_grade' => '07',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 2200,
            'status' => 'pending',
            'year_month' => '2026-10',
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.students.payments.update', ['id' => $student->id, 'payment' => $payment->id]),
            [
                'amount' => 2200,
                'year_month' => '2026-10',
                'status' => 'paid',
                'payment_method' => 'cash',
            ]
        );

        $response->assertSessionHas('success');
        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertNotNull($payment->receipt_no);

        Mail::assertSent(PaymentReceiptMail::class, function ($mail) use ($student) {
            return $mail->hasTo($student->email);
        });
    }

    public function test_admin_confirm_student_marks_payment_paid_and_triggers_receipt(): void
    {
        Mail::fake();

        $admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'Newly Registered Student',
            'email' => 'new_student@example.com',
            'phone_number' => null,
            'current_grade' => '08',
            'registration_status' => 'payment_completed',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 1500,
            'status' => 'pending',
            'year_month' => '2026-10',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.students.confirm', ['id' => $student->id]),
            ['batch_year' => 26]
        );

        $response->assertSessionHas('success');
        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertNotNull($payment->receipt_no);

        Mail::assertSent(PaymentReceiptMail::class, function ($mail) use ($student) {
            return $mail->hasTo($student->email);
        });
    }

    public function test_receipts_diagnose_command_executes_successfully(): void
    {
        $this->artisan('receipts:diagnose')
            ->expectsOutputToContain('Payment Receipt Diagnostic')
            ->expectsOutputToContain('PHP Version')
            ->expectsOutputToContain('mPDF Class Autoload')
            ->assertExitCode(0);
    }

    private function resolvePdftoppmPath(): ?string
    {
        if (file_exists('C:\\poppler\\bin\\pdftoppm.exe')) {
            return 'C:\\poppler\\bin\\pdftoppm.exe';
        }

        exec('where.exe pdftoppm 2>&1', $out, $ret);
        if ($ret === 0 && !empty($out[0]) && file_exists(trim($out[0]))) {
            return trim($out[0]);
        }

        exec('which pdftoppm 2>&1', $out2, $ret2);
        if ($ret2 === 0 && !empty($out2[0])) {
            return trim($out2[0]);
        }

        return null;
    }
}

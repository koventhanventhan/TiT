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

    public function test_receipt_pdf_renders_with_tamil_characters_and_subjects(): void
    {
        $teacher = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'teacher',
            'full_name' => 'Dr. K. Ramanathan',
        ]);

        $subject = Subject::create([
            'name' => 'Tamil Language',
            'category' => 'secondary_6_9',
            'price' => 500,
        ]);

        Timetable::create([
            'institute_id' => $this->institute->id,
            'title' => 'Grade 09 Tamil',
            'grade' => 'Grade 09',
            'day_of_week' => 'Monday',
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'subject_id' => $subject->id,
            'teacher_id' => $teacher->id,
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'full_name' => 'கவின் கந்தசாமி (Kavin Kandasamy)',
            'index_number' => 'TiT/28/105',
            'current_grade' => '9',
            'selected_subjects' => json_encode(['Tamil Language', 'Mathematics', 'Science', 'English', 'History', 'Commerce', 'ICT']),
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => $this->institute->id,
            'amount' => 3500,
            'status' => 'paid',
            'paid_at' => now(),
            'year_month' => '2026-10',
            'payment_method' => 'online',
        ]);

        $pdfBinary = $this->service->pdf($payment);

        $this->assertNotEmpty($pdfBinary);
        $this->assertStringStartsWith('%PDF-', $pdfBinary);
        $this->assertEquals("RC-" . date('Y') . "-000001", $payment->receipt_no);
    }
}

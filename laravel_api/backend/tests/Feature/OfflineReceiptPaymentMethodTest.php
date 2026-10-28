<?php

namespace Tests\Feature;

use App\Mail\PaymentReceiptMail;
use App\Models\AdvancePaymentRequest;
use App\Models\Institute;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use App\Services\PaymentReceiptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

class OfflineReceiptPaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $admin;
    private User $student;
    private PaymentReceiptService $receiptService;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit-institute',
            'domain' => 'localhost',
            'status' => 'active',
            'index_prefix' => 'TiT',
            'index_seq_start' => 100,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'full_name' => 'Admin User',
            'email' => 'admin@tit.com',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Test Student',
            'full_name' => 'Test Student Full',
            'email' => 'student@tit.com',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics']),
        ]);

        $this->receiptService = app(PaymentReceiptService::class);
    }

    private function extractPdfText(string $pdfBinary): string
    {
        $parser = new Parser();
        $pdf = $parser->parseContent($pdfBinary);

        return $pdf->getText();
    }

    public function test_pending_online_row_marked_paid_by_admin_becomes_offline_without_ph_ref(): void
    {
        Mail::fake();

        $payment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_6AE1C706E35BF',
            'transaction_id' => null,
        ]);

        $response = $this->actingAs($this->admin, 'web')->post(
            route('admin.students.payments.mark-paid', ['id' => $this->student->id, 'payment' => $payment->id]),
            [
                'paid_date' => '2026-10-20',
                'received_by' => 'cash',
            ]
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('offline', $payment->payment_method);
        $this->assertFalse(str_starts_with($payment->gateway_ref, 'PH_'));
        $this->assertStringStartsWith('ADMIN_' . $payment->id . '_', $payment->gateway_ref);
        $this->assertEquals('online', $payment->meta['original_method'] ?? null);
        $this->assertEquals('PH_6AE1C706E35BF', $payment->meta['original_gateway_ref'] ?? null);
        $this->assertEquals('cash', $payment->meta['received_by'] ?? null);

        // PDF receipt inspection
        $pdfBinary = $this->receiptService->pdf($payment);
        $pdfText = $this->extractPdfText($pdfBinary);

        $this->assertStringNotContainsString('PayHere', $pdfText);
        $this->assertStringNotContainsString('PH_6AE1C706E35BF', $pdfText);
        $this->assertStringContainsString('Cash', $pdfText);
        $this->assertStringContainsString('Received by admin on', $pdfText);
    }

    public function test_student_page_mark_month_paid_replaces_pending_online_and_sets_offline(): void
    {
        Mail::fake();

        $payment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_UNCONFIRMED_123',
            'transaction_id' => null,
        ]);

        $response = $this->actingAs($this->admin, 'web')->post(
            route('admin.students.mark-paid', $this->student->id),
            [
                'year_month' => '2026-10',
                'amount' => 3500,
                'received_by' => 'bank_transfer',
            ]
        );

        $response->assertRedirect();

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('offline', $payment->payment_method);
        $this->assertStringStartsWith('ADMIN_' . $payment->id . '_', $payment->gateway_ref);
        $this->assertEquals('bank_transfer', $payment->meta['received_by'] ?? null);

        $receiptData = $this->receiptService->getReceiptData($payment);
        $this->assertEquals('Bank Transfer', $receiptData['paymentMethod']);
        $this->assertStringContainsString('Received by admin on', $receiptData['transactionId']);
    }

    public function test_real_payhere_notify_stays_online_with_transaction_id(): void
    {
        config([
            'payhere.merchant_id' => 'TEST_MID',
            'payhere.merchant_secret' => 'TEST_SECRET',
            'payhere.currency' => 'LKR',
        ]);

        $payment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => '2026-10',
            'amount' => 2500,
            'status' => 'pending',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_REAL_ORDER_999',
            'transaction_id' => null,
        ]);

        $amountFormatted = number_format(2500, 2, '.', '');
        $secretHash = strtoupper(md5('TEST_SECRET'));
        $md5sig = strtoupper(md5('TEST_MID' . 'PH_REAL_ORDER_999' . $amountFormatted . 'LKR' . '2' . $secretHash));

        $response = $this->post(route('payhere.notify'), [
            'merchant_id' => 'TEST_MID',
            'order_id' => 'PH_REAL_ORDER_999',
            'payment_id' => 'PAYHERE_TX_777888',
            'payhere_amount' => $amountFormatted,
            'payhere_currency' => 'LKR',
            'status_code' => 2,
            'md5sig' => $md5sig,
        ]);

        $response->assertStatus(200);

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('online', $payment->payment_method);
        $this->assertEquals('PAYHERE_TX_777888', $payment->transaction_id);
        $this->assertTrue($payment->wasGatewayConfirmed());

        $receiptData = $this->receiptService->getReceiptData($payment);
        $this->assertEquals('Online Payment (PayHere)', $receiptData['paymentMethod']);
        $this->assertEquals('PAYHERE_TX_777888', $receiptData['transactionId']);
    }

    public function test_topup_marked_paid_by_admin_relabels_offline_and_receipt_shows_offline(): void
    {
        $ym = now()->format('Y-m');

        $topup = SubjectTopup::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200,
            'status' => 'pending',
            'payment_method' => 'online',
        ]);

        $payment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1200,
            'status' => 'pending',
            'kind' => 'topup',
            'payment_method' => 'online',
            'gateway_ref' => 'TOPUP_' . $topup->id . '_12345',
            'meta' => ['topup_id' => $topup->id, 'added_subjects' => ['Science']],
        ]);
        $topup->update(['payment_id' => $payment->id]);

        $response = $this->actingAs($this->admin, 'web')->post(
            route('admin.students.subject-topups.mark-paid', $topup->id),
            ['received_by' => 'cash']
        );

        $response->assertRedirect();

        $payment->refresh();
        $this->assertEquals('paid', $payment->status);
        $this->assertEquals('offline', $payment->payment_method);
        $this->assertEquals('cash', $payment->meta['received_by'] ?? null);

        $receiptData = $this->receiptService->getReceiptData($payment);
        $this->assertEquals('Cash', $receiptData['paymentMethod']);
        $this->assertStringContainsString('Received by admin on', $receiptData['transactionId']);
    }

    public function test_advance_payment_approval_receipt_uses_offline_label(): void
    {
        $advanceReq = AdvancePaymentRequest::create([
            'user_id' => $this->student->id,
            'months_count' => 3,
            'amount' => 9000,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'web')->post(
            route('admin.students.advance-payments.approve', $advanceReq->id),
            ['received_by' => 'bank_transfer']
        );

        $response->assertRedirect();

        $payments = Payment::where('user_id', $this->student->id)->where('kind', 'advance')->get();
        $this->assertCount(3, $payments);
        foreach ($payments as $p) {
            $this->assertEquals('offline', $p->payment_method);
            $receiptData = $this->receiptService->getReceiptData($p);
            $this->assertEquals('Bank Transfer', $receiptData['paymentMethod']);
            $this->assertStringContainsString('Received by admin on', $receiptData['transactionId']);
        }
    }

    public function test_fix_offline_payment_labels_command_dry_run_and_apply(): void
    {
        // 1. Paid payment that says online with empty transaction_id (wrong legacy data)
        $wrong1 = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => '2026-08',
            'amount' => 3000,
            'status' => 'paid',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_OLD_UNCONFIRMED_1',
            'transaction_id' => null,
        ]);

        // 2. Paid payment that says online with REAL transaction_id (genuine PayHere)
        $realOnline = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => '2026-09',
            'amount' => 3000,
            'status' => 'paid',
            'payment_method' => 'online',
            'gateway_ref' => 'PH_GENUINE_2',
            'transaction_id' => 'PAYHERE_CONFIRMED_123',
        ]);

        // Dry-run: should report but not write
        $this->artisan('payments:fix-offline-labels')
            ->expectsOutputToContain('would be relabelled')
            ->assertExitCode(0);

        $this->assertEquals('online', $wrong1->fresh()->payment_method);

        // Apply: should update wrong1 and leave realOnline untouched
        $this->artisan('payments:fix-offline-labels', ['--apply' => true])
            ->expectsOutputToContain('payment(s) relabelled as offline')
            ->assertExitCode(0);

        $wrong1->refresh();
        $this->assertEquals('offline', $wrong1->payment_method);
        $this->assertEquals('online', $wrong1->meta['original_method']);
        $this->assertEquals('PH_OLD_UNCONFIRMED_1', $wrong1->meta['original_gateway_ref']);
        $this->assertStringStartsWith('ADMIN_' . $wrong1->id . '_', $wrong1->gateway_ref);

        $realOnline->refresh();
        $this->assertEquals('online', $realOnline->payment_method);
        $this->assertEquals('PH_GENUINE_2', $realOnline->gateway_ref);

        // Idempotent: running again does nothing
        $this->artisan('payments:fix-offline-labels', ['--apply' => true])
            ->expectsOutputToContain('Nothing to fix')
            ->assertExitCode(0);
    }

    public function test_admin_update_payment_method_and_resend_receipt_generates_corrected_pdf(): void
    {
        Mail::fake();

        $payment = Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => '2026-10',
            'amount' => 3500,
            'status' => 'paid',
            'payment_method' => 'online',
            'paid_at' => '2026-10-15',
            'gateway_ref' => 'PH_ACCIDENTAL_1',
            'transaction_id' => null,
        ]);

        // Initially receipt would show "offline" because not gateway-confirmed
        $pdf1 = $this->receiptService->pdf($payment);
        $this->assertStringNotContainsString('PayHere', $this->extractPdfText($pdf1));

        // Update payment via admin PUT route to offline
        $response = $this->actingAs($this->admin, 'web')->put(
            route('admin.students.payments.update', ['id' => $this->student->id, 'payment' => $payment->id]),
            [
                'year_month' => '2026-10',
                'amount' => 3500,
                'status' => 'paid',
                'payment_method' => 'offline',
                'paid_date' => '2026-10-15',
            ]
        );
        $response->assertRedirect();

        // Resend receipt via admin POST route
        $resendResp = $this->actingAs($this->admin, 'web')->post(
            route('admin.students.payments.resend-receipt', ['id' => $this->student->id, 'payment' => $payment->id])
        );
        $resendResp->assertRedirect();
        $resendResp->assertSessionHas('success');

        Mail::assertSent(PaymentReceiptMail::class, function ($mail) use ($payment) {
            $this->assertEquals($payment->id, $mail->payment->id);
            $this->assertNotEmpty($mail->pdfContent);
            $text = $this->extractPdfText($mail->pdfContent);
            $this->assertStringContainsString('Bank Transfer / Cash', $text);
            $this->assertStringNotContainsString('PayHere', $text);
            $this->assertStringNotContainsString('PH_', $text);
            return true;
        });
    }
}

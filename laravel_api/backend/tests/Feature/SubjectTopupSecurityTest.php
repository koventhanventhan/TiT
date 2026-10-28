<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use App\Services\PayHereService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubjectTopupSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'TiT Security Test Institute',
            'slug' => 'tit-sec-test',
            'code' => 'TIT',
            'status' => 'active',
        ]);

        Subject::create(['name' => 'Mathematics', 'category' => 'grade_10_to_11', 'medium' => 'english', 'price' => 1500, 'institute_id' => $this->institute->id]);
        Subject::create(['name' => 'Science', 'category' => 'grade_10_to_11', 'medium' => 'english', 'price' => 1200, 'institute_id' => $this->institute->id]);

        $this->student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'current_grade' => '10',
            'medium' => 'english',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'selected_subjects' => json_encode(['Mathematics']),
        ]);
    }

    /**
     * PART A REGRESSION TEST:
     * Calling POST /api/student/subject-topups/{id}/pay-online with simulate_success=1
     * must NEVER mark the payment or top-up as paid, must not unlock subjects, and must not send receipt email.
     */
    public function test_pay_online_with_simulate_success_does_not_unlock_topup_or_send_receipt(): void
    {
        Mail::fake();

        $ym = now()->format('Y-m');

        // Student paid for month with 1 subject (Mathematics)
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1500,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->student);

        // Add Science -> creates pending top-up
        $updateResp = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);
        $updateResp->assertStatus(200);

        $topup = SubjectTopup::where('user_id', $this->student->id)->where('status', 'pending')->firstOrFail();
        $this->assertEquals(1200, (float) $topup->amount);
        $this->assertEquals(['Science'], $this->student->fresh()->lockedSubjects());

        // Attacker attempts to simulate success via POST pay-online
        $payOnlineResp = $this->postJson("/api/student/subject-topups/{$topup->id}/pay-online", [
            'simulate_success' => 1,
        ]);
        $payOnlineResp->assertStatus(200);

        // The topup and payment MUST stay pending
        $topup->refresh();
        $this->assertEquals('pending', $topup->status, 'Topup status must stay pending after pay-online');

        $payment = Payment::where('user_id', $this->student->id)->where('kind', 'topup')->firstOrFail();
        $this->assertEquals('pending', $payment->status, 'Payment status must stay pending after pay-online');

        // Added subjects MUST stay locked
        $this->assertEquals(['Science'], $this->student->fresh()->lockedSubjects(), 'Added subjects must remain locked');

        // No receipt mail sent
        Mail::assertNothingSent();
    }
}

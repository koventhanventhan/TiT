<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubjectTopupPaidSoFarTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $student;
    private User $admin;
    private Subject $math;
    private Subject $science;
    private Subject $english;
    private string $merchantId = 'TEST_MERCHANT_123';
    private string $merchantSecret = 'TEST_SECRET_456';

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        config([
            'payhere.merchant_id' => $this->merchantId,
            'payhere.merchant_secret' => $this->merchantSecret,
            'payhere.currency' => 'LKR',
        ]);

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit-institute',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'email' => 'admin@tit.lk',
        ]);

        $this->math = Subject::create([
            'name' => 'Mathematics',
            'price' => 1500,
            'category' => 'grade_10_to_11',
            'medium' => 'english',
        ]);

        $this->science = Subject::create([
            'name' => 'Science',
            'price' => 1200,
            'category' => 'grade_10_to_11',
            'medium' => 'english',
        ]);

        $this->english = Subject::create([
            'name' => 'English',
            'price' => 1000,
            'category' => 'grade_10_to_11',
            'medium' => 'english',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'Topup Student',
            'full_name' => 'Topup Student Full',
            'email' => 'topup_student@tit.lk',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics']),
        ]);
    }

    private function buildSignedPayload(string $orderId, float $amount, int $statusCode, string $paymentId = 'PAY_TEST_001'): array
    {
        $amountFormatted = number_format($amount, 2, '.', '');
        $secretHash = strtoupper(md5($this->merchantSecret));
        $md5sig = strtoupper(md5(
            $this->merchantId .
            $orderId .
            $amountFormatted .
            'LKR' .
            $statusCode .
            $secretHash
        ));

        return [
            'merchant_id' => $this->merchantId,
            'order_id' => $orderId,
            'payment_id' => $paymentId,
            'payhere_amount' => $amountFormatted,
            'payhere_currency' => 'LKR',
            'status_code' => $statusCode,
            'md5sig' => $md5sig,
        ];
    }

    /**
     * Requirement 5(a):
     * The 4 steps of evidence:
     * 1. Add Science -> diff=1200, locked_subjects=[Science], one pending top-up.
     * 2. Admin pays topup via real admin route -> top-up paid, lockedSubjects=[].
     * 3. Student re-saves SAME list [Mathematics, Science] -> diff=0, no pending topup, no_extra_needed=true.
     * 4. Student removes Science, saves, adds Science again -> diff=0, Science remains unlocked.
     */
    public function test_evidence_four_steps_resolved_via_http(): void
    {
        $ym = now()->format('Y-m');

        // Initial monthly payment of 1500 for Mathematics
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1500,
            'kind' => 'monthly',
            'status' => 'paid',
            'payment_method' => 'card',
            'paid_at' => now(),
        ]);

        // STEP 1: Student adds Science
        Sanctum::actingAs($this->student);
        $resp1 = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);

        $resp1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'no_extra_needed' => false,
                'diff' => 1200,
                'locked_subjects' => ['Science'],
            ]);

        $topup = SubjectTopup::where('user_id', $this->student->id)
            ->where('year_month', $ym)
            ->where('status', 'pending')
            ->firstOrFail();

        $this->assertEquals(1200, (float) $topup->amount);
        $this->assertEquals(['Science'], $this->student->fresh()->lockedSubjects());

        // STEP 2: Student initiates offline payment, Admin approves via real route
        $offlineResp = $this->postJson("/api/student/subject-topups/{$topup->id}/pay-offline");
        $offlineResp->assertStatus(200);

        $this->actingAs($this->admin, 'web');
        $adminResp = $this->postJson("/admin/students-requests/subject-topups/{$topup->id}/mark-paid");
        $adminResp->assertStatus(200);

        $topup->refresh();
        $this->assertEquals('paid', $topup->status);
        $this->assertEquals([], $this->student->fresh()->lockedSubjects());
        $this->assertEquals(['Mathematics', 'Science'], $this->student->fresh()->activeSubjects());

        // Assert paid payment row of kind topup exists
        $this->assertDatabaseHas('payments', [
            'user_id' => $this->student->id,
            'kind' => 'topup',
            'status' => 'paid',
            'amount' => 1200,
            'year_month' => $ym,
        ]);

        // STEP 3: Student re-saves with the EXACT SAME list [Mathematics, Science]
        Sanctum::actingAs($this->student);
        $resp3 = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);

        $resp3->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'no_extra_needed' => true,
                'diff' => 0,
                'locked_subjects' => [],
            ]);
        $this->assertNull($resp3->json('topup'));
        $this->assertEquals(0, SubjectTopup::where('user_id', $this->student->id)->where('status', 'pending')->count());

        // STEP 4: Student removes Science, saves, then adds Science again
        $resp4a = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics'],
            'medium' => 'english',
        ]);
        $resp4a->assertStatus(200)
            ->assertJson([
                'success' => true,
                'no_extra_needed' => true,
                'diff' => 0,
                'locked_subjects' => [],
            ]);
        $this->assertEquals(['Mathematics'], $this->student->fresh()->activeSubjects());

        // Adds Science again -> unlocked immediately because already paid 2700 this month
        $resp4b = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);
        $resp4b->assertStatus(200)
            ->assertJson([
                'success' => true,
                'no_extra_needed' => true,
                'diff' => 0,
                'locked_subjects' => [],
            ]);
        $this->assertNull($resp4b->json('topup'));
        $this->assertEquals([], $this->student->fresh()->lockedSubjects());
        $this->assertEquals(['Mathematics', 'Science'], $this->student->fresh()->activeSubjects());
    }

    /**
     * Requirement 5(b):
     * Paid top-up, then add a THIRD subject -> diff = only the price of the third subject minus anything already covered,
     * only the third subject locked.
     */
    public function test_paid_topup_then_add_third_subject(): void
    {
        $ym = now()->format('Y-m');

        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1500,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Add Science (1200) -> topup created
        Sanctum::actingAs($this->student);
        $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);

        $topup = SubjectTopup::where('user_id', $this->student->id)->where('status', 'pending')->firstOrFail();

        // Admin marks topup paid
        $this->actingAs($this->admin, 'web');
        $this->postJson("/admin/students-requests/subject-topups/{$topup->id}/mark-paid")->assertStatus(200);

        // Student now adds a third subject: English (1000)
        Sanctum::actingAs($this->student);
        $resp = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science', 'English'],
            'medium' => 'english',
        ]);

        // Mathematics (1500) + Science (1200) + English (1000) = 3700. Paid so far = 2700. Diff = 1000.
        $resp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'no_extra_needed' => false,
                'diff' => 1000,
                'locked_subjects' => ['English'],
            ]);

        $this->student->refresh();
        $this->assertEquals(['English'], $this->student->lockedSubjects());
        $this->assertEquals(['Mathematics', 'Science'], $this->student->activeSubjects());

        $newTopup = SubjectTopup::where('user_id', $this->student->id)->where('status', 'pending')->firstOrFail();
        $this->assertEquals(1000, (float) $newTopup->amount);
        $this->assertEquals(['English'], $newTopup->added_subjects);
    }

    /**
     * Requirement 5(c):
     * PayHere-paid top-up (signed notify) behaves the same.
     */
    public function test_payhere_paid_topup_behaves_the_same(): void
    {
        Mail::fake();
        $ym = now()->format('Y-m');

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
        $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);

        $topup = SubjectTopup::where('user_id', $this->student->id)->where('status', 'pending')->firstOrFail();

        // Student initiates online topup payment
        $payOnlineResp = $this->postJson("/api/student/subject-topups/{$topup->id}/pay-online");
        $payOnlineResp->assertStatus(200);
        $orderId = $payOnlineResp->json('order_id');

        // PayHere webhook sends signed notify
        $notifyPayload = $this->buildSignedPayload($orderId, 1200.0, 2);
        $notifyResp = $this->post('/api/payhere/notify', $notifyPayload);
        $notifyResp->assertStatus(200);

        $topup->refresh();
        $this->assertEquals('paid', $topup->status);
        $this->assertEquals([], $this->student->fresh()->lockedSubjects());

        // Now student saves the SAME list
        Sanctum::actingAs($this->student);
        $resaveResp = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);

        $resaveResp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'no_extra_needed' => true,
                'diff' => 0,
                'locked_subjects' => [],
            ]);
        $this->assertNull($resaveResp->json('topup'));
        $this->assertEquals(0, SubjectTopup::where('user_id', $this->student->id)->where('status', 'pending')->count());
    }

    /**
     * Requirement 5(d):
     * Month rollover (Carbon::setTestNow day 1 next month) -> paid-so-far restarts from 0.
     */
    public function test_month_rollover_restarts_paid_so_far_from_zero(): void
    {
        Carbon::setTestNow('2026-10-15 12:00:00');

        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => '2026-10',
            'amount' => 1500,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => '2026-10',
            'amount' => 1200,
            'kind' => 'topup',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertEquals(2700.0, $this->student->paidAmountForMonth('2026-10'));

        // Roll over to day 1 next month (November 2026)
        Carbon::setTestNow('2026-11-01 08:00:00');

        $this->assertEquals(0.0, $this->student->paidAmountForMonth('2026-11'));
        $this->assertFalse($this->student->hasPaidForMonth('2026-11'));

        // Student updates subjects in new month before paying monthly fee
        Sanctum::actingAs($this->student);
        $resp = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science', 'English'],
            'medium' => 'english',
        ]);

        // In new month, student has not paid yet (already_paid = false), no top-up created
        $resp->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => false,
                'no_extra_needed' => true,
                'diff' => 0,
                'locked_subjects' => [],
            ]);

        $this->assertDatabaseMissing('subject_topups', [
            'user_id' => $this->student->id,
            'year_month' => '2026-11',
            'status' => 'pending',
        ]);
    }

    /**
     * Requirement 4:
     * Re-saving the same list while a top-up is pending keeps exactly ONE pending top-up with the same amount.
     */
    public function test_resaving_same_list_while_topup_is_pending_keeps_single_pending_topup(): void
    {
        $ym = now()->format('Y-m');

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

        // Add Science -> topup created
        $resp1 = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);
        $resp1->assertStatus(200);

        $pendingTopupsBefore = SubjectTopup::where('user_id', $this->student->id)
            ->where('year_month', $ym)
            ->where('status', 'pending')
            ->get();
        $this->assertCount(1, $pendingTopupsBefore);
        $originalId = $pendingTopupsBefore->first()->id;

        // Re-save with the exact same list while still pending
        $resp2 = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);
        $resp2->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'no_extra_needed' => false,
                'diff' => 1200,
                'locked_subjects' => ['Science'],
            ]);

        $pendingTopupsAfter = SubjectTopup::where('user_id', $this->student->id)
            ->where('year_month', $ym)
            ->where('status', 'pending')
            ->get();
        $this->assertCount(1, $pendingTopupsAfter);
        $this->assertEquals($originalId, $pendingTopupsAfter->first()->id);
        $this->assertEquals(1200, (float) $pendingTopupsAfter->first()->amount);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\SiteSetting;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubjectTopupAdmissionFeeTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $student;
    private User $admin;
    private Subject $math;
    private Subject $history;

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
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
            'email' => 'admin@tit.lk',
        ]);

        SiteSetting::set('admission_fees_config', json_encode([
            10 => ['enabled' => true, 'amount' => 500]
        ]), 'general');

        $this->math = Subject::create([
            'name' => 'Mathematics',
            'price' => 1500,
            'category' => 'grade_10_to_11',
            'medium' => 'english',
        ]);

        $this->history = Subject::create([
            'name' => 'History',
            'price' => 500,
            'category' => 'grade_10_to_11',
            'medium' => 'english',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'name' => 'First Month Student',
            'full_name' => 'First Month Student Full',
            'email' => 'first_month@tit.lk',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'current_grade' => 'Grade 10',
            'medium' => 'english',
            'selected_subjects' => json_encode(['Mathematics']),
        ]);
    }

    /**
     * Part C Reproduction & Resolution:
     * First-month student, admission fee Rs 500, pays Rs 2,000 = Rs 1,500 subjects + Rs 500 admission
     * -> adds a Rs 500 subject -> expected diff Rs 500 (top-up).
     */
    public function test_first_month_student_admission_fee_not_counted_as_paid_subjects(): void
    {
        $ym = now()->format('Y-m');

        // First month payment: Rs 2000 = Rs 1500 Mathematics + Rs 500 admission fee
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 2000,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
            'meta' => ['admission_fee' => 500],
        ]);

        $this->assertEquals(1500.0, $this->student->paidAmountForMonth($ym));
        $this->assertEquals(500.0, $this->student->admissionFeeForMonth($ym));

        Sanctum::actingAs($this->student);

        // Student adds History (Rs 500)
        // Total monthly fee for Math (1500) + History (500) is 2000.
        // Paid for subjects so far is 1500.
        // Expected diff is 500 (NOT 0).
        $response = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'History'],
            'medium' => 'english',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'no_extra_needed' => false,
                'diff' => 500,
                'paid_amount' => 1500,
                'locked_subjects' => ['History'],
            ]);

        $this->assertDatabaseHas('subject_topups', [
            'user_id' => $this->student->id,
            'year_month' => $ym,
            'amount' => 500,
            'status' => 'pending',
        ]);

        $this->assertEquals(['History'], $this->student->fresh()->lockedSubjects());
    }

    /**
     * Part C: Second-month student test:
     * In month 2, no admission fee is deducted because it was a one-off fee in month 1.
     */
    public function test_second_month_student_no_admission_fee_deducted(): void
    {
        // Month 1 (October 2026)
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => '2026-10',
            'amount' => 2000,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => Carbon::parse('2026-10-05 10:00:00'),
            'meta' => ['admission_fee' => 500],
        ]);

        Carbon::setTestNow('2026-11-10 12:00:00');
        $ym = '2026-11';

        // Month 2 payment: Rs 1500 (pure subject fee, no admission fee)
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 1500,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Month 2 should have admissionFee = 0 and paidAmount = 1500
        $this->assertEquals(0.0, $this->student->admissionFeeForMonth($ym));
        $this->assertEquals(1500.0, $this->student->paidAmountForMonth($ym));

        Sanctum::actingAs($this->student);

        // Student adds History (Rs 500)
        $response = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'History'],
            'medium' => 'english',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'no_extra_needed' => false,
                'diff' => 500,
                'paid_amount' => 1500,
                'locked_subjects' => ['History'],
            ]);
    }

    /**
     * Part C: Legacy payment row without meta.admission_fee derives correctly from config.
     */
    public function test_legacy_first_month_payment_derives_admission_fee_from_config(): void
    {
        $ym = now()->format('Y-m');

        // Legacy payment row created without meta
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 2000,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
            'meta' => null,
        ]);

        // Should automatically derive 500 from admission_fees_config for Grade 10
        $this->assertEquals(500.0, $this->student->admissionFeeForMonth($ym));
        $this->assertEquals(1500.0, $this->student->paidAmountForMonth($ym));

        Sanctum::actingAs($this->student);

        $response = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'History'],
            'medium' => 'english',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'no_extra_needed' => false,
                'diff' => 500,
                'locked_subjects' => ['History'],
            ]);
    }
}

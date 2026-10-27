<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\Assignment;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Subject;
use App\Models\SubjectTopup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubjectTopupTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $student;
    private User $admin;
    private Subject $math;
    private Subject $science;
    private Subject $english;

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

    /**
     * B5 Scenario 1: Paid month + 1 subject added -> diff calculated, pending topup created, added subject locked.
     */
    public function test_paid_month_and_adding_subject_creates_pending_topup_and_locks_new_subject()
    {
        $ym = now()->format('Y-m');

        // Student paid 1500 for Mathematics
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

        Sanctum::actingAs($this->student);

        // Add Science (Maths 1500 + Science 1200 = 2700; diff = 1200)
        $response = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science'],
            'medium' => 'english',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'no_extra_needed' => false,
                'diff' => 1200,
                'locked_subjects' => ['Science'],
            ]);

        $this->student->refresh();
        $this->assertEquals(['Mathematics'], $this->student->activeSubjects());
        $this->assertEquals(['Science'], $this->student->lockedSubjects());

        $this->assertDatabaseHas('subject_topups', [
            'user_id' => $this->student->id,
            'year_month' => $ym,
            'status' => 'pending',
            'amount' => 1200,
        ]);
    }

    /**
     * B5 Scenario 2: Pay online or admin mark paid -> unlocked immediately, receipt issued.
     */
    public function test_pay_topup_online_and_admin_mark_paid_unlocks_and_issues_receipt()
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

        $topup = SubjectTopup::create([
            'institute_id' => $this->institute->id,
            'user_id' => $this->student->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200,
            'status' => 'pending',
        ]);

        $this->student->selected_subjects = json_encode(['Mathematics', 'Science']);
        $this->student->save();

        // Admin marks top-up as paid
        $this->actingAs($this->admin, 'web');
        $resp = $this->postJson("/admin/students-requests/subject-topups/{$topup->id}/mark-paid");
        $resp->assertStatus(200);

        $topup->refresh();
        $this->assertEquals('paid', $topup->status);
        $this->student->refresh();

        $this->assertEquals(['Mathematics', 'Science'], $this->student->activeSubjects());
        $this->assertEmpty($this->student->lockedSubjects());

        // Receipt issued
        $this->assertDatabaseHas('payments', [
            'user_id' => $this->student->id,
            'kind' => 'topup',
            'status' => 'paid',
            'amount' => 1200,
        ]);
    }

    /**
     * B5 Scenario 3: Package max price or diff <= 0 -> unlocked immediately, no topup created.
     */
    public function test_package_max_price_or_equal_fee_unlocks_immediately()
    {
        $ym = now()->format('Y-m');

        // Create package with capped price of 2000
        Package::create([
            'name' => 'Grade 10-11 All Subjects',
            'category' => 'grade_10_to_11',
            'type' => 'all_subjects',
            'package_price' => 2000,
            'medium' => 'english',
            'applicable_grades' => ['10', '11'],
        ]);

        // Student already paid package price 2000
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 2000,
            'kind' => 'monthly',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        Sanctum::actingAs($this->student);

        // Student selects all 3 subjects -> price capped at 2000 -> diff = 0
        $response = $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics', 'Science', 'English'],
            'medium' => 'english',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'already_paid' => true,
                'no_extra_needed' => true,
                'diff' => 0,
                'locked_subjects' => [],
            ]);

        $this->student->refresh();
        $this->assertCount(3, $this->student->activeSubjects());
        $this->assertEmpty($this->student->lockedSubjects());
        $this->assertDatabaseMissing('subject_topups', [
            'user_id' => $this->student->id,
            'year_month' => $ym,
            'status' => 'pending',
        ]);
    }

    /**
     * B5 Scenario 4: Cancel topup -> reverts selected_subjects, marks topup cancelled.
     */
    public function test_cancel_topup_reverts_subjects()
    {
        $ym = now()->format('Y-m');
        $topup = SubjectTopup::create([
            'institute_id' => $this->institute->id,
            'user_id' => $this->student->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200,
            'status' => 'pending',
        ]);

        $this->student->selected_subjects = json_encode(['Mathematics', 'Science']);
        $this->student->save();

        Sanctum::actingAs($this->student);
        $resp = $this->postJson("/api/student/subject-topups/{$topup->id}/cancel");

        $resp->assertStatus(200);
        $topup->refresh();
        $this->assertEquals('cancelled', $topup->status);

        $this->student->refresh();
        $this->assertEquals(['Mathematics'], $this->student->activeSubjects());
        $this->assertEmpty($this->student->lockedSubjects());
    }

    /**
     * B5 Scenario 5: Offline topup clicked twice within 10 min -> deduped.
     */
    public function test_offline_topup_deduped_within_10_minutes()
    {
        $ym = now()->format('Y-m');
        $topup = SubjectTopup::create([
            'institute_id' => $this->institute->id,
            'user_id' => $this->student->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->student);

        // First click
        $resp1 = $this->postJson("/api/student/subject-topups/{$topup->id}/pay-offline");
        $resp1->assertStatus(200)->assertJson(['deduped' => false]);

        // Second click within 10 min
        $resp2 = $this->postJson("/api/student/subject-topups/{$topup->id}/pay-offline");
        $resp2->assertStatus(200)->assertJson(['deduped' => true]);
    }

    /**
     * B5 Scenario 6 & 7: Direct API call to materials & assignments for locked subject returns 403.
     */
    public function test_direct_api_access_to_locked_subject_returns_403()
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

        SubjectTopup::create([
            'institute_id' => $this->institute->id,
            'user_id' => $this->student->id,
            'year_month' => $ym,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200,
            'status' => 'pending',
        ]);

        $this->student->selected_subjects = json_encode(['Mathematics', 'Science']);
        $this->student->save();

        Sanctum::actingAs($this->student);

        // Materials: Science should return 403
        $matRespLocked = $this->getJson('/api/student/materials?subject=Science');
        $matRespLocked->assertStatus(403)
            ->assertJson([
                'error' => 'subject_locked',
                'subject' => 'Science',
            ]);

        // Materials: Mathematics should return 200
        $matRespActive = $this->getJson('/api/student/materials?subject=Mathematics');
        $matRespActive->assertStatus(200);

        // Assignments: Science should return 403
        $assignRespLocked = $this->getJson('/api/student/assignments?subject=Science');
        $assignRespLocked->assertStatus(403)
            ->assertJson([
                'error' => 'subject_locked',
                'subject' => 'Science',
            ]);

        // Assignments: Mathematics should return 200
        $assignRespActive = $this->getJson('/api/student/assignments?subject=Mathematics');
        $assignRespActive->assertStatus(200);
    }

    /**
     * B5 Scenario 8: Month rollover auto-closes past pending top-up as superseded.
     */
    public function test_month_rollover_auto_closes_past_topup()
    {
        $lastMonth = now()->subMonth()->format('Y-m');
        $oldTopup = SubjectTopup::create([
            'institute_id' => $this->institute->id,
            'user_id' => $this->student->id,
            'year_month' => $lastMonth,
            'added_subjects' => ['Science'],
            'previous_subjects' => ['Mathematics'],
            'amount' => 1200,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($this->student);

        // Updating subjects in current month
        $this->postJson('/api/student/update-subjects', [
            'subjects' => ['Mathematics'],
            'medium' => 'english',
        ]);

        $oldTopup->refresh();
        $this->assertEquals('superseded', $oldTopup->status);
    }

    /**
     * Top-up payment does not satisfy hasPaidForMonth or corrupt studentAccessState.
     */
    public function test_topup_payment_does_not_satisfy_monthly_fee()
    {
        $ym = now()->format('Y-m');

        // Only top-up paid, no monthly payment
        Payment::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'year_month' => $ym,
            'amount' => 500,
            'kind' => 'topup',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->assertFalse($this->student->hasPaidForMonth($ym));
    }
}

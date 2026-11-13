<?php

namespace Tests\Feature;

use App\Exceptions\PlanLimitReachedException;
use App\Models\Institute;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PlanLimits;
use App\Services\StudentImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PlanLimitEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Institute 1 exists (primary institute)
        Institute::firstOrCreate(
            ['id' => 1],
            [
                'name' => 'TiT Primary',
                'slug' => 'tit',
                'status' => 'active',
                'is_active' => true,
            ]
        );
    }

    /**
     * Helper to create a plan and assign to an institute.
     */
    protected function createInstituteWithPlan(array $limits = [], string $name = 'Test Inst'): Institute
    {
        $maxStudents = array_key_exists('max_students', $limits) ? ($limits['max_students'] ?? -1) : -1;
        $maxTeachers = array_key_exists('max_teachers', $limits) ? ($limits['max_teachers'] ?? -1) : -1;

        $plan = SubscriptionPlan::create([
            'name' => 'Custom Tier',
            'slug' => 'custom-tier-' . uniqid(),
            'monthly_price' => 1000,
            'yearly_price' => 10000,
            'max_students' => $maxStudents,
            'max_teachers' => $maxTeachers,
            'features' => ['zoom'],
        ]);

        return Institute::create([
            'name' => $name,
            'slug' => 'inst-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
            'subscription_plan_id' => $plan->id,
        ]);
    }

    /**
     * 1. Admin student store at limit-1 succeeds, at limit is blocked.
     */
    public function test_admin_student_store_at_limit_minus_one_succeeds_and_at_limit_is_blocked(): void
    {
        $inst = $this->createInstituteWithPlan(['max_students' => 2]);

        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Inst Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'institute_id' => $inst->id,
        ]);

        // Pre-create 1 student (limit - 1)
        User::withoutGlobalScopes()->create([
            'name' => 'student1',
            'email' => 'student1@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'full_name' => 'Student One',
            'institute_id' => $inst->id,
            'registration_status' => 'approved',
        ]);

        // Attempt 1: Add second student (reaches limit) -> succeeds
        $response1 = $this->actingAs($admin, 'web')
            ->withHeader('X-Institute-Id', (string) $inst->id)
            ->post(route('admin.students.store'), [
                'full_name' => 'Student Two',
                'email' => 'student2@example.com',
                'phone_number' => '0771234567',
                'password' => 'password123',
                'date_of_birth' => '2005-01-01',
                'gender' => 'male',
                'school_name' => 'Royal College',
                'medium' => 'english',
                'online_experience' => 1,
                'device_used' => 'Laptop',
                'current_grade' => 'Grade 10',
            ]);

        $response1->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'student2@example.com',
            'institute_id' => $inst->id,
        ]);

        // Attempt 2: Add third student (at limit) -> blocked
        $response2 = $this->actingAs($admin, 'web')
            ->withHeader('X-Institute-Id', (string) $inst->id)
            ->from(route('admin.students.create'))
            ->post(route('admin.students.store'), [
                'full_name' => 'Student Three',
                'email' => 'student3@example.com',
                'phone_number' => '0779998888',
                'password' => 'password123',
                'date_of_birth' => '2005-01-01',
                'gender' => 'male',
                'school_name' => 'Royal College',
                'medium' => 'english',
                'online_experience' => 1,
                'device_used' => 'Laptop',
                'current_grade' => 'Grade 10',
            ]);

        $response2->assertRedirect(route('admin.students.create'));
        $response2->assertSessionHas('error', 'Student limit (2) reached. Your plan allows 2 students. Contact the platform owner to upgrade.');
        $this->assertDatabaseMissing('users', [
            'email' => 'student3@example.com',
        ]);
    }

    /**
     * 2. API registration at limit returns 403 with code plan_limit_reached.
     */
    public function test_api_registration_at_limit_returns_403_with_code_plan_limit_reached(): void
    {
        $inst = $this->createInstituteWithPlan(['max_students' => 1]);

        // Fill limit
        User::withoutGlobalScopes()->create([
            'name' => 'existing_student',
            'email' => 'existing@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'full_name' => 'Existing Student',
            'institute_id' => $inst->id,
            'registration_status' => 'approved',
        ]);

        // Pre-verify email in cache (required by AuthController)
        \Illuminate\Support\Facades\Cache::put('email_verified_newstudent@example.com', true, 3600);

        $response = $this->withHeader('X-Institute-Id', (string) $inst->id)
            ->postJson('/api/auth/register', [
                'name' => 'New Student',
                'email' => 'newstudent@example.com',
                'phone_number' => '0712345678',
                'password' => 'Password@123',
                'password_confirmation' => 'Password@123',
                'role' => 'user',
            ]);

        $response->assertStatus(403);
        $response->assertJson([
            'code' => 'plan_limit_reached',
        ]);
        $response->assertJsonFragment([
            'message' => 'Student limit (1) reached. Your plan allows 1 students. Contact the platform owner to upgrade.',
        ]);
    }

    /**
     * 3. Teacher store at limit-1 succeeds and at limit is blocked.
     */
    public function test_teacher_store_at_limit_minus_one_succeeds_and_at_limit_is_blocked(): void
    {
        $inst = $this->createInstituteWithPlan(['max_teachers' => 1]);

        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Inst Admin',
            'email' => 'admin@teacher-test.com',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'institute_id' => $inst->id,
        ]);

        // Add 1st teacher -> succeeds
        $res1 = $this->actingAs($admin, 'web')
            ->withHeader('X-Institute-Id', (string) $inst->id)
            ->post(route('admin.teachers.store'), [
                'name' => 'Teacher One',
                'email' => 'teacher1@example.com',
                'password' => 'password123',
                'phone_number' => '0711112222',
            ]);

        $res1->assertRedirect();
        $this->assertDatabaseHas('users', [
            'email' => 'teacher1@example.com',
            'role' => 'teacher',
            'institute_id' => $inst->id,
        ]);

        // Add 2nd teacher (limit is 1) -> blocked
        $res2 = $this->actingAs($admin, 'web')
            ->withHeader('X-Institute-Id', (string) $inst->id)
            ->from(route('admin.teachers.index'))
            ->post(route('admin.teachers.store'), [
                'name' => 'Teacher Two',
                'email' => 'teacher2@example.com',
                'password' => 'password123',
                'phone_number' => '0713334444',
            ]);

        $res2->assertRedirect(route('admin.teachers.index'));
        $res2->assertSessionHas('error', 'Teacher limit (1) reached. Your plan allows 1 teachers. Contact the platform owner to upgrade.');
        $this->assertDatabaseMissing('users', [
            'email' => 'teacher2@example.com',
        ]);
    }

    /**
     * 4. Student import of 5 rows with 3 free slots inserts 0.
     */
    public function test_student_import_with_more_rows_than_free_slots_inserts_zero_students(): void
    {
        $inst = $this->createInstituteWithPlan(['max_students' => 5]);

        // Pre-insert 2 students -> 3 free slots
        User::withoutGlobalScopes()->create([
            'name' => 's1',
            'email' => 's1@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'full_name' => 'S1',
            'institute_id' => $inst->id,
        ]);
        User::withoutGlobalScopes()->create([
            'name' => 's2',
            'email' => 's2@example.com',
            'password' => Hash::make('secret123'),
            'role' => 'user',
            'full_name' => 'S2',
            'institute_id' => $inst->id,
        ]);

        $importService = new StudentImportService();

        // 5 rows to import when only 3 slots are free
        $rows = [
            ['full_name' => 'Imported 1', 'email' => 'imp1@test.com'],
            ['full_name' => 'Imported 2', 'email' => 'imp2@test.com'],
            ['full_name' => 'Imported 3', 'email' => 'imp3@test.com'],
            ['full_name' => 'Imported 4', 'email' => 'imp4@test.com'],
            ['full_name' => 'Imported 5', 'email' => 'imp5@test.com'],
        ];

        try {
            $importService->importStudents($inst, $rows);
            $this->fail("Expected PlanLimitReachedException was not thrown.");
        } catch (PlanLimitReachedException $e) {
            $this->assertEquals("This file has 5 students but only 3 slots are free", $e->getMessage());
        }

        // Assert 0 rows were inserted
        $this->assertEquals(2, User::withoutGlobalScopes()->where('institute_id', $inst->id)->where('role', 'user')->count());
        $this->assertDatabaseMissing('users', ['email' => 'imp1@test.com']);

        // Now import 3 rows (exactly remaining slots) -> succeeds
        $validRows = [
            ['full_name' => 'Imported 1', 'email' => 'imp1@test.com'],
            ['full_name' => 'Imported 2', 'email' => 'imp2@test.com'],
            ['full_name' => 'Imported 3', 'email' => 'imp3@test.com'],
        ];

        $result = $importService->importStudents($inst, $validRows);
        $this->assertEquals(3, $result['inserted']);
        $this->assertEquals(5, User::withoutGlobalScopes()->where('institute_id', $inst->id)->where('role', 'user')->count());
    }

    /**
     * 5. Unlimited (0, -1, or null) never blocks.
     */
    public function test_unlimited_plan_never_blocks(): void
    {
        $instNeg  = $this->createInstituteWithPlan(['max_students' => -1]);
        $instZero = $this->createInstituteWithPlan(['max_students' => 0]);
        $instNoPlan = Institute::create([
            'name' => 'No Plan Inst',
            'slug' => 'no-plan-' . uniqid(),
            'status' => 'active',
            'is_active' => true,
            'subscription_plan_id' => null,
        ]);

        $this->assertNull(PlanLimits::remainingStudentSlots($instNeg));
        $this->assertNull(PlanLimits::remainingStudentSlots($instZero));
        $this->assertNull(PlanLimits::remainingStudentSlots($instNoPlan));

        // Assert no exception thrown even when adding 100
        PlanLimits::assertCanAddStudent($instNeg, 100);
        PlanLimits::assertCanAddStudent($instZero, 100);
        PlanLimits::assertCanAddStudent($instNoPlan, 100);
        $this->assertTrue(true);
    }

    /**
     * 6. Other institute's users never count toward this institute's limit.
     */
    public function test_other_institutes_users_never_count(): void
    {
        $instA = $this->createInstituteWithPlan(['max_students' => 2], 'Institute A');
        $instB = $this->createInstituteWithPlan(['max_students' => 2], 'Institute B');

        // Fill Inst A to limit
        User::withoutGlobalScopes()->create([
            'name' => 'a1',
            'email' => 'a1@test.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'full_name' => 'A1',
            'institute_id' => $instA->id,
        ]);
        User::withoutGlobalScopes()->create([
            'name' => 'a2',
            'email' => 'a2@test.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'full_name' => 'A2',
            'institute_id' => $instA->id,
        ]);

        // Inst A is full
        $this->assertEquals(0, PlanLimits::remainingStudentSlots($instA));

        // Inst B has 0 students -> should have 2 free slots
        $this->assertEquals(2, PlanLimits::remainingStudentSlots($instB));
        PlanLimits::assertCanAddStudent($instB, 1);
        $this->assertTrue(true);
    }

    /**
     * 7. Deleting a student/teacher frees a slot (admin delete route + bulk delete + teacher delete).
     */
    public function test_deleting_a_student_or_teacher_frees_a_slot_across_admin_and_bulk_delete_routes(): void
    {
        $inst = $this->createInstituteWithPlan(['max_students' => 2, 'max_teachers' => 1]);

        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Inst Admin',
            'email' => 'admin@inst-' . uniqid() . '.lk',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'institute_id' => $inst->id,
        ]);

        $student1 = User::withoutGlobalScopes()->create([
            'name' => 's1',
            'email' => 's1@test.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'full_name' => 'Student One',
            'institute_id' => $inst->id,
        ]);

        $student2 = User::withoutGlobalScopes()->create([
            'name' => 's2',
            'email' => 's2@test.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'full_name' => 'Student Two',
            'institute_id' => $inst->id,
        ]);

        // Students limit reached (2/2)
        $this->assertEquals(0, PlanLimits::remainingStudentSlots($inst));

        // a) Admin single student delete route frees 1 slot
        $this->actingAs($admin)
            ->delete(route('admin.students.destroy', $student1->id))
            ->assertRedirect(route('admin.students.index'))
            ->assertSessionHas('success', 'Student account deleted permanently.');

        $this->assertDatabaseMissing('users', ['id' => $student1->id]);
        $this->assertEquals(1, PlanLimits::remainingStudentSlots($inst));

        // Add back a student so we are full again
        $student3 = User::withoutGlobalScopes()->create([
            'name' => 's3',
            'email' => 's3@test.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'full_name' => 'Student Three',
            'institute_id' => $inst->id,
        ]);
        $this->assertEquals(0, PlanLimits::remainingStudentSlots($inst));

        // b) Admin bulk delete frees all selected slots
        $this->actingAs($admin)
            ->post(route('admin.students.bulk-delete'), ['ids' => [$student2->id, $student3->id]])
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $student2->id]);
        $this->assertDatabaseMissing('users', ['id' => $student3->id]);
        $this->assertEquals(2, PlanLimits::remainingStudentSlots($inst));

        // c) Teacher delete frees a teacher slot
        $teacher = User::withoutGlobalScopes()->create([
            'name' => 't1',
            'email' => 't1@test.com',
            'password' => Hash::make('secret'),
            'role' => 'teacher',
            'full_name' => 'Teacher One',
            'institute_id' => $inst->id,
        ]);
        $this->assertEquals(0, PlanLimits::remainingTeacherSlots($inst));

        $this->actingAs($admin)
            ->delete(route('admin.teachers.destroy', $teacher->id))
            ->assertRedirect(route('admin.teachers.index'))
            ->assertSessionHas('success', 'Teacher deleted permanently.');

        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);
        $this->assertEquals(1, PlanLimits::remainingTeacherSlots($inst));
    }

    /**
     * 7b. After deleting a student the same e-mail and phone can be registered again.
     */
    public function test_after_deleting_a_student_the_same_email_and_phone_can_be_registered_again(): void
    {
        $inst = $this->createInstituteWithPlan(['max_students' => 5]);

        $admin = User::withoutGlobalScopes()->create([
            'name' => 'Inst Admin 2',
            'email' => 'admin2@inst-' . uniqid() . '.lk',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'institute_id' => $inst->id,
        ]);

        $studentData = [
            'full_name' => 'Original Student',
            'email' => 'duplicate.check@example.com',
            'phone_number' => '0771234567',
            'password' => 'Password123!',
            'date_of_birth' => '2005-01-01',
            'gender' => 'male',
            'school_name' => 'Central College',
            'medium' => 'english',
            'online_experience' => 1,
            'device_used' => 'Laptop',
            'current_grade' => 'Grade 12',
            'stream' => 'bio_maths',
        ];

        // 1. Admin creates initial student
        $res = $this->actingAs($admin)
            ->post(route('admin.students.store'), $studentData);
        $res->assertRedirect();
        $created = User::withoutGlobalScopes()->where('email', 'duplicate.check@example.com')->firstOrFail();

        // 2. Admin deletes student
        $this->actingAs($admin)
            ->delete(route('admin.students.destroy', $created->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $created->id]);

        // 3. Admin creates another student with the EXACT SAME email and phone
        $res2 = $this->actingAs($admin)
            ->post(route('admin.students.store'), [
                'full_name' => 'Re-registered Student',
                'email' => 'duplicate.check@example.com',
                'phone_number' => '0771234567',
                'password' => 'NewPassword123!',
                'date_of_birth' => '2005-02-02',
                'gender' => 'female',
                'school_name' => 'Central College',
                'medium' => 'english',
                'online_experience' => 1,
                'device_used' => 'Phone',
                'current_grade' => 'Grade 12',
                'stream' => 'bio_maths',
            ]);

        $res2->assertSessionHasNoErrors();
        $res2->assertRedirect();

        $reRegistered = User::withoutGlobalScopes()->where('email', 'duplicate.check@example.com')->first();
        $this->assertNotNull($reRegistered);
        $this->assertEquals('Re-registered Student', $reRegistered->full_name);
    }

    /**
     * 8. Sequential locking path executes properly.
     */
    public function test_sequential_locking_path_verifies_row_lock_logic(): void
    {
        $inst = $this->createInstituteWithPlan(['max_students' => 2]);

        $executed = PlanLimits::runWithStudentLock($inst, 1, function () {
            return 'locked_and_executed';
        });

        $this->assertEquals('locked_and_executed', $executed);
    }
}

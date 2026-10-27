<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\StudentIndexCounter;
use App\Models\User;
use App\Services\StudentIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIndexNumberTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private StudentIndexService $service;

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

        $this->service = app(StudentIndexService::class);
    }

    public function test_sequential_numbering_starts_at_100_and_increments(): void
    {
        $student1 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'current_grade' => '10',
        ]);

        $student2 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'current_grade' => '10',
        ]);

        $index1 = $this->service->assign($student1, 28);
        $index2 = $this->service->assign($student2, 28);

        $this->assertEquals('TiT/28/100', $index1);
        $this->assertEquals('TiT/28/101', $index2);

        $student1->refresh();
        $student2->refresh();

        $this->assertEquals('TiT/28/100', $student1->index_number);
        $this->assertEquals(28, $student1->batch_year);
        $this->assertEquals(100, $student1->index_seq);

        $this->assertEquals('TiT/28/101', $student2->index_number);
        $this->assertEquals(28, $student2->batch_year);
        $this->assertEquals(101, $student2->index_seq);

        $counter = StudentIndexCounter::where('institute_id', $this->institute->id)
            ->where('batch_year', 28)
            ->first();

        $this->assertNotNull($counter);
        $this->assertEquals(101, $counter->last_seq);
    }

    public function test_different_batches_have_independent_counters(): void
    {
        $studentBatch28 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);

        $studentBatch27 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);

        $index28 = $this->service->assign($studentBatch28, 28);
        $index27 = $this->service->assign($studentBatch27, 27);

        $this->assertEquals('TiT/28/100', $index28);
        $this->assertEquals('TiT/27/100', $index27);
    }

    public function test_different_institutes_have_independent_counters_and_prefixes(): void
    {
        $otherInstitute = Institute::create([
            'id' => 2,
            'name' => 'Apex Academy',
            'slug' => 'apex',
            'status' => 'active',
            'index_prefix' => 'APEX',
            'index_seq_start' => 500,
        ]);

        $studentTiT = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);

        $studentApex = User::factory()->create([
            'institute_id' => $otherInstitute->id,
            'role' => 'user',
        ]);

        $indexTiT = $this->service->assign($studentTiT, 28);
        $indexApex = $this->service->assign($studentApex, 28);

        $this->assertEquals('TiT/28/100', $indexTiT);
        $this->assertEquals('APEX/28/500', $indexApex);
    }

    public function test_assign_is_idempotent_if_already_assigned(): void
    {
        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);

        $firstCall = $this->service->assign($student, 28);
        $secondCall = $this->service->assign($student, 28);
        $thirdCallWithDifferentBatch = $this->service->assign($student, 29);

        $this->assertEquals('TiT/28/100', $firstCall);
        $this->assertEquals('TiT/28/100', $secondCall);
        $this->assertEquals('TiT/28/100', $thirdCallWithDifferentBatch);

        // Counter should only be 100
        $counter = StudentIndexCounter::where('institute_id', $this->institute->id)
            ->where('batch_year', 28)
            ->first();
        $this->assertEquals(100, $counter->last_seq);
    }

    public function test_deleting_a_student_does_not_reuse_the_index_number(): void
    {
        $student1 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);
        $this->service->assign($student1, 28);
        $this->assertEquals('TiT/28/100', $student1->index_number);

        // Student 1 is deleted
        $student1->delete();

        $student2 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);
        $this->service->assign($student2, 28);

        // Sequence must NOT be reused; student2 must get 101
        $this->assertEquals('TiT/28/101', $student2->index_number);
    }

    public function test_reassign_allocates_new_number_and_logs_audit_trail(): void
    {
        $admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
            'name' => 'Admin User',
        ]);
        $this->actingAs($admin);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);

        $oldIndex = $this->service->assign($student, 28);
        $this->assertEquals('TiT/28/100', $oldIndex);

        $newIndex = $this->service->reassign($student, 29, $admin, 'Entered wrong batch year initially');

        $this->assertEquals('TiT/29/100', $newIndex);
        $student->refresh();
        $this->assertEquals('TiT/29/100', $student->index_number);
        $this->assertEquals(29, $student->batch_year);

        // Audit log check
        $log = ActivityLog::where('action', 'student_index_reassigned')->first();
        $this->assertNotNull($log);
        $this->assertEquals($student->id, $log->metadata['student_id']);
        $this->assertEquals($admin->id, $log->user_id);
        $this->assertEquals('TiT/28/100', $log->metadata['old_index_number']);
        $this->assertEquals('TiT/29/100', $log->metadata['new_index_number']);
        $this->assertEquals('Entered wrong batch year initially', $log->metadata['reason']);
    }

    public function test_batch_year_normalization_and_validation(): void
    {
        $this->assertEquals(28, $this->service->normalizeBatchYear(28));
        $this->assertEquals(28, $this->service->normalizeBatchYear('28'));
        $this->assertEquals(28, $this->service->normalizeBatchYear(2028));
        $this->assertEquals(28, $this->service->normalizeBatchYear('2028'));
        $this->assertEquals(15, $this->service->normalizeBatchYear(15));

        $this->expectException(\InvalidArgumentException::class);
        $this->service->normalizeBatchYear(0);
    }

    public function test_default_batch_returns_existing_or_null(): void
    {
        $studentWithoutBatch = User::factory()->make([
            'current_grade' => 'Grade 04',
            'batch_year' => null,
        ]);
        $this->assertNull($this->service->calculateDefaultBatchYear($studentWithoutBatch));

        $studentWithBatch = User::factory()->make([
            'current_grade' => 'Grade 04',
            'batch_year' => 28,
        ]);
        $this->assertEquals(28, $this->service->calculateDefaultBatchYear($studentWithBatch));
    }

    public function test_admin_confirm_assigns_index_number(): void
    {
        $admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'admin_confirmed_at' => null,
            'current_grade' => '10',
            'full_name' => 'Test Student',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.students.confirm', $student->id), [
                'batch_year' => 28,
            ]);

        $response->assertRedirect();
        $student->refresh();

        $this->assertNotNull($student->admin_confirmed_at);
        $this->assertEquals('TiT/28/100', $student->index_number);
        $this->assertEquals(28, $student->batch_year);
    }

    public function test_admin_single_assign_and_reassign_routes(): void
    {
        $admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'index_number' => null,
        ]);

        // Assign
        $assignResponse = $this->actingAs($admin)
            ->post(route('admin.students.assign-index', $student->id), [
                'batch_year' => 28,
            ]);

        $assignResponse->assertRedirect();
        $student->refresh();
        $this->assertEquals('TiT/28/100', $student->index_number);

        // Reassign
        $reassignResponse = $this->actingAs($admin)
            ->post(route('admin.students.reassign-index', $student->id), [
                'batch_year' => 29,
                'reason' => 'Changed exam year',
            ]);

        $reassignResponse->assertRedirect();
        $student->refresh();
        $this->assertEquals('TiT/29/100', $student->index_number);
    }

    public function test_admin_bulk_assign_route(): void
    {
        $admin = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'admin',
        ]);

        $student1 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'current_grade' => '10',
        ]);

        $student2 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'current_grade' => '10',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.students.bulk-assign-index'), [
                'ids' => "{$student1->id},{$student2->id}",
                'batch_year' => 28,
            ]);

        $response->assertRedirect();
        $student1->refresh();
        $student2->refresh();

        $this->assertEquals('TiT/28/100', $student1->index_number);
        $this->assertEquals('TiT/28/101', $student2->index_number);
    }

    public function test_artisan_command_assign_student_index(): void
    {
        $student1 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'current_grade' => '10',
            'index_number' => null,
        ]);

        $student2 = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
            'current_grade' => '10',
            'index_number' => null,
        ]);

        // Dry run
        $this->artisan('students:assign-index', [
            '--batch' => 28,
            '--dry-run' => true,
        ])->assertSuccessful();

        $student1->refresh();
        $this->assertNull($student1->index_number);

        // Real run
        $this->artisan('students:assign-index', [
            '--batch' => 28,
        ])->assertSuccessful();

        $student1->refresh();
        $student2->refresh();

        $this->assertEquals('TiT/28/100', $student1->index_number);
        $this->assertEquals('TiT/28/101', $student2->index_number);
    }
}

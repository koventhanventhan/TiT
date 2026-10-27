<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\StudentIndexCounter;
use App\Models\User;
use App\Services\StudentIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentIndexPartBTest extends TestCase
{
    use RefreshDatabase;

    protected StudentIndexService $indexService;
    protected Institute $institute;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Jaffna',
            'slug' => 'tit',
            'index_format' => 'grade',
            'index_prefix' => 'TIT',
            'index_seq_start' => 1,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => 1,
        ]);

        $this->indexService = app(StudentIndexService::class);
    }

    /**
     * 1. Assign with typed series 58 + typed number 110 -> TIT/58/110.
     */
    public function test_assign_with_typed_series_58_and_typed_number_110(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'index_number' => null,
            'full_name' => 'Koventhan Test',
        ]);

        $assigned = $this->indexService->assign($student, '58', 110);

        $this->assertEquals('TIT/58/110', $assigned);
        $student->refresh();
        $this->assertEquals('TIT/58/110', $student->index_number);
        $this->assertEquals(58, $student->batch_year);
        $this->assertEquals(110, $student->index_seq);

        // Counter for series 58 must advance to at least 110
        $counter = StudentIndexCounter::where('institute_id', 1)->where('batch_year', 58)->first();
        $this->assertNotNull($counter);
        $this->assertEquals(110, $counter->last_seq);
    }

    /**
     * 2. Auto for Grade 11 student with counter next=550 -> TIT/11/550.
     */
    public function test_auto_for_grade_11_student_with_counter_next_550(): void
    {
        // Admin pre-sets series 11 counter next number to 550 (last_seq = 549)
        StudentIndexCounter::create([
            'institute_id' => 1,
            'batch_year' => 11,
            'last_seq' => 549,
        ]);

        $student11 = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => 'Grade 11',
            'index_number' => null,
            'full_name' => 'Auto Student 11',
        ]);

        // Auto assign without typing a sequence
        $assigned = $this->indexService->assign($student11);

        $this->assertEquals('TIT/11/550', $assigned);
        $student11->refresh();
        $this->assertEquals('TIT/11/550', $student11->index_number);
        $this->assertEquals(11, $student11->batch_year);
        $this->assertEquals(550, $student11->index_seq);

        // Next automatic assignment in series 11 will be 551
        $studentNext = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '11',
            'index_number' => null,
        ]);
        $nextAssigned = $this->indexService->assign($studentNext);
        $this->assertEquals('TIT/11/551', $nextAssigned);
    }

    /**
     * 3. Duplicate exact number rejected with clear error.
     */
    public function test_duplicate_exact_number_rejected(): void
    {
        $studentA = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'full_name' => 'First Student',
            'current_grade' => 'Grade 11',
        ]);
        $this->indexService->assign($studentA, '11', 550);

        $studentB = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'full_name' => 'Second Student',
            'current_grade' => 'Grade 11',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TIT/11/550 is already used by First Student');

        $this->indexService->assign($studentB, '11', 550);
    }

    /**
     * 4. Counter never goes below an issued number.
     */
    public function test_counter_never_goes_below_an_issued_number(): void
    {
        // Counter is at 200
        $counter = StudentIndexCounter::create([
            'institute_id' => 1,
            'batch_year' => 8,
            'last_seq' => 200,
        ]);

        $studentLow = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
        ]);

        // Assign exact sequence 150 (< 200)
        $this->indexService->assign($studentLow, '08', 150);

        $counter->refresh();
        // Counter must remain 200 (max(200, 150)), never go backwards
        $this->assertEquals(200, $counter->last_seq);

        // Next automatic assignment should be 201
        $studentAuto = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
        ]);
        $autoIndex = $this->indexService->assign($studentAuto);
        $this->assertEquals('TIT/08/201', $autoIndex);
    }

    /**
     * 5. Changing "next" below the highest issued number rejected.
     */
    public function test_changing_next_below_highest_issued_number_rejected(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
        ]);
        $this->indexService->assign($student, '08', 110);

        // Artisan command should reject setting next <= 110
        $this->artisan('students:index-counter', [
            'series' => '08',
            'next' => '100',
            'institute' => 1,
        ])
            ->expectsOutputToContain('Next number must be greater than highest number issued in series 08 (110)')
            ->assertExitCode(1);

        // Web route should also reject
        $response = $this->actingAs($this->admin)->post(route('admin.settings.index-series.update-counter'), [
            'series' => '08',
            'next' => 105,
        ]);
        $response->assertSessionHas('error');

        // Setting next > 110 (e.g. 150) succeeds
        $this->artisan('students:index-counter', [
            'series' => '08',
            'next' => '150',
            'institute' => 1,
        ])
            ->expectsOutputToContain('Counter successfully updated')
            ->assertExitCode(0);

        $counter = StudentIndexCounter::where('institute_id', 1)->where('batch_year', 8)->first();
        $this->assertEquals(149, $counter->last_seq);
    }

    /**
     * 6. Format switch keeps existing numbers.
     */
    public function test_format_switch_keeps_existing_numbers(): void
    {
        $this->institute->update([
            'index_format' => 'batch',
            'index_prefix' => 'TiT',
            'index_seq_start' => 100,
        ]);

        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'index_number' => 'TiT/58/101',
            'batch_year' => 58,
            'index_seq' => 101,
        ]);

        // Switch institute via Artisan
        $this->artisan('institutes:index-config', [
            'institute' => 1,
            '--format' => 'grade',
            '--prefix' => 'TIT',
            '--start' => '1',
        ])
            ->expectsOutputToContain('Configuration successfully updated')
            ->assertExitCode(0);

        $this->institute->refresh();
        $this->assertEquals('grade', $this->institute->getIndexFormat());
        $this->assertEquals('TIT', $this->institute->getIndexPrefix());
        $this->assertEquals(1, $this->institute->index_seq_start);

        // Student's index number is untouched!
        $student->refresh();
        $this->assertEquals('TiT/58/101', $student->index_number);
    }

    /**
     * 7. TIT/11/550 not flagged old-format.
     */
    public function test_tit_11_550_not_flagged_old_format(): void
    {
        $this->assertFalse(StudentIndexService::isOldOrWrongFormat('TIT/11/550', $this->institute));
        $this->assertFalse(StudentIndexService::isOldOrWrongFormat('TIT/58/110', $this->institute));
        $this->assertFalse(StudentIndexService::isOldOrWrongFormat('TIT/08/001', $this->institute));

        // Invalid format or wrong prefix IS flagged old format
        $this->assertTrue(StudentIndexService::isOldOrWrongFormat('TiT/11/550', $this->institute)); // wrong prefix case
        $this->assertTrue(StudentIndexService::isOldOrWrongFormat('INVALID', $this->institute));
    }

    /**
     * 8. Padding: 7 -> 007, 1000 stays 1000.
     */
    public function test_padding_behavior(): void
    {
        $student7 = User::factory()->create(['role' => 'user', 'institute_id' => 1, 'current_grade' => 'Grade 8']);
        $idx7 = $this->indexService->assign($student7, '08', 7);
        $this->assertEquals('TIT/08/007', $idx7);

        $student1000 = User::factory()->create(['role' => 'user', 'institute_id' => 1, 'current_grade' => 'Grade 8']);
        $idx1000 = $this->indexService->assign($student1000, '08', 1000);
        $this->assertEquals('TIT/08/1000', $idx1000);
    }

    /**
     * 9. PDF index list orders numerically (not as strings) and includes 3- and 4-digit numbers.
     */
    public function test_pdf_index_list_orders_numerically(): void
    {
        $s1000 = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'batch_year' => 8,
            'index_seq' => 1000,
            'index_number' => 'TIT/08/1000',
            'medium' => 'English',
            'registration_status' => 'confirmed',
        ]);

        $s550 = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'batch_year' => 8,
            'index_seq' => 550,
            'index_number' => 'TIT/08/550',
            'medium' => 'English',
            'registration_status' => 'confirmed',
        ]);

        $s007 = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'batch_year' => 8,
            'index_seq' => 7,
            'index_number' => 'TIT/08/007',
            'medium' => 'English',
            'registration_status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.students.index-lists', ['grade' => 'Grade 8']));
        $response->assertStatus(200);

        $englishStudents = $response->viewData('englishStudents');
        $this->assertCount(3, $englishStudents);
        // Numerical order: 7 -> 550 -> 1000 (NOT string order where 1000 comes before 550)
        $this->assertEquals('TIT/08/007', $englishStudents[0]->index_number);
        $this->assertEquals('TIT/08/550', $englishStudents[1]->index_number);
        $this->assertEquals('TIT/08/1000', $englishStudents[2]->index_number);
    }

    /**
     * 10. Re-assign dialog via controller passes running number and advances counter.
     */
    public function test_reassign_via_controller_with_exact_seq(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'index_number' => 'TIT/08/001',
            'batch_year' => 8,
            'index_seq' => 1,
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.students.reassign-index', $student->id), [
            'batch_year' => '08',
            'running_number' => 550,
            'reason' => 'Admin manual correction',
        ]);

        $response->assertSessionHas('success');
        $student->refresh();
        $this->assertEquals('TIT/08/550', $student->index_number);
        $this->assertEquals(550, $student->index_seq);

        $log = ActivityLog::where('action', 'student_index_reassigned')->first();
        $this->assertNotNull($log);
        $this->assertEquals('TIT/08/001', $log->metadata['old_index_number']);
        $this->assertEquals('TIT/08/550', $log->metadata['new_index_number']);
    }
}

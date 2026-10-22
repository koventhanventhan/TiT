<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\SiteSetting;
use App\Models\StudentIndexCounter;
use App\Models\User;
use App\Services\StudentIndexService;
use App\Services\StudentPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentIndexBulkTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private Institute $otherInstitute;
    private User $admin;
    private User $otherAdmin;
    private User $regularUser;
    private StudentIndexService $indexService;

    protected function setUp(): void
    {
        parent::setUp();

        // Institute 1
        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit',
            'status' => 'active',
            'index_prefix' => 'TiT',
            'index_seq_start' => 100,
        ]);

        // Institute 2
        $this->otherInstitute = Institute::create([
            'id' => 2,
            'name' => 'Apex Academy',
            'slug' => 'apex',
            'status' => 'active',
            'index_prefix' => 'APEX',
            'index_seq_start' => 100,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => 1,
            'email' => 'admin@tit.lk',
        ]);

        $this->otherAdmin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => 2,
            'email' => 'admin@apex.lk',
        ]);

        $this->regularUser = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'email' => 'student@tit.lk',
            'index_number' => 'TiT/20/001',
        ]);

        $this->indexService = app(StudentIndexService::class);
    }

    /**
     * 1. Mapping save/load (validation 20-99, per institute).
     */
    public function test_mapping_save_load_validation_and_institute_isolation(): void
    {
        // Validation: batch outside 20-99 rejected
        $res = $this->actingAs($this->admin)->post(route('admin.settings.index-batches.store'), [
            'batches' => [
                '10' => 19, // invalid (< 20)
                '11' => 105, // invalid (> 99)
            ],
        ]);
        $res->assertSessionHasErrors(['batches.10', 'batches.11']);

        // Valid mapping for Institute 1
        $res1 = $this->actingAs($this->admin)->post(route('admin.settings.index-batches.store'), [
            'batches' => [
                '10' => 28,
                '11' => 27,
                'Grade 12' => 26,
            ],
        ]);
        $res1->assertSessionHas('success');

        $map1 = $this->indexService->getGradeBatchMapping(1);
        $this->assertEquals(28, $map1['10']);
        $this->assertEquals(27, $map1['11']);
        $this->assertEquals(26, $map1['Grade 12']);

        // Valid mapping for Institute 2
        $res2 = $this->actingAs($this->otherAdmin)->post(route('admin.settings.index-batches.store'), [
            'batches' => [
                '10' => 30,
                '11' => 29,
            ],
        ]);
        $res2->assertSessionHas('success');

        $map2 = $this->indexService->getGradeBatchMapping(2);
        $this->assertEquals(30, $map2['10']);
        $this->assertEquals(29, $map2['11']);

        // Isolation: Institute 1 still has its original mapping
        $map1Reloaded = $this->indexService->getGradeBatchMapping(1);
        $this->assertEquals(28, $map1Reloaded['10']);
    }

    /**
     * 2. Confirm student auto-assigns index number from mapping with no admin input.
     */
    public function test_confirm_student_auto_assigns_index_from_mapping_with_no_admin_input(): void
    {
        $this->indexService->setGradeBatchMapping(['10' => 28], 1);

        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'full_name' => 'Koventhan Test',
            'current_grade' => '10',
            'admin_confirmed_at' => null,
            'registration_status' => 'pending',
            'index_number' => null,
        ]);

        // Admin confirms with NO batch_year input
        $res = $this->actingAs($this->admin)->post(route('admin.students.confirm', $student->id));
        $res->assertSessionHas('success');

        $student->refresh();
        $this->assertNotNull($student->admin_confirmed_at);
        $this->assertEquals('confirmed', $student->registration_status);
        $this->assertEquals('TiT/28/100', $student->index_number);
        $this->assertEquals(28, $student->batch_year);
        $this->assertEquals(100, $student->index_seq);
    }

    /**
     * 3. Confirm student with unmapped grade succeeds without number and flashes warning.
     */
    public function test_confirm_student_with_unmapped_grade_succeeds_without_number_and_flashes_warning(): void
    {
        $this->indexService->setGradeBatchMapping(['10' => 28], 1); // Grade 7 unmapped

        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'full_name' => 'Unmapped Student',
            'current_grade' => 'Grade 7',
            'admin_confirmed_at' => null,
            'registration_status' => 'pending',
            'index_number' => null,
        ]);

        $res = $this->actingAs($this->admin)->post(route('admin.students.confirm', $student->id));
        $res->assertSessionHas('warning', 'No batch year for Grade Grade 7 – set it in Index Number Batches, the number was not assigned');

        $student->refresh();
        $this->assertNotNull($student->admin_confirmed_at);
        $this->assertEquals('confirmed', $student->registration_status);
        $this->assertNull($student->index_number);
    }

    /**
     * 4. Confirm student batch input can override mapping.
     */
    public function test_confirm_student_batch_input_overrides_mapping(): void
    {
        $this->indexService->setGradeBatchMapping(['10' => 28], 1);

        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'full_name' => 'Override Student',
            'current_grade' => '10',
            'admin_confirmed_at' => null,
            'registration_status' => 'pending',
            'index_number' => null,
        ]);

        $res = $this->actingAs($this->admin)->post(route('admin.students.confirm', $student->id), [
            'batch_year' => '29',
        ]);
        $res->assertSessionHas('success');

        $student->refresh();
        $this->assertEquals('TiT/29/100', $student->index_number);
        $this->assertEquals(29, $student->batch_year);
    }

    /**
     * 5. Unconfirmed, rejected, or abandoned registrations never burn index numbers.
     */
    public function test_rejected_or_unconfirmed_students_never_burn_numbers(): void
    {
        $unconfirmed = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'admin_confirmed_at' => null,
            'registration_status' => 'pending',
            'index_number' => null,
        ]);

        $rejected = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'admin_confirmed_at' => null,
            'registration_status' => 'rejected',
            'index_number' => null,
        ]);

        $counter = StudentIndexCounter::where('institute_id', 1)->where('batch_year', 28)->first();
        $this->assertNull($counter);
        $this->assertNull($unconfirmed->index_number);
        $this->assertNull($rejected->index_number);
    }

    /**
     * 6. Promotion does NOT change index number or batch year (permanent).
     */
    public function test_promotion_does_not_change_index_number_or_batch_year(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
            'index_number' => 'TiT/28/100',
            'batch_year' => 28,
            'index_seq' => 100,
            'last_promoted_at' => now()->subMonths(7),
        ]);

        // Promote student via promotion service
        $promotionService = app(StudentPromotionService::class);
        $promotionService->promoteStudents([$student->id], true);

        $student->refresh();
        $this->assertEquals(11, $student->getNumericGrade());
        $this->assertEquals('TiT/28/100', $student->index_number);
        $this->assertEquals(28, $student->batch_year);
        $this->assertEquals(100, $student->index_seq);
    }

    /**
     * 7. Preview == Real run for 250 seeded students over 3 grades.
     */
    public function test_preview_matches_real_run_for_250_seeded_students_over_3_grades(): void
    {
        $this->indexService->setGradeBatchMapping([
            '10' => 28,
            '11' => 27,
            '12' => 26,
        ], 1);

        // Seed 100 in Grade 10, 80 in Grade 11, 70 in Grade 12 = 250 total
        $grades = [
            '10' => 100,
            '11' => 80,
            '12' => 70,
        ];

        $studentIds = [];
        $time = now()->subDays(300);

        foreach ($grades as $grade => $count) {
            for ($i = 1; $i <= $count; $i++) {
                $time = $time->addMinute();
                $u = User::factory()->create([
                    'role' => 'user',
                    'institute_id' => 1,
                    'full_name' => "Student {$grade}_{$i}",
                    'current_grade' => $grade,
                    'admin_confirmed_at' => now(),
                    'registration_status' => 'confirmed',
                    'index_number' => null,
                    'created_at' => $time,
                ]);
                $studentIds[] = $u->id;
            }
        }

        // Call Preview endpoint
        $previewRes = $this->actingAs($this->admin)->postJson(route('admin.students.assign-index-tool.preview'), [
            'batch_source' => 'mapping',
            'order' => 'registered',
        ]);

        $previewRes->assertStatus(200);
        $previewData = $previewRes->json();

        $this->assertEquals(250, $previewData['total_students']);
        $this->assertEquals(0, $previewData['skipped_count']);
        $this->assertCount(3, $previewData['grade_summaries']);

        // Check preview ranges
        $summaryMap = collect($previewData['grade_summaries'])->keyBy('grade');
        $this->assertEquals('TiT/28/100', $summaryMap['10']['first_index']);
        $this->assertEquals('TiT/28/199', $summaryMap['10']['last_index']);
        $this->assertEquals('TiT/27/100', $summaryMap['11']['first_index']);
        $this->assertEquals('TiT/27/179', $summaryMap['11']['last_index']);
        $this->assertEquals('TiT/26/100', $summaryMap['12']['first_index']);
        $this->assertEquals('TiT/26/169', $summaryMap['12']['last_index']);

        // Run the real assignment in chunks of 100
        $assignedRows = [];
        $excludedIds = [];
        $totalAssigned = 0;

        while (true) {
            $chunkRes = $this->actingAs($this->admin)->postJson(route('admin.students.assign-index-tool.run-chunk'), [
                'batch_source' => 'mapping',
                'order' => 'registered',
                'chunk_size' => 100,
                'exclude_ids' => $excludedIds,
            ]);

            $chunkRes->assertStatus(200);
            $chunkData = $chunkRes->json();

            $totalAssigned += $chunkData['assigned_in_chunk'];
            foreach ($chunkData['assigned_list'] as $item) {
                $assignedRows[$item['student_id']] = $item['index_number'];
            }
            $excludedIds = array_merge($excludedIds, $chunkData['newly_excluded_ids'] ?? []);

            if ($chunkData['done'] || $chunkData['remaining'] === 0) {
                break;
            }
        }

        $this->assertEquals(250, $totalAssigned);

        // Assert EVERY single preview row matches the real assigned number
        foreach ($previewData['preview_rows'] as $pRow) {
            $this->assertArrayHasKey($pRow['student_id'], $assignedRows);
            $this->assertEquals($pRow['index_number'], $assignedRows[$pRow['student_id']]);
        }
    }

    /**
     * 8. Numbering order inside sequence: Registration order vs Alphabetical.
     */
    public function test_numbering_order_registration_vs_alphabetical(): void
    {
        $this->indexService->setGradeBatchMapping(['10' => 28], 1);

        $studentZack = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'full_name' => 'Zack Student',
            'current_grade' => '10',
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
            'created_at' => now()->subDays(5),
            'index_number' => null,
        ]);

        $studentAlice = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'full_name' => 'Alice Student',
            'current_grade' => '10',
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
            'created_at' => now()->subDays(1),
            'index_number' => null,
        ]);

        // Preview with registration order (created_at asc)
        $previewReg = $this->indexService->previewBulkAssign(
            [$studentZack, $studentAlice],
            1,
            'mapping',
            null,
            ['10' => 28]
        );
        $this->assertEquals('TiT/28/100', $previewReg['preview_rows'][0]['index_number']);
        $this->assertEquals($studentZack->id, $previewReg['preview_rows'][0]['student_id']);
        $this->assertEquals('TiT/28/101', $previewReg['preview_rows'][1]['index_number']);
        $this->assertEquals($studentAlice->id, $previewReg['preview_rows'][1]['student_id']);

        // Preview with alphabetical order (Alice first)
        $previewAlpha = $this->indexService->previewBulkAssign(
            [$studentAlice, $studentZack],
            1,
            'mapping',
            null,
            ['10' => 28]
        );
        $this->assertEquals('TiT/28/100', $previewAlpha['preview_rows'][0]['index_number']);
        $this->assertEquals($studentAlice->id, $previewAlpha['preview_rows'][0]['student_id']);
        $this->assertEquals('TiT/28/101', $previewAlpha['preview_rows'][1]['index_number']);
        $this->assertEquals($studentZack->id, $previewAlpha['preview_rows'][1]['student_id']);
    }

    /**
     * 9. Grades without mapping are skipped, pending/rejected/deactivated rules enforced.
     */
    public function test_grades_without_mapping_skipped_and_status_rules_enforced(): void
    {
        $this->indexService->setGradeBatchMapping(['10' => 28], 1);

        $confirmed10 = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
        ]);

        $confirmed7 = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '7',
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
        ]);

        $pending = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'admin_confirmed_at' => null,
            'registration_status' => 'pending',
        ]);

        $rejected = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'admin_confirmed_at' => null,
            'registration_status' => 'rejected',
        ]);

        $deactivated = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
            'deactivated_at' => now(),
        ]);

        // Preview without including deactivated
        $previewRes = $this->actingAs($this->admin)->postJson(route('admin.students.assign-index-tool.preview'), [
            'batch_source' => 'mapping',
            'include_deactivated' => 0,
        ]);

        $previewData = $previewRes->json();
        // Only confirmed10 should be in preview rows; confirmed7 should be skipped
        $this->assertEquals(1, $previewData['total_students']);
        $this->assertEquals(1, $previewData['skipped_count']);
        $this->assertEquals($confirmed10->id, $previewData['preview_rows'][0]['student_id']);
        $this->assertEquals($confirmed7->id, $previewData['skipped'][0]['student_id']);

        // Now include deactivated
        $previewWithDeactivated = $this->actingAs($this->admin)->postJson(route('admin.students.assign-index-tool.preview'), [
            'batch_source' => 'mapping',
            'include_deactivated' => 1,
        ]);
        $this->assertEquals(2, $previewWithDeactivated->json('total_students'));
    }

    /**
     * 10. Chunked run: resume assigns everyone exactly once, idempotent, no duplicates.
     */
    public function test_chunked_run_resumption_and_idempotence(): void
    {
        $this->indexService->setGradeBatchMapping(['10' => 28], 1);

        for ($i = 1; $i <= 35; $i++) {
            User::factory()->create([
                'role' => 'user',
                'institute_id' => 1,
                'current_grade' => '10',
                'admin_confirmed_at' => now(),
                'registration_status' => 'confirmed',
                'index_number' => null,
            ]);
        }

        // Chunk 1: 15 students
        $chunk1 = $this->actingAs($this->admin)->postJson(route('admin.students.assign-index-tool.run-chunk'), [
            'batch_source' => 'mapping',
            'chunk_size' => 15,
        ]);
        $chunk1->assertStatus(200);
        $this->assertEquals(15, $chunk1->json('assigned_in_chunk'));
        $this->assertEquals(20, $chunk1->json('remaining'));
        $this->assertFalse($chunk1->json('done'));

        // Chunk 2: 15 students
        $chunk2 = $this->actingAs($this->admin)->postJson(route('admin.students.assign-index-tool.run-chunk'), [
            'batch_source' => 'mapping',
            'chunk_size' => 15,
        ]);
        $chunk2->assertStatus(200);
        $this->assertEquals(15, $chunk2->json('assigned_in_chunk'));
        $this->assertEquals(5, $chunk2->json('remaining'));

        // Chunk 3 (final): 5 students
        $chunk3 = $this->actingAs($this->admin)->postJson(route('admin.students.assign-index-tool.run-chunk'), [
            'batch_source' => 'mapping',
            'chunk_size' => 15,
        ]);
        $chunk3->assertStatus(200);
        $this->assertEquals(5, $chunk3->json('assigned_in_chunk'));
        $this->assertEquals(0, $chunk3->json('remaining'));
        $this->assertTrue($chunk3->json('done'));

        // Calling again assigns nothing
        $chunk4 = $this->actingAs($this->admin)->postJson(route('admin.students.assign-index-tool.run-chunk'), [
            'batch_source' => 'mapping',
            'chunk_size' => 15,
        ]);
        $chunk4->assertStatus(200);
        $this->assertEquals(0, $chunk4->json('assigned_in_chunk'));
        $this->assertTrue($chunk4->json('done'));

        // Verify all 35 have unique index numbers
        $numbers = User::where('role', 'user')->where('institute_id', 1)->where('current_grade', '10')->whereNotNull('index_number')->pluck('index_number')->all();
        $this->assertCount(35, $numbers);
        $this->assertCount(35, array_unique($numbers));
    }

    /**
     * 11. CLI --dry-run writes nothing, --map works, no batch skipped with clear message (no current-year fallback).
     */
    public function test_cli_dry_run_map_and_no_fallback_to_current_year(): void
    {
        $student10 = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
            'index_number' => null,
        ]);

        $studentUnmapped = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '5',
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
            'index_number' => null,
        ]);

        // Dry Run with inline map
        $exitCode = Artisan::call('students:assign-index', [
            '--dry-run' => true,
            '--map' => '10=28',
            '--institute' => 1,
        ]);
        $this->assertEquals(0, $exitCode);

        // Dry run writes NOTHING
        $this->assertNull($student10->fresh()->index_number);
        $this->assertNull($studentUnmapped->fresh()->index_number);

        // Real CLI run with map
        $exitReal = Artisan::call('students:assign-index', [
            '--map' => '10=28',
            '--institute' => 1,
        ]);
        $this->assertEquals(0, $exitReal);

        // Grade 10 assigned TiT/28/100
        $this->assertEquals('TiT/28/100', $student10->fresh()->index_number);

        // Grade 5 has NO batch year -> SKIPPED (no fallback to current year date('y')!)
        $this->assertNull($studentUnmapped->fresh()->index_number);
    }

    /**
     * 12. CSV import: validation, counter moved ahead, and next auto number never collides.
     */
    public function test_csv_import_validation_and_counter_advancement(): void
    {
        $studentA = User::factory()->create([
            'id' => 501,
            'role' => 'user',
            'institute_id' => 1,
            'name' => 'student_a',
            'email' => 'student_a@tit.lk',
            'index_number' => null,
        ]);

        $studentB = User::factory()->create([
            'id' => 502,
            'role' => 'user',
            'institute_id' => 1,
            'name' => 'student_b',
            'email' => 'student_b@tit.lk',
            'index_number' => null,
        ]);

        $studentAlreadyAssigned = User::factory()->create([
            'id' => 503,
            'role' => 'user',
            'institute_id' => 1,
            'name' => 'student_c',
            'email' => 'student_c@tit.lk',
            'index_number' => 'TiT/28/050',
            'batch_year' => 28,
            'index_seq' => 50,
        ]);

        $rawRows = [
            // Valid row matching by student_id
            ['student_id' => '501', 'index_number' => 'TiT/28/150'],
            // Valid row matching by email
            ['email' => 'student_b@tit.lk', 'index_number' => 'TiT/28/180'],
            // Invalid format
            ['student_id' => '501', 'index_number' => 'INVALID_FORMAT'],
            // Already assigned student
            ['student_id' => '503', 'index_number' => 'TiT/28/190'],
            // Duplicate index inside file
            ['email' => 'nonexistent@tit.lk', 'index_number' => 'TiT/28/150'],
        ];

        $validation = $this->indexService->previewAndValidateImport($rawRows, 1);

        $this->assertEquals(5, $validation['total']);
        $this->assertEquals(2, $validation['valid_count']);
        $this->assertEquals(3, $validation['error_count']);
        $this->assertTrue($validation['has_errors']);

        // Execute import with abortOnError = false (import valid only)
        $importResult = $this->indexService->executeImport($validation['rows'], 1, false);

        $this->assertEquals(2, $importResult['imported_count']);
        $this->assertEquals(3, $importResult['skipped_count']);

        $studentA->refresh();
        $studentB->refresh();
        $this->assertEquals('TiT/28/150', $studentA->index_number);
        $this->assertEquals(150, $studentA->index_seq);
        $this->assertEquals('TiT/28/180', $studentB->index_number);
        $this->assertEquals(180, $studentB->index_seq);

        // Counter MUST have advanced ahead to 180!
        $counter = StudentIndexCounter::where('institute_id', 1)->where('batch_year', 28)->first();
        $this->assertNotNull($counter);
        $this->assertEquals(180, $counter->last_seq);

        // The next auto-generated index number MUST be TiT/28/181 (never collides with imported 150 or 180!)
        $studentNext = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'index_number' => null,
        ]);

        $nextAssigned = $this->indexService->assign($studentNext, 28);
        $this->assertEquals('TiT/28/181', $nextAssigned);
    }

    /**
     * 13. CSV import CLI command --dry-run and file execution.
     */
    public function test_csv_import_cli(): void
    {
        $student = User::factory()->create([
            'id' => 601,
            'role' => 'user',
            'institute_id' => 1,
            'name' => 'cli_student',
            'index_number' => null,
        ]);

        $csvContent = "student_id,index_number\n601,TiT/28/220\n";
        $tmpFile = tempnam(sys_get_temp_dir(), 'idx_test_') . '.csv';
        file_put_contents($tmpFile, $csvContent);

        // Dry run
        $exitDry = Artisan::call('students:import-index', [
            'file' => $tmpFile,
            '--dry-run' => true,
            '--institute' => 1,
        ]);
        $this->assertEquals(0, $exitDry);
        $this->assertNull($student->fresh()->index_number);

        // Real import
        $exitReal = Artisan::call('students:import-index', [
            'file' => $tmpFile,
            '--institute' => 1,
        ]);
        $this->assertEquals(0, $exitReal);
        $this->assertEquals('TiT/28/220', $student->fresh()->index_number);

        @unlink($tmpFile);
    }

    /**
     * 14. Non-admin and other-institute access denied.
     */
    public function test_authorization_checks(): void
    {
        // Regular user denied
        $res = $this->actingAs($this->regularUser)->get(route('admin.students.assign-index-tool'));
        $this->assertTrue($res->isRedirect() || $res->status() === 403);

        $previewRes = $this->actingAs($this->regularUser)->postJson(route('admin.students.assign-index-tool.preview'));
        $previewRes->assertStatus(403);

        // Other-institute admin cannot see or assign Institute 1 students
        $studentInst1 = User::factory()->create([
            'role' => 'user',
            'institute_id' => 1,
            'current_grade' => '10',
            'admin_confirmed_at' => now(),
            'registration_status' => 'confirmed',
            'index_number' => null,
        ]);

        $this->indexService->setGradeBatchMapping(['10' => 30], 2);

        $previewOther = $this->actingAs($this->otherAdmin)->postJson(route('admin.students.assign-index-tool.preview'), [
            'batch_source' => 'mapping',
        ]);
        $previewOther->assertStatus(200);
        // Institute 2 has 0 students
        $this->assertEquals(0, $previewOther->json('total_students'));
    }

    /**
     * 15. Performance sanity test: 1000 seeded students assigned in chunks of 100 finishes < 60s.
     */
    public function test_performance_sanity_1000_students_assigned_in_chunks_of_100(): void
    {
        $this->indexService->setGradeBatchMapping(['10' => 28], 1);

        $now = now()->toDateTimeString();
        $userData = [];

        for ($i = 1; $i <= 1000; $i++) {
            $userData[] = [
                'name' => "speed_student_{$i}",
                'email' => "speed_{$i}@tit.lk",
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
                'role' => 'user',
                'institute_id' => 1,
                'full_name' => "Speed Student {$i}",
                'current_grade' => '10',
                'registration_status' => 'confirmed',
                'admin_confirmed_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Bulk insert 1000 students in 2 batches of 500
        foreach (array_chunk($userData, 500) as $chunk) {
            User::insert($chunk);
        }

        $startTime = microtime(true);
        $totalAssigned = 0;
        $excludedIds = [];

        while (true) {
            $chunkRes = $this->actingAs($this->admin)->postJson(route('admin.students.assign-index-tool.run-chunk'), [
                'batch_source' => 'mapping',
                'chunk_size' => 100,
                'exclude_ids' => $excludedIds,
            ]);

            $chunkRes->assertStatus(200);
            $chunkData = $chunkRes->json();
            $totalAssigned += $chunkData['assigned_in_chunk'];
            $excludedIds = array_merge($excludedIds, $chunkData['newly_excluded_ids'] ?? []);

            if ($chunkData['done'] || $chunkData['remaining'] === 0) {
                break;
            }
        }

        $elapsed = microtime(true) - $startTime;

        $this->assertEquals(1000, $totalAssigned);
        $this->assertLessThan(60.0, $elapsed, "Bulk assign of 1000 students should take < 60s, took {$elapsed}s");

        // Verify sequence spans exactly from 100 to 1099
        $counter = StudentIndexCounter::where('institute_id', 1)->where('batch_year', 28)->first();
        $this->assertEquals(1099, $counter->last_seq);
    }
}

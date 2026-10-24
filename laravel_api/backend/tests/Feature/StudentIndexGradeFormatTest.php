<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\StudentIndexCounter;
use App\Models\User;
use App\Services\StudentIndexService;
use App\Services\StudentPromotionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StudentIndexGradeFormatTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $admin;
    private StudentIndexService $indexService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit',
            'status' => 'active',
            'index_prefix' => 'TIT',
            'index_seq_start' => 1,
            'index_format' => 'grade',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => 1,
            'email' => 'admin@tit.lk',
        ]);

        $this->indexService = app(StudentIndexService::class);
    }

    /**
     * 1. Sequential numbering in grade format starts at 001 and pads to at least 3 digits.
     */
    public function test_grade_format_sequential_assignment_starts_at_001(): void
    {
        $student1 = User::factory()->create([
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'medium' => 'English',
        ]);

        $student2 = User::factory()->create([
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'medium' => 'English',
        ]);

        $index1 = $this->indexService->assign($student1);
        $index2 = $this->indexService->assign($student2);

        $this->assertEquals('TIT/08/001', $index1);
        $this->assertEquals('TIT/08/002', $index2);
        $this->assertEquals(8, $student1->fresh()->batch_year);
        $this->assertEquals(1, $student1->fresh()->index_seq);
        $this->assertEquals(2, $student2->fresh()->index_seq);
    }

    /**
     * 2. Independent counter per grade series.
     */
    public function test_independent_counter_per_grade(): void
    {
        $studentG8_1 = User::factory()->create(['institute_id' => 1, 'current_grade' => 'Grade 8']);
        $studentG10_1 = User::factory()->create(['institute_id' => 1, 'current_grade' => 'Grade 10']);
        $studentG8_2 = User::factory()->create(['institute_id' => 1, 'current_grade' => 'Grade 8']);

        $idx8_1 = $this->indexService->assign($studentG8_1);
        $idx10_1 = $this->indexService->assign($studentG10_1);
        $idx8_2 = $this->indexService->assign($studentG8_2);

        $this->assertEquals('TIT/08/001', $idx8_1);
        $this->assertEquals('TIT/10/001', $idx10_1);
        $this->assertEquals('TIT/08/002', $idx8_2);

        $counter8 = StudentIndexCounter::where('institute_id', 1)->where('batch_year', 8)->first();
        $counter10 = StudentIndexCounter::where('institute_id', 1)->where('batch_year', 10)->first();
        $this->assertEquals(2, $counter8->last_seq);
        $this->assertEquals(1, $counter10->last_seq);
    }

    /**
     * 3. English and Tamil students share the same grade series.
     */
    public function test_english_and_tamil_share_same_grade_series(): void
    {
        $studentEng = User::factory()->create(['institute_id' => 1, 'current_grade' => 'Grade 8', 'medium' => 'English']);
        $studentTam = User::factory()->create(['institute_id' => 1, 'current_grade' => 'Grade 8', 'medium' => 'Tamil']);

        $idxEng = $this->indexService->assign($studentEng);
        $idxTam = $this->indexService->assign($studentTam);

        $this->assertEquals('TIT/08/001', $idxEng);
        $this->assertEquals('TIT/08/002', $idxTam);
    }

    /**
     * 4. 'both' medium student gets a single permanent number.
     */
    public function test_both_medium_student_receives_single_number(): void
    {
        $studentBoth = User::factory()->create([
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'medium' => 'both',
        ]);

        $idx1 = $this->indexService->assign($studentBoth);
        $idx2 = $this->indexService->assign($studentBoth);

        $this->assertEquals('TIT/08/001', $idx1);
        $this->assertEquals($idx1, $idx2);
    }

    /**
     * 5. Promotion keeps the index number and does not change it.
     */
    public function test_promotion_preserves_permanent_index_number(): void
    {
        $student = User::factory()->create([
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'medium' => 'English',
        ]);

        $idx = $this->indexService->assign($student);
        $this->assertEquals('TIT/08/001', $idx);

        // Promote to Grade 9
        $promotionService = app(StudentPromotionService::class);
        $promotionService->promoteStudents([$student->id], true, $this->admin->id);

        $student->refresh();
        $this->assertEquals('Grade 9', $student->current_grade);
        $this->assertEquals('TIT/08/001', $student->index_number);
        $this->assertEquals(8, $student->batch_year);
    }

    /**
     * 6. Import of owner's 65-row fixture with name matching and advances counter to 065.
     */
    public function test_csv_import_of_owner_fixture_with_name_matching_and_advances_counter(): void
    {
        $fixturePath = base_path('tests/Fixtures/grade08-index-import.csv');
        $this->assertFileExists($fixturePath);

        // Seed students matching the CSV names
        $lines = file($fixturePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        array_shift($lines); // header
        foreach ($lines as $line) {
            $parts = str_getcsv($line);
            $name = trim($parts[2] ?? '');
            if ($name !== '-' && $name !== '—') {
                User::factory()->create([
                    'institute_id' => 1,
                    'full_name' => $name,
                    'current_grade' => trim($parts[0] ?? 'Grade 8'),
                    'medium' => trim($parts[1] ?? 'English'),
                    'registration_status' => 'confirmed',
                ]);
            }
        }

        $exitCode = Artisan::call('students:import-index', [
            'file' => $fixturePath,
            '--institute' => 1,
        ]);
        $this->assertEquals(0, $exitCode);

        // Check assigned numbers
        $s1 = User::where('full_name', 'A. Abinayan')->first();
        $s2 = User::where('full_name', 'B. Bavithran')->first();
        $s65 = User::where('full_name', 'S. Sivaranjan')->first();
        $this->assertNotNull($s1);
        $this->assertNotNull($s2);
        $this->assertNotNull($s65);
        $this->assertEquals('TIT/08/001', $s1->index_number);
        $this->assertEquals('TIT/08/002', $s2->index_number);
        $this->assertEquals('TIT/08/065', $s65->index_number);

        // Counter for Grade 8 must have advanced to 65
        $counter8 = StudentIndexCounter::where('institute_id', 1)->where('batch_year', 8)->first();
        $this->assertNotNull($counter8);
        $this->assertEquals(65, $counter8->last_seq);

        // Next student assigned receives TIT/08/066
        $newStudent = User::factory()->create([
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'medium' => 'English',
        ]);
        $newIndex = $this->indexService->assign($newStudent);
        $this->assertEquals('TIT/08/066', $newIndex);
    }

    /**
     * 7. Index lists view and PDF export route.
     */
    public function test_index_lists_admin_view_and_pdf_download(): void
    {
        $engStudent = User::factory()->create([
            'institute_id' => 1,
            'full_name' => 'K. Kumar',
            'current_grade' => 'Grade 8',
            'medium' => 'English',
            'index_number' => 'TIT/08/001',
            'registration_status' => 'confirmed',
        ]);

        $tamStudent = User::factory()->create([
            'institute_id' => 1,
            'full_name' => 'S. Siva',
            'current_grade' => 'Grade 8',
            'medium' => 'Tamil',
            'index_number' => 'TIT/08/002',
            'registration_status' => 'confirmed',
        ]);

        $bothStudent = User::factory()->create([
            'institute_id' => 1,
            'full_name' => 'R. Ravi',
            'current_grade' => 'Grade 8',
            'medium' => 'both',
            'index_number' => 'TIT/08/003',
            'registration_status' => 'confirmed',
        ]);

        // 7a. Web view
        $viewRes = $this->actingAs($this->admin)->get(route('admin.students.index-lists', ['grade' => 'Grade 8']));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('TIT/08/001');
        $viewRes->assertSee('TIT/08/002');
        $viewRes->assertSee('TIT/08/003');
        $viewRes->assertSee('K. Kumar');
        $viewRes->assertSee('S. Siva');
        $viewRes->assertSee('R. Ravi');

        // 7b. PDF export
        $pdfRes = $this->actingAs($this->admin)->get(route('admin.students.index-lists.pdf', ['grade' => 'Grade 8', 'medium' => 'english']));
        $pdfRes->assertStatus(200);
        $this->assertEquals('application/pdf', $pdfRes->headers->get('Content-Type'));
        $content = $pdfRes->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
    }

    /**
     * 8. Admin edit page assign & reassign actions return 302 with flash and update number.
     */
    public function test_admin_edit_page_assign_and_reassign_actions(): void
    {
        $student = User::factory()->create([
            'institute_id' => 1,
            'current_grade' => 'Grade 8',
            'medium' => 'English',
            'registration_status' => 'confirmed',
        ]);

        // Assign
        $assignRes = $this->actingAs($this->admin)->post(route('admin.students.assign-index', $student->id), [
            'batch_year' => '08',
        ]);
        $assignRes->assertStatus(302);
        $assignRes->assertSessionHas('success');
        $this->assertEquals('TIT/08/001', $student->fresh()->index_number);

        // Reassign
        $reassignRes = $this->actingAs($this->admin)->post(route('admin.students.reassign-index', $student->id), [
            'batch_year' => '08',
            'reason' => 'Administrative correction',
        ]);
        $reassignRes->assertStatus(302);
        $reassignRes->assertSessionHas('success');
        $this->assertEquals('TIT/08/002', $student->fresh()->index_number);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\StudentIndexCounter;
use App\Models\User;
use App\Services\StudentIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class StudentIndexPartATest extends TestCase
{
    use RefreshDatabase;

    protected StudentIndexService $service;
    protected Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'TiT Institute',
                'slug' => 'tit',
                'index_format' => 'grade',
                'index_prefix' => 'TIT',
                'index_seq_start' => 1,
            ]
        );

        $this->service = app(StudentIndexService::class);
    }

    public function test_normalize_grade_series_accepts_1_to_99_and_pads(): void
    {
        $this->assertEquals('01', $this->service->normalizeGradeSeries(1));
        $this->assertEquals('01', $this->service->normalizeGradeSeries('01'));
        $this->assertEquals('08', $this->service->normalizeGradeSeries(8));
        $this->assertEquals('08', $this->service->normalizeGradeSeries('Grade 8'));
        $this->assertEquals('11', $this->service->normalizeGradeSeries(11));
        $this->assertEquals('13', $this->service->normalizeGradeSeries('13'));
        $this->assertEquals('26', $this->service->normalizeGradeSeries(26));
        $this->assertEquals('58', $this->service->normalizeGradeSeries('58'));
        $this->assertEquals('99', $this->service->normalizeGradeSeries(99));
    }

    public function test_normalize_grade_series_rejects_out_of_range_or_invalid(): void
    {
        $invalidInputs = [0, '0', '00', 100, '100', -1, 'abc', '', null, 2026];

        foreach ($invalidInputs as $invalid) {
            try {
                $this->service->normalizeGradeSeries($invalid);
                $this->fail("Expected InvalidArgumentException for input: " . var_export($invalid, true));
            } catch (\InvalidArgumentException $e) {
                $this->assertEquals("Series must be a number 01–99", $e->getMessage());
            }
        }
    }

    public function test_derive_grade_series_only_accepts_grades_1_to_13(): void
    {
        $this->assertEquals('08', $this->service->deriveGradeSeries('Grade 8'));
        $this->assertEquals('11', $this->service->deriveGradeSeries('Grade 11'));
        $this->assertEquals('13', $this->service->deriveGradeSeries('Grade 13'));

        $invalidDerives = ['26', '2026', 'Grade 58', 'A/L', '', null];
        foreach ($invalidDerives as $inv) {
            $this->expectException(\InvalidArgumentException::class);
            $this->service->deriveGradeSeries($inv);
        }
    }

    public function test_assign_with_explicit_series_outside_13_succeeds_in_grade_mode(): void
    {
        $student = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'current_grade' => 'Grade 8',
            'index_number' => null,
        ]);

        $assigned = $this->service->assign($student, 58);
        $this->assertEquals('TIT/58/001', $assigned);
        $this->assertEquals('TIT/58/001', $student->fresh()->index_number);
        $this->assertEquals(58, $student->fresh()->batch_year);
        $this->assertEquals(1, $student->fresh()->index_seq);
    }

    public function test_batch_mode_accepts_01_to_99_and_pads_to_two_digits(): void
    {
        $this->assertEquals(8, $this->service->normalizeBatchYear(8));
        $this->assertEquals(8, $this->service->normalizeBatchYear('08'));
        $this->assertEquals(58, $this->service->normalizeBatchYear(58));

        $batchInst = Institute::create([
            'id' => 2,
            'name' => 'Legacy Batch Institute',
            'slug' => 'legacy-batch',
            'index_format' => 'batch',
            'index_prefix' => 'TiT',
            'index_seq_start' => 100,
        ]);

        $student = User::factory()->create([
            'institute_id' => $batchInst->id,
            'role' => 'user',
            'current_grade' => 'Grade 8',
            'index_number' => null,
        ]);

        $assigned = $this->service->assign($student, 8);
        $this->assertEquals('TiT/08/100', $assigned);
    }

    public function test_shared_validation_helpers(): void
    {
        $this->assertTrue(StudentIndexService::isValidIndexNumber('TIT/11/550', 'TIT'));
        $this->assertTrue(StudentIndexService::isValidIndexNumber('TIT/58/110', 'TIT'));
        $this->assertTrue(StudentIndexService::isValidIndexNumber('TIT/26/001', 'TIT'));
        $this->assertTrue(StudentIndexService::isValidIndexNumber('TIT/08/001', 'TIT'));
        $this->assertTrue(StudentIndexService::isValidIndexNumber('TIT/99/9999', 'TIT'));

        // Case-sensitive prefix mismatch
        $this->assertFalse(StudentIndexService::isValidIndexNumber('TiT/11/550', 'TIT'));
        // Single-digit series
        $this->assertFalse(StudentIndexService::isValidIndexNumber('TIT/8/110', 'TIT'));
        // Less than 3-digit sequence
        $this->assertFalse(StudentIndexService::isValidIndexNumber('TIT/08/11', 'TIT'));
        // Series 00
        $this->assertFalse(StudentIndexService::isValidIndexNumber('TIT/00/100', 'TIT'));
        // Non-numeric
        $this->assertFalse(StudentIndexService::isValidIndexNumber('TIT/XX/100', 'TIT'));
        $this->assertFalse(StudentIndexService::isValidIndexNumber('', 'TIT'));
        $this->assertFalse(StudentIndexService::isValidIndexNumber(null, 'TIT'));

        // isOldOrWrongFormat
        $this->assertFalse(StudentIndexService::isOldOrWrongFormat('TIT/11/550', $this->institute));
        $this->assertFalse(StudentIndexService::isOldOrWrongFormat('TIT/58/110', $this->institute));
        $this->assertTrue(StudentIndexService::isOldOrWrongFormat('TiT/58/101', $this->institute));
        $this->assertTrue(StudentIndexService::isOldOrWrongFormat('TiT/28/100', $this->institute));
        $this->assertTrue(StudentIndexService::isOldOrWrongFormat('TIT/8/100', $this->institute));
    }
}

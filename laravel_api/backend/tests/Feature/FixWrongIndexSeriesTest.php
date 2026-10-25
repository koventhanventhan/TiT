<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Payment;
use App\Models\StudentIndexCounter;
use App\Models\User;
use App\Services\PaymentReceiptService;
use App\Services\StudentIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class FixWrongIndexSeriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup institute 1 in grade mode with TIT prefix
        $institute = Institute::find(1);
        if (!$institute) {
            $institute = Institute::create([
                'id' => 1,
                'name' => 'TiT Institute',
                'slug' => 'tit-institute',
                'index_format' => 'grade',
                'index_prefix' => 'TIT',
                'index_seq_start' => 1,
            ]);
        } else {
            $institute->update([
                'index_format' => 'grade',
                'index_prefix' => 'TIT',
                'index_seq_start' => 1,
            ]);
        }
    }

    public function test_promoted_student_with_valid_index_is_not_listed(): void
    {
        // Promoted Grade 9 student who received permanent index TIT/08/004 in Grade 8
        $student = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'current_grade' => 'Grade 9',
            'index_number' => 'TIT/08/004',
            'batch_year' => 8,
            'index_seq' => 4,
        ]);

        $exitCode = Artisan::call('students:fix-wrong-index-series', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringNotContainsString('TIT/08/004', $output);
        $this->assertStringContainsString('No wrong index numbers detected', $output);
    }

    public function test_wrong_format_and_series_are_listed_and_fixed_with_apply(): void
    {
        // 1. Promoted Grade 9 student (should NOT be touched)
        $promoted = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'name' => 'promoted_student',
            'current_grade' => 'Grade 9',
            'index_number' => 'TIT/08/004',
            'batch_year' => 8,
            'index_seq' => 4,
        ]);

        // 2. Student with old casing and batch series (TiT/28/100)
        $oldFormatStudent = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'name' => 'legacy_batch_student',
            'current_grade' => 'Grade 8',
            'index_number' => 'TiT/28/100',
            'batch_year' => 28,
            'index_seq' => 100,
        ]);

        // 3. Student with series 26 (outside 01-13)
        $outOfRangeStudent = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'name' => 'out_of_range_student',
            'current_grade' => 'Grade 8',
            'index_number' => 'TIT/26/001',
            'batch_year' => 26,
            'index_seq' => 1,
        ]);

        // Initialize series 08 counter at seq 4
        StudentIndexCounter::updateOrCreate(
            ['institute_id' => 1, 'series' => '08'],
            ['batch_year' => 8, 'last_seq' => 4]
        );

        // Dry-run
        $exitCode = Artisan::call('students:fix-wrong-index-series', [
            '--dry-run' => true,
            '--series-map' => '28:08',
        ]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('TiT/28/100', $output);
        $this->assertStringContainsString('TIT/26/001', $output);
        $this->assertStringNotContainsString('TIT/08/004', $output);

        // Apply fix
        $applyCode = Artisan::call('students:fix-wrong-index-series', [
            '--apply' => true,
            '--series-map' => '28:08',
        ]);
        $applyOutput = Artisan::output();

        $this->assertEquals(0, $applyCode);
        $this->assertStringContainsString('FIXED -> TIT/08/005', $applyOutput);
        $this->assertStringContainsString('FIXED -> TIT/08/006', $applyOutput);

        $freshOld = $oldFormatStudent->fresh();
        $freshOutOfRange = $outOfRangeStudent->fresh();
        $freshPromoted = $promoted->fresh();

        $this->assertEquals('TIT/08/004', $freshPromoted->index_number);
        $this->assertEquals('TIT/08/005', $freshOld->index_number);
        $this->assertEquals('TIT/08/006', $freshOutOfRange->index_number);

        // Counter moved to 6
        $counter = StudentIndexCounter::where('institute_id', 1)->where('batch_year', 8)->first();
        $this->assertEquals(6, $counter->last_seq);
    }

    public function test_receipt_data_shows_new_index_after_reassign_and_not_cached(): void
    {
        $student = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'current_grade' => 'Grade 8',
            'index_number' => 'TiT/28/100',
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'institute_id' => 1,
            'amount' => 3000,
            'status' => 'paid',
            'payment_method' => 'offline',
            'year_month' => now()->format('Y-m'),
            'paid_at' => now(),
            'transaction_id' => 'TXN-TEST-1',
        ]);

        $receiptService = app(PaymentReceiptService::class);
        $dataBefore = $receiptService->getReceiptData($payment);
        $this->assertEquals('TiT/28/100', $dataBefore['indexNumber']);

        // Reassign student index
        $indexService = app(StudentIndexService::class);
        $newIndex = $indexService->reassign($student, '08', null, 'Convert to new format');

        $this->assertEquals('TIT/08/001', $newIndex);

        // Fetch receipt data again with the same payment object
        $dataAfter = $receiptService->getReceiptData($payment);
        $this->assertEquals('TIT/08/001', $dataAfter['indexNumber']);
    }
}

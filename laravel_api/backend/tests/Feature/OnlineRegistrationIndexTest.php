<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\Payment;
use App\Models\User;
use App\Services\PayHereService;
use App\Services\StudentIndexService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OnlineRegistrationIndexTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
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

        $this->indexService = app(StudentIndexService::class);
    }

    /**
     * Test normalizeGradeSeries accepts 01-99 and rejects out of range.
     */
    public function test_normalize_grade_series_accepts_01_to_99_and_rejects_out_of_range(): void
    {
        $this->assertEquals('26', $this->indexService->normalizeGradeSeries(26));
        $this->assertEquals('08', $this->indexService->normalizeGradeSeries(8));
        $this->assertEquals('58', $this->indexService->normalizeGradeSeries('58'));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Series must be a number 01–99');
        $this->indexService->normalizeGradeSeries(100);
    }

    /**
     * Test automatic derivation rejects year-like values.
     */
    public function test_automatic_derivation_rejects_year_like_series(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Cannot derive grade series from '26' – set the series manually");
        $this->indexService->deriveGradeSeries(26);
    }

    /**
     * Test online PayHere registration success:
     * - Grade 8 -> TIT/08/001
     * - Next Grade 8 -> TIT/08/002
     * - Grade 10 -> TIT/10/001
     * - "A/L" grade registration completes without an index number
     */
    public function test_online_payhere_registration_assigns_correct_grade_series(): void
    {
        // Mock PayHereService to approve webhook notification
        $payHereMock = $this->createMock(PayHereService::class);
        $payHereMock->method('verifyNotification')->willReturn(true);
        $this->app->instance(PayHereService::class, $payHereMock);

        $currentMonth = now()->format('Y-m');

        // 1. First Grade 8 student
        $studentG8_1 = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'current_grade' => 'Grade 8',
            'registration_status' => 'pending_payment',
        ]);
        $payment1 = Payment::create([
            'user_id' => $studentG8_1->id,
            'institute_id' => 1,
            'amount' => 1500,
            'status' => 'pending',
            'payment_method' => 'online',
            'year_month' => $currentMonth,
            'gateway_ref' => 'ORDER_G8_1',
        ]);

        $res1 = $this->postJson(route('payhere.notify'), [
            'order_id' => 'ORDER_G8_1',
            'status_code' => 2,
            'payment_id' => 'PAY_001',
        ]);
        $res1->assertStatus(200);
        $this->assertEquals('TIT/08/001', $studentG8_1->fresh()->index_number);
        $this->assertEquals('approved', $studentG8_1->fresh()->registration_status);

        // 2. Second Grade 8 student
        $studentG8_2 = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'current_grade' => 'Grade 8',
            'registration_status' => 'pending_payment',
        ]);
        $payment2 = Payment::create([
            'user_id' => $studentG8_2->id,
            'institute_id' => 1,
            'amount' => 1500,
            'status' => 'pending',
            'payment_method' => 'online',
            'year_month' => $currentMonth,
            'gateway_ref' => 'ORDER_G8_2',
        ]);

        $res2 = $this->postJson(route('payhere.notify'), [
            'order_id' => 'ORDER_G8_2',
            'status_code' => 2,
            'payment_id' => 'PAY_002',
        ]);
        $res2->assertStatus(200);
        $this->assertEquals('TIT/08/002', $studentG8_2->fresh()->index_number);
        $this->assertEquals('approved', $studentG8_2->fresh()->registration_status);

        // 3. Grade 10 student
        $studentG10 = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'current_grade' => 'Grade 10',
            'registration_status' => 'pending_payment',
        ]);
        $payment3 = Payment::create([
            'user_id' => $studentG10->id,
            'institute_id' => 1,
            'amount' => 2000,
            'status' => 'pending',
            'payment_method' => 'online',
            'year_month' => $currentMonth,
            'gateway_ref' => 'ORDER_G10_1',
        ]);

        $res3 = $this->postJson(route('payhere.notify'), [
            'order_id' => 'ORDER_G10_1',
            'status_code' => 2,
            'payment_id' => 'PAY_003',
        ]);
        $res3->assertStatus(200);
        $this->assertEquals('TIT/10/001', $studentG10->fresh()->index_number);
        $this->assertEquals('approved', $studentG10->fresh()->registration_status);

        // 4. "A/L" grade registration completes without a number
        $studentAL = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'current_grade' => 'A/L',
            'registration_status' => 'pending_payment',
        ]);
        $paymentAL = Payment::create([
            'user_id' => $studentAL->id,
            'institute_id' => 1,
            'amount' => 2500,
            'status' => 'pending',
            'payment_method' => 'online',
            'year_month' => $currentMonth,
            'gateway_ref' => 'ORDER_AL_1',
        ]);

        $resAL = $this->postJson(route('payhere.notify'), [
            'order_id' => 'ORDER_AL_1',
            'status_code' => 2,
            'payment_id' => 'PAY_004',
        ]);
        $resAL->assertStatus(200);
        $studentAL->refresh();
        $this->assertEquals('approved', $studentAL->registration_status);
        $this->assertNull($studentAL->index_number);
    }

    /**
     * Test data repair command dry run lists seeded TiT/28/100 Grade 8 student and --apply fixes it.
     */
    public function test_repair_command_dry_run_lists_mismatch_and_apply_fixes(): void
    {
        // Seed Grade 8 student with wrong prefix index TiT/28/100
        $student = User::factory()->create([
            'institute_id' => 1,
            'role' => 'user',
            'current_grade' => 'Grade 8',
            'index_number' => 'TiT/28/100',
            'batch_year' => 28,
            'index_seq' => 100,
        ]);

        // 1. Dry run
        $exitCode = Artisan::call('students:fix-wrong-index-series', ['--dry-run' => true]);
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('TiT/28/100', $output);
        $this->assertStringContainsString('Grade 8', $output);
        $this->assertStringContainsString('MISMATCH', $output);

        // Student's number remains unchanged after dry-run
        $this->assertEquals('TiT/28/100', $student->fresh()->index_number);

        // 2. Apply fix
        $applyExitCode = Artisan::call('students:fix-wrong-index-series', ['--apply' => true]);
        $applyOutput = Artisan::output();

        $this->assertEquals(0, $applyExitCode);
        $this->assertStringContainsString('FIXED -> TIT/08/001', $applyOutput);

        // Student now has TIT/08/001
        $this->assertEquals('TIT/08/001', $student->fresh()->index_number);
        $this->assertEquals(8, $student->fresh()->batch_year);
        $this->assertEquals(1, $student->fresh()->index_seq);

        // Audit log exists
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'student_index_reassigned',
            'institute_id' => 1,
        ]);
    }
}

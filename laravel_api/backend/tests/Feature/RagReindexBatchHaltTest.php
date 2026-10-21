<?php

namespace Tests\Feature;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Services\Rag\PdfTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RagReindexBatchHaltTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'name' => 'Reindex Test Institute',
            'slug' => 'reindex-test-inst',
            'domain' => 'localhost',
            'status' => 'active',
        ]);
    }

    public function test_reindex_batch_stops_immediately_on_429_resource_exhausted(): void
    {
        // Create 3 materials without triggering model event dispatch
        [$m1, $m2, $m3] = LearningMaterial::withoutEvents(function () {
            return [
                LearningMaterial::create([
                    'title' => 'First Material 429',
                    'type' => 'pdf',
                    'grade' => '10',
                    'medium' => 'english',
                    'rag_status' => 'pending',
                    'institute_id' => $this->institute->id,
                    'file_path' => 'notes/m1.pdf',
                ]),
                LearningMaterial::create([
                    'title' => 'Second Material Should Not Be Processed',
                    'type' => 'pdf',
                    'grade' => '10',
                    'medium' => 'english',
                    'rag_status' => 'pending',
                    'institute_id' => $this->institute->id,
                    'file_path' => 'notes/m2.pdf',
                ]),
                LearningMaterial::create([
                    'title' => 'Third Material Should Not Be Processed',
                    'type' => 'pdf',
                    'grade' => '10',
                    'medium' => 'english',
                    'rag_status' => 'pending',
                    'institute_id' => $this->institute->id,
                    'file_path' => 'notes/m3.pdf',
                ]),
            ];
        });

        // Mock extractor: when m1 is processed, simulate 429 exception
        $mockExtractor = $this->createMock(PdfTextExtractor::class);
        $mockExtractor->expects($this->any())
            ->method('inspectPagesForIngestion')
            ->willThrowException(new \RuntimeException('Gemini generation failed (429): RESOURCE_EXHAUSTED Quota exceeded'));
        $mockExtractor->expects($this->any())
            ->method('extract')
            ->willThrowException(new \RuntimeException('Gemini generation failed (429): RESOURCE_EXHAUSTED Quota exceeded'));
        $this->app->instance(PdfTextExtractor::class, $mockExtractor);

        $exitCode = Artisan::call('rag:reindex', ['--institute' => $this->institute->id, '--delay' => 0]);
        $output = Artisan::output();

        $this->assertEquals(1, $exitCode);
        $this->assertStringContainsString('quota exhausted, check plan and billing', $output);

        $m1->refresh();
        $this->assertEquals('failed', $m1->rag_status);

        $m2->refresh();
        $this->assertEquals('pending', $m2->rag_status, 'm2 must NOT have been processed after batch halt');

        $m3->refresh();
        $this->assertEquals('pending', $m3->rag_status, 'm3 must NOT have been processed after batch halt');
    }

    public function test_reindex_batch_distinguishes_per_minute_rate_limit_versus_daily_quota(): void
    {
        // 1. Per-minute rate limit error
        $perMinuteMsg = 'Gemini generation failed (429): {"error":{"code":429,"message":"Resource has been exhausted","status":"RESOURCE_EXHAUSTED","details":[{"@type":"type.googleapis.com/google.rpc.ErrorInfo","reason":"RATE_LIMIT_EXCEEDED","metadata":{"quota_limit":"GenerateContentRequestsPerMinutePerProject"}}]}}';
        $classif1 = \App\Services\Rag\GeminiRagClient::classifyQuotaError($perMinuteMsg);
        $this->assertEquals('per_minute', $classif1['category']);
        $this->assertStringContainsString('Rate Limit (Per-Minute)', $classif1['label']);

        // 2. Daily / Billing quota error
        $dailyMsg = 'Gemini generation failed (429): {"error":{"code":429,"message":"Quota exceeded for quota metric GenerateContentRequestsPerDayPerProject and limit GenerateContentRequestsPerDayPerProject","status":"RESOURCE_EXHAUSTED","details":[{"reason":"QUOTA_EXCEEDED"}]}}';
        $classif2 = \App\Services\Rag\GeminiRagClient::classifyQuotaError($dailyMsg);
        $this->assertEquals('daily_or_billing', $classif2['category']);
        $this->assertStringContainsString('Daily / Billing', $classif2['label']);

        // 3. Unspecified 429 error
        $unspecMsg = 'Gemini generation failed (429): {"error":{"code":429,"message":"Resource has been exhausted","status":"RESOURCE_EXHAUSTED"}}';
        $classif3 = \App\Services\Rag\GeminiRagClient::classifyQuotaError($unspecMsg);
        $this->assertEquals('unspecified', $classif3['category']);
        $this->assertStringContainsString('Unspecified 429', $classif3['label']);
    }
}


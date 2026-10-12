<?php

namespace Tests\Feature;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use App\Models\User;
use App\Services\Rag\AnswerService;
use App\Services\Rag\LanguageDetector;
use App\Services\Rag\RetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeRetrievalGeminiClient implements GeminiClientInterface
{
    public bool $generateAnswerCalled = false;
    public string $lastPrompt = '';
    public ?string $lastSystemInstruction = null;
    public float $mockSimilarity = 0.50;

    public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        // Return 768-dim vector
        $vector = array_fill(0, 768, 0.0);
        $vector[0] = 1.0;
        return $vector;
    }

    public function batchEmbedContents(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        return array_map(fn($t) => $this->embedContent($t, $taskType, $title), $texts);
    }

    public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string
    {
        return 'Mock OCR';
    }

    public function generateAnswer(string $prompt, ?string $systemInstruction = null): string
    {
        $this->generateAnswerCalled = true;
        $this->lastPrompt = $prompt;
        $this->lastSystemInstruction = $systemInstruction;

        return "Based on the notes [1], photosynthesis produces glucose and oxygen.";
    }

    public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void
    {
        $this->generateAnswerCalled = true;
        $this->lastPrompt = $prompt;
        $this->lastSystemInstruction = $systemInstruction;

        $onChunk("Based on the notes [1], photosynthesis produces glucose and oxygen.");
    }
}

class RagRetrievalAndAnswerTest extends TestCase
{
    use RefreshDatabase;

    private Institute $instituteA;
    private Institute $instituteB;
    private FakeRetrievalGeminiClient $fakeGemini;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Queue::fake();

        $this->instituteA = Institute::create([
            'name' => 'Institute A',
            'slug' => 'institute-a',
            'domain' => 'inst-a.localhost',
            'status' => 'active',
        ]);

        $this->instituteB = Institute::create([
            'name' => 'Institute B',
            'slug' => 'institute-b',
            'domain' => 'inst-b.localhost',
            'status' => 'active',
        ]);

        $this->fakeGemini = new FakeRetrievalGeminiClient();
        $this->app->instance(GeminiClientInterface::class, $this->fakeGemini);
    }

    public function test_language_detection_for_tamil_sinhala_and_english(): void
    {
        $detector = new LanguageDetector();

        // 1. Tamil
        $tamil = "ஒளிச்சேர்க்கை என்பது தாவரங்கள் சூரிய ஒளியைப் பயன்படுத்தி உணவு தயாரிக்கும் செயல்முறை ஆகும்.";
        $this->assertEquals('ta', $detector->detect($tamil));
        $this->assertEquals('Tamil', $detector->getLanguageName('ta'));

        // 2. Sinhala
        $sinhala = "ප්‍රභාසංශ්ලේෂණය යනු ශාක විසින් සූර්යාලෝකය භාවිතයෙන් ආහාර නිපදවන ක්‍රියාවලියයි.";
        $this->assertEquals('si', $detector->detect($sinhala));
        $this->assertEquals('Sinhala', $detector->getLanguageName('si'));

        // 3. English
        $english = "Photosynthesis is the process used by plants to convert light energy into chemical energy.";
        $this->assertEquals('en', $detector->detect($english));
        $this->assertEquals('English', $detector->getLanguageName('en'));
    }

    public function test_cross_institute_and_cross_grade_isolation(): void
    {
        // Material 1: Institute A, Grade 10, Tamil (Eligible)
        $materialA10 = LearningMaterial::create([
            'title' => 'Grade 10 Biology Note',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'file_path' => 'materials/bio10.pdf',
            'institute_id' => $this->instituteA->id,
            'rag_status' => 'ready',
        ]);

        // Material 2: Institute A, Grade 11, Tamil (Ineligible: Grade mismatch)
        $materialA11 = LearningMaterial::create([
            'title' => 'Grade 11 Biology Note',
            'type' => 'note',
            'grade' => 'grade-11',
            'medium' => 'tamil',
            'subject' => 'Science',
            'file_path' => 'materials/bio11.pdf',
            'institute_id' => $this->instituteA->id,
            'rag_status' => 'ready',
        ]);

        // Material 3: Institute B, Grade 10, Tamil (Ineligible: Institute mismatch)
        $materialB10 = LearningMaterial::create([
            'title' => 'Institute B Grade 10 Note',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'file_path' => 'materials/b10.pdf',
            'institute_id' => $this->instituteB->id,
            'rag_status' => 'ready',
        ]);

        // Student is in Institute A, Grade 10, Tamil
        $student = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'current_grade' => 'grade-10',
            'medium' => 'tamil',
            'role' => 'user',
        ]);

        // Authenticate student to trigger BelongsToInstitute / InstituteScope
        $this->actingAs($student);

        $retrievalService = app(RetrievalService::class);
        $allowedIds = $retrievalService->getAllowedMaterialIds($student);

        // Assert Material A10 is allowed
        $this->assertContains($materialA10->id, $allowedIds);

        // Assert Material A11 is NOT allowed (Cross-grade isolation)
        $this->assertNotContains($materialA11->id, $allowedIds, 'Failed cross-grade isolation');

        // Assert Material B10 is NOT allowed (Cross-institute isolation)
        $this->assertNotContains($materialB10->id, $allowedIds, 'Failed cross-institute isolation');
    }

    public function test_below_threshold_returns_static_message_without_calling_llm(): void
    {
        $material = LearningMaterial::create([
            'title' => 'Math Formulas',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Mathematics',
            'file_path' => 'materials/math.pdf',
            'institute_id' => $this->instituteA->id,
            'rag_status' => 'ready',
        ]);

        // Chunk with low similarity vector (orthogonal vector)
        $chunkVector = array_fill(0, 768, 0.0);
        $chunkVector[10] = 1.0; // query vector in fake client has $vector[0] = 1.0, so dot product = 0.0

        MaterialChunk::create([
            'institute_id' => $this->instituteA->id,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Mathematics',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'Pythagorean theorem formulas.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$chunkVector),
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'current_grade' => 'grade-10',
            'medium' => 'tamil',
            'role' => 'user',
        ]);

        $this->actingAs($student);

        $answerService = app(AnswerService::class);

        // Tamil question below similarity threshold
        $tamilQuestion = "வரலாற்று கால அரசர்கள் யார்?";
        $response = $answerService->answer($student, $tamilQuestion);

        // 1. Verify LLM was NOT called
        $this->assertFalse($response['llm_called']);
        $this->assertFalse($this->fakeGemini->generateAnswerCalled);

        // 2. Verify static Tamil fallback message was returned
        $this->assertEquals('ta', $response['language']);
        $this->assertStringContainsString('வினாத்தாள்களில் உங்கள் கேள்விக்கான விடை கிடைக்கவில்லை', $response['answer']);
        $this->assertEmpty($response['citations']);
    }

    public function test_above_threshold_calls_llm_and_returns_citations(): void
    {
        $material = LearningMaterial::create([
            'title' => 'Biology Cell Division',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'english',
            'subject' => 'Science',
            'file_path' => 'materials/bio_cell.pdf',
            'institute_id' => $this->instituteA->id,
            'rag_status' => 'ready',
        ]);

        // Chunk with vector identical to fake client's query vector ($vector[0] = 1.0, similarity = 1.0)
        $highSimVector = array_fill(0, 768, 0.0);
        $highSimVector[0] = 1.0;

        MaterialChunk::create([
            'institute_id' => $this->instituteA->id,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'english',
            'subject' => 'Science',
            'chunk_type' => 'page',
            'page_number' => 3,
            'content' => 'Mitosis produces two genetically identical diploid daughter cells.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$highSimVector),
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'current_grade' => 'grade-10',
            'medium' => 'english',
            'role' => 'user',
        ]);

        $this->actingAs($student);

        $answerService = app(AnswerService::class);
        $response = $answerService->answer($student, 'What does mitosis produce?');

        // 1. Verify LLM was called
        $this->assertTrue($response['llm_called']);
        $this->assertTrue($this->fakeGemini->generateAnswerCalled);

        // 2. Verify citation extraction
        $this->assertContains(1, $response['citations']);
        $this->assertNotEmpty($response['sources']);
        $this->assertEquals($material->id, $response['sources'][0]['material_id']);
        $this->assertEquals(3, $response['sources'][0]['page_number']);
        $this->assertGreaterThanOrEqual(0.99, $response['top_similarity_score']);
    }

    public function test_rag_eval_artisan_command(): void
    {
        $material = LearningMaterial::create([
            'title' => 'Grade 10 Test Physics',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'english',
            'subject' => 'Physics',
            'file_path' => 'materials/physics.pdf',
            'institute_id' => $this->instituteA->id,
            'rag_status' => 'ready',
        ]);

        $chunkVector = array_fill(0, 768, 0.0);
        $chunkVector[0] = 1.0;

        MaterialChunk::create([
            'institute_id' => $this->instituteA->id,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'english',
            'subject' => 'Physics',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'Newton second law states force equals mass times acceleration.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$chunkVector),
        ]);

        // Write a temporary JSON test dataset with both answerable and negative questions
        $testData = [
            [
                'question' => 'State Newton second law of motion',
                'grade' => 'grade-10',
                'medium' => 'english',
                'subject' => 'Physics',
                'should_answer' => true,
                'expected_material_id' => $material->id,
            ],
            [
                'question' => 'Who was the king of Anuradhapura in 200 BC?',
                'grade' => 'grade-10',
                'medium' => 'english',
                'subject' => 'History',
                'should_answer' => false,
                'expected_material_id' => null,
            ]
        ];

        $tempPath = tempnam(sys_get_temp_dir(), 'rag_eval_') . '.json';
        file_put_contents($tempPath, json_encode($testData));

        try {
            $this->artisan('rag:eval', [
                'file' => $tempPath,
                '--institute' => $this->instituteA->id,
            ])
            ->expectsOutputToContain('ANSWERABLE QUESTIONS METRICS')
            ->expectsOutputToContain('NEGATIVE QUESTIONS (NO NOTES) METRICS')
            ->expectsOutputToContain('THRESHOLD CALIBRATION SWEEP')
            ->assertExitCode(0);
        } finally {
            if (file_exists($tempPath)) {
                @unlink($tempPath);
            }
        }
    }

    public function test_not_found_message_returns_actual_localized_text_and_never_key_string(): void
    {
        $answerService = app(AnswerService::class);

        // Tamil
        $taMsg = $answerService->getNotFoundMessage('ta');
        $this->assertNotEquals('rag.not_found', $taMsg);
        $this->assertStringContainsString('உங்கள் ஆசிரியரின் குறிப்புகள்', $taMsg);

        // Sinhala
        $siMsg = $answerService->getNotFoundMessage('si');
        $this->assertNotEquals('rag.not_found', $siMsg);
        $this->assertStringContainsString('ඔබගේ ගුරුවරයා විසින් ලබාදී ඇති සටහන්', $siMsg);

        // English
        $enMsg = $answerService->getNotFoundMessage('en');
        $this->assertNotEquals('rag.not_found', $enMsg);
        $this->assertStringContainsString('could not find an answer', $enMsg);
    }

    public function test_strict_subject_filtering_without_fallback(): void
    {
        // Create Science material
        $scienceMaterial = LearningMaterial::create([
            'title' => 'Grade 10 Biology',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'english',
            'subject' => 'Science',
            'file_path' => 'materials/science.pdf',
            'institute_id' => $this->instituteA->id,
            'rag_status' => 'ready',
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->instituteA->id,
            'current_grade' => 'grade-10',
            'medium' => 'english',
            'role' => 'user',
        ]);

        $this->actingAs($student);

        $retrievalService = app(RetrievalService::class);

        // 1. When querying for Mathematics: zero materials allowed, strictly NO fallback to Science
        $mathAllowedIds = $retrievalService->getAllowedMaterialIds($student, 'Mathematics');
        $this->assertEmpty($mathAllowedIds, 'Failed strict subject isolation; fell back to other subjects');

        // 2. When querying for Science: Science material is allowed
        $scienceAllowedIds = $retrievalService->getAllowedMaterialIds($student, 'Science');
        $this->assertContains($scienceMaterial->id, $scienceAllowedIds);

        // 3. When querying with no subject (All subjects default): Science material is allowed
        $allAllowedIds = $retrievalService->getAllowedMaterialIds($student, null);
        $this->assertContains($scienceMaterial->id, $allAllowedIds);

        // 4. AnswerService with unindexed subject returns static not-found message without calling LLM
        $answerService = app(AnswerService::class);
        $res = $answerService->answer($student, 'Solve quadratic formula', 'Mathematics');
        $this->assertFalse($res['llm_called']);
        $this->assertStringContainsString('could not find an answer', $res['answer']);
    }
}

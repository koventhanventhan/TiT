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
use App\Services\Rag\TextNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FakeGroundingGeminiClient implements GeminiClientInterface
{
    public string $answerToReturn = '';

    public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        $v = array_fill(0, 768, 0.0);
        $v[0] = 1.0;
        return $v;
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
        return $this->answerToReturn;
    }

    public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void
    {
        $onChunk($this->answerToReturn);
    }
}

class RagGroundingInflectionsTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private FakeGroundingGeminiClient $fakeGemini;
    private AnswerService $answerService;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Queue::fake();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'name' => 'Grounding Test Institute',
            'slug' => 'grounding-test',
            'domain' => 'grounding.localhost',
            'status' => 'active',
        ]);

        $this->fakeGemini = new FakeGroundingGeminiClient();
        $this->app->instance(GeminiClientInterface::class, $this->fakeGemini);
        $this->answerService = app(AnswerService::class);
    }

    /**
     * Test Tamil inflected forms are correctly grounded without false flags.
     */
    public function test_tamil_inflected_forms_are_not_flagged(): void
    {
        $context = "தவில் செய்வதற்கு பலா மரம் மற்றும் கருங்காலி மரம் பயன்படும். நாதஸ்வரத்துடன் தவில் வாசிக்கப்படும்.";
        $answer = "தவிலில் [1] பலா மரத்தின் பகுதிகள் பயன்படுத்தப்படுகின்றன. நாதஸ்வரத்தோடு தவில் இசைக்கப்படுகிறது.";

        $result = $this->answerService->verifyGrounding($answer, $context, "தவில் பற்றிய தகவல்");

        $this->assertFalse($result['flagged'], 'Tamil inflected forms (தவிலில், மரத்தின்) must not be flagged as ungrounded.');
        $this->assertEmpty($result['ungrounded_terms']);
    }

    /**
     * Test Sinhala inflected forms are correctly grounded without false flags.
     */
    public function test_sinhala_inflected_forms_are_not_flagged(): void
    {
        $context = "ගුරුතුමා විසින් ලබාදුන් සටහන්වල ශාක ප්‍රභාසංස්ලේෂණය පිළිබඳව සඳහන් වේ.";
        $answer = "ගුරුතුමාගේ [1] සටහන්වලින් ශාක ප්‍රභාසංස්ලේෂණය පිළිබඳ පැහැදිලි කරයි.";

        $result = $this->answerService->verifyGrounding($answer, $context, "ශාක ගැන");

        $this->assertFalse($result['flagged'], 'Sinhala inflected forms (ගුරුතුමාගේ, සටහන්වලින්) must not be flagged as ungrounded.');
        $this->assertEmpty($result['ungrounded_terms']);
    }

    /**
     * Test Tamil out-of-context terms are correctly flagged as ungrounded.
     */
    public function test_tamil_hallucinated_terms_are_flagged(): void
    {
        $context = "தவில் செய்வதற்கு பலா மரம் மற்றும் கருங்காலி மரம் பயன்படும்.";
        $answer = "தவிலை செய்ய சந்தன மரம் மற்றும் யானைத் தந்தம் பயன்படும். [1]";

        $result = $this->answerService->verifyGrounding($answer, $context, "தவில் பற்றிய தகவல்");

        $this->assertTrue($result['flagged']);
        $this->assertContains('சந்தன', $result['ungrounded_terms']);
        $this->assertContains('தந்தம்', $result['ungrounded_terms']);
    }

    /**
     * Test Sinhala out-of-context terms are correctly flagged as ungrounded.
     */
    public function test_sinhala_hallucinated_terms_are_flagged(): void
    {
        $context = "ගුරුතුමා විසින් ලබාදුන් සටහන්වල ශාක ප්‍රභාසංස්ලේෂණය පිළිබඳව සඳහන් වේ.";
        $answer = "ශාකවලට යුරේනියම් සහ න්‍යෂ්ටික ශක්තිය අවශ්‍ය වේ. [1]";

        $result = $this->answerService->verifyGrounding($answer, $context, "ශාක ගැන");

        $this->assertTrue($result['flagged']);
        $this->assertContains('යුරේනියම්', $result['ungrounded_terms']);
        $this->assertContains('ශක්තිය', $result['ungrounded_terms']);
    }

    /**
     * Test configurable action 'replace' replaces flagged answer with not-found message.
     */
    public function test_grounding_action_replace_replaces_unsupported_answer(): void
    {
        $material = LearningMaterial::create([
            'title' => 'Tamil Music Notes',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Music',
            'file_path' => 'materials/music.pdf',
            'institute_id' => $this->institute->id,
            'rag_status' => 'ready',
        ]);

        $v = array_fill(0, 768, 0.0);
        $v[0] = 1.0;

        MaterialChunk::create([
            'institute_id' => $this->institute->id,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Music',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'தவில் செய்வதற்கு பலா மரம் பயன்படும்.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$v),
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'current_grade' => 'grade-10',
            'medium' => 'tamil',
            'role' => 'user',
        ]);

        $this->actingAs($student);

        // Configure grounding action to 'replace' and inject hallucinated answer
        config(['rag.grounding.action' => 'replace']);
        $fakeGemini = new FakeGroundingGeminiClient();
        $fakeGemini->answerToReturn = "தவிலை செய்ய சந்தன மரம் மற்றும் யானைத் தந்தம் பயன்படும். [1]";
        $this->app->instance(GeminiClientInterface::class, $fakeGemini);

        $service = app(AnswerService::class);
        $response = $service->answer($student, 'தவில் மரம் எது?', 'Music');

        $this->assertTrue($response['has_unsupported_terms']);
        $this->assertStringContainsString('கிடைக்கவில்லை', $response['answer']);
    }

    /**
     * Test configurable action 'log' retains answer with warning when unsupported terms exist.
     */
    public function test_grounding_action_log_keeps_answer(): void
    {
        $material = LearningMaterial::create([
            'title' => 'Tamil Music Notes 2',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Music',
            'file_path' => 'materials/music2.pdf',
            'institute_id' => $this->institute->id,
            'rag_status' => 'ready',
        ]);

        $v = array_fill(0, 768, 0.0);
        $v[0] = 1.0;

        MaterialChunk::create([
            'institute_id' => $this->institute->id,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Music',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'தவில் செய்வதற்கு பலா மரம் பயன்படும்.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$v),
        ]);

        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'current_grade' => 'grade-10',
            'medium' => 'tamil',
            'role' => 'user',
        ]);

        $this->actingAs($student);

        // Configure grounding action to 'log' and inject hallucinated answer
        config(['rag.grounding.action' => 'log']);
        $fakeGemini = new FakeGroundingGeminiClient();
        $rawAnswer = "தவிலை செய்ய சந்தன மரம் மற்றும் யானைத் தந்தம் பயன்படும். [1]";
        $fakeGemini->answerToReturn = $rawAnswer;
        $this->app->instance(GeminiClientInterface::class, $fakeGemini);

        $service = app(AnswerService::class);

        $response = $service->answer($student, 'தவில் மரம் எது?', 'Music');

        $this->assertTrue($response['has_unsupported_terms']);
        $this->assertEquals($rawAnswer, $response['answer'], 'When grounding action is log, original answer must be retained.');
    }
}

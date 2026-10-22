<?php

namespace Tests\Feature;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use App\Models\User;
use App\Services\Rag\AnswerService;
use App\Services\Rag\RetrievalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RagAnswerServiceRule7Test extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $student;
    protected LearningMaterial $material;
    protected MaterialChunk $chunk;
    protected array $vector;
    protected ?string $fakeAnswerToReturn = null;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Queue::fake();

        $this->institute = Institute::create([
            'name' => 'Rule 7 Test Institute',
            'slug' => 'rule7-test',
            'domain' => 'rule7.localhost',
            'status' => 'active',
            'rag_enabled' => true,
        ]);

        $this->student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'current_grade' => 'grade-10',
            'medium' => 'tamil',
            'role' => 'user',
            'admin_confirmed_at' => now(),
        ]);

        $this->actingAs($this->student);

        $this->material = LearningMaterial::create([
            'title' => 'Grade 10 Dance Exam Paper',
            'type' => 'past_paper',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Dance',
            'file_path' => 'materials/dance_paper.pdf',
            'institute_id' => $this->institute->id,
            'rag_status' => 'ready',
        ]);

        $this->vector = array_fill(0, 768, 0.0);
        $this->vector[0] = 1.0;

        $this->chunk = MaterialChunk::create([
            'institute_id' => $this->institute->id,
            'material_id' => $this->material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Dance',
            'chunk_type' => 'qa',
            'page_number' => 1,
            'question_number' => '1',
            'content' => "1. பரத நாட்டியத்திற்கு பயன்படும் முக்கிய தோல் வாத்தியம்:
உடுக்கி    தவில்     மிருதங்கம்     வயலின்",
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'word_count' => 12,
            'is_ocr' => true,
            'embedding' => pack('f*', ...$this->vector),
        ]);

        $testCase = $this;
        $fakeClient = new class($testCase, $this->vector) implements GeminiClientInterface {
            public function __construct(protected RagAnswerServiceRule7Test $testCase, protected array $vector) {}
            public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array {
                return $this->vector;
            }
            public function batchEmbedContents(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array {
                return array_fill(0, count($texts), $this->vector);
            }
            public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string {
                return '';
            }
            public function generateAnswer(string $prompt, ?string $systemInstruction = null): string {
                return $this->testCase->getFakeAnswer() ?? 'Standard answer';
            }
            public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void {
                $onChunk($this->testCase->getFakeAnswer() ?? 'Standard answer');
            }
        };

        $this->app->instance(GeminiClientInterface::class, $fakeClient);
        $this->app->forgetInstance(RetrievalService::class);
        $this->app->forgetInstance(AnswerService::class);
    }

    public function getFakeAnswer(): ?string
    {
        return $this->fakeAnswerToReturn;
    }

    public function test_system_prompt_includes_rewritten_rule_7(): void
    {
        $answerService = app(AnswerService::class);
        $context = $answerService->prepareContext($this->student, 'பரத நாட்டியத்திற்கு பயன்படும் முக்கிய தோல் வாத்தியம் எது?');

        $this->assertNotEmpty($context['qualified_chunks']);
        $instruction = $context['system_instruction'];

        $this->assertStringContainsString('7. QUESTION PAPERS WITHOUT ANSWERS', $instruction);
        $this->assertStringContainsString('(a) state what the notes or question paper actually contain', $instruction);
        $this->assertStringContainsString('(b) state clearly and politely that the answer key or correct answer is NOT present', $instruction);
        $this->assertStringContainsString('(c) suggest asking the teacher', $instruction);
        $this->assertStringContainsString('reasoning step-by-step', $instruction);
    }

    public function test_fake_llm_adding_outside_facts_is_flagged_by_post_check(): void
    {
        $this->fakeAnswerToReturn = "[1] பரத நாட்டியத்திற்கு பயன்படும் முக்கிய தோல் வாத்தியம் மிருதங்கம் ஆகும். இது சோழர் காலத்தில் தமிழ்நாட்டில் பலா மரத்தினால் உருவாக்கப்பட்டது.";

        $answerService = app(AnswerService::class);
        $result = $answerService->answer($this->student, 'பரத நாட்டியத்திற்கு பயன்படும் முக்கிய தோல் வாத்தியம் எது?');

        $this->assertTrue($result['llm_called']);
        $this->assertTrue($result['has_unsupported_terms'], 'Post-check must flag answers containing terms not found in context or question.');
        $this->assertNotEmpty($result['unsupported_terms']);
        $this->assertContains('சோழர்', $result['unsupported_terms']);
        $this->assertContains('தமிழ்நாட்டில்', $result['unsupported_terms']);
    }

    public function test_grounded_answer_strictly_following_rule_7_passes_post_check(): void
    {
        $this->fakeAnswerToReturn = "ஆசிரியரின் குறிப்புகளில் உள்ள வினாத்தாளில் [1] '1. பரத நாட்டியத்திற்கு பயன்படும் முக்கிய தோல் வாத்தியம்' என்ற வினாவும் உடுக்கி, தவில், மிருதங்கம், வயலின் ஆகிய தெரிவுகளும் கொடுக்கப்பட்டுள்ளன. ஆனால் விடைக்குறிப்பு வழங்கப்படவில்லை. சரியான விடையை உங்கள் ஆசிரியரிடம் கேட்டுத் தெரிந்துகொள்ளவும்.";

        $answerService = app(AnswerService::class);
        $result = $answerService->answer($this->student, 'பரத நாட்டியத்திற்கு பயன்படும் முக்கிய தோல் வாத்தியம் எது?');

        $this->assertTrue($result['llm_called']);
        $this->assertFalse($result['has_unsupported_terms'], 'Grounded answer following Rule 7 must not be flagged.');
        $this->assertEmpty($result['unsupported_terms']);
    }
}

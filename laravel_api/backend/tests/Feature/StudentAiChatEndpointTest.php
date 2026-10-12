<?php

namespace Tests\Feature;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use App\Models\StudentAiQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class FakeEndpointGeminiClient implements GeminiClientInterface
{
    public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
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
        return "Photosynthesis converts light into chemical energy [1].";
    }

    public function streamAnswer(string $prompt, ?string $systemInstruction, callable $onChunk): void
    {
        $onChunk("Photosynthesis converts light ");
        $onChunk("into chemical energy [1].");
    }
}

class StudentAiChatEndpointTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Queue::fake();

        $this->institute = Institute::create([
            'name' => 'Apex Tuition Institute',
            'slug' => 'apex-tuition',
            'domain' => 'localhost',
            'status' => 'active',
            'rag_enabled' => true,
        ]);

        $this->student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'current_grade' => 'grade-10',
            'medium' => 'english',
            'role' => 'user',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);

        // Mock payment for current month so reg_status middleware passes
        $this->student->payments()->create([
            'amount' => 1500,
            'status' => 'paid',
            'year_month' => now()->format('Y-m'),
        ]);

        $this->app->instance(GeminiClientInterface::class, new FakeEndpointGeminiClient());

        RateLimiter::clear('rag_min_' . $this->student->id);
    }

    public function test_unauthenticated_requests_are_blocked(): void
    {
        $response = $this->postJson('/api/student/ai-chat', [
            'question' => 'What is photosynthesis?',
        ]);

        $response->assertStatus(401);
    }

    public function test_unconfirmed_students_are_blocked_by_reg_status(): void
    {
        $unconfirmed = User::factory()->create([
            'institute_id' => $this->institute->id,
            'current_grade' => 'grade-10',
            'medium' => 'english',
            'role' => 'user',
            'registration_status' => 'pending_approval',
            'admin_confirmed_at' => null,
        ]);

        $response = $this->actingAs($unconfirmed)->postJson('/api/student/ai-chat', [
            'question' => 'What is photosynthesis?',
        ]);

        $response->assertStatus(403);
    }

    public function test_validation_rejects_empty_and_short_questions(): void
    {
        $response = $this->actingAs($this->student)->postJson('/api/student/ai-chat', [
            'question' => 'hi', // Under 3 characters
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['question']);
    }

    public function test_global_kill_switch_returns_service_unavailable(): void
    {
        config(['rag.enabled' => false]);

        $response = $this->actingAs($this->student)->postJson('/api/student/ai-chat', [
            'question' => 'What is photosynthesis?',
        ]);

        $response->assertStatus(503);
        $response->assertJsonFragment(['message' => 'The AI Study Assistant is temporarily disabled.']);
    }

    public function test_per_institute_kill_switch_returns_forbidden(): void
    {
        $this->institute->update(['rag_enabled' => false]);

        $response = $this->actingAs($this->student)->postJson('/api/student/ai-chat', [
            'question' => 'What is photosynthesis?',
        ]);

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'The AI Study Assistant is disabled for your institute.']);
    }

    public function test_rate_limiting_per_minute(): void
    {
        config(['rag.limits.per_minute' => 2]);

        // Request 1
        $res1 = $this->actingAs($this->student)->postJson('/api/student/ai-chat', ['question' => 'Question 1 test?']);
        $this->assertNotEquals(429, $res1->getStatusCode());

        // Request 2
        $res2 = $this->actingAs($this->student)->postJson('/api/student/ai-chat', ['question' => 'Question 2 test?']);
        $this->assertNotEquals(429, $res2->getStatusCode());

        // Request 3 (exceeds 2/min limit)
        $res3 = $this->actingAs($this->student)->postJson('/api/student/ai-chat', ['question' => 'Question 3 test?']);
        $res3->assertStatus(429);
    }

    public function test_daily_limit_only_counts_answered_and_not_found(): void
    {
        config(['rag.limits.daily_questions_per_student' => 2]);

        // Seed 1 error query (must NOT count towards daily limit)
        StudentAiQuery::create([
            'institute_id' => $this->institute->id,
            'user_id' => $this->student->id,
            'question' => 'Past error question',
            'status' => 'error',
        ]);

        // Seed 2 answered queries (reaches the daily limit of 2)
        StudentAiQuery::create([
            'institute_id' => $this->institute->id,
            'user_id' => $this->student->id,
            'question' => 'Past answered query 1',
            'status' => 'answered',
        ]);

        StudentAiQuery::create([
            'institute_id' => $this->institute->id,
            'user_id' => $this->student->id,
            'question' => 'Past not found query 2',
            'status' => 'no_notes_found',
        ]);

        $response = $this->actingAs($this->student)->postJson('/api/student/ai-chat', [
            'question' => 'New query today?',
        ]);

        $response->assertStatus(429);
        $this->assertStringContainsString('daily limit of 2 questions', $response->json('message'));
    }

    public function test_distress_detection_flags_query_and_streams_caring_message(): void
    {
        $response = $this->actingAs($this->student)->post('/api/student/ai-chat', [
            'question' => 'I feel like I want to kill myself today',
        ]);

        $response->assertStatus(200);
        $this->assertStringStartsWith('text/event-stream', $response->headers->get('Content-Type'));

        // Verify StudentAiQuery record was created and flagged
        $query = StudentAiQuery::where('user_id', $this->student->id)->latest()->first();
        $this->assertNotNull($query);
        $this->assertEquals('distress_blocked', $query->status);
        $this->assertTrue($query->is_flagged);
        $this->assertStringContainsString('Personal distress', $query->flag_reason);
    }

    public function test_feedback_rating_and_ownership_enforcement(): void
    {
        // 1. Create a query belonging to this student
        $myQuery = StudentAiQuery::create([
            'institute_id' => $this->institute->id,
            'user_id' => $this->student->id,
            'question' => 'What is mitosis?',
            'answer' => 'Mitosis is cell division.',
            'status' => 'answered',
        ]);

        // Rate query with thumbs up
        $res = $this->actingAs($this->student)->postJson("/api/student/ai-chat/{$myQuery->id}/feedback", [
            'rating' => 'up',
            'comment' => 'Very clear explanation!',
        ]);

        $res->assertStatus(200);
        $myQuery->refresh();
        $this->assertEquals('up', $myQuery->feedback_rating);
        $this->assertEquals('Very clear explanation!', $myQuery->feedback_comment);

        // 2. Ownership check: Other student trying to rate myQuery gets 403
        $otherStudent = User::factory()->create([
            'institute_id' => $this->institute->id,
            'current_grade' => 'grade-10',
            'medium' => 'english',
            'role' => 'user',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
        ]);
        $otherStudent->payments()->create([
            'amount' => 1500,
            'status' => 'paid',
            'year_month' => now()->format('Y-m'),
        ]);

        $forbiddenRes = $this->actingAs($otherStudent)->postJson("/api/student/ai-chat/{$myQuery->id}/feedback", [
            'rating' => 'down',
        ]);

        $forbiddenRes->assertStatus(403);
    }

    public function test_streaming_endpoint_returns_event_stream_format(): void
    {
        // Create note and chunk with high similarity
        $material = LearningMaterial::create([
            'title' => 'Grade 10 Biology Cell Notes',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'english',
            'subject' => 'Science',
            'file_path' => 'materials/cell.pdf',
            'institute_id' => $this->institute->id,
            'rag_status' => 'ready',
        ]);

        $vector = array_fill(0, 768, 0.0);
        $vector[0] = 1.0;

        MaterialChunk::create([
            'institute_id' => $this->institute->id,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'english',
            'subject' => 'Science',
            'chunk_type' => 'page',
            'page_number' => 2,
            'content' => 'Photosynthesis produces oxygen and glucose in chloroplasts.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$vector),
        ]);

        $response = $this->actingAs($this->student)->post('/api/student/ai-chat', [
            'question' => 'Explain photosynthesis products',
            'subject' => 'Science',
        ]);

        $response->assertStatus(200);
        $this->assertStringStartsWith('text/event-stream', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('event: message', $content);
        $this->assertStringContainsString('event: end', $content);

        // Query row was persisted
        $this->assertDatabaseHas('student_ai_queries', [
            'user_id' => $this->student->id,
            'status' => 'answered',
            'subject' => 'Science',
        ]);
    }
}

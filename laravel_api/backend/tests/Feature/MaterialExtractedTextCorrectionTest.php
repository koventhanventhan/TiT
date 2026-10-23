<?php

namespace Tests\Feature;

use App\Contracts\Rag\GeminiClientInterface;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use App\Models\MaterialPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MaterialExtractedTextCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $ownerTeacher;
    protected User $otherTeacher;
    protected User $student;
    protected User $admin;
    protected LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->institute = Institute::firstOrCreate(['id' => 1], [
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'status' => 'active',
            'rag_enabled' => true,
        ]);

        $this->ownerTeacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => 1,
            'medium' => 'tamil',
        ]);

        $this->otherTeacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => 1,
            'medium' => 'tamil',
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'current_grade' => 'grade-10',
            'medium' => 'tamil',
            'institute_id' => 1,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => 1,
        ]);

        $this->material = LearningMaterial::create([
            'institute_id' => 1,
            'teacher_id' => $this->ownerTeacher->id,
            'title' => 'Grade 10 Music Instruments Notes',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Music',
            'type' => 'note',
            'file_path' => 'materials/music_sample.pdf',
            'rag_status' => 'ready',
        ]);
        $this->material->updateQuietly(['rag_status' => 'ready']);

        // Seed page 1 with raw OCR text and ocr_uncertain flag
        MaterialPage::create([
            'material_id' => $this->material->id,
            'page_number' => 1,
            'text' => '20. தரில் செய்யப்படும் மரப் பலா மரம் ஆகும்.',
            'is_ocr' => true,
            'ocr_uncertain' => true,
            'teacher_verified' => false,
        ]);

        // Seed chunk from page 1 with is_ocr = true
        MaterialChunk::create([
            'institute_id' => 1,
            'material_id' => $this->material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Music',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => '20. தரில் செய்யப்படும் மரப் பலா மரம் ஆகும்.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...array_fill(0, 768, 0.05)),
            'word_count' => 7,
            'is_ocr' => true,
        ]);
    }

    /**
     * Test 1: Teacher owner and admin can view extracted pages; other teacher and student are forbidden (403).
     */
    public function test_authorization_for_viewing_extracted_pages(): void
    {
        // 1. Teacher owner -> 200 OK
        $resOwner = $this->actingAs($this->ownerTeacher)
            ->getJson("/api/materials/{$this->material->id}/pages");
        $resOwner->assertStatus(200);
        $resOwner->assertJsonCount(1, 'pages');
        $this->assertTrue($resOwner->json('pages.0.ocr_uncertain'));
        $this->assertFalse($resOwner->json('pages.0.teacher_verified'));

        // 2. Admin -> 200 OK
        $resAdmin = $this->actingAs($this->admin)
            ->getJson("/api/materials/{$this->material->id}/pages");
        $resAdmin->assertStatus(200);

        // 3. Another teacher -> 403 Forbidden
        $resOther = $this->actingAs($this->otherTeacher)
            ->getJson("/api/materials/{$this->material->id}/pages");
        $resOther->assertStatus(403);

        // 4. Student -> 403 Forbidden
        $resStudent = $this->actingAs($this->student)
            ->getJson("/api/materials/{$this->material->id}/pages");
        $resStudent->assertStatus(403);
    }

    /**
     * Test 2: Another teacher and student cannot edit extracted text (403).
     */
    public function test_unauthorized_users_cannot_edit_extracted_text(): void
    {
        $payload = ['text' => '20. தவில் செய்யப்படும் மரம் பலா மரம் ஆகும்.'];

        // Other teacher -> 403
        $resOther = $this->actingAs($this->otherTeacher)
            ->putJson("/api/materials/{$this->material->id}/pages/1", $payload);
        $resOther->assertStatus(403);

        // Student -> 403
        $resStudent = $this->actingAs($this->student)
            ->putJson("/api/materials/{$this->material->id}/pages/1", $payload);
        $resStudent->assertStatus(403);
    }

    /**
     * Test 3: Text size exceeding 50,000 characters is rejected with 422.
     */
    public function test_text_size_exceeding_50000_chars_is_rejected(): void
    {
        $hugeText = str_repeat('அ', 50001);

        $response = $this->actingAs($this->ownerTeacher)
            ->putJson("/api/materials/{$this->material->id}/pages/1", [
                'text' => $hugeText,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['text']);
    }

    /**
     * Test 4: Teacher owner saves corrected text:
     * - Re-chunks and re-embeds without calling OCR
     * - Marks page teacher_verified = true, ocr_uncertain = false
     * - Drops raw OCR flag on chunks (is_ocr = false)
     * - Logs who edited and when without logging the text content
     */
    public function test_owner_teacher_saves_corrected_text_rechunks_reembeds_and_verifies_page(): void
    {
        $correctedText = '20. தவில் செய்வதற்கு பலா மரம் மிகவும் சிறந்தது.';

        // Mock GeminiClient: verify batchEmbedContents is called, but ocrPage is NEVER called!
        $mockGemini = $this->createMock(GeminiClientInterface::class);
        $mockGemini->expects($this->never())->method('ocrPage');
        $mockGemini->expects($this->once())
            ->method('batchEmbedContents')
            ->willReturn([array_fill(0, 768, 0.05)]);

        $this->app->instance(GeminiClientInterface::class, $mockGemini);

        Log::spy();

        $response = $this->actingAs($this->ownerTeacher)
            ->putJson("/api/materials/{$this->material->id}/pages/1", [
                'text' => $correctedText,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Page text updated, re-chunked and re-indexed successfully.',
            'chunks_reindexed' => 1,
        ]);

        // 1. Verify MaterialPage record
        $page = MaterialPage::where('material_id', $this->material->id)->where('page_number', 1)->first();
        $this->assertNotNull($page);
        $this->assertEquals($correctedText, $page->text);
        $this->assertTrue($page->teacher_verified);
        $this->assertFalse($page->ocr_uncertain);
        $this->assertEquals($this->ownerTeacher->id, $page->last_edited_by);
        $this->assertNotNull($page->last_edited_at);

        // 2. Verify MaterialChunk record: is_ocr is false (note dropped!)
        $chunks = MaterialChunk::where('material_id', $this->material->id)->where('page_number', 1)->get();
        $this->assertCount(1, $chunks);
        $this->assertEquals($correctedText, $chunks[0]->content);
        $this->assertFalse($chunks[0]->is_ocr, 'Verified page chunks must have is_ocr = false.');

        // 3. Verify Log audit: logs user id, role, material id, page number, timestamp, NEVER the text
        Log::shouldHaveReceived('info')
            ->with('Material extracted page text reviewed and corrected', $this->callback(function ($context) use ($correctedText) {
                return isset($context['user_id'])
                    && $context['user_id'] === $this->ownerTeacher->id
                    && isset($context['material_id'])
                    && $context['material_id'] === $this->material->id
                    && isset($context['page_number'])
                    && $context['page_number'] === 1
                    && isset($context['edited_at'])
                    && !isset($context['text']) // Text must NEVER be in log
                    && !in_array($correctedText, $context, true);
            }));
    }
}

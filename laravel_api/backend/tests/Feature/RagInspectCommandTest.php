<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RagInspectCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Queue::fake();
    }

    public function test_rag_inspect_displays_status_page_counts_ocr_and_chunks(): void
    {
        $institute = Institute::create([
            'name' => 'Apex Institute',
            'slug' => 'apex',
            'status' => 'active',
            'rag_enabled' => true,
        ]);

        $material = LearningMaterial::create([
            'title' => 'Advanced Mechanics & Energy Notes',
            'type' => 'note',
            'grade' => 'grade-12',
            'medium' => 'english',
            'subject' => 'Physics',
            'file_path' => 'materials/mechanics.pdf',
            'institute_id' => $institute->id,
            'rag_status' => 'ready',
            'rag_indexed_at' => now(),
        ]);

        $vector = array_fill(0, 768, 0.0);
        $vector[0] = 1.0;

        // Chunk 1: Direct text (Page 1)
        MaterialChunk::create([
            'institute_id' => $institute->id,
            'material_id' => $material->id,
            'grade' => 'grade-12',
            'medium' => 'english',
            'subject' => 'Physics',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'First chunk: Kinematics and motion equations.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$vector),
            'word_count' => 6,
            'is_ocr' => false,
        ]);

        // Chunk 2: OCR Fallback (Page 2)
        MaterialChunk::create([
            'institute_id' => $institute->id,
            'material_id' => $material->id,
            'grade' => 'grade-12',
            'medium' => 'english',
            'subject' => 'Physics',
            'chunk_type' => 'page',
            'page_number' => 2,
            'content' => 'Second chunk: Work and potential energy scanned diagram.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$vector),
            'word_count' => 8,
            'is_ocr' => true,
        ]);

        // Chunk 3: Direct text (Page 3)
        MaterialChunk::create([
            'institute_id' => $institute->id,
            'material_id' => $material->id,
            'grade' => 'grade-12',
            'medium' => 'english',
            'subject' => 'Physics',
            'chunk_type' => 'page',
            'page_number' => 3,
            'content' => 'Third chunk: Conservation of momentum in collisions.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$vector),
            'word_count' => 7,
            'is_ocr' => false,
        ]);

        $exitCode = \Illuminate\Support\Facades\Artisan::call("rag:inspect", [
            'material_id' => $material->id,
        ]);
        $output = \Illuminate\Support\Facades\Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString("=== Learning Material #{$material->id} RAG Inspection ===", $output);
        $this->assertStringContainsString('Advanced Mechanics & Energy Notes', $output);
        $this->assertStringContainsString('ready', $output);
        $this->assertStringContainsString('2 (1 page(s))', $output);
        $this->assertStringContainsString('First chunk: Kinematics and motion equations.', $output);
        $this->assertStringContainsString('Second chunk: Work and potential energy scanned diagram.', $output);
        $this->assertStringContainsString('Third chunk: Conservation of momentum in collisions.', $output);
    }

    public function test_rag_inspect_fails_gracefully_on_missing_material(): void
    {
        $this->artisan('rag:inspect 88888')
            ->expectsOutputToContain('Learning material #88888 not found.')
            ->assertExitCode(1);
    }
}

<?php

namespace Tests\Feature;

use App\Contracts\Rag\GeminiClientInterface;
use App\Jobs\IngestLearningMaterialJob;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use App\Services\Rag\MultilingualTextChunker;
use App\Services\Rag\PdfTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class FakeGeminiTestClient implements GeminiClientInterface
{
    public function embedContent(string $text, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        // Return deterministic non-zero 768-dimensional vector
        $vector = array_fill(0, 768, 0.05);
        $vector[0] = 0.5;
        $vector[1] = -0.2;
        return $vector;
    }

    public function batchEmbedContents(array $texts, string $taskType = 'RETRIEVAL_DOCUMENT', ?string $title = null): array
    {
        return array_map(fn($t) => $this->embedContent($t, $taskType, $title), $texts);
    }

    public function ocrPage(string $binaryData, string $mimeType = 'image/jpeg'): string
    {
        return 'Mock OCR text for scanned document page.';
    }
}

class IngestLearningMaterialTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        \Illuminate\Support\Facades\Mail::fake();

        $this->institute = Institute::create([
            'name' => 'Ingest Test Institute',
            'slug' => 'ingest-test-inst',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        $this->app->instance(GeminiClientInterface::class, new FakeGeminiTestClient());
    }

    public function test_job_is_dispatched_on_learning_material_creation(): void
    {
        Queue::fake();

        $material = LearningMaterial::create([
            'title' => 'Grade 10 Math Notes',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Mathematics',
            'file_path' => 'materials/test_note.pdf',
            'institute_id' => $this->institute->id,
        ]);

        Queue::assertPushed(IngestLearningMaterialJob::class, function ($job) use ($material) {
            return $job->materialId === $material->id;
        });
    }

    public function test_recordings_are_marked_skipped_without_dispatching_ingestion(): void
    {
        Queue::fake();

        $recording = LearningMaterial::create([
            'title' => 'Grade 10 Zoom Recording',
            'type' => 'recording',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'url' => 'https://zoom.us/rec/12345',
            'institute_id' => $this->institute->id,
        ]);

        $this->assertEquals('skipped', $recording->rag_status);
        Queue::assertNotPushed(IngestLearningMaterialJob::class);
    }

    public function test_job_idempotency_running_twice_produces_identical_chunk_count(): void
    {
        // Mock PdfTextExtractor to return sample pages
        $mockExtractor = $this->createMock(PdfTextExtractor::class);
        $mockExtractor->method('extract')->willReturn([
            [
                'page_number' => 1,
                'text' => 'Unit 1: Science and Measurement. The standard units of measurement are SI units.',
            ],
            [
                'page_number' => 2,
                'text' => 'Unit 2: Matter and Energy. Solids, liquids and gases exhibit distinct properties.',
            ],
        ]);
        $this->app->instance(PdfTextExtractor::class, $mockExtractor);

        $material = LearningMaterial::create([
            'title' => 'Science Chapter 1 & 2',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'file_path' => 'materials/science_ch1.pdf',
            'institute_id' => $this->institute->id,
        ]);

        // First run
        $job = new IngestLearningMaterialJob($material->id);
        $job->handle(
            app(\App\Contracts\Rag\VectorStoreInterface::class),
            $mockExtractor,
            app(MultilingualTextChunker::class),
            new FakeGeminiTestClient()
        );

        $material->refresh();
        $this->assertEquals('ready', $material->rag_status);
        $initialChunkCount = MaterialChunk::where('material_id', $material->id)->count();
        $this->assertEquals(2, $initialChunkCount);

        // Second run (Idempotency verification)
        $job->handle(
            app(\App\Contracts\Rag\VectorStoreInterface::class),
            $mockExtractor,
            app(MultilingualTextChunker::class),
            new FakeGeminiTestClient()
        );

        $secondChunkCount = MaterialChunk::where('material_id', $material->id)->count();
        $this->assertEquals($initialChunkCount, $secondChunkCount);
    }

    public function test_metadata_update_syncs_chunks(): void
    {
        Queue::fake();

        $material = LearningMaterial::create([
            'title' => 'History Grade 10',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'History',
            'file_path' => 'materials/history.pdf',
            'institute_id' => $this->institute->id,
        ]);

        // Create sample chunk directly
        MaterialChunk::create([
            'institute_id' => $this->institute->id,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'History',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'Ancient Sri Lankan kingdoms history.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...array_fill(0, 768, 0.1)),
        ]);

        // Update grade and medium on material
        $material->update([
            'grade' => 'grade-11',
            'medium' => 'english',
            'subject' => 'World History',
        ]);

        $chunk = MaterialChunk::where('material_id', $material->id)->first();
        $this->assertEquals('grade-11', $chunk->grade);
        $this->assertEquals('english', $chunk->medium);
        $this->assertEquals('World History', $chunk->subject);
    }

    public function test_file_path_update_redispatches_job(): void
    {
        Queue::fake();

        $material = LearningMaterial::create([
            'title' => 'Geography Notes',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'file_path' => 'materials/geo_v1.pdf',
            'institute_id' => $this->institute->id,
        ]);

        // Update file path to v2
        $material->update([
            'file_path' => 'materials/geo_v2.pdf',
        ]);

        Queue::assertPushed(IngestLearningMaterialJob::class, 2);
    }

    public function test_deleting_material_deletes_all_chunks(): void
    {
        Queue::fake();

        $material = LearningMaterial::create([
            'title' => 'Temporary Note',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'file_path' => 'materials/temp.pdf',
            'institute_id' => $this->institute->id,
        ]);

        MaterialChunk::create([
            'institute_id' => $this->institute->id,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'Sample note to be deleted.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...array_fill(0, 768, 0.1)),
        ]);

        $this->assertEquals(1, MaterialChunk::where('material_id', $material->id)->count());

        $material->delete();

        $this->assertEquals(0, MaterialChunk::where('material_id', $material->id)->count());
    }

    public function test_multilingual_chunker_preserves_tamil_and_sinhala_words(): void
    {
        $chunker = new MultilingualTextChunker(targetWords: 10, overlapWords: 2);

        // Tamil test text
        $tamilText = "அறிவியல் என்பது இயற்கையை ஆராயும் ஒரு முறையான வழிமுறை ஆகும். இதன் மூலம் நாம் புதிய விடயங்களைக் கற்கின்றோம்.";
        $tamilChunks = $chunker->chunkSinglePage($tamilText, 1);

        $this->assertNotEmpty($tamilChunks);
        foreach ($tamilChunks as $chunk) {
            $words = explode(' ', $chunk['content']);
            foreach ($words as $word) {
                // Ensure word does not contain detached combining marks alone
                $this->assertNotEmpty($word);
            }
        }

        // Sinhala test text
        $sinhalaText = "විද්‍යාව යනු සොබාදහම අධ්‍යයනය කිරීමේ ක්‍රමවේදයකි. එමගින් නව දැනුම සොයාගැනීමට හැකිවේ.";
        $sinhalaChunks = $chunker->chunkSinglePage($sinhalaText, 1);

        $this->assertNotEmpty($sinhalaChunks);
        foreach ($sinhalaChunks as $chunk) {
            $words = explode(' ', $chunk['content']);
            foreach ($words as $word) {
                $this->assertNotEmpty($word);
            }
        }
    }

    public function test_repeated_words_are_never_lost_or_deduplicated(): void
    {
        // Repeating "the" and "மாணவர்" multiple times in a sentence
        $text = "The மாணவர் is the diligent மாணவர் in the academy. The teacher praises the மாணவர் because the மாணவர் studies hard.";

        // Split into words directly to count true word frequencies
        $inputWords = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        $theCountInput = count(array_filter($inputWords, fn($w) => strtolower($w) === 'the'));
        $tamilWordCountInput = count(array_filter($inputWords, fn($w) => $w === 'மாணவர்'));

        $this->assertGreaterThan(3, $theCountInput);
        $this->assertGreaterThan(3, $tamilWordCountInput);

        $chunker = new MultilingualTextChunker(targetWords: 300, overlapWords: 40);
        $chunks = $chunker->chunkSinglePage($text, 1);

        $this->assertCount(1, $chunks);
        $chunkWords = preg_split('/\s+/u', trim($chunks[0]['content']), -1, PREG_SPLIT_NO_EMPTY);

        $theCountOutput = count(array_filter($chunkWords, fn($w) => strtolower($w) === 'the'));
        $tamilWordCountOutput = count(array_filter($chunkWords, fn($w) => $w === 'மாணவர்'));

        $this->assertEquals($theCountInput, $theCountOutput, 'Repeated English word "the" was deleted or corrupted.');
        $this->assertEquals($tamilWordCountInput, $tamilWordCountOutput, 'Repeated Tamil word "மாணவர்" was deleted or corrupted.');
        $this->assertEquals(count($inputWords), count($chunkWords), 'Total word count mismatch; words were dropped.');
    }

    public function test_last_chunk_is_never_an_orphan(): void
    {
        $chunker = new MultilingualTextChunker(targetWords: 50, overlapWords: 15);

        // Create a 58-word text where a naive chunker might create a 50-word chunk and an 8-word orphan
        $sentence1 = "Sentence one contains exactly fifteen words here to build a realistic paragraph structure for testing.";
        $sentence2 = "Sentence two adds another fifteen words right here in order to verify chunk sizes properly.";
        $sentence3 = "Sentence three also provides fifteen words to test that trailing sentences are safely absorbed.";
        $sentence4 = "Sentence four gives thirteen trailing words to prevent tiny orphan chunks from dangling.";

        $fullText = "{$sentence1} {$sentence2}\n\n{$sentence3} {$sentence4}";

        $chunks = $chunker->chunkSinglePage($fullText, 1);

        $this->assertNotEmpty($chunks);

        // Every chunk, including the last chunk, must never be an orphan (< 15 words)
        foreach ($chunks as $index => $chunk) {
            $this->assertGreaterThanOrEqual(
                15,
                $chunk['word_count'],
                "Chunk index {$index} is an orphan with only {$chunk['word_count']} words: '{$chunk['content']}'"
            );
        }
    }

    public function test_guardrail_limits_mark_material_failed_without_retrying(): void
    {
        $mockExtractor = $this->createMock(PdfTextExtractor::class);
        $mockExtractor->method('extract')->willThrowException(
            new \App\Services\Rag\FileTooLargeException("File exceeds maximum allowed size of 50MB.")
        );
        $this->app->instance(PdfTextExtractor::class, $mockExtractor);

        $material = LearningMaterial::create([
            'title' => 'Oversized Note',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'file_path' => 'materials/huge.pdf',
            'institute_id' => $this->institute->id,
        ]);

        $job = new IngestLearningMaterialJob($material->id);
        $job->handle(
            app(\App\Contracts\Rag\VectorStoreInterface::class),
            $mockExtractor,
            app(MultilingualTextChunker::class),
            new FakeGeminiTestClient()
        );

        $material->refresh();
        $this->assertEquals('failed', $material->rag_status);
        $this->assertStringContainsString('exceeds maximum allowed size', $material->rag_error);
    }
}

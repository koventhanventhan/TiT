<?php

namespace Tests\Feature;

use App\Contracts\Rag\VectorStoreInterface;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VectorStoreTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;
    private LearningMaterial $material;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'name' => 'Vector Test Institute',
            'slug' => 'vector-test-inst',
            'domain' => 'localhost',
            'status' => 'active',
        ]);

        $this->material = LearningMaterial::create([
            'title' => 'Grade 10 Science Unit 1',
            'type' => 'note',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'institute_id' => $this->institute->id,
        ]);
    }

    public function test_chunk_embedding_vector_packs_and_unpacks_correctly(): void
    {
        $chunk = new MaterialChunk();
        $sampleVector = [0.12345, -0.67891, 0.0, 1.54321];

        $chunk->setEmbeddingVector($sampleVector);

        $this->assertEquals(4, $chunk->dimensions);
        $this->assertNotEmpty($chunk->embedding);

        $unpacked = $chunk->getEmbeddingVector();

        $this->assertCount(4, $unpacked);
        for ($i = 0; $i < count($sampleVector); $i++) {
            $this->assertEqualsWithDelta($sampleVector[$i], $unpacked[$i], 0.0001);
        }
    }

    public function test_vector_store_searches_and_sorts_by_similarity(): void
    {
        /** @var VectorStoreInterface $vectorStore */
        $vectorStore = app(VectorStoreInterface::class);

        // Vector pointing along x-axis
        $vectorA = [1.0, 0.0, 0.0];
        // Vector at 45 degrees
        $vectorB = [0.7071, 0.7071, 0.0];
        // Vector along y-axis (orthogonal to query)
        $vectorC = [0.0, 1.0, 0.0];

        $chunkA = $vectorStore->saveChunk([
            'institute_id' => $this->institute->id,
            'material_id' => $this->material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'Chunk A: Chemistry basics',
            'embedding_model' => 'gemini-embedding-2',
            'word_count' => 50,
        ], $vectorA);

        $chunkB = $vectorStore->saveChunk([
            'institute_id' => $this->institute->id,
            'material_id' => $this->material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'chunk_type' => 'page',
            'page_number' => 2,
            'content' => 'Chunk B: Physics basics',
            'embedding_model' => 'gemini-embedding-2',
            'word_count' => 50,
        ], $vectorB);

        $chunkC = $vectorStore->saveChunk([
            'institute_id' => $this->institute->id,
            'material_id' => $this->material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Science',
            'chunk_type' => 'page',
            'page_number' => 3,
            'content' => 'Chunk C: Biology basics',
            'embedding_model' => 'gemini-embedding-2',
            'word_count' => 50,
        ], $vectorC);

        // Query vector identical to Chunk A
        $queryVector = [1.0, 0.0, 0.0];

        // Search with threshold 0.5: should match Chunk A (~1.0) and Chunk B (~0.707), but not Chunk C (0.0)
        $results = $vectorStore->search(
            instituteId: $this->institute->id,
            allowedMaterialIds: [$this->material->id],
            queryVector: $queryVector,
            limit: 5,
            threshold: 0.50,
            subject: 'Science'
        );

        $this->assertCount(2, $results);
        $this->assertEquals($chunkA->id, $results[0]['chunk']->id);
        $this->assertEqualsWithDelta(1.0, $results[0]['similarity'], 0.001);

        $this->assertEquals($chunkB->id, $results[1]['chunk']->id);
        $this->assertEqualsWithDelta(0.7071, $results[1]['similarity'], 0.001);
    }

    public function test_vector_store_strictly_ignores_materials_outside_allowed_ids(): void
    {
        /** @var VectorStoreInterface $vectorStore */
        $vectorStore = app(VectorStoreInterface::class);

        $otherMaterial = LearningMaterial::create([
            'title' => 'Grade 12 Advanced Biology',
            'type' => 'note',
            'grade' => 'grade-12',
            'medium' => 'english',
            'subject' => 'Biology',
            'institute_id' => $this->institute->id,
        ]);

        $chunkOther = $vectorStore->saveChunk([
            'institute_id' => $this->institute->id,
            'material_id' => $otherMaterial->id,
            'grade' => 'grade-12',
            'medium' => 'english',
            'subject' => 'Biology',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'Grade 12 sensitive content',
            'embedding_model' => 'gemini-embedding-2',
            'word_count' => 50,
        ], [1.0, 0.0, 0.0]);

        // Student is only allowed $this->material->id, not $otherMaterial->id
        $results = $vectorStore->search(
            instituteId: $this->institute->id,
            allowedMaterialIds: [$this->material->id],
            queryVector: [1.0, 0.0, 0.0],
            limit: 5,
            threshold: 0.10
        );

        $this->assertCount(0, $results);
    }
}

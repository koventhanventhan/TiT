<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\MaterialChunk;
use App\Models\User;
use App\Contracts\Rag\GeminiClientInterface;
use App\Services\Rag\RetrievalService;
use App\Services\Rag\TextNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HybridRetrievalAndNormalizerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Queue::fake();
        \App\Models\Institute::firstOrCreate(['id' => 1], [
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'status' => 'active',
            'rag_enabled' => true,
        ]);
    }

    /**
     * Test (a): A Sinhala question against Tamil notes has zero keyword overlap
     * and MUST keep EXACTLY its embedding score (formula never lowers score).
     */
    public function test_sinhala_question_keeps_exact_embedding_score_with_zero_keyword_overlap(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'current_grade' => 'grade-10',
            'medium' => 'sinhala',
            'institute_id' => 1,
        ]);

        $material = LearningMaterial::create([
            'institute_id' => 1,
            'title' => 'Grade 10 Dance Tamil Notes',
            'grade' => 'grade-10',
            'medium' => 'all',
            'subject' => 'Dance',
            'type' => 'note',
            'file_path' => 'notes/test.pdf',
            'rag_status' => 'ready',
        ]);
        $material->updateQuietly(['rag_status' => 'ready']);

        $vectorA = array_fill(0, 768, 0.0);
        $vectorA[0] = 1.0;

        $chunk = MaterialChunk::create([
            'institute_id' => 1,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'all',
            'subject' => 'Dance',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'தவில் செய்வதற்கு பலா மரம் மிகவும் சிறந்தது.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$vectorA),
            'word_count' => 7,
            'is_ocr' => false,
        ]);

        $mockGemini = $this->createMock(GeminiClientInterface::class);
        $mockGemini->method('embedContent')->willReturn($vectorA);
        $this->app->instance(GeminiClientInterface::class, $mockGemini);

        $retrieval = app(RetrievalService::class);
        $normalizer = app(TextNormalizer::class);

        $sinhalaQuestion = 'තවිල් සෑදීමට ගන්නා ලී වර්ගය කුමක්ද?';

        // 1. Verify zero keyword overlap between Sinhala query and Tamil chunk
        $queryKeywords = $normalizer->extractKeywords($sinhalaQuestion);
        $kwScore = $normalizer->keywordOverlapScore($chunk->content, $queryKeywords);
        $this->assertEquals(0.0, $kwScore, 'Sinhala keywords against Tamil chunk must produce 0.0 keyword overlap.');

        // 2. Run retrieval
        $result = $retrieval->retrieve($student, $sinhalaQuestion, 'Dance', 5);

        $this->assertNotEmpty($result['chunks']);
        $retrievedChunk = $result['chunks'][0];

        // 3. Assert embedding_similarity equals final similarity exactly (never lowered!)
        $this->assertEquals($retrievedChunk['embedding_similarity'], $retrievedChunk['similarity']);
        $this->assertEquals(0.0, $retrievedChunk['keyword_score']);
    }

    /**
     * Test (b): A high keyword overlap lifts a low embedding score without exceeding 1.0.
     */
    public function test_high_keyword_overlap_lifts_low_embedding_score(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'current_grade' => 'grade-10',
            'medium' => 'tamil',
            'institute_id' => 1,
        ]);

        $material = LearningMaterial::create([
            'institute_id' => 1,
            'title' => 'Grade 10 Music Instruments Notes',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Music',
            'type' => 'note',
            'file_path' => 'notes/test.pdf',
            'rag_status' => 'ready',
        ]);
        $material->updateQuietly(['rag_status' => 'ready']);

        $chunkVec = array_fill(0, 768, 0.0);
        $chunkVec[0] = 1.0;

        $chunk = MaterialChunk::create([
            'institute_id' => 1,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Music',
            'chunk_type' => 'page',
            'page_number' => 1,
            'content' => 'மங்கல வாத்தியம் எனப்படுவது தவில் மற்றும் நாதஸ்வரம் ஆகும்.',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$chunkVec),
            'word_count' => 7,
            'is_ocr' => false,
        ]);

        // Query vector has dot product 0.40 with chunkVec: 0.40 at index 0, sqrt(0.84) at index 1
        $queryVec = array_fill(0, 768, 0.0);
        $queryVec[0] = 0.40;
        $queryVec[1] = sqrt(0.84); // 0.40^2 + 0.84 = 1.0 (unit vector)

        $mockGemini = $this->createMock(GeminiClientInterface::class);
        $mockGemini->method('embedContent')->willReturn($queryVec);
        $this->app->instance(GeminiClientInterface::class, $mockGemini);

        $retrieval = app(RetrievalService::class);

        $query = 'மங்கல வாத்தியம் எது?';
        $result = $retrieval->retrieve($student, $query, 'Music', 5);

        $this->assertNotEmpty($result['chunks']);
        $hit = $result['chunks'][0];

        $embSim = $hit['embedding_similarity'];
        $finalSim = $hit['similarity'];
        $kwScore = $hit['keyword_score'];

        $this->assertGreaterThan(0.0, $kwScore, 'Relevant keywords must produce positive overlap.');
        $this->assertGreaterThanOrEqual($embSim, $finalSim, 'Hybrid score must be >= embedding score.');
        $this->assertLessThanOrEqual(1.0, $finalSim, 'Hybrid score must never exceed 1.0.');
    }

    /**
     * Test (c): Five unrelated questions still fail thresholds and yield not-found.
     */
    public function test_five_unrelated_questions_go_to_not_found(): void
    {
        $student = User::factory()->create([
            'role' => 'user',
            'current_grade' => 'grade-10',
            'medium' => 'tamil',
            'institute_id' => 1,
        ]);

        $material = LearningMaterial::create([
            'institute_id' => 1,
            'title' => 'Grade 10 Dance Exam 2026',
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Dance',
            'type' => 'past_paper',
            'file_path' => 'notes/test.pdf',
            'rag_status' => 'ready',
        ]);
        $material->updateQuietly(['rag_status' => 'ready']);

        // Orthogonal vector so cosine similarity is ~0.0
        $vector = array_fill(0, 768, 0.0);
        $vector[0] = 1.0;

        MaterialChunk::create([
            'institute_id' => 1,
            'material_id' => $material->id,
            'grade' => 'grade-10',
            'medium' => 'tamil',
            'subject' => 'Dance',
            'chunk_type' => 'qa',
            'page_number' => 2,
            'question_number' => '12-15',
            'content' => '12. மங்கல வாத்தியம் எனப்படுவது மிருதங்கம் வயலின் தவில் உடுக்கு',
            'embedding_model' => 'gemini-embedding-2',
            'dimensions' => 768,
            'embedding' => pack('f*', ...$vector),
            'word_count' => 8,
            'is_ocr' => false,
        ]);

        // Mock query vector orthogonal to chunk vector (cosine similarity = 0.0)
        $queryVector = array_fill(0, 768, 0.0);
        $queryVector[1] = 1.0;

        $mockGemini = $this->createMock(GeminiClientInterface::class);
        $mockGemini->method('embedContent')->willReturn($queryVector);
        $this->app->instance(GeminiClientInterface::class, $mockGemini);

        $retrieval = app(RetrievalService::class);

        $unrelatedQuestions = [
            'பிரான்சின் தலைநகரம் எது?',
            'How do you bake a chocolate cake at home?',
            'කොළඹ නගරයේ අද කාලගුණය කෙසේද?',
            'Who won the FIFA football world cup in 2022?',
            'ஒளிச்சேர்க்கை என்பது எவ்வாறு தாவரங்களில் நடைபெறுகின்றது?',
        ];

        foreach ($unrelatedQuestions as $question) {
            $result = $retrieval->retrieve($student, $question, 'Dance', 5);
            $validCandidates = array_filter($result['chunks'], fn($c) => $c['similarity'] >= 0.50);
            $this->assertEmpty($validCandidates, "Unrelated question '{$question}' must not return candidate chunks above threshold.");
        }
    }

    /**
     * Test Task 2: Distinct words are NOT merged by normalizer:
     * - கல் (stone) / கள் (toddy)
     * - அறம் (virtue) / அரம் (file/saw)
     * - மரம் (tree) / மறம் (courage)
     */
    public function test_distinct_words_are_not_merged_by_normalizer(): void
    {
        $normalizer = new TextNormalizer();

        // 1. கல் (stone) vs கள் (toddy)
        $this->assertFalse(
            $normalizer->tokensMatch('கல்', 'கள்'),
            "Distinct words 'கல்' (stone) and 'கள்' (toddy) must NEVER match."
        );
        $scoreKal = $normalizer->computeOverlapScore(['கல்'], 'கள் குடித்தல் உடலுக்கு கேடு');
        $this->assertEquals(0.0, $scoreKal, "'கல்' must have 0 overlap with text containing 'கள்'.");

        // 2. அறம் (virtue) vs அரம் (saw)
        $this->assertFalse(
            $normalizer->tokensMatch('அறம்', 'அரம்'),
            "Distinct words 'அறம்' (virtue) and 'அரம்' (saw/file) must NEVER match."
        );
        $scoreAram = $normalizer->computeOverlapScore(['அறம்'], 'அரம் கொண்டு இரும்பை அறுத்தார்கள்');
        $this->assertEquals(0.0, $scoreAram, "'அறம்' must have 0 overlap with text containing 'அரம்'.");

        // 3. மரம் (tree) vs மறம் (bravery/wrath)
        $this->assertFalse(
            $normalizer->tokensMatch('மரம்', 'மறம்'),
            "Distinct words 'மரம்' (tree) and 'மறம்' (wrath/courage) must NEVER match."
        );
        $scoreMaram = $normalizer->computeOverlapScore(['மறம்'], 'பலா மரம் மிகவும் உறுதியானது');
        $this->assertEquals(0.0, $scoreMaram, "'மறம்' must have 0 overlap with text containing 'மரம்'.");
    }

    /**
     * Test Task 2: Ordinary inflections STILL match via suffix stripping.
     */
    public function test_ordinary_inflections_match_via_suffix_stripping(): void
    {
        $normalizer = new TextNormalizer();

        // Noun plurals and case inflections of மரம்
        $inflectionsOfMaram = [
            'மரங்கள்',
            'மரத்தின்',
            'மரத்தில்',
            'மரத்திற்கு',
            'மரத்தை',
            'மரங்களின்',
        ];

        foreach ($inflectionsOfMaram as $inflection) {
            $stemmed = $normalizer->stemTamil($inflection);
            $this->assertEquals('மரம்', $stemmed, "Inflection '{$inflection}' must stem to 'மரம்'.");
            $this->assertTrue(
                $normalizer->tokensMatch('மரம்', $inflection),
                "'மரம்' must match inflected form '{$inflection}'."
            );
        }

        // Inflections of தவில்
        $inflectionsOfThavil = [
            'தவிலில்',
            'தவிலின்',
            'தவிலுக்கு',
            'தவிலை',
        ];

        foreach ($inflectionsOfThavil as $inflection) {
            $stemmed = $normalizer->stemTamil($inflection);
            $this->assertEquals('தவில்', $stemmed, "Inflection '{$inflection}' must stem to 'தவில்'.");
            $this->assertTrue(
                $normalizer->tokensMatch('தவில்', $inflection),
                "'தவில்' must match inflected form '{$inflection}'."
            );
        }

        // Inflections of வாத்தியம்
        $inflectionsOfVaathiyam = [
            'வாத்தியத்தை',
            'வாத்தியங்கள்',
            'வாத்தியத்தின்',
            'வாத்தியத்திற்கு',
            'வாத்தியத்தில்',
        ];

        foreach ($inflectionsOfVaathiyam as $inflection) {
            $stemmed = $normalizer->stemTamil($inflection);
            $this->assertEquals('வாத்தியம்', $stemmed, "Inflection '{$inflection}' must stem to 'வாத்தியம்'.");
            $this->assertTrue(
                $normalizer->tokensMatch('வாத்தியம்', $inflection),
                "'வாத்தியம்' must match inflected form '{$inflection}'."
            );
        }
    }

    /**
     * Test Task 2: Configurable variants list and toggles.
     */
    public function test_configurable_variants_list_and_toggles(): void
    {
        // 1. With variants ENABLED: மங்கல matches மங்கள
        $normalizerWithVariants = new TextNormalizer(
            suffixStrippingEnabled: true,
            variantsEnabled: true,
            fuzzyMatchingEnabled: false,
            variants: ['மங்கல' => 'மங்கள', 'மங்கள' => 'மங்கல']
        );
        $this->assertTrue(
            $normalizerWithVariants->tokensMatch('மங்கல', 'மங்கள'),
            "When variants are enabled, 'மங்கல' must match 'மங்கள'."
        );

        // 2. With variants DISABLED (and fuzzy disabled): மங்கல does NOT match மங்கள
        $normalizerWithoutVariants = new TextNormalizer(
            suffixStrippingEnabled: true,
            variantsEnabled: false,
            fuzzyMatchingEnabled: false
        );
        $this->assertFalse(
            $normalizerWithoutVariants->tokensMatch('மங்கல', 'மங்கள'),
            "When variants and fuzzy matching are disabled, 'மங்கல' must NOT match 'மங்கள'."
        );
    }

    /**
     * Test Task 2: Fuzzy matching bounded to edit distance 1 for words of 5 or more characters.
     */
    public function test_fuzzy_matching_bounded_to_length_five_and_edit_distance_one(): void
    {
        // 1. Words of length >= 5 with edit distance 1 match when fuzzy enabled
        $normalizerFuzzy = new TextNormalizer(
            suffixStrippingEnabled: true,
            variantsEnabled: false,
            fuzzyMatchingEnabled: true,
            minFuzzyWordLength: 5
        );

        // மங்கல (length 5) vs மங்கள (length 5): edit distance = 1
        $this->assertTrue(
            $normalizerFuzzy->tokensMatch('மங்கல', 'மங்கள'),
            "Words of length >= 5 with edit distance 1 must match under fuzzy matching."
        );

        // 2. Words with edit distance > 1 do NOT match under fuzzy matching
        $this->assertFalse(
            $normalizerFuzzy->tokensMatch('வாத்தியம்', 'பாத்திரம்'),
            "Edit distance > 1 must not match under fuzzy matching."
        );

        // 3. Short words (< 5 characters) like கல் / கள் (len 3) or மரம் / மறம் (len 4) NEVER match fuzzy
        $this->assertFalse(
            $normalizerFuzzy->tokensMatch('கல்', 'கள்'),
            "Short words (len 3) must never be matched by fuzzy matching."
        );
        $this->assertFalse(
            $normalizerFuzzy->tokensMatch('மரம்', 'மறம்'),
            "Short words (len 4) must never be matched by fuzzy matching."
        );

        // 4. When fuzzy matching is DISABLED, edit distance 1 words of len >= 5 do NOT match
        $normalizerNoFuzzy = new TextNormalizer(
            suffixStrippingEnabled: true,
            variantsEnabled: false,
            fuzzyMatchingEnabled: false,
            minFuzzyWordLength: 5
        );
        $this->assertFalse(
            $normalizerNoFuzzy->tokensMatch('மங்கல', 'மங்கள'),
            "When fuzzy matching is disabled, words with edit distance 1 must not match."
        );
    }
}

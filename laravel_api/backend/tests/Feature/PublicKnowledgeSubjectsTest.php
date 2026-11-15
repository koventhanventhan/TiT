<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\Subject;
use App\Services\PublicKnowledgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PublicKnowledgeSubjectsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regression: PublicKnowledgeService queried subjects.status, a column that does not exist,
     * which made every public assistant chat return HTTP 500 on live.
     */
    public function test_compile_context_lists_subjects_without_status_column(): void
    {
        $this->assertFalse(Schema::hasColumn('subjects', 'status'), 'subjects must not have a status column in this schema.');

        $institute = Institute::first() ?? Institute::create([
            'name' => 'Knowledge Test Institute',
            'slug' => 'knowledge-test',
            'status' => 'active',
        ]);

        Subject::create(['name' => 'Mathematics Regression', 'price' => 1000, 'category' => 'grade_6_to_11', 'medium' => 'english']);

        Cache::flush();
        $context = app(PublicKnowledgeService::class)->compileContext($institute->id);

        $this->assertStringContainsString('Mathematics Regression', $context);
    }
}

<?php

namespace Tests\Feature;

use App\Jobs\IngestLearningMaterialJob;
use App\Models\ActivityLog;
use App\Models\Faq;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminAssistantFaqAndReindexTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'FAQ Test Institute',
            'slug' => 'faq-inst',
            'status' => 'active',
        ]);

        $this->admin = User::factory()->create([
            'name' => 'FAQ Admin',
            'email' => 'admin@faq.lk',
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);
    }

    /**
     * Test full FAQ CRUD lifecycle per institute via Blade routes.
     */
    public function test_faq_crud_lifecycle_per_institute(): void
    {
        $this->actingAs($this->admin, 'web');

        // 1. Create FAQ
        $createRes = $this->post('/admin/assistant/faqs', [
            'question' => 'What subjects are offered?',
            'answer' => 'Mathematics, Science, and English.',
            'language' => 'en',
            'sort_order' => 1,
            'is_active' => 1,
        ]);
        $createRes->assertSessionHas('success');

        $faq = Faq::where('institute_id', $this->institute->id)->first();
        $this->assertNotNull($faq);
        $this->assertSame('What subjects are offered?', $faq->question);
        $this->assertSame('Mathematics, Science, and English.', $faq->answer);

        // Verify creation activity log
        $this->assertDatabaseHas('activity_logs', [
            'institute_id' => $this->institute->id,
            'action' => 'faq_created',
        ]);

        // 2. Read FAQ on page
        $viewRes = $this->get('/admin/assistant');
        $viewRes->assertOk();
        $viewRes->assertSee('What subjects are offered?');

        // 3. Update FAQ
        $updateRes = $this->put("/admin/assistant/faqs/{$faq->id}", [
            'question' => 'What subjects are currently offered?',
            'answer' => 'Mathematics, Science, English, and Tamil.',
            'language' => 'ta',
            'sort_order' => 5,
            'is_active' => 1,
        ]);
        $updateRes->assertSessionHas('success');

        $faq->refresh();
        $this->assertSame('What subjects are currently offered?', $faq->question);
        $this->assertSame('Mathematics, Science, English, and Tamil.', $faq->answer);
        $this->assertSame('ta', $faq->language);
        $this->assertSame(5, $faq->sort_order);

        // 4. Delete FAQ
        $deleteRes = $this->delete("/admin/assistant/faqs/{$faq->id}");
        $deleteRes->assertSessionHas('success');

        $this->assertDatabaseMissing('faqs', ['id' => $faq->id]);
        $this->assertDatabaseHas('activity_logs', [
            'institute_id' => $this->institute->id,
            'action' => 'faq_deleted',
        ]);
    }

    /**
     * Test failed material Re-index only dispatches for own institute.
     */
    public function test_failed_material_reindex_only_for_own_institute(): void
    {
        Queue::fake();

        $ownMat = LearningMaterial::create([
            'institute_id' => $this->institute->id,
            'title' => 'Biology Chapter 1 Notes',
            'type' => 'note',
            'file_path' => 'materials/bio1.pdf',
        ]);
        $ownMat->updateQuietly(['rag_status' => 'failed', 'rag_error' => 'Timeout during parsing']);

        $otherInstitute = Institute::create([
            'name' => 'Foreign Institute',
            'slug' => 'foreign-inst',
            'status' => 'active',
        ]);
        $foreignMat = LearningMaterial::create([
            'institute_id' => $otherInstitute->id,
            'title' => 'Foreign Chemistry Notes',
            'type' => 'note',
            'file_path' => 'materials/chem.pdf',
        ]);
        $foreignMat->updateQuietly(['rag_status' => 'failed', 'rag_error' => 'Syntax error']);

        $this->actingAs($this->admin, 'web');

        // 1. Re-index own material succeeds
        $resOwn = $this->post("/admin/assistant/materials/{$ownMat->id}/reindex");
        $resOwn->assertSessionHas('success');

        Queue::assertPushed(IngestLearningMaterialJob::class, function ($job) use ($ownMat) {
            return $job->materialId === $ownMat->id;
        });

        $this->assertSame('pending', $ownMat->fresh()->rag_status);
        $this->assertNull($ownMat->fresh()->rag_error);

        // 2. Re-index foreign material returns 404
        $resForeign = $this->post("/admin/assistant/materials/{$foreignMat->id}/reindex");
        $resForeign->assertNotFound();

        $this->assertSame('failed', $foreignMat->fresh()->rag_status);
    }
}


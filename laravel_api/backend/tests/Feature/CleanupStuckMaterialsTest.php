<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\LearningMaterial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CleanupStuckMaterialsTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();

        $this->institute = Institute::create([
            'name' => 'Stuck Materials Test Institute',
            'slug' => 'stuck-test-inst',
            'domain' => 'localhost',
            'status' => 'active',
        ]);
    }

    public function test_cleanup_stuck_materials_moves_old_processing_materials_to_failed(): void
    {
        Queue::fake();

        // 1. Stuck material older than 15 minutes
        $stuckMaterial = LearningMaterial::create([
            'title' => 'Stuck In Processing Note',
            'type' => 'pdf',
            'grade' => '10',
            'medium' => 'tamil',
            'rag_status' => 'processing',
            'institute_id' => $this->institute->id,
            'file_path' => 'notes/stuck.pdf',
        ]);
        $stuckMaterial->rag_status = 'processing';
        $stuckMaterial->rag_error = null;
        $stuckMaterial->updated_at = now()->subMinutes(25);
        $stuckMaterial->saveQuietly();

        // 2. Fresh processing material (only 2 minutes old)
        $freshMaterial = LearningMaterial::create([
            'title' => 'Fresh Processing Note',
            'type' => 'pdf',
            'grade' => '10',
            'medium' => 'tamil',
            'rag_status' => 'processing',
            'institute_id' => $this->institute->id,
            'file_path' => 'notes/fresh.pdf',
        ]);
        $freshMaterial->updated_at = now()->subMinutes(2);
        $freshMaterial->saveQuietly();

        // 3. Ready material (should remain untouched)
        $readyMaterial = LearningMaterial::create([
            'title' => 'Ready Note',
            'type' => 'pdf',
            'grade' => '10',
            'medium' => 'tamil',
            'rag_status' => 'ready',
            'institute_id' => $this->institute->id,
            'file_path' => 'notes/ready.pdf',
        ]);

        $exitCode = Artisan::call('rag:cleanup-stuck-materials', ['--minutes' => 15]);
        $this->assertEquals(0, $exitCode);

        $stuckMaterial->refresh();
        $this->assertEquals('failed', $stuckMaterial->rag_status);
        $this->assertEquals('Processing timed out after 15 minutes, retry later', $stuckMaterial->rag_error);

        $freshMaterial->refresh();
        $this->assertEquals('processing', $freshMaterial->rag_status, 'Fresh material must still be processing');

        $readyMaterial->refresh();
        $this->assertEquals('ready', $readyMaterial->rag_status, 'Ready material must not be affected');
    }
}

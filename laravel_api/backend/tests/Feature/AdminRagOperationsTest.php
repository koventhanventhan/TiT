<?php

namespace Tests\Feature;

use App\Jobs\IngestLearningMaterialJob;
use App\Models\Institute;
use App\Models\LearningMaterial;
use App\Models\SiteSetting;
use App\Models\StudentAiQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminRagOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $institute;
    protected User $admin;
    protected User $teacher;
    protected User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'name' => 'Premier Academy',
            'code' => 'PREMIER',
            'slug' => 'premier-academy',
            'status' => 'active',
            'rag_enabled' => true,
            'public_assistant_enabled' => true,
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'institute_id' => $this->institute->id,
        ]);

        $this->teacher = User::factory()->create([
            'role' => 'teacher',
            'institute_id' => $this->institute->id,
        ]);

        $this->student = User::factory()->create([
            'role' => 'user',
            'institute_id' => $this->institute->id,
            'registration_status' => 'approved',
        ]);
    }

    public function test_admin_can_retrieve_rag_status_telemetry(): void
    {
        Queue::fake();

        $readyMat = LearningMaterial::create([
            'title' => 'Biology Revision',
            'institute_id' => $this->institute->id,
            'type' => 'note',
            'file_path' => 'materials/bio.pdf',
        ]);
        $readyMat->updateQuietly(['rag_status' => 'ready', 'rag_error' => null]);

        $failedMat = LearningMaterial::create([
            'title' => 'Corrupt File Note',
            'institute_id' => $this->institute->id,
            'type' => 'note',
            'file_path' => 'materials/corrupt.pdf',
        ]);
        $failedMat->updateQuietly(['rag_status' => 'failed', 'rag_error' => 'File corrupted during extraction']);

        StudentAiQuery::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'question' => 'How does osmosis work?',
            'answer' => 'Osmosis is water transport.',
            'subject' => 'Biology',
            'status' => 'answered',
            'feedback_rating' => 'up',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/rag/status');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals(1, $data['materials_status']['ready']);
        $this->assertEquals(1, $data['materials_status']['failed']);
        $this->assertCount(1, $data['failed_materials']);
        $this->assertEquals('Corrupt File Note', $data['failed_materials'][0]['title']);
        $this->assertEquals('File corrupted during extraction', $data['failed_materials'][0]['rag_error']);
        $this->assertTrue($data['switches']['rag_enabled']);
        $this->assertTrue($data['switches']['public_assistant_enabled']);
    }

    public function test_admin_can_trigger_material_reindexing(): void
    {
        Queue::fake();

        $material = LearningMaterial::create([
            'title' => 'Chemistry Past Paper',
            'institute_id' => $this->institute->id,
            'type' => 'past_paper',
            'file_path' => 'materials/chem.pdf',
        ]);
        $material->updateQuietly(['rag_status' => 'failed', 'rag_error' => 'OCR failure']);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/admin/rag/materials/{$material->id}/reindex");

        $response->assertStatus(200);
        $this->assertStringContainsString('queued for re-indexing', $response->json('message'));

        $material->refresh();
        $this->assertEquals('pending', $material->rag_status);
        $this->assertNull($material->rag_error);

        Queue::assertPushed(IngestLearningMaterialJob::class, function ($job) use ($material) {
            return $job->materialId === $material->id;
        });
    }

    public function test_admin_can_toggle_assistant_switches(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/rag/toggle-assistant', [
                'rag_enabled' => false,
                'public_assistant_enabled' => false,
            ]);

        $response->assertStatus(200);
        $this->assertFalse($response->json('switches.rag_enabled'));
        $this->assertFalse($response->json('switches.public_assistant_enabled'));

        $this->institute->refresh();
        $this->assertFalse((bool) $this->institute->rag_enabled);
        $this->assertFalse((bool) $this->institute->public_assistant_enabled);
    }

    public function test_admin_and_teacher_can_view_redacted_feedback_loop_grouped_by_subject(): void
    {
        StudentAiQuery::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'question' => 'What is the speed of sound in nitrogen?',
            'answer' => 'I could not find notes about nitrogen.',
            'subject' => 'Physics',
            'status' => 'no_notes_found',
            'created_at' => now()->subDays(2),
        ]);

        StudentAiQuery::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'question' => 'Explain quantum entanglement simply',
            'answer' => 'Vague answer.',
            'subject' => 'Physics',
            'status' => 'answered',
            'feedback_rating' => 'down',
            'created_at' => now()->subDays(1),
        ]);

        StudentAiQuery::create([
            'user_id' => $this->student->id,
            'institute_id' => $this->institute->id,
            'question' => 'Who signed the Treaty of Versailles?',
            'answer' => 'No notes on Treaty of Versailles.',
            'subject' => 'History',
            'status' => 'no_notes_found',
            'created_at' => now()->subHours(5),
        ]);

        // Admin access
        $adminRes = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/rag/feedback-loop');
        $adminRes->assertStatus(200);
        $adminGroups = $adminRes->json('feedback_groups');

        $this->assertCount(2, $adminGroups);
        $this->assertEquals('Physics', $adminGroups[0]['subject']);
        $this->assertEquals(2, $adminGroups[0]['total_count']);
        $this->assertEquals(1, $adminGroups[0]['no_notes_count']);
        $this->assertEquals(1, $adminGroups[0]['thumbs_down_count']);

        // Check strict redaction: NO user_id, email, phone or student name
        $sampleQ = $adminGroups[0]['questions'][0];
        $this->assertArrayNotHasKey('user_id', $sampleQ);
        $this->assertArrayNotHasKey('user', $sampleQ);
        $this->assertArrayNotHasKey('email', $sampleQ);
        $this->assertArrayNotHasKey('phone', $sampleQ);

        // Teacher access
        $teacherRes = $this->actingAs($this->teacher, 'sanctum')
            ->getJson('/api/teacher/rag/feedback-loop');
        $teacherRes->assertStatus(200);
        $this->assertCount(2, $teacherRes->json('feedback_groups'));
    }

    public function test_admin_can_manage_public_contact_settings(): void
    {
        $updateRes = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/admin/rag/contact-settings', [
                'phone' => '+94 11 222 3333',
                'whatsapp' => '+94 77 999 8888',
                'email' => 'admissions@premier.lk',
                'address' => 'No 45 Temple Road, Colombo',
                'hours' => 'Mon-Sat 8AM - 5PM',
            ]);

        $updateRes->assertStatus(200);

        $getRes = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/admin/rag/contact-settings');

        $getRes->assertStatus(200);
        $getRes->assertJson([
            'phone' => '+94 11 222 3333',
            'whatsapp' => '+94 77 999 8888',
            'email' => 'admissions@premier.lk',
            'address' => 'No 45 Temple Road, Colombo',
            'hours' => 'Mon-Sat 8AM - 5PM',
        ]);
    }

    public function test_student_cannot_access_admin_rag_endpoints(): void
    {
        $this->actingAs($this->student, 'sanctum')
            ->getJson('/api/admin/rag/status')
            ->assertStatus(403);

        $this->actingAs($this->student, 'sanctum')
            ->postJson('/api/admin/rag/contact-settings', ['phone' => '123'])
            ->assertStatus(403);
    }
}

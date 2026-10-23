<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RagSafeDefaultsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
    }

    /**
     * Test config defaults to false when env vars are empty/missing.
     */
    public function test_rag_config_defaults_to_false_with_empty_env(): void
    {
        // When RAG_ENABLED and RAG_PUBLIC_ENABLED are not set or false:
        $this->assertFalse(config('rag.enabled'));
        $this->assertFalse(config('rag.public_enabled'));
    }

    /**
     * Test User::isRagEnabled() defaults to false.
     */
    public function test_user_is_rag_enabled_defaults_to_false(): void
    {
        $user = new User();
        $this->assertFalse($user->isRagEnabled(), 'User with no institute must default to RAG disabled.');

        $institute = Institute::create([
            'name' => 'Default Test Institute',
            'slug' => 'default-test',
            'domain' => 'default.localhost',
            'status' => 'active',
            'rag_enabled' => false,
        ]);

        $user->institute_id = $institute->id;
        $this->assertFalse($user->isRagEnabled(), 'User with institute having rag_enabled=false must return false.');
    }

    /**
     * Test StudentAiChatController denies access when RAG is disabled.
     */
    public function test_student_chat_denied_when_rag_disabled(): void
    {
        $institute = Institute::create([
            'name' => 'Institute Disabled',
            'slug' => 'inst-disabled',
            'domain' => 'localhost',
            'status' => 'active',
            'rag_enabled' => false,
        ]);

        $student = User::factory()->create([
            'institute_id' => $institute->id,
            'current_grade' => 'grade-10',
            'medium' => 'english',
            'role' => 'user',
            'registration_status' => 'approved',
            'admin_confirmed_at' => now(),
            'deactivated_at' => null,
        ]);

        $student->payments()->create([
            'amount' => 1500,
            'status' => 'paid',
            'year_month' => now()->format('Y-m'),
        ]);

        $this->actingAs($student);

        config(['rag.enabled' => false]);

        $response = $this->postJson('/api/student/ai-chat', [
            'question' => 'What is photosynthesis?',
        ]);

        $this->assertEquals(503, $response->status());
        $this->assertStringContainsString('disabled', strtolower($response->json('message') ?? ''));
    }

    /**
     * Test AssistantChatController denies access when public assistant is disabled.
     */
    public function test_public_assistant_denies_access_when_disabled(): void
    {
        $institute = Institute::create([
            'name' => 'Institute Public Disabled',
            'slug' => 'public-disabled',
            'domain' => 'public-disabled.localhost',
            'status' => 'active',
            'public_assistant_enabled' => false,
        ]);

        config(['rag.public_enabled' => false]);

        $response = $this->withHeaders([
            'X-Institute-Id' => (string) $institute->id,
        ])->postJson('/api/assistant/chat', [
            'question' => 'What courses do you offer?',
        ]);

        $this->assertEquals(403, $response->status());
        $this->assertFalse($response->json('public_enabled'));
    }
}

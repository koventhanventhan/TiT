<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\StudentAiQuery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RagInstituteCommandTest extends TestCase
{
    use RefreshDatabase;

    private Institute $institute;

    protected function setUp(): void
    {
        parent::setUp();

        $this->institute = Institute::create([
            'id' => 1,
            'name' => 'Apex Test Academy',
            'slug' => 'apex',
            'status' => 'active',
            'rag_enabled' => false,
        ]);
    }

    public function test_rag_institute_on_refuses_when_helplines_or_institute_contact_missing(): void
    {
        // Empty primary helpline and institute contact
        config([
            'rag.safety.helplines' => [
                ['name' => '', 'number' => ''],
                ['name' => '', 'number' => ''],
                ['name' => 'Institute Support', 'number' => ''],
            ],
        ]);

        $this->artisan('rag:institute', ['id' => 1, 'status' => 'on'])
            ->expectsOutputToContain('Cannot enable RAG Study Assistant')
            ->expectsOutputToContain('Primary crisis helpline name and phone number')
            ->expectsOutputToContain('Institute emergency contact number')
            ->assertFailed();

        $this->institute->refresh();
        $this->assertFalse((bool) $this->institute->rag_enabled, 'Institute RAG must remain false when helplines are missing.');
    }

    public function test_rag_institute_on_succeeds_when_contacts_are_configured(): void
    {
        config([
            'rag.safety.helplines' => [
                ['name' => 'National Child Helpline', 'number' => '1929'],
                ['name' => 'Mental Health Hotline', 'number' => '1926'],
                ['name' => 'Institute Emergency Line', 'number' => '0112345678'],
            ],
        ]);

        $this->artisan('rag:institute', ['id' => 1, 'status' => 'on'])
            ->expectsOutputToContain('ENABLED (ON)')
            ->assertSuccessful();

        $this->institute->refresh();
        $this->assertTrue((bool) $this->institute->rag_enabled, 'Institute RAG must be enabled.');
    }

    public function test_rag_institute_off_disables_assistant(): void
    {
        $this->institute->update(['rag_enabled' => true]);

        $this->artisan('rag:institute', ['id' => 1, 'status' => 'off'])
            ->expectsOutputToContain('DISABLED (OFF)')
            ->assertSuccessful();

        $this->institute->refresh();
        $this->assertFalse((bool) $this->institute->rag_enabled);
    }

    public function test_rag_status_shows_safety_blocks_as_count_and_excludes_them_from_distress_list(): void
    {
        $student = User::factory()->create([
            'institute_id' => $this->institute->id,
            'role' => 'user',
        ]);

        // 1. Distress query: is_flagged = true, flag_reason = 'Personal distress / self-harm detected'
        StudentAiQuery::create([
            'institute_id' => $this->institute->id,
            'user_id' => $student->id,
            'question' => 'I feel like I want to end my life',
            'answer' => 'Caring help message',
            'status' => 'distress_blocked',
            'is_flagged' => true,
            'flag_reason' => 'Personal distress / self-harm detected',
        ]);

        // 2. Safety block query: is_flagged = false, flag_reason = 'safety_block'
        StudentAiQuery::create([
            'institute_id' => $this->institute->id,
            'user_id' => $student->id,
            'question' => 'Blocked chemistry inquiry',
            'answer' => 'This question was blocked by our child safety filters.',
            'status' => 'blocked_safety',
            'is_flagged' => false,
            'flag_reason' => 'safety_block',
        ]);

        $this->artisan('rag:status')
            ->expectsOutputToContain('Flagged Distress Queries')
            ->expectsOutputToContain('Blocked Safety Queries')
            ->expectsOutputToContain('Personal distress / self-harm detected')
            ->assertSuccessful();
    }
}

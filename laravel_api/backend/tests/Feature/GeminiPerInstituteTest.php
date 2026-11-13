<?php

namespace Tests\Feature;

use App\Jobs\FinalizeMaterialIngestionJob;
use App\Models\AssistantVisitorLog;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\InstituteIntegration;
use App\Models\LearningMaterial;
use App\Models\MaterialPage;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\Rag\GeminiRagClient;
use App\Support\Integrations;
use App\Support\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GeminiPerInstituteTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $instOne;
    protected Institute $instTwo;
    protected User $studentOne;
    protected User $studentTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instOne = Institute::create([
            'name' => 'TiT Primary',
            'slug' => 'tit-primary',
            'status' => 'active',
            'rag_enabled' => true,
            'public_assistant_enabled' => true,
        ]);

        $this->instTwo = Institute::create([
            'name' => 'Colombo Campus',
            'slug' => 'colombo-campus',
            'status' => 'active',
            'rag_enabled' => true,
            'public_assistant_enabled' => true,
        ]);

        InstituteDomain::create([
            'institute_id' => $this->instOne->id,
            'domain' => 'primary.test',
            'status' => 'active',
        ]);

        InstituteDomain::create([
            'institute_id' => $this->instTwo->id,
            'domain' => 'colombo.test',
            'status' => 'active',
        ]);

        $this->studentOne = User::factory()->create([
            'institute_id' => $this->instOne->id,
            'role' => 'user',
            'current_grade' => '12',
            'medium' => 'english',
            'admin_confirmed_at' => now(),
        ]);

        $this->studentOne->payments()->create([
            'order_id' => 'PAY_' . uniqid(),
            'amount' => 1500,
            'status' => 'paid',
            'year_month' => now()->format('Y-m'),
        ]);

        $this->studentTwo = User::factory()->create([
            'institute_id' => $this->instTwo->id,
            'role' => 'user',
            'current_grade' => '12',
            'medium' => 'english',
            'admin_confirmed_at' => now(),
        ]);

        $this->studentTwo->payments()->create([
            'order_id' => 'PAY_' . uniqid(),
            'amount' => 1500,
            'status' => 'paid',
            'year_month' => now()->format('Y-m'),
        ]);

        config([
            'integrations.env_fallback_institutes' => [$this->instOne->id],
            'services.gemini.api_key' => 'AIzaSyENV_KEY_FOR_INST_ONE_1111111',
            'rag.public_enabled' => true,
        ]);
    }

    public function test_two_queued_ingestion_jobs_for_institute_one_and_two_use_their_own_keys(): void
    {
        // Inst 2 has its own DB key
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_GEMINI,
            'credentials' => [
                'api_key' => 'AIzaSyINST_TWO_CUSTOM_KEY_2222222',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        $matOne = LearningMaterial::create([
            'institute_id' => $this->instOne->id,
            'title' => 'Doc Inst One',
            'type' => 'note',
            'file_path' => 'materials/doc1.pdf',
            'subject' => 'Physics',
            'grade' => '12',
            'medium' => 'english',
        ]);

        $matTwo = LearningMaterial::create([
            'institute_id' => $this->instTwo->id,
            'title' => 'Doc Inst Two',
            'type' => 'note',
            'file_path' => 'materials/doc2.pdf',
            'subject' => 'Physics',
            'grade' => '12',
            'medium' => 'english',
        ]);

        $text1 = 'Sir Isaac Newton stated three fundamental laws of motion that describe the relationship between any physical body and the forces acting upon it in classical mechanics. Every body continues in its state of rest, or of uniform motion in a right line, unless it is compelled to change that state by forces impressed upon it.';
        $text2 = 'Quantum mechanics is a fundamental theory in physics that provides a description of the physical properties of nature at the scale of atoms and subatomic particles. It is the foundation of all quantum physics including quantum chemistry and quantum field theory.';

        Storage::disk('local')->put("rag_temp/material_{$matOne->id}/page_1.json", json_encode([
            'page_number' => 1,
            'text' => $text1,
            'used_ocr' => false,
        ]));

        Storage::disk('local')->put("rag_temp/material_{$matTwo->id}/page_1.json", json_encode([
            'page_number' => 1,
            'text' => $text2,
            'used_ocr' => false,
        ]));

        $capturedKeys = [];

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function (Request $request) use (&$capturedKeys) {
                $capturedKeys[] = $request->header('x-goog-api-key')[0] ?? null;

                return Http::response([
                    'embeddings' => [
                        ['values' => array_fill(0, 768, 0.05)],
                    ],
                    'candidates' => [
                        [
                            'content' => [
                                'parts' => [
                                    ['text' => '{"readable": true, "score": 0.95, "language": "english", "reason": "Clear english text."}'],
                                ],
                            ],
                        ],
                    ],
                ], 200);
            },
        ]);

        // Same client instance simulating a single long-running queue worker!
        $client = app(GeminiRagClient::class);

        $jobOne = new FinalizeMaterialIngestionJob($matOne->id, 1);
        app()->call([$jobOne, 'handle'], ['geminiClient' => $client]);

        $this->assertNotEmpty($capturedKeys);
        $this->assertSame('AIzaSyENV_KEY_FOR_INST_ONE_1111111', $capturedKeys[0]);

        $capturedCountAfterJobOne = count($capturedKeys);

        $jobTwo = new FinalizeMaterialIngestionJob($matTwo->id, 1);
        app()->call([$jobTwo, 'handle'], ['geminiClient' => $client]);

        $this->assertGreaterThan($capturedCountAfterJobOne, count($capturedKeys));
        $this->assertSame('AIzaSyINST_TWO_CUSTOM_KEY_2222222', $capturedKeys[$capturedCountAfterJobOne]);
    }

    public function test_institute_without_key_returns_typed_ai_not_configured_error(): void
    {
        // Inst 2 has NO Gemini credentials
        // 1. Public assistant chat -> 403 {code: 'ai_not_configured', public_enabled: false}
        Tenant::run($this->instTwo, function () {
            $response = $this->postJson('http://colombo.test/api/assistant/chat', [
                'question' => 'What courses do you offer?',
            ]);

            $response->assertStatus(403);
            $response->assertJson([
                'code' => 'ai_not_configured',
                'public_enabled' => false,
            ]);
        });

        // 2. Study assistant chat -> 422 {code: 'ai_not_configured'}
        $studyResponse = $this->actingAs($this->studentTwo)
            ->postJson('/api/assistant/chat', [
                'question' => 'Explain Newton third law of motion in detail.',
            ]);

        $studyResponse->assertStatus(422);
        $studyResponse->assertJson([
            'code' => 'ai_not_configured',
        ]);
        $this->assertStringContainsString('Settings', $studyResponse->json('message'));

        // 3. Translate endpoint -> 422 {code: 'ai_not_configured'}
        Tenant::run($this->instTwo, function () {
            $transResponse = $this->postJson('/api/translate', [
                'text' => 'Hello World',
                'target_lang' => 'ta',
            ]);

            $transResponse->assertStatus(422);
            $transResponse->assertJson([
                'code' => 'ai_not_configured',
            ]);
        });

        // 4. /api/settings flag assistant_available is false
        Tenant::run($this->instTwo, function () {
            $settingsRes = $this->getJson('/api/settings');
            $settingsRes->assertOk();
            $this->assertFalse($settingsRes->json('assistant_available'));
            $this->assertFalse($settingsRes->json('public_assistant_enabled'));
        });
    }

    public function test_assistant_institute_resolved_from_host_and_ignores_visitor_x_institute_id_header(): void
    {
        // Inst 1 (host: primary.test) has fallback env Gemini key.
        // Inst 2 (host: colombo.test) has NO key.
        // A visitor on host A (primary.test) sending X-Institute-Id: 2 must answer from Inst 1, not 2!
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'GENERAL_INFO: Welcome to TiT Primary.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->withHeader('X-Institute-Id', (string) $this->instTwo->id)
            ->postJson('http://primary.test/api/assistant/chat', [
                'question' => 'Where is the school located?',
            ]);

        // If it resolved to Inst 2, it would have returned 403 ai_not_configured!
        // But because host is primary.test, it resolved to Inst 1 and succeeded!
        $this->assertNotEquals(403, $response->status());
        $this->assertNotEquals('ai_not_configured', $response->json('code'));
    }

    public function test_per_institute_visitor_daily_cap_and_rate_limits(): void
    {
        // Inst 2 has custom cap 60 and its own key
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_GEMINI,
            'credentials' => [
                'api_key' => 'AIzaSyINST2_KEY_123',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);

        SiteSetting::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'key' => 'assistant_daily_cap',
            'value' => '50',
            'group' => 'rag_settings',
        ]);

        // Seed 50 logs for Inst 2 today
        for ($i = 0; $i < 50; $i++) {
            AssistantVisitorLog::create([
                'institute_id' => $this->instTwo->id,
                'visitor_hash' => 'hash_' . $i,
                'ip_address' => '192.168.1.' . $i,
                'status' => 'answered',
                'language' => 'en',
            ]);
        }

        // Querying Inst 2 hits daily cap 429
        Tenant::run($this->instTwo, function () {
            $resInst2 = $this->postJson('http://colombo.test/api/assistant/chat', [
                'question' => 'What are the admission fees?',
            ]);

            $resInst2->assertStatus(429);
            $this->assertTrue($resInst2->json('daily_cap_reached'));
        });

        // Meanwhile Inst 1 has 0 logs and still works fine
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'GENERAL_INFO: Welcome.'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        Tenant::run($this->instOne, function () {
            $resInst1 = $this->postJson('http://primary.test/api/assistant/chat', [
                'question' => 'What are the admission fees?',
            ]);

            $this->assertNotEquals(429, $resInst1->status());
        });
    }
}

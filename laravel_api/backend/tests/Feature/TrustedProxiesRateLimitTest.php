<?php

namespace Tests\Feature;

use App\Models\Institute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TrustedProxiesRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('assistant_ip_min:127.0.0.1');
        RateLimiter::clear('assistant_ip_day:127.0.0.1');
        Http::fake([
            '*' => Http::response(['candidates' => [['content' => ['parts' => [['text' => 'Faked response']]]]]], 200),
        ]);
    }

    public function test_forged_x_forwarded_for_header_does_not_change_client_ip_when_no_proxy_is_trusted()
    {
        // Default TRUSTED_PROXIES is empty string (untrusted)
        $institute = Institute::create([
            'name' => 'TiT Test Institute',
            'slug' => 'tit-test',
            'rag_enabled' => true,
            'public_assistant_enabled' => true,
        ]);

        // When client sends request with forged X-Forwarded-For: 203.0.113.195
        // but TRUSTED_PROXIES is empty, $request->ip() must remain the direct client IP (127.0.0.1)
        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.195'])
            ->postJson('/api/assistant/chat', [
                'question' => 'grade 10 past paper notes',
                'institute_id' => $institute->id,
            ]);

        // Request succeeds or returns valid assistant response
        $this->assertTrue(in_array($response->status(), [200, 429]));

        // Check that rate limiter recorded against 127.0.0.1, NOT against the forged 203.0.113.195
        $this->assertTrue(
            RateLimiter::attempts('assistant_ip_min:127.0.0.1') > 0 ||
            RateLimiter::attempts('assistant_ip_day:127.0.0.1') > 0,
            'Rate limit attempts should be recorded against the real REMOTE_ADDR (127.0.0.1)'
        );
        $this->assertEquals(
            0,
            RateLimiter::attempts('assistant_ip_min:203.0.113.195'),
            'Rate limit attempts must NOT be recorded against untrusted forged X-Forwarded-For header'
        );
    }

    public function test_varying_forged_headers_cannot_bypass_rate_limiting()
    {
        $institute = Institute::create([
            'name' => 'TiT Test Institute',
            'slug' => 'tit-test',
            'rag_enabled' => true,
            'public_assistant_enabled' => true,
        ]);

        Config::set('rag.assistant.visitor_per_minute', 5);

        // Send 5 requests from same REMOTE_ADDR but changing X-Forwarded-For each time
        for ($i = 1; $i <= 5; $i++) {
            $resp = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
                ->withHeaders(['X-Forwarded-For' => "198.51.100.{$i}"])
                ->postJson('/api/assistant/chat', [
                    'question' => 'grade 10 past paper notes', // quick academic rejection without external call
                    'institute_id' => $institute->id,
                ]);
            $this->assertEquals(200, $resp->status());
        }

        // The 6th request with yet another forged IP should be blocked with 429 because REMOTE_ADDR is 127.0.0.1
        $blockedResp = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders(['X-Forwarded-For' => '198.51.100.99'])
            ->postJson('/api/assistant/chat', [
                'question' => 'grade 10 past paper notes',
                'institute_id' => $institute->id,
            ]);

        $this->assertEquals(429, $blockedResp->status(), '6th request must be rate-limited to 429 despite forged X-Forwarded-For header');
    }
}

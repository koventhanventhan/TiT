<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RegistrationMergeTest extends TestCase
{
    use RefreshDatabase;

    protected $institute;

    protected function setUp(): void
    {
        parent::setUp();
        
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        
        $this->institute = \App\Models\Institute::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'domain' => 'test',
            'status' => 'active',
        ]);
    }

    public function test_existing_parent_account_returns_409()
    {
        $parent = User::factory()->create([
            'email' => 'parent@example.com',
            'phone_number' => '94771234567',
            'role' => 'user',
            'parent_id' => null,
            'institute_id' => $this->institute->id
        ]);

        $payload = [
            'username' => 'parent@example.com',
            'full_name' => 'New Student',
            'phone_number' => '94771234567',
            'date_of_birth' => '2010-01-01',
            'gender' => 'male',
            'school_name' => 'Test School',
            'medium' => 'english',
            'online_experience' => 'yes',
            'device_used' => 'mobile',
            'current_grade' => 'Grade 10',
            'selected_subjects' => json_encode(['Maths', 'Science'])
        ];

        // Ensure header is sent to match Institute ID
        $response = $this->postJson('/api/register/step1', $payload, ['X-Institute-Id' => $this->institute->id]);

        $response->assertStatus(409)
                 ->assertJsonStructure(['status', 'merge_token', 'masked_contact']);
        
        $mergeToken = $response->json('merge_token');
        $this->assertNotNull($mergeToken);
    }

    public function test_send_merge_otp_caches_otp_and_respects_rate_limit()
    {
        $parent = User::factory()->create([
            'email' => 'parent@example.com',
            'phone_number' => '94771234567',
            'role' => 'user',
            'parent_id' => null,
            'institute_id' => $this->institute->id
        ]);

        $mergeToken = 'test-merge-token-1';
        Cache::put('merge_token_' . $mergeToken, $parent->id, now()->addMinutes(10));

        RateLimiter::clear('merge_otp_requests_' . $parent->id);

        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/register/send-merge-otp', ['merge_token' => $mergeToken]);
            $response->assertStatus(200);
        }

        // 4th request should fail due to rate limit
        $response = $this->postJson('/api/register/send-merge-otp', ['merge_token' => $mergeToken]);
        $response->assertStatus(429);

        // Assert OTP is in cache
        $this->assertTrue(Cache::has('merge_otp_' . $parent->id));
    }

    public function test_send_merge_otp_respects_ip_rate_limit()
    {
        $parent = User::factory()->create([
            'email' => 'parent2@example.com',
            'phone_number' => '94771234568',
            'role' => 'user',
            'parent_id' => null,
            'institute_id' => $this->institute->id
        ]);

        $ipLimitKey = 'merge_otp_ip_127.0.0.1';
        RateLimiter::clear($ipLimitKey);

        for ($i = 0; $i < 5; $i++) {
            // We can just use an invalid token because the IP check happens before the token lookup
            $response = $this->postJson('/api/register/send-merge-otp', ['merge_token' => 'invalid-token-here'], ['REMOTE_ADDR' => '127.0.0.1']);
            // The token is invalid, so it returns 400 (not 429)
            $response->assertStatus(400);
        }

        // 6th request should fail due to IP rate limit
        $response = $this->postJson('/api/register/send-merge-otp', ['merge_token' => 'invalid-token-here'], ['REMOTE_ADDR' => '127.0.0.1']);
        $response->assertStatus(429)
                 ->assertJson(['message' => 'Too many OTP requests from this IP. Please try again later.']);
    }

    public function test_verify_merge_otp_creates_sibling()
    {
        $parent = User::factory()->create([
            'email' => 'parent@example.com',
            'phone_number' => '94771234567',
            'role' => 'user',
            'parent_id' => null,
            'institute_id' => $this->institute->id
        ]);

        $mergeToken = 'test-merge-token-2';
        Cache::put('merge_token_' . $mergeToken, $parent->id, now()->addMinutes(10));
        Cache::put('merge_otp_' . $parent->id, '123456', now()->addMinutes(10));
        RateLimiter::clear('merge_verify_requests_' . $parent->id);

        $payload = [
            'merge_token' => $mergeToken,
            'otp' => '123456',
            'full_name' => 'Sibling Student',
            'date_of_birth' => '2012-05-05',
            'gender' => 'female',
            'school_name' => 'Another School',
            'medium' => 'english',
            'current_grade' => 'Grade 8',
            'selected_subjects' => json_encode(['English'])
        ];

        $response = $this->postJson('/api/register/verify-merge-otp', $payload);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user', 'profiles']);

        // Assert sibling is in database
        $this->assertDatabaseHas('users', [
            'parent_id' => $parent->id,
            'role' => 'user',
            'full_name' => 'Sibling Student'
        ]);

        // Assert OTP is deleted
        $this->assertFalse(Cache::has('merge_otp_' . $parent->id));
    }

    public function test_verify_merge_otp_handles_incorrect_attempts()
    {
        $parent = User::factory()->create([
            'email' => 'parent@example.com',
            'phone_number' => '94771234567',
            'role' => 'user',
            'parent_id' => null,
            'institute_id' => $this->institute->id
        ]);

        $mergeToken = 'test-merge-token-3';
        Cache::put('merge_token_' . $mergeToken, $parent->id, now()->addMinutes(10));
        Cache::put('merge_otp_' . $parent->id, '123456', now()->addMinutes(10));
        RateLimiter::clear('merge_verify_requests_' . $parent->id);
        Cache::forget('merge_otp_attempts_' . $parent->id);

        $payload = [
            'merge_token' => $mergeToken,
            'otp' => '654321', // Incorrect OTP
            'full_name' => 'Sibling Student'
        ];

        // 5 incorrect attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/register/verify-merge-otp', $payload);
            if ($i < 4) {
                $response->assertStatus(400)->assertJson(['message' => 'Invalid OTP.']);
            } else {
                $response->assertStatus(400)->assertJson(['message' => 'Too many incorrect attempts. The OTP has been invalidated. Please request a new one.']);
            }
        }

        // OTP should be deleted after 5 attempts
        $this->assertFalse(Cache::has('merge_otp_' . $parent->id));
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use Tests\TestCase;
use App\Mail\EmailVerificationOtpMail;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $institute;

    public function setUp(): void
    {
        parent::setUp();
        
        \Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
        
        $this->institute = \App\Models\Institute::create([
            'name' => 'Test Institute',
            'slug' => 'test-institute',
            'domain' => 'localhost',
            'status' => 'active',
        ]);
        
        // Clear rate limits
        RateLimiter::clear('email_otp_ip_127.0.0.1');
    }

    public function test_send_otp_rejects_fake_domain()
    {
        $response = $this->postJson('/api/auth/send-verification-otp', [
            'email' => 'test@fakedomain123xyz.com'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_send_otp_success_with_valid_domain()
    {
        Mail::fake();

        $email = 'test@example.com';
        
        $response = $this->postJson('/api/auth/send-verification-otp', [
            'email' => $email
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'OTP sent successfully.']);

        $this->assertTrue(Cache::has('email_verify_' . $email));

        Mail::assertSent(EmailVerificationOtpMail::class, function ($mail) use ($email) {
            return $mail->hasTo($email);
        });
    }

    public function test_send_otp_rejects_already_registered_user()
    {
        $email = 'existing@example.com';
        User::factory()->create([
            'email' => $email,
            'full_name' => 'Existing User'
        ]);

        $response = $this->postJson('/api/auth/send-verification-otp', [
            'email' => $email
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_verify_otp_success()
    {
        $email = 'test@example.com';
        $otp = '123456';
        
        Cache::put('email_verify_' . $email, $otp, now()->addMinutes(10));

        $response = $this->postJson('/api/auth/verify-email-otp', [
            'email' => $email,
            'otp' => $otp
        ]);

        $response->assertStatus(200)
                 ->assertJson(['message' => 'Email verified successfully.']);

        $this->assertFalse(Cache::has('email_verify_' . $email));
        $this->assertTrue(Cache::has('email_verified_' . $email));
    }

    public function test_verify_otp_invalid_code()
    {
        $email = 'test@example.com';
        
        Cache::put('email_verify_' . $email, '123456', now()->addMinutes(10));

        $response = $this->postJson('/api/auth/verify-email-otp', [
            'email' => $email,
            'otp' => '654321' // wrong otp
        ]);

        $response->assertStatus(400)
                 ->assertJson(['message' => 'Invalid OTP.']);
                 
        $this->assertEquals(1, Cache::get('email_verify_attempts_' . $email));
    }

    public function test_register_fails_if_email_not_verified()
    {
        $email = 'newuser@example.com';
        
        $response = $this->postJson('/api/auth/register', [
            'email' => $email,
            'password' => 'Password123!',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_register_succeeds_if_email_verified()
    {
        $email = 'newuser@example.com';
        
        Cache::put('email_verified_' . $email, true, now()->addMinutes(30));
        
        $response = $this->postJson('/api/auth/register', [
            'email' => $email,
            'password' => 'Password123!',
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);

        $response->assertStatus(201);
        $this->assertFalse(Cache::has('email_verified_' . $email));
    }
}

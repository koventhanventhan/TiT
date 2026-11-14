<?php

namespace Tests\Feature;

use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\InstituteIntegration;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleLoginIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected Institute $instOne;
    protected Institute $instTwo;

    protected function setUp(): void
    {
        parent::setUp();

        \Illuminate\Support\Facades\Http::fake();

        $this->instOne = Institute::create([
            'name' => 'TiT Primary',
            'slug' => 'tit-primary',
            'status' => 'active',
        ]);

        $this->instTwo = Institute::create([
            'name' => 'Colombo Campus',
            'slug' => 'colombo-campus',
            'status' => 'active',
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

        // Configure Google integration for institute 2
        InstituteIntegration::withoutGlobalScopes()->create([
            'institute_id' => $this->instTwo->id,
            'provider' => InstituteIntegration::PROVIDER_GOOGLE,
            'credentials' => [
                'client_secret' => 'INST2_GOOGLE_CLIENT_SECRET',
            ],
            'settings' => [
                'client_id' => 'INST2_GOOGLE_CLIENT_ID',
                'redirect_uri' => 'http://colombo.test/api/auth/google/callback',
            ],
            'status' => InstituteIntegration::STATUS_CONFIGURED,
        ]);
    }

    protected function mockSocialiteUser(string $email, string $googleId = 'google-123456', string $name = 'Test User'): void
    {
        $socialiteUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $socialiteUser->shouldReceive('getId')->andReturn($googleId);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getNickname')->andReturn('testuser');
        $socialiteUser->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar/123');

        $provider = Mockery::mock(\Laravel\Socialite\Two\GoogleProvider::class);
        $provider->shouldReceive('stateless')->andReturnSelf();
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_google_login_with_email_existing_in_other_institute_is_rejected(): void
    {
        // User exists in Institute 1
        $userInst1 = User::create([
            'name' => 'Inst1 Student',
            'email' => 'shared@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'institute_id' => $this->instOne->id,
            'registration_status' => 'confirmed',
        ]);

        $this->mockSocialiteUser('shared@example.com', 'google-inst1-user');

        // Attempt Google callback on Institute 2 host
        $response = $this->withServerVariables(['HTTP_HOST' => 'colombo.test'])
            ->get('http://colombo.test/api/auth/google/callback');

        $response->assertOk();
        $content = $response->getContent();

        // 1. Must NOT return auth token
        $this->assertStringNotContainsString('"token":', $content);

        // 2. Must NOT create new user for Institute 2
        $this->assertEquals(0, User::where('institute_id', $this->instTwo->id)->count());

        // 3. Must NOT update google_id on Institute 1 user
        $this->assertNull($userInst1->fresh()->google_id);

        // 4. Must return friendly error without exposing other tenant info
        $this->assertStringContainsString('This e-mail address is already registered with another institute. Please use a different e-mail address.', $content);
        $this->assertStringNotContainsString('TiT Primary', $content);

        // 5. PostMessage target origin must equal host
        $this->assertStringContainsString("window.opener.postMessage", $content);
        $this->assertStringContainsString("'http://colombo.test'", $content);
        $this->assertStringNotContainsString("'*'", $content);
    }

    public function test_google_login_existing_user_of_same_institute_succeeds(): void
    {
        // User exists in Institute 2
        $userInst2 = User::create([
            'name' => 'Colombo Student',
            'email' => 'colombo@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'institute_id' => $this->instTwo->id,
            'registration_status' => 'confirmed',
        ]);

        $this->mockSocialiteUser('colombo@example.com', 'google-inst2-existing');

        $response = $this->withServerVariables(['HTTP_HOST' => 'colombo.test'])
            ->get('http://colombo.test/api/auth/google/callback');

        $response->assertOk();
        $content = $response->getContent();

        // Must succeed with token and institute_id 2
        $this->assertStringContainsString('"message":"Login successful"', $content);
        $this->assertStringContainsString('"token":', $content);
        $this->assertStringContainsString('"institute_id":' . $this->instTwo->id, $content);

        // google_id written
        $this->assertSame('google-inst2-existing', $userInst2->fresh()->google_id);

        // PostMessage target origin equals host
        $this->assertStringContainsString("'http://colombo.test'", $content);
        $this->assertStringNotContainsString("'*'", $content);
    }

    public function test_google_login_new_email_on_institute_creates_pending_student(): void
    {
        $this->mockSocialiteUser('brandnew@example.com', 'google-new-student', 'New Student');

        $response = $this->withServerVariables(['HTTP_HOST' => 'colombo.test'])
            ->get('http://colombo.test/api/auth/google/callback');

        $response->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('"message":"Login successful"', $content);
        $this->assertStringContainsString('"token":', $content);

        // User created under institute 2 with pending status
        $newUser = User::where('email', 'brandnew@example.com')->first();
        $this->assertNotNull($newUser);
        $this->assertEquals($this->instTwo->id, $newUser->institute_id);
        $this->assertEquals('pending', $newUser->registration_status);
        $this->assertEquals('google-new-student', $newUser->google_id);

        // PostMessage target origin equals host
        $this->assertStringContainsString("'http://colombo.test'", $content);
    }

    public function test_google_login_respects_plan_limit_for_new_student(): void
    {
        // Put instTwo on a plan with max_students = 1
        $plan = SubscriptionPlan::create([
            'name' => 'Capped Plan',
            'slug' => 'capped-plan',
            'monthly_price' => 1000,
            'yearly_price' => 10000,
            'max_students' => 1,
            'max_teachers' => 1,
            'features' => ['all'],
            'is_active' => true,
        ]);
        $this->instTwo->update(['subscription_plan_id' => $plan->id]);

        // Create 1 existing student to hit the capacity
        User::create([
            'name' => 'Existing Student',
            'email' => 'existing_capped@example.com',
            'password' => Hash::make('secret'),
            'role' => 'user',
            'institute_id' => $this->instTwo->id,
            'registration_status' => 'confirmed',
        ]);

        $this->mockSocialiteUser('blocked_by_plan@example.com', 'google-blocked');

        $response = $this->withServerVariables(['HTTP_HOST' => 'colombo.test'])
            ->get('http://colombo.test/api/auth/google/callback');

        $response->assertOk();
        $content = $response->getContent();

        // No new user created
        $this->assertNull(User::where('email', 'blocked_by_plan@example.com')->first());
        $this->assertStringContainsString('plan_limit_reached', $content);
        $this->assertStringContainsString("'http://colombo.test'", $content);
    }

    public function test_token_issued_on_institute_a_rejected_by_institute_b_host(): void
    {
        $studentA = User::create([
            'name' => 'Student In Inst One',
            'email' => 'student_a@test.com',
            'password' => Hash::make('password123'),
            'role' => 'user',
            'institute_id' => $this->instOne->id,
            'registration_status' => 'confirmed',
        ]);

        $tokenOnA = $studentA->createToken('auth_token')->plainTextToken;

        // Present token issued on Institute A's host to Institute B's host
        $response = $this->withServerVariables(['HTTP_HOST' => 'colombo.test'])
            ->withHeader('Authorization', 'Bearer ' . $tokenOnA)
            ->getJson('http://colombo.test/api/student/payment-status');

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Institute context mismatch',
        ]);
    }
}


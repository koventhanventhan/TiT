<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use App\Models\ActivityLog;
use App\Models\Institute;
use App\Models\InstituteDomain;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AdminLoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected SubscriptionPlan $plan;
    protected Institute $institute1;
    protected User $admin1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plan = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'monthly_price' => 5000,
            'yearly_price' => 50000,
            'max_students' => 0,
            'max_teachers' => 0,
        ]);

        $this->institute1 = Institute::create([
            'id' => 1,
            'name' => 'TiT Institute',
            'slug' => 'tit',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'index_prefix' => 'TIT',
            'index_format' => 'grade',
        ]);

        $this->admin1 = User::create([
            'name' => 'Admin One',
            'email' => 'admin1@tit.lk',
            'password' => Hash::make('CorrectPassword!123'),
            'role' => 'admin',
            'institute_id' => 1,
            'is_active' => true,
        ]);
    }

    public function test_admin_login_locks_out_after_five_failed_attempts_even_with_correct_password_on_sixth(): void
    {
        Event::fake([Lockout::class]);

        for ($i = 1; $i <= 5; $i++) {
            $resp = $this->from(route('admin.login'))->post('/admin/login', [
                'email' => 'admin1@tit.lk',
                'password' => 'WrongPassword!999',
            ]);

            $resp->assertRedirect(route('admin.login'));
            $resp->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);
            $this->assertGuest('web');
        }

        Event::assertNotDispatched(Lockout::class);

        // 6th attempt with CORRECT password still returns the lockout message
        $sixth = $this->from(route('admin.login'))->post('/admin/login', [
            'email' => 'admin1@tit.lk',
            'password' => 'CorrectPassword!123',
        ]);

        $sixth->assertRedirect(route('admin.login'));
        $sixth->assertSessionHasErrors('email');
        $errorMessage = session('errors')->first('email');
        $this->assertMatchesRegularExpression('/Too many sign-in attempts\. Please wait \d+ seconds and try again\./', $errorMessage);
        $this->assertGuest('web');

        Event::assertDispatched(Lockout::class);

        // JSON requests receive HTTP 429
        $jsonResp = $this->postJson('/admin/login', [
            'email' => 'admin1@tit.lk',
            'password' => 'CorrectPassword!123',
        ]);

        $jsonResp->assertStatus(429);
        $jsonResp->assertJsonValidationErrors('email');
        $this->assertMatchesRegularExpression(
            '/Too many sign-in attempts\. Please wait \d+ seconds and try again\./',
            $jsonResp->json('errors.email.0')
        );

        // ActivityLog recorded without passwords
        $log = ActivityLog::where('action', 'admin_login_lockout')->latest()->first();
        $this->assertNotNull($log);
        $this->assertSame('admin1@tit.lk', $log->metadata['email'] ?? null);
        $this->assertArrayNotHasKey('password', $log->metadata ?? []);
        $this->assertStringNotContainsString('CorrectPassword!123', $log->description);
        $this->assertStringNotContainsString('WrongPassword!999', $log->description);
    }

    public function test_different_email_from_same_ip_is_not_locked_until_ip_limit(): void
    {
        // 5 failures for email A
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/admin/login', [
                'email' => 'email-a@tit.lk',
                'password' => 'WrongPass',
            ]);
        }

        // Email A is now locked
        $respA = $this->from(route('admin.login'))->post('/admin/login', [
            'email' => 'email-a@tit.lk',
            'password' => 'WrongPass',
        ]);
        $respA->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many sign-in attempts', session('errors')->first('email'));

        // Email B from the SAME IP is NOT locked (gives generic invalid credentials message)
        $respB = $this->from(route('admin.login'))->post('/admin/login', [
            'email' => 'email-b@tit.lk',
            'password' => 'WrongPass',
        ]);
        $respB->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);

        // 14 more failures from the same IP (total attempts from IP = 5 (A) + 1 (B) + 14 = 20 failures)
        for ($i = 1; $i <= 14; $i++) {
            $this->post('/admin/login', [
                'email' => "filler-{$i}@tit.lk",
                'password' => 'WrongPass',
            ]);
        }

        // Now IP limit (20) has been exceeded; ANY email from this IP is locked out
        $respC = $this->from(route('admin.login'))->post('/admin/login', [
            'email' => 'fresh-never-seen-email@tit.lk',
            'password' => 'WrongPass',
        ]);
        $respC->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many sign-in attempts', session('errors')->first('email'));
    }

    public function test_after_rate_limiter_clear_or_time_travel_correct_password_logs_in(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin1@tit.lk',
                'password' => 'WrongPass',
            ]);
        }

        // Verify locked out
        $this->post('/admin/login', [
            'email' => 'admin1@tit.lk',
            'password' => 'CorrectPassword!123',
        ])->assertSessionHasErrors('email');

        // Time travel 61 seconds
        $this->travel(61)->seconds();

        // Correct password now logs in successfully
        $loginResp = $this->post('/admin/login', [
            'email' => 'admin1@tit.lk',
            'password' => 'CorrectPassword!123',
        ]);
        $loginResp->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin1, 'web');

        // Test explicit RateLimiter::clear
        $this->post('/admin/logout');
        $this->assertGuest('web');

        for ($i = 1; $i <= 5; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin1@tit.lk',
                'password' => 'WrongPass',
            ]);
        }

        RateLimiter::clear(AdminAuthController::emailThrottleKey('admin1@tit.lk', '127.0.0.1'));
        RateLimiter::clear(AdminAuthController::ipThrottleKey('127.0.0.1'));

        $afterClear = $this->post('/admin/login', [
            'email' => 'admin1@tit.lk',
            'password' => 'CorrectPassword!123',
        ]);
        $afterClear->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($this->admin1, 'web');
    }

    public function test_success_resets_failure_counter(): void
    {
        // 4 failed attempts (not yet locked)
        for ($i = 1; $i <= 4; $i++) {
            $this->post('/admin/login', [
                'email' => 'admin1@tit.lk',
                'password' => 'WrongPass',
            ]);
        }

        // 5th attempt: successful login clears counter
        $this->post('/admin/login', [
            'email' => 'admin1@tit.lk',
            'password' => 'CorrectPassword!123',
        ])->assertRedirect(route('admin.dashboard'));

        $this->post('/admin/logout');
        $this->assertGuest('web');

        // 4 more failed attempts should NOT lock out because the counter was reset to 0
        for ($i = 1; $i <= 4; $i++) {
            $resp = $this->from(route('admin.login'))->post('/admin/login', [
                'email' => 'admin1@tit.lk',
                'password' => 'WrongPass',
            ]);
            $resp->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);
        }
    }

    public function test_institute_a_admin_credentials_on_institute_b_host_gives_generic_message_and_counts_as_failures(): void
    {
        $inst2 = Institute::create([
            'id' => 2,
            'name' => 'Northern Horizon',
            'slug' => 'northern-horizon',
            'status' => 'active',
            'subscription_plan_id' => $this->plan->id,
            'index_prefix' => 'NH',
            'index_format' => 'grade',
        ]);

        InstituteDomain::create([
            'institute_id' => 2,
            'domain' => 'northern-horizon.lk',
            'is_primary' => true,
            'is_verified' => true,
        ]);

        // Attempt login on institute 2's host with institute 1's admin credentials
        for ($i = 1; $i <= 5; $i++) {
            $resp = $this->withServerVariables(['HTTP_HOST' => 'northern-horizon.lk'])
                ->from('http://northern-horizon.lk/admin/login')
                ->post('http://northern-horizon.lk/admin/login', [
                    'email' => 'admin1@tit.lk',
                    'password' => 'CorrectPassword!123',
                ]);

            $resp->assertSessionHasErrors(['email' => 'The provided credentials do not match our records.']);
            $this->assertGuest('web');
        }

        // 6th attempt on institute 2 is locked out
        $sixth = $this->withServerVariables(['HTTP_HOST' => 'northern-horizon.lk'])
            ->from('http://northern-horizon.lk/admin/login')
            ->post('http://northern-horizon.lk/admin/login', [
                'email' => 'admin1@tit.lk',
                'password' => 'CorrectPassword!123',
            ]);

        $sixth->assertSessionHasErrors('email');
        $this->assertStringContainsString('Too many sign-in attempts', session('errors')->first('email'));
    }

    public function test_react_api_login_is_rate_limited(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $resp = $this->postJson('/api/auth/login', [
                'usernameOrEmail' => 'admin1@tit.lk',
                'password' => 'WrongPass',
            ]);
            $resp->assertStatus(401);
            $resp->assertJson(['message' => 'Invalid login credentials']);
        }

        // 6th attempt returns HTTP 429
        $sixth = $this->postJson('/api/auth/login', [
            'usernameOrEmail' => 'admin1@tit.lk',
            'password' => 'CorrectPassword!123',
        ]);
        $sixth->assertStatus(429);
        $sixth->assertJsonStructure(['message', 'errors' => ['email']]);
        $this->assertStringContainsString('Too many sign-in attempts', $sixth->json('message'));

        // Alias /api/login also works and is rate limited
        $alias = $this->postJson('/api/login', [
            'usernameOrEmail' => 'admin1@tit.lk',
            'password' => 'CorrectPassword!123',
        ]);
        $alias->assertStatus(429);
    }
}

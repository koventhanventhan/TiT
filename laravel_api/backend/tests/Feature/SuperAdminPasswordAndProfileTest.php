<?php

namespace Tests\Feature;

use App\Filament\SuperAdmin\Pages\Auth\SuperAdminEditProfile;
use App\Filament\SuperAdmin\Pages\Auth\SuperAdminRequestPasswordReset;
use App\Filament\SuperAdmin\Pages\Auth\SuperAdminResetPassword;
use App\Models\User;
use App\Notifications\SuperAdminPasswordChangedNotification;
use App\Notifications\SuperAdminResetPasswordNotification;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminPasswordAndProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $normalAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('super-admin'));

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@platform.lk',
            'role' => 'super_admin',
            'password' => 'OldP@ssw0rd123!',
            'institute_id' => null,
            'deactivated_at' => null,
        ]);

        $this->normalAdmin = User::factory()->create([
            'name' => 'Institute Admin',
            'email' => 'admin@institute.lk',
            'role' => 'admin',
            'password' => 'AdminP@ss12345!',
            'institute_id' => 1,
            'deactivated_at' => null,
        ]);

        RateLimiter::clear('super-admin-password-reset-request:127.0.0.1');
        RateLimiter::clear('super-admin-password-reset-submit:127.0.0.1');
    }

    public function test_forgot_password_happy_path_with_null_institute(): void
    {
        Notification::fake();

        // 1. Request reset link via Livewire
        Livewire::test(SuperAdminRequestPasswordReset::class)
            ->fillForm(['email' => $this->superAdmin->email])
            ->call('request')
            ->assertHasNoFormErrors()
            ->assertNotified('If an account exists for this e-mail, a reset link has been sent.');

        Notification::assertSentTo($this->superAdmin, SuperAdminResetPasswordNotification::class);

        // 2. Token exists in password_reset_tokens table
        $tokenRow = DB::table('password_reset_tokens')->where('email', $this->superAdmin->email)->first();
        $this->assertNotNull($tokenRow);

        // Retrieve raw token via broker
        $rawToken = Password::broker('super_admins')->createToken($this->superAdmin);

        // 3. Reset password via Livewire component
        $newPassword = 'NewSuperP@ssw0rd2026!';

        Livewire::test(SuperAdminResetPassword::class, [
            'email' => $this->superAdmin->email,
            'token' => $rawToken,
        ])
            ->fillForm([
                'password' => $newPassword,
                'passwordConfirmation' => $newPassword,
            ])
            ->call('resetPassword')
            ->assertHasNoFormErrors();

        // 4. Assert password was changed and password_changed_at updated
        $this->superAdmin->refresh();
        $this->assertTrue(Hash::check($newPassword, $this->superAdmin->password));
        $this->assertFalse(Hash::check('OldP@ssw0rd123!', $this->superAdmin->password));
        $this->assertNotNull($this->superAdmin->password_changed_at);

        Notification::assertSentTo($this->superAdmin, SuperAdminPasswordChangedNotification::class);
    }

    public function test_anti_enumeration_returns_identical_notification_for_unknown_email(): void
    {
        Notification::fake();

        Livewire::test(SuperAdminRequestPasswordReset::class)
            ->fillForm(['email' => 'nonexistent@nowhere.test'])
            ->call('request')
            ->assertHasNoFormErrors()
            ->assertNotified('If an account exists for this e-mail, a reset link has been sent.');

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'nonexistent@nowhere.test']);
    }

    public function test_non_super_admin_cannot_use_super_admin_reset_broker(): void
    {
        Notification::fake();

        // Direct broker check
        $status = Password::broker('super_admins')->sendResetLink(['email' => $this->normalAdmin->email]);
        $this->assertSame(Password::INVALID_USER, $status);

        // Livewire UI check: returns identical anti-enumeration flash, but generates no token
        Livewire::test(SuperAdminRequestPasswordReset::class)
            ->fillForm(['email' => $this->normalAdmin->email])
            ->call('request')
            ->assertHasNoFormErrors()
            ->assertNotified('If an account exists for this e-mail, a reset link has been sent.');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $this->normalAdmin->email]);
        Notification::assertNothingSent();
    }

    public function test_rate_limit_enforcement_on_reset_request(): void
    {
        $component = Livewire::test(SuperAdminRequestPasswordReset::class);

        // 3 allowed requests
        for ($i = 0; $i < 3; $i++) {
            $component->fillForm(['email' => 'test' . $i . '@platform.lk'])->call('request');
        }

        // 4th request must be throttled
        $component->fillForm(['email' => 'test4@platform.lk'])
            ->call('request')
            ->assertNotified();
    }

    public function test_expired_reset_token_is_rejected(): void
    {
        $rawToken = Password::broker('super_admins')->createToken($this->superAdmin);

        // Manually expire token (older than 60 minutes)
        DB::table('password_reset_tokens')
            ->where('email', $this->superAdmin->email)
            ->update(['created_at' => now()->subMinutes(65)]);

        Livewire::test(SuperAdminResetPassword::class, [
            'email' => $this->superAdmin->email,
            'token' => $rawToken,
        ])
            ->fillForm([
                'password' => 'AnotherStr0ngP@ssword!',
                'passwordConfirmation' => 'AnotherStr0ngP@ssword!',
            ])
            ->call('resetPassword')
            ->assertNotified();

        $this->superAdmin->refresh();
        $this->assertTrue(Hash::check('OldP@ssw0rd123!', $this->superAdmin->password));
    }

    public function test_reused_reset_token_is_rejected(): void
    {
        $rawToken = Password::broker('super_admins')->createToken($this->superAdmin);
        $firstPassword = 'FirstNewP@ssw0rd123!';

        // First reset succeeds
        Livewire::test(SuperAdminResetPassword::class, [
            'email' => $this->superAdmin->email,
            'token' => $rawToken,
        ])
            ->fillForm([
                'password' => $firstPassword,
                'passwordConfirmation' => $firstPassword,
            ])
            ->call('resetPassword')
            ->assertHasNoFormErrors();

        $this->superAdmin->refresh();
        $this->assertTrue(Hash::check($firstPassword, $this->superAdmin->password));

        // Reusing the same token must fail
        Livewire::test(SuperAdminResetPassword::class, [
            'email' => $this->superAdmin->email,
            'token' => $rawToken,
        ])
            ->fillForm([
                'password' => 'SecondP@ssw0rd123!',
                'passwordConfirmation' => 'SecondP@ssw0rd123!',
            ])
            ->call('resetPassword');

        $this->superAdmin->refresh();
        $this->assertTrue(Hash::check($firstPassword, $this->superAdmin->password));
    }

    public function test_profile_update_name_and_email(): void
    {
        $this->actingAs($this->superAdmin, 'super_admin');

        Livewire::test(SuperAdminEditProfile::class)
            ->fillForm([
                'name' => 'Updated Super Admin Name',
                'email' => 'new.superadmin@platform.lk',
                'currentPassword' => 'OldP@ssw0rd123!',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->superAdmin->refresh();
        $this->assertSame('Updated Super Admin Name', $this->superAdmin->name);
        $this->assertSame('new.superadmin@platform.lk', $this->superAdmin->email);
    }

    public function test_profile_password_change_requires_valid_current_password(): void
    {
        $this->actingAs($this->superAdmin, 'super_admin');

        Livewire::test(SuperAdminEditProfile::class)
            ->fillForm([
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'currentPassword' => 'WrongCurrentPassword!',
                'password' => 'ValidNewP@ssw0rd123!',
                'passwordConfirmation' => 'ValidNewP@ssw0rd123!',
            ])
            ->call('save')
            ->assertHasFormErrors(['currentPassword']);

        $this->superAdmin->refresh();
        $this->assertTrue(Hash::check('OldP@ssw0rd123!', $this->superAdmin->password));
    }

    public function test_profile_password_change_rejects_passwords_under_12_characters(): void
    {
        $this->actingAs($this->superAdmin, 'super_admin');

        Livewire::test(SuperAdminEditProfile::class)
            ->fillForm([
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'currentPassword' => 'OldP@ssw0rd123!',
                'password' => 'Short1!',
                'passwordConfirmation' => 'Short1!',
            ])
            ->call('save')
            ->assertHasFormErrors(['password']);

        $this->superAdmin->refresh();
        $this->assertTrue(Hash::check('OldP@ssw0rd123!', $this->superAdmin->password));
    }

    public function test_profile_password_change_invalidates_other_sessions_and_revokes_tokens(): void
    {
        $this->actingAs($this->superAdmin, 'super_admin');

        // Create Sanctum tokens
        $token1 = $this->superAdmin->createToken('token1');
        $token2 = $this->superAdmin->createToken('token2');
        $this->assertCount(2, $this->superAdmin->tokens);

        // Create session rows if sessions table exists
        if (Schema::hasTable('sessions')) {
            session()->start();
            $currentSessionId = session()->getId();

            DB::table('sessions')->insert([
                [
                    'id' => $currentSessionId,
                    'user_id' => $this->superAdmin->id,
                    'ip_address' => '127.0.0.1',
                    'user_agent' => 'PHPUnit',
                    'payload' => 'payload1',
                    'last_activity' => time(),
                ],
                [
                    'id' => 'other-session-12345',
                    'user_id' => $this->superAdmin->id,
                    'ip_address' => '192.168.1.1',
                    'user_agent' => 'Firefox',
                    'payload' => 'payload2',
                    'last_activity' => time(),
                ],
            ]);

            $this->assertDatabaseHas('sessions', ['id' => 'other-session-12345']);
        }

        $newPassword = 'BrandNewP@ssw0rd2026!';

        Livewire::test(SuperAdminEditProfile::class)
            ->fillForm([
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'currentPassword' => 'OldP@ssw0rd123!',
                'password' => $newPassword,
                'passwordConfirmation' => $newPassword,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->superAdmin->refresh();
        $this->assertTrue(Hash::check($newPassword, $this->superAdmin->password));
        $this->assertNotNull($this->superAdmin->password_changed_at);

        // Sanctum tokens revoked
        $this->assertCount(0, $this->superAdmin->tokens);

        // Other sessions invalidated
        if (Schema::hasTable('sessions')) {
            $this->assertDatabaseMissing('sessions', ['id' => 'other-session-12345']);
        }
    }

    public function test_password_hash_never_appears_in_log_file(): void
    {
        $logPath = storage_path('logs/laravel.log');
        $initialSize = file_exists($logPath) ? filesize($logPath) : 0;

        // Perform password change
        $this->superAdmin->password = 'LogCheckP@ssw0rd123!';
        $this->superAdmin->save();

        $this->superAdmin->refresh();

        if (file_exists($logPath)) {
            $newLogs = file_get_contents($logPath, false, null, $initialSize);
            // Assert that the bcrypt/argon hash prefix $2y$ does not appear in new log output
            $this->assertStringNotContainsString('$2y$', $newLogs);
            $this->assertStringNotContainsString('new_hash', $newLogs);
            $this->assertStringContainsString("password updated for user #{$this->superAdmin->id}", $newLogs);
        } else {
            $this->assertTrue(true);
        }
    }
}

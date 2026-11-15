<?php

namespace App\Filament\SuperAdmin\Pages\Auth;

use App\Notifications\SuperAdminPasswordChangedNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\PasswordReset\ResetPassword;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Auth\Http\Responses\Contracts\PasswordResetResponse;
use Filament\Notifications\Notification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class SuperAdminResetPassword extends ResetPassword
{
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/password-reset/reset-password.form.password.label'))
            ->password()
            ->revealable(Filament::arePasswordsRevealable())
            ->rule(
                PasswordRule::min(12)
                    ->letters()
                    ->numbers()
                    ->symbols()
            )
            ->helperText('Minimum 12 characters with letters, numbers, and symbols.')
            ->showAllValidationMessages()
            ->autocomplete('new-password')
            ->required()
            ->same('passwordConfirmation');
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        $ip = request()->ip() ?: 'unknown';
        $rateLimitKey = 'super-admin-password-reset-submit:' . $ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            Notification::make()
                ->title('Too many attempts. Please try again later.')
                ->danger()
                ->send();

            return null;
        }

        RateLimiter::hit($rateLimitKey, 3600);

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return null;
        }

        $data = $this->form->getState();
        $data['email'] = $this->email;
        $data['token'] = $this->token;

        $hasPanelAccess = true;

        $status = Password::broker('super_admins')->reset(
            $this->getCredentialsFromFormData($data),
            function ($user) use ($data, &$hasPanelAccess): void {
                if (
                    ($user instanceof \Filament\Models\Contracts\FilamentUser) &&
                    (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
                ) {
                    $hasPanelAccess = false;
                    return;
                }

                // Raw assignment; User model has cast 'password' => 'hashed'
                $user->forceFill([
                    'password' => $data['password'],
                    'remember_token' => Str::random(60),
                    'password_changed_at' => now(),
                ])->save();

                // Revoke all active sessions
                if (Schema::hasTable('sessions')) {
                    DB::table('sessions')
                        ->where('user_id', $user->id)
                        ->delete();
                }

                // Revoke all Sanctum tokens
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }

                // Dispatch security notification
                try {
                    $user->notify(new SuperAdminPasswordChangedNotification());
                } catch (\Throwable $e) {
                    \Log::warning('Security notification failed: ' . $e->getMessage());
                }

                \Log::info("password reset completed for super admin #{$user->id}");

                event(new PasswordReset($user));
            }
        );

        if ($hasPanelAccess === false) {
            $status = Password::INVALID_USER;
        }

        if ($status === Password::PASSWORD_RESET) {
            Notification::make()
                ->title('Your password has been reset successfully. Please log in with your new password.')
                ->success()
                ->send();

            return app(PasswordResetResponse::class);
        }

        Notification::make()
            ->title(__($status))
            ->danger()
            ->send();

        return null;
    }
}

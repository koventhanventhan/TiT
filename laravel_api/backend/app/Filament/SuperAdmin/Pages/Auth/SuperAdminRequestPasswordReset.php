<?php

namespace App\Filament\SuperAdmin\Pages\Auth;

use App\Notifications\SuperAdminResetPasswordNotification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;

class SuperAdminRequestPasswordReset extends RequestPasswordReset
{
    public function request(): void
    {
        // IP & Form rate limit (3 attempts per hour)
        $ip = request()->ip() ?: 'unknown';
        $rateLimitKey = 'super-admin-password-reset-request:' . $ip;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 3)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);
            $minutes = max(1, (int) ceil($seconds / 60));

            Notification::make()
                ->title("Too many password reset attempts. Please try again in {$minutes} minutes.")
                ->danger()
                ->send();

            return;
        }

        RateLimiter::hit($rateLimitKey, 3600);

        try {
            $this->rateLimit(3);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();
            return;
        }

        $data = $this->form->getState();

        // Broker query strictly using 'super_admins'
        Password::broker('super_admins')->sendResetLink(
            $this->getCredentialsFromFormData($data),
            function (CanResetPassword $user, string $token): void {
                if (
                    ($user instanceof \Filament\Models\Contracts\FilamentUser) &&
                    (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))
                ) {
                    return;
                }

                $notification = app(SuperAdminResetPasswordNotification::class, ['token' => $token]);
                $notification->url = Filament::getResetPasswordUrl($token, $user);

                $user->notify($notification);

                \Log::info("super admin password reset link generated for user #{$user->id}");
            }
        );

        // Strict Anti-enumeration: ALWAYS show identical success notification
        Notification::make()
            ->title('If an account exists for this e-mail, a reset link has been sent.')
            ->success()
            ->send();

        $this->form->fill();
    }
}

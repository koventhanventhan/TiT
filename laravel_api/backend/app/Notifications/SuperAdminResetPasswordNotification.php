<?php

namespace App\Notifications;

use Filament\Auth\Notifications\ResetPassword as FilamentResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class SuperAdminResetPasswordNotification extends FilamentResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $platformMailer = config('mail.default') ?: env('MAIL_MAILER', 'smtp');

        return (new MailMessage)
            ->mailer($platformMailer)
            ->subject('Super Admin Password Reset Request')
            ->line('You are receiving this email because a password reset request was received for your platform Super Admin account.')
            ->action('Reset Super Admin Password', $this->resetUrl($notifiable))
            ->line('This password reset link will expire in 60 minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}

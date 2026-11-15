<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SuperAdminPasswordChangedNotification extends Notification
{
    use Queueable;

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $platformMailer = config('mail.default') ?: env('MAIL_MAILER', 'smtp');

        return (new MailMessage)
            ->mailer($platformMailer)
            ->subject('Security Notice: Your Super Admin Password Was Changed')
            ->greeting("Hello {$notifiable->name},")
            ->line('This is a security confirmation that your Super Admin password was successfully changed.')
            ->line('All other active sessions and access tokens have been revoked for your security.')
            ->line('If you did not initiate this change, please contact platform security immediately.');
    }
}

<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $type;
    public array $data;

    /**
     * Supported types:
     * welcome, payment_reminder, payment_success, admin_approved,
     * account_deactivated, account_suspended, zoom_reminder, admin_alert,
     * class_created
     */
    public function __construct(string $type, array $data = [])
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        // Allow callers to pass an explicit subject line
        if (!empty($this->data['subject_line'])) {
            return new Envelope(subject: $this->data['subject_line']);
        }

        $subjects = [
            'general_update'       => 'Update - TiT Education',
            'welcome'                  => 'Welcome to TiT Education! 🎓',
            'payment_reminder'         => 'Payment Reminder - TiT Education',
            'payment_request_received' => 'Offline Payment Request Received 📋 - TiT Education',
            'payment_success'          => 'Payment Confirmed ✅ - TiT Education',
            'admin_approved'       => 'Account Approved! 🎉 - TiT Education',
            'account_deactivated'  => 'Account Deactivated - TiT Education',
            'account_suspended'    => 'Account Suspended - TiT Education',
            'account_reactivated'  => 'Account Reactivated ✅ - TiT Education',
            'zoom_reminder'        => 'Zoom Class Reminder 📹 - TiT Education',
            'admin_alert'          => 'Admin Alert - TiT Education',
            'class_created'        => 'New Class Scheduled - TiT Education',
        ];

        return new Envelope(
            subject: $subjects[$this->type] ?? 'Notification - TiT Education',
        );
    }

    public function content(): Content
    {
        // Dedicated template if it exists, otherwise the generic one
        $view = view()->exists('emails.notifications.' . $this->type)
            ? 'emails.notifications.' . $this->type
            : 'emails.notifications.generic';

        return new Content(view: $view);
    }

    public function attachments(): array
    {
        return [];
    }
}

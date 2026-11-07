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

    protected function resolveBrandName(): string
    {
        $instituteId = isset($this->data['institute_id']) ? (int) $this->data['institute_id'] : null;

        return \App\Models\Institute::mailBrandName($instituteId ?: null);
    }

    public function envelope(): Envelope
    {
        // Allow callers to pass an explicit subject line
        if (!empty($this->data['subject_line'])) {
            return new Envelope(subject: $this->data['subject_line']);
        }

        $brand = $this->resolveBrandName();

        $subjects = [
            'general_update'           => "Update - {$brand}",
            'welcome'                  => "Welcome to {$brand}! 🎓",
            'payment_reminder'         => "Payment Reminder - {$brand}",
            'payment_request_received' => "Offline Payment Request Received 📋 - {$brand}",
            'payment_success'          => "Payment Confirmed ✅ - {$brand}",
            'admin_approved'           => "Account Approved! 🎉 - {$brand}",
            'account_deactivated'      => "Account Deactivated - {$brand}",
            'account_suspended'        => "Account Suspended - {$brand}",
            'account_reactivated'      => "Account Reactivated ✅ - {$brand}",
            'zoom_reminder'            => "Zoom Class Reminder 📹 - {$brand}",
            'admin_alert'              => "Admin Alert - {$brand}",
            'class_created'            => "New Class Scheduled - {$brand}",
        ];

        return new Envelope(
            subject: $subjects[$this->type] ?? "Notification - {$brand}",
        );
    }

    public function content(): Content
    {
        // Dedicated template if it exists, otherwise the generic one
        $view = view()->exists('emails.notifications.' . $this->type)
            ? 'emails.notifications.' . $this->type
            : 'emails.notifications.generic';

        return new Content(
            view: $view,
            with: [
                'brandName' => $this->resolveBrandName(),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

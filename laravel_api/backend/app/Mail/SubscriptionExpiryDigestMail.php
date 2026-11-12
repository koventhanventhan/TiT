<?php

namespace App\Mail;

use App\Models\Institute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $subjectLine;
    public string $heading;
    public string $messageText;

    public function __construct(string $subjectLine, string $heading, string $messageText)
    {
        $this->subjectLine = $subjectLine;
        $this->heading = $heading;
        $this->messageText = $messageText;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        $brand = Institute::mailBrandName(1);

        return new Content(
            view: 'emails.notifications.generic',
            with: [
                'brandName' => $brand,
                'data' => [
                    'heading' => $this->heading,
                    'message' => $this->messageText,
                    'institute_id' => 1,
                ],
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

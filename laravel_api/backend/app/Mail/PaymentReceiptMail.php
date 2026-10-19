<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Attachment;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $student;
    public Payment $payment;
    public string $pdfContent;
    public string $receiptNo;
    public string $monthName;
    public string $downloadUrl;

    public function __construct(
        User $student,
        Payment $payment,
        string $pdfContent,
        string $receiptNo,
        string $monthName,
        string $downloadUrl = ''
    ) {
        $this->student = $student;
        $this->payment = $payment;
        $this->pdfContent = $pdfContent;
        $this->receiptNo = $receiptNo;
        $this->monthName = $monthName;
        $this->downloadUrl = $downloadUrl;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment Receipt [{$this->receiptNo}] - TiT Education",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment_receipt',
            with: [
                'student' => $this->student,
                'payment' => $this->payment,
                'receiptNo' => $this->receiptNo,
                'monthName' => $this->monthName,
                'downloadUrl' => $this->downloadUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdfContent, "receipt-{$this->receiptNo}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}

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
    public ?string $pdfContent;
    public string $receiptNo;
    public string $monthName;
    public string $downloadUrl;

    public function __construct(
        User $student,
        Payment $payment,
        ?string $pdfContent,
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

    protected function resolveInstituteId(): ?int
    {
        $id = (int) ($this->payment->institute_id ?: ($this->student->institute_id ?: 0));

        return $id > 0 ? $id : null;
    }

    public function envelope(): Envelope
    {
        $brand = \App\Models\Institute::mailBrandName($this->resolveInstituteId());

        return new Envelope(
            subject: "Payment Receipt [{$this->receiptNo}] - {$brand}",
        );
    }

    public function content(): Content
    {
        $instituteId = $this->resolveInstituteId();
        $brand = \App\Models\Institute::mailBrandName($instituteId);
        $footerLine = $instituteId === 1
            ? 'TiT Education • Jaffna, Sri Lanka'
            : $brand;

        return new Content(
            view: 'emails.payment_receipt',
            with: [
                'student' => $this->student,
                'payment' => $this->payment,
                'receiptNo' => $this->receiptNo,
                'monthName' => $this->monthName,
                'downloadUrl' => $this->downloadUrl,
                'hasAttachment' => !empty($this->pdfContent),
                'brandName' => $brand,
                'footerLine' => $footerLine,
            ]
        );
    }

    public function attachments(): array
    {
        if (empty($this->pdfContent)) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->pdfContent, "receipt-{$this->receiptNo}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}

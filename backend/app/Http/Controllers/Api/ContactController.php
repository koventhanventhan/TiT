<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Models\SiteSetting;

class ContactController extends Controller
{
    /**
     * Handle contact form submission — save to DB, optionally send email.
     */
    public function send(Request $request)
    {
        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'phone'   => 'nullable|string|max:20',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $data = $request->only(['name', 'email', 'phone', 'subject', 'message']);

        // Always save to database
        ContactMessage::create($data);

        // Try to send email (non-blocking — if it fails, the message is still saved)
        $this->trySendEmail($data);

        return response()->json([
            'success' => true,
            'message' => 'Your message has been sent successfully!'
        ]);
    }

    /**
     * Attempt to send email notification. Fails silently if mail is not configured.
     */
    private function trySendEmail(array $data): void
    {
        try {
            $toEmail = 'admin@titjaffna.lk';
            $setting = SiteSetting::where('key', 'footer_email')->first();
            if ($setting && $setting->value) {
                $toEmail = $setting->value;
            }

            // Only attempt if MAIL_PASSWORD is set
            if (!config('mail.mailers.smtp.password')) {
                Log::info('Contact message saved to DB (email skipped — no MAIL_PASSWORD configured)', [
                    'from' => $data['email']
                ]);
                return;
            }

            Mail::raw($this->buildEmailBody($data), function ($mail) use ($data, $toEmail) {
                $mail->to($toEmail)
                     ->replyTo($data['email'], $data['name'])
                     ->subject('Contact Form: ' . $data['subject']);
            });

            Log::info('Contact form email sent', ['to' => $toEmail, 'from' => $data['email']]);
        } catch (\Exception $e) {
            Log::warning('Contact email failed (message saved to DB): ' . $e->getMessage());
        }
    }

    /**
     * Build plain-text email body from form data.
     */
    private function buildEmailBody(array $data): string
    {
        $body  = "New Contact Form Message\n";
        $body .= "========================\n\n";
        $body .= "Name:    {$data['name']}\n";
        $body .= "Email:   {$data['email']}\n";
        $body .= "Phone:   " . ($data['phone'] ?: 'Not provided') . "\n";
        $body .= "Subject: {$data['subject']}\n\n";
        $body .= "Message:\n";
        $body .= "--------\n";
        $body .= $data['message'] . "\n";
        $body .= "--------\n\n";
        $body .= "Sent from TiT Education website contact form.";

        return $body;
    }
}

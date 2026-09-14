<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\User;
use App\Traits\ValidatesEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    use ValidatesEmail;

    protected WhatsAppService $whatsApp;
    protected string $channel;

    public function __construct(WhatsAppService $whatsApp)
    {
        $this->whatsApp = $whatsApp;
        // 'auto' = try WhatsApp first, fallback to email
        // 'email' = email only
        // 'whatsapp' = whatsapp only
        $this->channel = config('services.notification_channel', 'auto');
    }

    /**
     * Send a notification to a student/user via WhatsApp (with email fallback).
     *
     * @param User   $user          The user to notify
     * @param string $type          Notification type (welcome, payment_reminder, etc.)
     * @param string $templateName  WhatsApp template name
     * @param array  $templateVars  Variables for WhatsApp template
     * @param array  $emailData     Data for email template (merged with defaults)
     */
    public function notifyUser(
        User $user,
        string $type,
        string $templateName,
        array $templateVars = [],
        array $emailData = [],
        string $langCode = 'en'
    ): bool {
        $phone = $user->phone_number;
        $email = $user->email;
        $sent = false;

        // Try WhatsApp first (if channel allows)
        if ($this->shouldTryWhatsApp() && $phone) {
            $sent = $this->whatsApp->sendTemplate($phone, $templateName, $langCode, $templateVars);
            if ($sent) {
                Log::info("NotificationService: [{$type}] sent via WhatsApp to {$phone}");
                return true;
            }
            Log::warning("NotificationService: WhatsApp failed for [{$type}] to {$phone}, attempting email fallback...");
        }

        // Fallback to Email (if channel allows) — with DNS/MX validation
        if ($this->shouldTryEmail() && $this->isValidEmailForSending($email)) {
            return $this->sendEmail($email, $type, $emailData);
        }

        // If neither worked
        if (!$sent) {
            Log::info("NotificationService: [{$type}] could not be sent — no viable channel for user #{$user->id} (phone: {$phone}, email: {$email})");
        }

        return $sent;
    }

    /**
     * Send an admin notification via WhatsApp (with email fallback).
     *
     * @param string $type         Notification type (usually 'admin_alert')
     * @param string $templateName WhatsApp template name
     * @param array  $templateVars Variables for WhatsApp template
     * @param array  $emailData    Data for email template
     */
    public function notifyAdmin(
        string $type,
        string $templateName,
        array $templateVars = [],
        array $emailData = [],
        string $langCode = 'en'
    ): bool {
        $adminPhone = config('services.admin_whatsapp_number');
        $adminEmail = $this->getAdminEmail();
        $sent = false;

        // Try WhatsApp first
        if ($this->shouldTryWhatsApp() && $adminPhone) {
            $sent = $this->whatsApp->sendTemplate($adminPhone, $templateName, $langCode, $templateVars);
            if ($sent) {
                Log::info("NotificationService: Admin [{$type}] sent via WhatsApp to {$adminPhone}");
                return true;
            }
            Log::warning("NotificationService: Admin WhatsApp failed for [{$type}], attempting email fallback...");
        }

        // Fallback to Email — admin email is validated too
        if ($this->shouldTryEmail() && $adminEmail && $this->isValidEmailForSending($adminEmail)) {
            return $this->sendEmail($adminEmail, $type, $emailData);
        }

        if (!$sent) {
            Log::info("NotificationService: Admin [{$type}] could not be sent — no viable channel");
        }

        return $sent;
    }

    /**
     * Send email using the NotificationMail Mailable.
     * Records bounces on failure for future blocklisting.
     */
    protected function sendEmail(string $to, string $type, array $data): bool
    {
        try {
            Mail::to($to)->send(new NotificationMail($type, $data));
            Log::info("NotificationService: [{$type}] email sent successfully to {$to}");
            return true;
        } catch (\Throwable $e) {
            Log::error("NotificationService: Email failed for [{$type}] to {$to}: " . $e->getMessage());

            // Track the bounce — after 2 bounces the address will be blocklisted
            $this->markEmailAsBounced($to);

            return false;
        }
    }

    /**
     * Get the admin email from users table or .env fallback.
     */
    protected function getAdminEmail(): ?string
    {
        $admin = User::where('role', 'admin')
            ->whereNotNull('email')
            ->first();

        return $admin?->email ?? config('mail.from.address');
    }

    /**
     * Whether WhatsApp channel should be attempted.
     */
    protected function shouldTryWhatsApp(): bool
    {
        if (!config('services.whatsapp.enabled', false)) {
            return false;
        }
        return in_array($this->channel, ['auto', 'whatsapp']);
    }

    /**
     * Whether Email channel should be attempted.
     */
    protected function shouldTryEmail(): bool
    {
        return in_array($this->channel, ['auto', 'email']);
    }
}

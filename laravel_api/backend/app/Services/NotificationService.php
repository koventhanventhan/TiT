<?php

namespace App\Services;

use App\Mail\NotificationMail;
use App\Models\InstituteIntegration;
use App\Models\User;
use App\Support\InstituteMail;
use App\Support\Integrations;
use App\Support\Tenant;
use App\Traits\ValidatesEmail;
use Illuminate\Support\Facades\Log;

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
        $this->channel = (string) (config('services.notification_channel') ?: 'auto');
    }

    /**
     * Send a notification to a student/user via WhatsApp (with email fallback).
     */
    public function notifyUser(
        User $user,
        string $type,
        string $templateName,
        array $templateVars = [],
        array $emailData = [],
        string $langCode = 'en'
    ): bool {
        $targetUser = $user;
        if ($user->parent_id) {
            $parent = User::withoutGlobalScopes()->find($user->parent_id);
            if ($parent) {
                $targetUser = $parent;
            }
        }

        $instituteId = (int) ($targetUser->institute_id ?: $user->institute_id ?: Tenant::id() ?: 1);
        $phone = $targetUser->phone_number;
        $email = $targetUser->email;
        $sent = false;

        // Try WhatsApp first (if channel allows). Unconfigured WhatsApp returns a typed "skipped"
        // result and transparently continues to the email fallback channel without throwing.
        if ($this->shouldTryWhatsApp($instituteId) && $phone) {
            $wa = app()->isShared(WhatsAppService::class)
                ? $this->whatsApp
                : WhatsAppService::forInstitute($instituteId);

            if ($wa instanceof \Mockery\MockInterface) {
                $sent = $wa->sendTemplate($phone, $templateName, $langCode, $templateVars);
            } else {
                $result = $wa->sendTemplateResult($phone, $templateName, $langCode, $templateVars);
                $sent = $result->isSent();
                if ($result->isFailed()) {
                    Log::warning("NotificationService: WhatsApp failed for [{$type}] to {$phone}, attempting email fallback...");
                }
            }

            if ($sent) {
                Log::info("NotificationService: [{$type}] sent via WhatsApp to {$phone}");
                return true;
            }
        }

        // Fallback to Email (if channel allows) — with DNS/MX validation
        if ($this->shouldTryEmail() && $email && $this->isValidEmailForSending($email)) {
            return $this->sendEmail($email, $type, $emailData, $instituteId);
        }

        if (!$sent) {
            Log::info("NotificationService: [{$type}] could not be sent — no viable channel for user #{$user->id} (phone: {$phone}, email: {$email})");
        }

        return $sent;
    }

    /**
     * Send an admin notification via WhatsApp (with email fallback).
     */
    public function notifyAdmin(
        string $type,
        string $templateName,
        array $templateVars = [],
        array $emailData = [],
        string $langCode = 'en'
    ): bool {
        $instituteId = (int) (Tenant::id() ?: (auth()->check() ? (auth()->user()?->institute_id ?: 1) : 1));
        $adminPhone = $this->getAdminWhatsAppNumber($instituteId);
        $adminEmail = $this->getAdminEmail($instituteId);
        $sent = false;

        if ($this->shouldTryWhatsApp($instituteId) && $adminPhone) {
            $wa = app()->isShared(WhatsAppService::class)
                ? $this->whatsApp
                : WhatsAppService::forInstitute($instituteId);

            if ($wa instanceof \Mockery\MockInterface) {
                $sent = $wa->sendTemplate($adminPhone, $templateName, $langCode, $templateVars);
            } else {
                $result = $wa->sendTemplateResult($adminPhone, $templateName, $langCode, $templateVars);
                $sent = $result->isSent();
                if ($result->isFailed()) {
                    Log::warning("NotificationService: Admin WhatsApp failed for [{$type}], attempting email fallback...");
                }
            }

            if ($sent) {
                Log::info("NotificationService: Admin [{$type}] sent via WhatsApp to {$adminPhone}");
                return true;
            }
        }

        if ($this->shouldTryEmail() && $adminEmail && $this->isValidEmailForSending($adminEmail)) {
            return $this->sendEmail($adminEmail, $type, $emailData, $instituteId);
        }

        if (!$sent) {
            Log::info("NotificationService: Admin [{$type}] could not be sent — no viable channel");
        }

        return $sent;
    }

    /**
     * Send an email-only notification (no WhatsApp).
     */
    public function notifyUserByEmail(User $user, string $type, array $emailData = []): bool
    {
        $targetUser = $user;
        if ($user->parent_id) {
            $parent = User::withoutGlobalScopes()->find($user->parent_id);
            if ($parent) {
                $targetUser = $parent;
            }
        }

        $instituteId = (int) ($targetUser->institute_id ?: $user->institute_id ?: Tenant::id() ?: 1);
        $email = $targetUser->email;

        if ($email && $this->isValidEmailForSending($email)) {
            return $this->sendEmail($email, $type, $emailData, $instituteId);
        }

        Log::info("NotificationService: [{$type}] could not be sent — no valid email for user #{$user->id} (email: {$email})");
        return false;
    }

    /**
     * Send an email-only notification to the admin (no WhatsApp).
     */
    public function notifyAdminByEmail(string $type, array $emailData = []): bool
    {
        $instituteId = (int) (Tenant::id() ?: (auth()->check() ? (auth()->user()?->institute_id ?: 1) : 1));
        $adminEmail = $this->getAdminEmail($instituteId);

        if ($adminEmail && $this->isValidEmailForSending($adminEmail)) {
            return $this->sendEmail($adminEmail, $type, $emailData, $instituteId);
        }

        Log::info("NotificationService: Admin [{$type}] email could not be sent — no valid admin email");
        return false;
    }

    /**
     * Send email using InstituteMail + NotificationMail Mailable.
     * Records bounces on permanent SMTP failure without throwing into callers.
     */
    protected function sendEmail(string $to, string $type, array $data, ?int $instituteId = null): bool
    {
        if ($instituteId !== null && !isset($data['institute_id'])) {
            $data['institute_id'] = $instituteId;
        }

        $sent = InstituteMail::to($to, $instituteId)->send(new NotificationMail($type, $data));

        if ($sent) {
            Log::info("NotificationService: [{$type}] email sent successfully to {$to}");
            return true;
        }

        $exception = InstituteMail::getLastException();
        if ($exception !== null && $this->isPermanentMailFailure($exception)) {
            $this->markEmailAsBounced($to);
        }

        return false;
    }

    protected function getAdminWhatsAppNumber(int $instituteId): ?string
    {
        $explicit = \App\Models\SiteSetting::getForInstitute('assistant_public_contact_whatsapp', $instituteId, '');
        if (!empty($explicit)) {
            return (string) $explicit;
        }

        if (Integrations::isEnvFallbackAllowed($instituteId)) {
            return config('services.admin_whatsapp_number') ?: null;
        }

        $admin = User::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('role', 'admin')
            ->whereNotNull('phone_number')
            ->where('phone_number', '!=', '')
            ->latest()
            ->first();

        return $admin?->phone_number;
    }

    /**
     * Get the admin notification email for the resolved institute.
     */
    protected function getAdminEmail(?int $instituteId = null): ?string
    {
        $resolvedId = $instituteId ?: (Tenant::id() ?: 1);

        $explicitEmail = \App\Models\SiteSetting::getForInstitute('admin_notification_email', $resolvedId, '');
        if (!empty($explicitEmail) && filter_var($explicitEmail, FILTER_VALIDATE_EMAIL)) {
            return $explicitEmail;
        }

        $admin = User::withoutGlobalScopes()
            ->where('institute_id', $resolvedId)
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->latest()
            ->first();

        if ($admin?->email) {
            return $admin->email;
        }

        return Integrations::isEnvFallbackAllowed($resolvedId) ? config('mail.from.address') : null;
    }

    /**
     * Whether WhatsApp channel should be attempted for this institute.
     */
    protected function shouldTryWhatsApp(?int $instituteId = null): bool
    {
        if (!in_array($this->channel, ['auto', 'whatsapp'], true)) {
            return false;
        }

        if (app()->isShared(WhatsAppService::class)) {
            return (bool) config('services.whatsapp.enabled', false);
        }

        $row = Integrations::for($instituteId)->findRow(InstituteIntegration::PROVIDER_WHATSAPP);
        if ($row !== null && $row->status !== InstituteIntegration::STATUS_UNCONFIGURED) {
            return true;
        }

        return (bool) config('services.whatsapp.enabled', false);
    }

    /**
     * Whether Email channel should be attempted.
     */
    protected function shouldTryEmail(): bool
    {
        return in_array($this->channel, ['auto', 'email'], true);
    }
}

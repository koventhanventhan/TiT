<?php

namespace App\Services;

use App\Models\Institute;
use App\Models\SiteSetting;
use App\Support\Integrations;

class PublicAssistantStatusService
{
    public const STATE_PLATFORM_DISABLED = 'platform_disabled';
    public const STATE_PLAN_MISSING_FEATURE = 'plan_missing_feature';
    public const STATE_GEMINI_MISSING = 'gemini_missing';
    public const STATE_RECAPTCHA_MISSING = 'recaptcha_missing';
    public const STATE_CONTACT_DETAILS_MISSING = 'contact_details_missing';
    public const STATE_INSTITUTE_DISABLED = 'institute_disabled';
    public const STATE_LIVE = 'live';

    /**
     * Get missing official contact fields for an institute.
     * Requires at least one phone/whatsapp AND at least one email/address.
     *
     * @return array<int, string>
     */
    public function getMissingContactFields(int $instituteId): array
    {
        $phone = trim((string) SiteSetting::getForInstitute('assistant_public_contact_phone', $instituteId, ''));
        $whatsapp = trim((string) SiteSetting::getForInstitute('assistant_public_contact_whatsapp', $instituteId, ''));
        $email = trim((string) SiteSetting::getForInstitute('assistant_public_contact_email', $instituteId, ''));
        $address = trim((string) SiteSetting::getForInstitute('assistant_public_contact_address', $instituteId, ''));

        $missing = [];
        if (empty($phone) && empty($whatsapp)) {
            $missing[] = 'phone or WhatsApp';
        }
        if (empty($email) && empty($address)) {
            $missing[] = 'email or address';
        }

        return $missing;
    }

    /**
     * Check safety rules for turning the public assistant ON.
     *
     * Rules:
     * 1. Official contact details must be present (phone/whatsapp and email/address).
     * 2. Outside local and testing environments, Google reCAPTCHA must be configured.
     *
     * @param Institute|int $institute
     * @param array<int, string> &$refusalReasons
     * @return bool
     */
    public function canTurnOn(Institute|int $institute, array &$refusalReasons = []): bool
    {
        $institute = $institute instanceof Institute ? $institute : Institute::find($institute);
        if (!$institute) {
            $refusalReasons = ['Institute not found'];
            return false;
        }

        $instituteId = (int) $institute->id;
        $refusalReasons = [];

        // 1. Outside local and testing environments, reCAPTCHA must be configured
        $env = config('app.env') ?: app()->environment();
        $isLocalOrTesting = in_array($env, ['local', 'testing'], true);
        $recaptcha = Integrations::for($instituteId)->recaptcha();
        if (!$isLocalOrTesting && ($recaptcha === null || !$recaptcha->isConfigured())) {
            $refusalReasons[] = 'Google reCAPTCHA is missing or incomplete (required in production)';
        }

        // 2. Official contact details must be configured
        $missing = $this->getMissingContactFields($instituteId);
        if (!empty($missing)) {
            $refusalReasons[] = 'Required contact settings are missing: ' . implode(', ', $missing);
        }

        return empty($refusalReasons);
    }

    /**
     * Evaluate the 7 blocking states in the exact required order:
     * 1) Platform switch RAG_PUBLIC_ENABLED off (say "ask the platform owner")
     * 2) Plan lacks ai_assistant
     * 3) Gemini key missing
     * 4) reCAPTCHA missing
     * 5) Official contact details missing
     * 6) Institute switch off
     * 7) "Live – visitors can use the assistant"
     *
     * @param Institute|int $institute
     * @return array<string, mixed>
     */
    public function check(Institute|int $institute): array
    {
        $institute = $institute instanceof Institute ? $institute : Institute::find($institute);
        if (!$institute) {
            return [
                'state' => 'unknown',
                'sentence' => 'Institute not found.',
                'is_live' => false,
                'can_turn_on' => false,
                'refusal_reasons' => ['Institute not found'],
                'has_gemini' => false,
                'has_recaptcha' => false,
                'plan_allows_ai' => false,
                'has_contacts' => false,
                'missing_contacts' => ['phone or WhatsApp', 'email or address'],
                'institute_switch_on' => false,
            ];
        }

        $instituteId = (int) $institute->id;
        $globalPublicEnabled = filter_var(config('rag.public_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $planAllowsAi = $institute->hasFeature('ai_assistant');
        $geminiCreds = Integrations::for($instituteId)->gemini();
        $hasGemini = $geminiCreds !== null && $geminiCreds->isConfigured();
        $recaptchaCreds = Integrations::for($instituteId)->recaptcha();
        $hasRecaptcha = $recaptchaCreds !== null && $recaptchaCreds->isConfigured();
        $missingContacts = $this->getMissingContactFields($instituteId);
        $hasContacts = empty($missingContacts);
        $instituteSwitchOn = (bool) $institute->public_assistant_enabled;

        if (!$globalPublicEnabled) {
            $state = self::STATE_PLATFORM_DISABLED;
            $sentence = 'The platform-wide assistant switch (RAG_PUBLIC_ENABLED) is off. Ask the platform owner to enable it.';
        } elseif (!$planAllowsAi) {
            $state = self::STATE_PLAN_MISSING_FEATURE;
            $sentence = 'Your subscription plan does not include the AI Assistant feature. Please upgrade your plan.';
        } elseif (!$hasGemini) {
            $state = self::STATE_GEMINI_MISSING;
            $sentence = 'Google Gemini API key is missing. Configure your Gemini API key in AI & Security Settings.';
        } elseif (!$hasRecaptcha) {
            $state = self::STATE_RECAPTCHA_MISSING;
            $sentence = 'Google reCAPTCHA is missing. Configure reCAPTCHA credentials in AI & Security Settings.';
        } elseif (!$hasContacts) {
            $state = self::STATE_CONTACT_DETAILS_MISSING;
            $sentence = 'Official contact details are incomplete (missing ' . implode(' and ', $missingContacts) . '). Visitors are sent to these contacts; the assistant never invents contacts.';
        } elseif (!$instituteSwitchOn) {
            $state = self::STATE_INSTITUTE_DISABLED;
            $sentence = 'The public assistant switch is turned off for this institute.';
        } else {
            $state = self::STATE_LIVE;
            $sentence = 'Live – visitors can use the assistant.';
        }

        $refusalReasons = [];
        $canTurnOn = $this->canTurnOn($institute, $refusalReasons);

        return [
            'state' => $state,
            'sentence' => $sentence,
            'is_live' => $state === self::STATE_LIVE,
            'has_gemini' => $hasGemini,
            'has_recaptcha' => $hasRecaptcha,
            'plan_allows_ai' => $planAllowsAi,
            'has_contacts' => $hasContacts,
            'missing_contacts' => $missingContacts,
            'can_turn_on' => $canTurnOn,
            'refusal_reasons' => $refusalReasons,
            'institute_switch_on' => $instituteSwitchOn,
        ];
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\InstituteIntegration;
use App\Services\PayHereService;
use App\Services\WhatsAppService;
use App\Support\Credentials\PayHereCredentials;
use App\Support\InstituteMail;
use App\Support\Integrations;
use App\Support\Redact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IntegrationController extends Controller
{
    /**
     * Strictly authorize institute `admin` role and resolve `institute_id` ONLY from the authenticated user.
     *
     * @return array{0: int, 1: Institute}
     */
    private function resolveAuthorizedInstitute(Request $request): array
    {
        $user = $request->user();
        if (!$user || !Gate::forUser($user)->allows('manage', InstituteIntegration::class)) {
            abort(403, 'Only institute administrators may manage integration credentials.');
        }

        $instituteId = (int) $user->institute_id;
        $institute = Institute::withoutGlobalScopes()->findOrFail($instituteId);

        return [$instituteId, $institute];
    }

    public function onlinePayments(Request $request): View
    {
        [$instituteId, $institute] = $this->resolveAuthorizedInstitute($request);

        $resolver = Integrations::for($instituteId);
        $integration = $resolver->findRow(InstituteIntegration::PROVIDER_PAYHERE);
        $resolved = $resolver->payhere();
        $masked = $integration ? $integration->maskedCredentials() : [];

        $primaryDomain = $institute->primaryDomainHost();
        $notifyUrl = 'https://' . $primaryDomain . '/api/payhere/notify';

        $usingPlatformFallback = ($integration === null || $integration->status === InstituteIntegration::STATUS_UNCONFIGURED)
            && $resolved !== null
            && $resolved->source === 'env';

        return view('admin.settings.online_payments', [
            'institute' => $institute,
            'integration' => $integration,
            'masked' => $masked,
            'usingPlatformFallback' => $usingPlatformFallback,
            'primaryDomain' => $primaryDomain,
            'notifyUrl' => $notifyUrl,
        ]);
    }

    public function updateOnlinePayments(Request $request): RedirectResponse
    {
        [$instituteId] = $this->resolveAuthorizedInstitute($request);

        $existing = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('provider', InstituteIntegration::PROVIDER_PAYHERE)
            ->first();

        $existingCreds = is_array($existing?->credentials) ? $existing->credentials : [];
        $existingSettings = is_array($existing?->settings) ? $existing->settings : [];
        $hasExistingSecret = !empty($existingCreds['merchant_secret']);

        $validated = $request->validate([
            'merchant_id' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'],
            'merchant_secret' => [$hasExistingSecret ? 'nullable' : 'required', 'string', 'min:8', 'max:255'],
            'app_id' => ['nullable', 'string', 'max:120', 'regex:/^[A-Za-z0-9_.-]*$/'],
            'app_secret' => ['nullable', 'string', 'min:8', 'max:255'],
            'is_sandbox' => ['required', 'boolean'],
            'currency' => ['required', 'string', Rule::in(['LKR', 'USD'])],
        ]);

        $newMerchantSecret = filled($validated['merchant_secret'] ?? null)
            ? trim((string) $validated['merchant_secret'])
            : (string) ($existingCreds['merchant_secret'] ?? '');

        $newAppSecret = filled($validated['app_secret'] ?? null)
            ? trim((string) $validated['app_secret'])
            : ($existingCreds['app_secret'] ?? null);

        $credentials = array_filter([
            'merchant_secret' => $newMerchantSecret,
            'app_secret' => $newAppSecret ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        $newMerchantId = trim((string) $validated['merchant_id']);
        $newIsSandbox = (bool) $validated['is_sandbox'];
        $newCurrency = strtoupper(trim((string) $validated['currency']));
        $newAppId = filled($validated['app_id'] ?? null) ? trim((string) $validated['app_id']) : null;

        $settings = [
            'merchant_id' => $newMerchantId,
            'app_id' => $newAppId,
            'is_sandbox' => $newIsSandbox,
            'currency' => $newCurrency,
        ];

        $coreUnchanged = $existing !== null
            && ($existingSettings['merchant_id'] ?? null) === $newMerchantId
            && (bool) ($existingSettings['is_sandbox'] ?? true) === $newIsSandbox
            && ($existingCreds['merchant_secret'] ?? null) === $newMerchantSecret;

        $nextStatus = ($coreUnchanged && $existing?->status === InstituteIntegration::STATUS_VERIFIED)
            ? InstituteIntegration::STATUS_VERIFIED
            : InstituteIntegration::STATUS_CONFIGURED;

        if ($existing) {
            Gate::forUser($request->user())->authorize('manage', $existing);
            $existing->fill([
                'credentials' => $credentials,
                'settings' => $settings,
                'status' => $nextStatus,
                'last_error' => null,
                'last_verified_at' => $nextStatus === InstituteIntegration::STATUS_VERIFIED ? $existing->last_verified_at : null,
            ]);
            $existing->save();
        } else {
            InstituteIntegration::withoutGlobalScopes()->create([
                'institute_id' => $instituteId,
                'provider' => InstituteIntegration::PROVIDER_PAYHERE,
                'credentials' => $credentials,
                'settings' => $settings,
                'status' => InstituteIntegration::STATUS_CONFIGURED,
                'last_error' => null,
            ]);
        }

        return redirect()
            ->route('admin.settings.online-payments')
            ->with('success', 'PayHere online payment settings saved.');
    }

    public function checkOnlinePayments(Request $request): RedirectResponse
    {
        [$instituteId] = $this->resolveAuthorizedInstitute($request);

        $integration = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('provider', InstituteIntegration::PROVIDER_PAYHERE)
            ->first();

        if (!$integration || $integration->status === InstituteIntegration::STATUS_UNCONFIGURED) {
            return redirect()
                ->route('admin.settings.online-payments')
                ->with('error', 'Please save your Merchant ID and Merchant Secret before running a configuration check.');
        }

        Gate::forUser($request->user())->authorize('manage', $integration);

        $settings = is_array($integration->settings) ? $integration->settings : [];
        $creds = is_array($integration->credentials) ? $integration->credentials : [];
        $merchantId = trim((string) ($settings['merchant_id'] ?? ''));
        $merchantSecret = trim((string) ($creds['merchant_secret'] ?? ''));
        $currency = strtoupper(trim((string) ($settings['currency'] ?? 'LKR')));

        if ($merchantId === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $merchantId) || strlen($merchantSecret) < 8) {
            $integration->recordFailure('Invalid Merchant ID or Merchant Secret format.', [$merchantSecret]);
            return redirect()
                ->route('admin.settings.online-payments')
                ->with('error', 'PayHere format validation failed. Please review your Merchant ID and Secret.');
        }

        $localCreds = new PayHereCredentials(
            instituteId: $instituteId,
            merchantId: $merchantId,
            merchantSecret: $merchantSecret,
            isSandbox: array_key_exists('is_sandbox', $settings) ? (bool) $settings['is_sandbox'] : true,
            currency: $currency !== '' ? $currency : 'LKR',
        );
        $hash = PayHereService::forInstitute($instituteId)->generateHash('LOCAL_CONFIG_CHECK', 100.00, $localCreds);
        if (strlen($hash) !== 32 || !ctype_xdigit($hash)) {
            $integration->recordFailure('Could not generate valid PayHere checkout signature.', [$merchantSecret]);
            return redirect()
                ->route('admin.settings.online-payments')
                ->with('error', 'Local checkout hash check failed.');
        }

        if ($integration->status !== InstituteIntegration::STATUS_VERIFIED) {
            $integration->status = InstituteIntegration::STATUS_CONFIGURED;
        }
        $integration->last_error = null;
        $integration->save();

        return redirect()
            ->route('admin.settings.online-payments')
            ->with('success', 'PayHere configuration validated locally (checkout signature generated). Status will update to Verified after the first live payment notification.');
    }

    public function clearOnlinePayments(Request $request): RedirectResponse
    {
        [$instituteId] = $this->resolveAuthorizedInstitute($request);

        $integration = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('provider', InstituteIntegration::PROVIDER_PAYHERE)
            ->first();

        if ($integration) {
            Gate::forUser($request->user())->authorize('manage', $integration);
            $integration->clearCredentials();
        }

        return redirect()
            ->route('admin.settings.online-payments')
            ->with('success', 'PayHere credentials cleared. Online payments are now disabled for your institute.');
    }

    public function messaging(Request $request): View
    {
        [$instituteId, $institute] = $this->resolveAuthorizedInstitute($request);

        $resolver = Integrations::for($instituteId);

        $mailIntegration = $resolver->findRow(InstituteIntegration::PROVIDER_MAIL);
        $resolvedMail = $resolver->mail();
        $mailMasked = $mailIntegration ? $mailIntegration->maskedCredentials() : [];
        $mailUsingPlatformFallback = ($mailIntegration === null || $mailIntegration->status === InstituteIntegration::STATUS_UNCONFIGURED)
            && $resolvedMail !== null
            && $resolvedMail->source === 'env';

        $whatsappIntegration = $resolver->findRow(InstituteIntegration::PROVIDER_WHATSAPP);
        $resolvedWhatsApp = $resolver->whatsapp(logWhenUnconfigured: false);
        $whatsappMasked = $whatsappIntegration ? $whatsappIntegration->maskedCredentials() : [];
        $whatsappUsingPlatformFallback = ($whatsappIntegration === null || $whatsappIntegration->status === InstituteIntegration::STATUS_UNCONFIGURED)
            && $resolvedWhatsApp !== null
            && $resolvedWhatsApp->source === 'env';

        $defaultTemplates = (array) config('integrations.whatsapp.templates', []);
        $contactReplyToDefault = $resolver->resolveInstituteContactEmail($instituteId) ?: $request->user()->email;

        return view('admin.settings.messaging', [
            'institute' => $institute,
            'adminEmail' => $request->user()->email,
            'mailIntegration' => $mailIntegration,
            'mailMasked' => $mailMasked,
            'mailUsingPlatformFallback' => $mailUsingPlatformFallback,
            'contactReplyToDefault' => $contactReplyToDefault,
            'whatsappIntegration' => $whatsappIntegration,
            'whatsappMasked' => $whatsappMasked,
            'whatsappUsingPlatformFallback' => $whatsappUsingPlatformFallback,
            'defaultTemplates' => $defaultTemplates,
        ]);
    }

    public function updateMail(Request $request): RedirectResponse
    {
        [$instituteId, $institute] = $this->resolveAuthorizedInstitute($request);

        $existing = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('provider', InstituteIntegration::PROVIDER_MAIL)
            ->first();

        $existingCreds = is_array($existing?->credentials) ? $existing->credentials : [];

        $validated = $request->validate([
            'host' => ['required', 'string', 'max:191', 'regex:/^[A-Za-z0-9.-]+$/'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'encryption' => ['required', 'string', Rule::in(['tls', 'ssl', 'none'])],
            'username' => ['nullable', 'string', 'max:191'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => ['required', 'email:rfc', 'max:191'],
            'from_name' => ['required', 'string', 'max:120'],
            'reply_to' => ['nullable', 'email:rfc', 'max:191'],
        ]);

        $newPassword = filled($validated['password'] ?? null)
            ? (string) $validated['password']
            : ($existingCreds['password'] ?? null);

        $credentials = array_filter([
            'password' => $newPassword,
        ], fn ($v) => $v !== null && $v !== '');

        $settings = [
            'host' => trim((string) $validated['host']),
            'port' => (int) $validated['port'],
            'encryption' => strtolower(trim((string) $validated['encryption'])),
            'username' => filled($validated['username'] ?? null) ? trim((string) $validated['username']) : null,
            'from_address' => trim((string) $validated['from_address']),
            'from_name' => trim((string) $validated['from_name']) ?: $institute->name,
            'reply_to' => filled($validated['reply_to'] ?? null) ? trim((string) $validated['reply_to']) : null,
        ];

        if ($existing) {
            Gate::forUser($request->user())->authorize('manage', $existing);
            $existing->fill([
                'credentials' => $credentials,
                'settings' => $settings,
                'status' => InstituteIntegration::STATUS_CONFIGURED,
                'last_error' => null,
            ]);
            $existing->save();
        } else {
            InstituteIntegration::withoutGlobalScopes()->create([
                'institute_id' => $instituteId,
                'provider' => InstituteIntegration::PROVIDER_MAIL,
                'credentials' => $credentials,
                'settings' => $settings,
                'status' => InstituteIntegration::STATUS_CONFIGURED,
                'last_error' => null,
            ]);
        }

        return redirect()
            ->route('admin.settings.messaging')
            ->with('success', 'Outgoing e-mail (SMTP) settings saved.');
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        [$instituteId] = $this->resolveAuthorizedInstitute($request);

        $adminEmail = trim((string) ($request->user()->email ?? ''));
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return redirect()
                ->route('admin.settings.messaging')
                ->with('error', 'Your administrator profile does not have a valid e-mail address configured.');
        }

        $result = InstituteMail::sendTestEmail($instituteId, $adminEmail);

        if ($result['success']) {
            return redirect()
                ->route('admin.settings.messaging')
                ->with('success', $result['message']);
        }

        return redirect()
            ->route('admin.settings.messaging')
            ->with('error', 'Test e-mail failed: ' . Redact::sanitizeText($result['message']));
    }

    public function clearMail(Request $request): RedirectResponse
    {
        [$instituteId] = $this->resolveAuthorizedInstitute($request);

        $integration = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('provider', InstituteIntegration::PROVIDER_MAIL)
            ->first();

        if ($integration) {
            Gate::forUser($request->user())->authorize('manage', $integration);
            $integration->clearCredentials();
        }

        return redirect()
            ->route('admin.settings.messaging')
            ->with('success', 'Custom SMTP settings cleared.');
    }

    public function updateWhatsApp(Request $request): RedirectResponse
    {
        [$instituteId] = $this->resolveAuthorizedInstitute($request);

        $existing = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('provider', InstituteIntegration::PROVIDER_WHATSAPP)
            ->first();

        $existingCreds = is_array($existing?->credentials) ? $existing->credentials : [];
        $hasExistingToken = !empty($existingCreds['access_token']);

        $defaultTemplateKeys = array_keys((array) config('integrations.whatsapp.templates', []));
        $templateRules = [];
        foreach ($defaultTemplateKeys as $eventKey) {
            $templateRules["templates.{$eventKey}"] = ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9_]+$/'];
        }

        $validated = $request->validate(array_merge([
            'access_token' => [$hasExistingToken ? 'nullable' : 'required', 'string', 'min:10', 'max:1024'],
            'phone_number_id' => ['required', 'string', 'max:40', 'regex:/^[0-9]+$/'],
            'api_version' => ['required', 'string', 'max:16', 'regex:/^v\d+\.\d+$/'],
            'templates' => ['nullable', 'array'],
        ], $templateRules));

        $newToken = filled($validated['access_token'] ?? null)
            ? trim((string) $validated['access_token'])
            : (string) ($existingCreds['access_token'] ?? '');

        $cleanTemplates = [];
        foreach ($defaultTemplateKeys as $eventKey) {
            $val = trim((string) ($validated['templates'][$eventKey] ?? ''));
            if ($val !== '') {
                $cleanTemplates[$eventKey] = $val;
            }
        }

        $credentials = [
            'access_token' => $newToken,
        ];

        $settings = [
            'phone_number_id' => trim((string) $validated['phone_number_id']),
            'api_version' => trim((string) $validated['api_version']),
            'templates' => $cleanTemplates,
        ];

        if ($existing) {
            Gate::forUser($request->user())->authorize('manage', $existing);
            $existing->fill([
                'credentials' => $credentials,
                'settings' => $settings,
                'status' => InstituteIntegration::STATUS_CONFIGURED,
                'last_error' => null,
            ]);
            $existing->save();
        } else {
            InstituteIntegration::withoutGlobalScopes()->create([
                'institute_id' => $instituteId,
                'provider' => InstituteIntegration::PROVIDER_WHATSAPP,
                'credentials' => $credentials,
                'settings' => $settings,
                'status' => InstituteIntegration::STATUS_CONFIGURED,
                'last_error' => null,
            ]);
        }

        return redirect()
            ->route('admin.settings.messaging')
            ->with('success', 'WhatsApp Cloud API settings saved.');
    }

    public function sendTestWhatsApp(Request $request): RedirectResponse
    {
        [$instituteId, $institute] = $this->resolveAuthorizedInstitute($request);

        $validated = $request->validate([
            'test_to' => ['required', 'string', 'regex:/^\+[1-9]\d{7,14}$/'],
        ], [
            'test_to.regex' => 'Enter a valid E.164 phone number starting with + and country code (for example +94771234567).',
        ]);

        // Strict per-institute rate limit: max 3 WhatsApp test messages per hour per institute
        $rateKey = "integrations-whatsapp-test:{$instituteId}";
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($rateKey) / 60));
            return redirect()
                ->route('admin.settings.messaging')
                ->with('error', "WhatsApp test limit reached (maximum 3 per hour per institute). Please wait {$minutes} minute(s).");
        }

        RateLimiter::hit($rateKey, 3600);

        $service = WhatsAppService::forInstitute($instituteId);
        if (!$service->isConfigured()) {
            return redirect()
                ->route('admin.settings.messaging')
                ->with('error', 'WhatsApp is not configured for your institute. Save your Access Token and Phone Number ID first.');
        }

        $result = $service->sendTemplateResult(
            $validated['test_to'],
            'general_update',
            'en',
            [$institute->name, 'Test message from your institute settings panel']
        );

        if ($result->isSent()) {
            return redirect()
                ->route('admin.settings.messaging')
                ->with('success', "Test WhatsApp message sent to {$validated['test_to']}.");
        }

        $reason = $result->reason ? Redact::sanitizeText($result->reason) : 'Message could not be delivered.';
        return redirect()
            ->route('admin.settings.messaging')
            ->with('error', 'WhatsApp test failed: ' . $reason);
    }

    public function clearWhatsApp(Request $request): RedirectResponse
    {
        [$instituteId] = $this->resolveAuthorizedInstitute($request);

        $integration = InstituteIntegration::withoutGlobalScopes()
            ->where('institute_id', $instituteId)
            ->where('provider', InstituteIntegration::PROVIDER_WHATSAPP)
            ->first();

        if ($integration) {
            Gate::forUser($request->user())->authorize('manage', $integration);
            $integration->clearCredentials();
        }

        return redirect()
            ->route('admin.settings.messaging')
            ->with('success', 'WhatsApp credentials cleared.');
    }
}

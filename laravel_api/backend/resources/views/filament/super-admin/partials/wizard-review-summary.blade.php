@php
    /** @var \App\Filament\SuperAdmin\Resources\InstituteResource\Pages\CreateInstitute $this */
    $name = trim((string) data_get($this->data, 'name', ''));
    $brand = trim((string) data_get($this->data, 'short_brand_name', '')) ?: $name;
    $hasLogo = !empty(data_get($this->data, 'logo_path'));
    $contactEmail = trim((string) data_get($this->data, 'contact_email', ''));
    $contactPhone = trim((string) data_get($this->data, 'contact_phone', ''));

    $deferDomain = (bool) data_get($this->data, 'defer_domain', false);
    $domainRaw = trim((string) data_get($this->data, 'domain', ''));
    $normalizedDomain = (!$deferDomain && $domainRaw !== '')
        ? app(\App\Services\DomainChecker::class)->normalize($domainRaw)
        : null;
    $dnsBadge = $this->dnsCheckResult['badge'] ?? 'Not checked';

    $planId = data_get($this->data, 'subscription_plan_id');
    $plan = $planId ? \App\Models\SubscriptionPlan::find($planId) : null;
    $expiresAtRaw = data_get($this->data, 'expires_at');
    $expiresFormatted = $expiresAtRaw ? \Carbon\Carbon::parse($expiresAtRaw)->format('M d, Y') : '30 days from today';

    $createAdmin = (bool) data_get($this->data, 'create_admin', true);
    $adminName = trim((string) data_get($this->data, 'admin_name', ''));
    $adminEmail = trim((string) data_get($this->data, 'admin_email', ''));
    $adminPhone = trim((string) data_get($this->data, 'admin_phone', ''));

    $preset = ucfirst(strtolower((string) data_get($this->data, 'theme_preset', 'royal')));
    $prefix = strtoupper(trim((string) data_get($this->data, 'index_prefix', '')));
    if ($prefix === '') {
        $prefix = \App\Services\InstituteProvisioner::deriveIndexPrefix($name ?: 'Institute');
    }
    $format = data_get($this->data, 'index_format', 'grade') === 'batch' ? 'batch' : 'grade';
    $idExample = $format === 'batch' ? "{$prefix}/" . date('y') . '/1001' : "{$prefix}/08/001";
@endphp

<div class="wiz-review-card" data-testid="wizard-review-summary">
    <style>
        .wiz-review-card {
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            box-sizing: border-box;
            max-width: 100%;
        }
        html.dark .wiz-review-card {
            border-color: #334155;
            background: #0f172a;
            color: #f8fafc;
        }
        .wiz-review-row {
            display: grid;
            grid-template-columns: minmax(140px, 210px) 1fr;
            gap: 0.5rem 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
            font-size: 0.875rem;
        }
        .wiz-review-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        @media (max-width: 640px) {
            .wiz-review-row {
                grid-template-columns: 1fr;
                gap: 0.25rem;
            }
        }
        .wiz-review-label {
            font-weight: 600;
            color: #475569;
        }
        html.dark .wiz-review-label {
            color: #cbd5e1;
        }
        .wiz-review-val {
            word-break: break-word;
        }
    </style>

    <div style="font-size: 1rem; font-weight: 700;">
        Review your choices before creating the institute
    </div>

    <div class="wiz-review-row">
        <div class="wiz-review-label">Institute</div>
        <div class="wiz-review-val">
            <strong>{{ $name ?: '—' }}</strong>
            @if ($brand && $brand !== $name)
                (Brand name: {{ $brand }})
            @endif
            &bull; Logo: {{ $hasLogo ? 'Uploaded' : 'None (can be added later)' }}
            @if ($contactEmail || $contactPhone)
                <br />Public contact: {{ implode(' • ', array_filter([$contactEmail, $contactPhone])) }}
            @endif
        </div>
    </div>

    <div class="wiz-review-row">
        <div class="wiz-review-label">Website address</div>
        <div class="wiz-review-val">
            @if ($normalizedDomain)
                <strong>{{ $normalizedDomain }}</strong> (DNS check: {{ $dnsBadge }})
            @else
                Will be added later
            @endif
        </div>
    </div>

    <div class="wiz-review-row">
        <div class="wiz-review-label">Subscription plan</div>
        <div class="wiz-review-val">
            @if ($plan)
                <strong>{{ $plan->name }}</strong>
                (Students: {{ $plan->formattedLimit('max_students') }}, Teachers: {{ $plan->formattedLimit('max_teachers') }})
            @else
                —
            @endif
            &bull; Valid until {{ $expiresFormatted }}
        </div>
    </div>

    <div class="wiz-review-row">
        <div class="wiz-review-label">First administrator</div>
        <div class="wiz-review-val">
            @if ($createAdmin)
                <strong>{{ $adminName ?: '—' }}</strong> &bull; {{ $adminEmail ?: '—' }} &bull; {{ $adminPhone ?: '—' }}
                <br /><span style="font-size: 0.8125rem; opacity: 0.85;">A strong one-time password will be shown once on the next screen.</span>
            @else
                Will be created later from the setup checklist
            @endif
        </div>
    </div>

    <div class="wiz-review-row">
        <div class="wiz-review-label">Look &amp; feel</div>
        <div class="wiz-review-val">
            Theme: <strong>{{ $preset }}</strong> &bull; Student ID example: <strong>{{ $idExample }}</strong>
        </div>
    </div>
</div>


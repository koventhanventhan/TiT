@php
    /** @var \App\Filament\SuperAdmin\Resources\InstituteResource\Pages\CreateInstitute $this */
    $deferDomain = (bool) data_get($this->data, 'defer_domain', false);
    $rawDomain = trim((string) data_get($this->data, 'domain', ''));
    $dnsResult = $this->dnsCheckResult;
    $badgeLabel = $dnsResult['badge'] ?? \App\Services\DomainChecker::badgeLabel(\App\Services\DomainChecker::STATUS_NOT_CHECKED);
    $status = $dnsResult['status'] ?? \App\Services\DomainChecker::STATUS_NOT_CHECKED;
    $message = $dnsResult['message'] ?? 'Click "Check DNS now" to verify whether this address points to the server. Verification is optional — you can create the institute even if DNS has not propagated yet.';
@endphp

@if (!$deferDomain)
    <div class="wiz-dns-box" data-testid="wizard-dns-box">
        <style>
            .fi-sc-wizard .fi-btn,
            .fi-sc-wizard-header-step-btn {
                min-height: 44px;
            }
            .wiz-dns-box {
                border-radius: 0.75rem;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #0f172a;
                padding: 1rem 1.125rem;
                display: flex;
                flex-direction: column;
                gap: 0.75rem;
                box-sizing: border-box;
                max-width: 100%;
            }
            html.dark .wiz-dns-box {
                border-color: #334155;
                background: #0f172a;
                color: #f1f5f9;
            }
            .wiz-dns-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.25rem 0.625rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 700;
            }
            .wiz-dns-gray {
                background: #e2e8f0;
                color: #0f172a;
            }
            html.dark .wiz-dns-gray {
                background: #334155;
                color: #f8fafc;
            }
            .wiz-dns-success {
                background: #dcfce7;
                color: #14532d;
            }
            html.dark .wiz-dns-success {
                background: #14532d;
                color: #dcfce7;
            }
            .wiz-dns-warning {
                background: #fef3c7;
                color: #78350f;
            }
            html.dark .wiz-dns-warning {
                background: #78350f;
                color: #fef3c7;
            }
            .wiz-dns-danger {
                background: #fee2e2;
                color: #7f1d1d;
            }
            html.dark .wiz-dns-danger {
                background: #7f1d1d;
                color: #fee2e2;
            }
            .wiz-action-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-height: 44px;
                min-width: 44px;
                padding: 0.5rem 0.875rem;
                border-radius: 0.5rem;
                border: 1px solid #cbd5e1;
                background: #f8fafc;
                color: #0f172a;
                font-size: 0.875rem;
                font-weight: 600;
                cursor: pointer;
            }
            .wiz-action-btn:hover {
                background: #e2e8f0;
            }
            html.dark .wiz-action-btn {
                border-color: #475569;
                background: #1e293b;
                color: #f8fafc;
            }
            html.dark .wiz-action-btn:hover {
                background: #334155;
            }
        </style>

        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
            <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.625rem;">
                <span style="font-size: 0.875rem; font-weight: 600;">DNS status:</span>
                @php
                    $badgeClass = match ($status) {
                        \App\Services\DomainChecker::STATUS_POINTS_HERE => 'wiz-dns-success',
                        \App\Services\DomainChecker::STATUS_POINTS_ELSEWHERE => 'wiz-dns-warning',
                        \App\Services\DomainChecker::STATUS_NOT_FOUND => 'wiz-dns-danger',
                        default => 'wiz-dns-gray',
                    };
                @endphp
                <span class="wiz-dns-badge {{ $badgeClass }}" data-testid="wizard-dns-badge">
                    {{ $badgeLabel }}
                </span>
            </div>

            <button
                type="button"
                wire:click="checkWizardDomainDns"
                class="wiz-action-btn"
                data-testid="wizard-check-dns-btn"
            >
                <span wire:loading.remove wire:target="checkWizardDomainDns">Check DNS now</span>
                <span wire:loading wire:target="checkWizardDomainDns">Checking...</span>
            </button>
        </div>

        <div style="font-size: 0.8125rem; line-height: 1.5; color: inherit;" data-testid="wizard-dns-message">
            {{ $message }}
        </div>
    </div>
@endif


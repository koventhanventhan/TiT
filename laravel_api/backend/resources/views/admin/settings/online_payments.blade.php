@extends('layouts.admin')

@section('title', 'Online Payments (PayHere)')

@push('styles')
<style>
    .intg-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.375rem 0.875rem;
        border-radius: 999px;
        font-size: 0.8125rem;
        font-weight: 600;
        background: var(--adm-input-bg);
        color: var(--adm-text);
        border: 1px solid var(--adm-border);
    }
    .intg-chip-dot {
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 50%;
        flex-shrink: 0;
        background: var(--adm-text-2);
    }
    .intg-chip--verified { border-color: var(--adm-success); }
    .intg-chip--verified .intg-chip-dot { background: var(--adm-success); }
    .intg-chip--configured { border-color: var(--adm-accent); }
    .intg-chip--configured .intg-chip-dot { background: var(--adm-accent); }
    .intg-chip--failing { border-color: var(--adm-danger); }
    .intg-chip--failing .intg-chip-dot { background: var(--adm-danger); }
    .intg-chip--fallback { border-color: var(--adm-warning); }
    .intg-chip--fallback .intg-chip-dot { background: var(--adm-warning); }

    .intg-failing-banner {
        background: var(--adm-input-bg);
        border: 1px solid var(--adm-danger);
        border-left: 4px solid var(--adm-danger);
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        color: var(--adm-text);
    }
    .intg-failing-title {
        font-weight: 700;
        color: var(--adm-danger-light);
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
    }

    .intg-readonly-box {
        background: var(--adm-input-bg);
        border: 1px solid var(--adm-border);
        border-radius: 0.75rem;
        padding: 0.875rem 1rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .intg-readonly-val {
        font-family: monospace;
        font-size: 0.875rem;
        color: var(--adm-text);
        word-break: break-all;
        flex: 1 1 200px;
    }

    .intg-btn-secondary {
        min-height: 44px;
        min-width: 44px;
        padding: 0.5rem 1rem;
        border-radius: 0.625rem;
        background: var(--adm-input-bg);
        color: var(--adm-text);
        border: 1px solid var(--adm-border);
        font-weight: 600;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        cursor: pointer;
    }
    .intg-btn-secondary:hover,
    .intg-btn-secondary:focus {
        border-color: var(--adm-accent);
        color: var(--adm-text);
    }

    .intg-btn-danger {
        min-height: 44px;
        min-width: 44px;
        padding: 0.5rem 1rem;
        border-radius: 0.625rem;
        background: var(--adm-input-bg);
        color: var(--adm-danger-light);
        border: 1px solid var(--adm-danger);
        font-weight: 600;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        cursor: pointer;
    }

    .intg-btn-primary {
        min-height: 44px;
        min-width: 44px;
        padding: 0.5rem 1.25rem;
        border-radius: 0.625rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
    }

    .intg-control {
        min-height: 44px;
        background: var(--adm-input-bg) !important;
        color: var(--adm-text) !important;
        border: 1px solid var(--adm-border) !important;
        border-radius: 0.625rem;
    }

    .intg-secret-saved {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.5rem 0.875rem;
        border-radius: 0.625rem;
        background: var(--adm-input-bg);
        border: 1px solid var(--adm-border);
        min-height: 48px;
    }
    .intg-secret-mask {
        font-family: monospace;
        font-size: 0.95rem;
        letter-spacing: 0.06em;
        color: var(--adm-text);
    }

    .intg-mode-group {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.625rem;
    }
    .intg-mode-btn {
        min-height: 46px;
        border-radius: 0.625rem;
        border: 1px solid var(--adm-border);
        background: var(--adm-input-bg);
        color: var(--adm-text-2);
        font-weight: 600;
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
        cursor: pointer;
        text-align: center;
    }
    .intg-mode-btn.is-active {
        border: 2px solid var(--adm-accent);
        background: var(--adm-surface-2);
        color: var(--adm-text);
    }

    .intg-actions-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--adm-border);
    }
    .intg-actions-left {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    @media (max-width: 575.98px) {
        .intg-mode-group {
            grid-template-columns: 1fr;
        }
        .intg-actions-bar,
        .intg-actions-left {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
        }
        .intg-actions-bar .btn,
        .intg-actions-bar button,
        .intg-readonly-box button {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $status = $integration?->status ?? 'unconfigured';
    $settings = is_array($integration?->settings) ? $integration->settings : [];
    $isConfiguredRow = $integration !== null && $status !== 'unconfigured';
    $isSandbox = array_key_exists('is_sandbox', $settings) ? (bool) $settings['is_sandbox'] : true;
    if (old('is_sandbox') !== null) {
        $isSandbox = (bool) old('is_sandbox');
    }
    $currency = strtoupper((string) old('currency', $settings['currency'] ?? 'LKR'));
    $merchantIdVal = old('merchant_id', $settings['merchant_id'] ?? '');
    $appIdVal = old('app_id', $settings['app_id'] ?? '');
    $hasMerchantSecret = !empty($masked['merchant_secret']);
    $hasAppSecret = !empty($masked['app_secret']);

    if ($status === 'verified') {
        $chipClass = 'intg-chip--verified';
        $chipLabel = 'Verified';
    } elseif ($status === 'configured') {
        $chipClass = 'intg-chip--configured';
        $chipLabel = 'Configured';
    } elseif ($status === 'failing') {
        $chipClass = 'intg-chip--failing';
        $chipLabel = 'Failing';
    } elseif ($usingPlatformFallback) {
        $chipClass = 'intg-chip--fallback';
        $chipLabel = 'Using Platform Default';
    } else {
        $chipClass = '';
        $chipLabel = 'Unconfigured';
    }
@endphp

<div class="row page-titles mx-0 mb-4">
    <div class="col-sm-7 p-md-0">
        <div class="welcome-text">
            <h4 style="color: var(--adm-text);">Online Payments (PayHere)</h4>
            <p class="mb-0" style="color: var(--adm-text-2);">Connect your institute's own PayHere merchant account so student fees settle directly to your bank account.</p>
        </div>
    </div>
    <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-none d-sm-flex align-items-center">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.settings.admission-fees') }}">Payment Settings</a></li>
            <li class="breadcrumb-item active"><span>Online Payments</span></li>
        </ol>
    </div>
</div>

@if($status === 'failing' && !empty($integration?->last_error))
<div class="intg-failing-banner" role="alert" data-testid="payhere-failing-banner">
    <div class="intg-failing-title">PayHere attention needed</div>
    <div style="font-size: 0.875rem; color: var(--adm-text-2);">{{ $integration->last_error }}</div>
</div>
@endif

@if($errors->any())
<div class="intg-failing-banner" role="alert">
    <div class="intg-failing-title">Please review the highlighted fields</div>
    <ul class="mb-0 pl-3" style="font-size: 0.875rem; color: var(--adm-text);">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row">
    <div class="col-xl-7 col-lg-12">
        <div class="card" id="payhere-settings-card" data-testid="payhere-form-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                <div>
                    <h4 class="card-title mb-1" style="color: var(--adm-text);">PayHere Merchant Credentials</h4>
                    <small style="color: var(--adm-text-2);">Secrets are encrypted at rest and never shown in full after saving.</small>
                </div>
                <span class="intg-chip {{ $chipClass }}" data-testid="payhere-status-chip">
                    <span class="intg-chip-dot" aria-hidden="true"></span>
                    <span>{{ $chipLabel }}</span>
                </span>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.settings.online-payments.update') }}" id="payhereMainForm" autocomplete="off">
                    @csrf

                    <div class="form-group">
                        <label for="payhere_merchant_id" style="color: var(--adm-text); font-weight: 600;">Merchant ID <span style="color: var(--adm-danger-light);">*</span></label>
                        <input
                            type="text"
                            id="payhere_merchant_id"
                            name="merchant_id"
                            class="form-control intg-control"
                            value="{{ $merchantIdVal }}"
                            placeholder="e.g. 1224567"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="payhere_merchant_secret" style="color: var(--adm-text); font-weight: 600;">Merchant Secret <span style="color: var(--adm-danger-light);">*</span></label>

                        @if($hasMerchantSecret)
                            <div class="intg-secret-saved" id="merchantSecretSavedWrap" data-testid="payhere-merchant-secret-masked">
                                <div>
                                    <span class="intg-secret-mask">{{ $masked['merchant_secret'] }}</span>
                                    <small class="d-block" style="color: var(--adm-text-2);">Leave unchanged to keep your saved secret.</small>
                                </div>
                                <button
                                    type="button"
                                    class="intg-btn-secondary"
                                    data-replace-target="merchantSecretInputWrap"
                                    data-saved-target="merchantSecretSavedWrap"
                                    data-input-id="payhere_merchant_secret"
                                    data-testid="replace-merchant-secret-btn"
                                >Replace</button>
                            </div>
                        @endif

                        <div id="merchantSecretInputWrap" style="{{ $hasMerchantSecret ? 'display: none;' : '' }}">
                            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                <input
                                    type="password"
                                    id="payhere_merchant_secret"
                                    name="merchant_secret"
                                    class="form-control intg-control"
                                    style="flex: 1 1 220px;"
                                    value=""
                                    autocomplete="new-password"
                                    placeholder="{{ $hasMerchantSecret ? 'Enter new Merchant Secret' : 'Paste your PayHere domain Merchant Secret' }}"
                                    {{ $hasMerchantSecret ? '' : 'required' }}
                                >
                                @if($hasMerchantSecret)
                                    <button
                                        type="button"
                                        class="intg-btn-secondary"
                                        data-cancel-replace="merchantSecretInputWrap"
                                        data-saved-target="merchantSecretSavedWrap"
                                        data-input-id="payhere_merchant_secret"
                                    >Keep Existing</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="payhere_app_id" style="color: var(--adm-text); font-weight: 600;">App ID <small style="color: var(--adm-text-2);">(Optional)</small></label>
                            <input
                                type="text"
                                id="payhere_app_id"
                                name="app_id"
                                class="form-control intg-control"
                                value="{{ $appIdVal }}"
                                placeholder="Optional PayHere App ID"
                            >
                        </div>

                        <div class="form-group col-md-6">
                            <label for="payhere_currency" style="color: var(--adm-text); font-weight: 600;">Settlement Currency</label>
                            <select id="payhere_currency" name="currency" class="form-control intg-control">
                                <option value="LKR" {{ $currency === 'LKR' ? 'selected' : '' }}>LKR – Sri Lankan Rupee</option>
                                <option value="USD" {{ $currency === 'USD' ? 'selected' : '' }}>USD – US Dollar</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="payhere_app_secret" style="color: var(--adm-text); font-weight: 600;">App Secret <small style="color: var(--adm-text-2);">(Optional)</small></label>

                        @if($hasAppSecret)
                            <div class="intg-secret-saved" id="appSecretSavedWrap" data-testid="payhere-app-secret-masked">
                                <span class="intg-secret-mask">{{ $masked['app_secret'] }}</span>
                                <button
                                    type="button"
                                    class="intg-btn-secondary"
                                    data-replace-target="appSecretInputWrap"
                                    data-saved-target="appSecretSavedWrap"
                                    data-input-id="payhere_app_secret"
                                >Replace</button>
                            </div>
                        @endif

                        <div id="appSecretInputWrap" style="{{ $hasAppSecret ? 'display: none;' : '' }}">
                            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                <input
                                    type="password"
                                    id="payhere_app_secret"
                                    name="app_secret"
                                    class="form-control intg-control"
                                    style="flex: 1 1 220px;"
                                    value=""
                                    autocomplete="new-password"
                                    placeholder="Optional PayHere App Secret"
                                >
                                @if($hasAppSecret)
                                    <button
                                        type="button"
                                        class="intg-btn-secondary"
                                        data-cancel-replace="appSecretInputWrap"
                                        data-saved-target="appSecretSavedWrap"
                                        data-input-id="payhere_app_secret"
                                    >Keep Existing</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label style="color: var(--adm-text); font-weight: 600; display: block;">Gateway Environment</label>
                        <input type="hidden" name="is_sandbox" id="payhereIsSandboxInput" value="{{ $isSandbox ? '1' : '0' }}">
                        <div class="intg-mode-group" role="group" aria-label="PayHere environment mode">
                            <button
                                type="button"
                                class="intg-mode-btn {{ $isSandbox ? 'is-active' : '' }}"
                                id="btnModeSandbox"
                                aria-pressed="{{ $isSandbox ? 'true' : 'false' }}"
                                data-testid="payhere-mode-sandbox"
                            >Sandbox (Test Payments)</button>
                            <button
                                type="button"
                                class="intg-mode-btn {{ !$isSandbox ? 'is-active' : '' }}"
                                id="btnModeLive"
                                aria-pressed="{{ !$isSandbox ? 'true' : 'false' }}"
                                data-testid="payhere-mode-live"
                            >Live (Real Payments)</button>
                        </div>
                        <small class="d-block mt-2" style="color: var(--adm-text-2);">
                            Status advances to <strong>Verified</strong> automatically after PayHere delivers your first signed payment notification.
                        </small>
                    </div>

                    <div class="intg-actions-bar">
                        <div class="intg-actions-left">
                            <button type="submit" class="btn btn-primary intg-btn-primary" data-testid="payhere-save-btn">
                                Save PayHere Settings
                            </button>
                            @if($isConfiguredRow)
                                <button type="button" class="intg-btn-secondary" id="btnCheckPayHereConfig" data-testid="payhere-check-btn">
                                    Check Configuration
                                </button>
                            @endif
                        </div>

                        @if($isConfiguredRow)
                            <button type="button" class="intg-btn-danger" id="btnClearPayHere" data-testid="payhere-clear-btn">
                                Clear / Disable
                            </button>
                        @endif
                    </div>
                </form>

                @if($isConfiguredRow)
                    <form method="POST" action="{{ route('admin.settings.online-payments.check') }}" id="payhereCheckForm" class="d-none">
                        @csrf
                    </form>
                    <form method="POST" action="{{ route('admin.settings.online-payments.clear') }}" id="payhereClearForm" class="d-none">
                        @csrf
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-xl-5 col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title mb-0" style="color: var(--adm-text);">PayHere Dashboard Values</h4>
            </div>
            <div class="card-body">
                <p style="color: var(--adm-text-2); font-size: 0.875rem;">
                    Copy these two values into your PayHere Merchant Portal under <strong>Settings &rarr; Domains &amp; Credentials</strong> before accepting online payments.
                </p>

                <div class="form-group">
                    <label style="color: var(--adm-text); font-weight: 600;">Domain to approve in PayHere</label>
                    <div class="intg-readonly-box">
                        <span class="intg-readonly-val" id="payhereApproveDomainVal" data-testid="payhere-approve-domain">{{ $primaryDomain }}</span>
                        <button
                            type="button"
                            class="intg-btn-secondary"
                            data-copy-target="payhereApproveDomainVal"
                            data-copy-label="Domain"
                            data-testid="copy-approve-domain-btn"
                        >Copy</button>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label style="color: var(--adm-text); font-weight: 600;">Notify URL for your PayHere dashboard</label>
                    <div class="intg-readonly-box">
                        <span class="intg-readonly-val" id="payhereNotifyUrlVal" data-testid="payhere-notify-url">{{ $notifyUrl }}</span>
                        <button
                            type="button"
                            class="intg-btn-secondary"
                            data-copy-target="payhereNotifyUrlVal"
                            data-copy-label="Notify URL"
                            data-testid="copy-notify-url-btn"
                        >Copy</button>
                    </div>
                </div>

                @if($integration?->last_verified_at)
                    <div class="mt-3 pt-3" style="border-top: 1px solid var(--adm-border); font-size: 0.85rem; color: var(--adm-text-2);">
                        Last verified notification: <strong style="color: var(--adm-text);">{{ $integration->last_verified_at->format('Y-m-d H:i') }}</strong>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    @if($status === 'failing' && !empty($integration?->last_error))
    document.addEventListener('DOMContentLoaded', function () {
        if (window.TitNotify) {
            window.TitNotify.warning(@json('PayHere issue: ' . $integration->last_error));
        }
    });
    @endif

    // Write-only secret Replace / Keep Existing toggles
    document.querySelectorAll('[data-replace-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var inputWrap = document.getElementById(btn.getAttribute('data-replace-target'));
            var savedWrap = document.getElementById(btn.getAttribute('data-saved-target'));
            var inputEl = document.getElementById(btn.getAttribute('data-input-id'));
            if (savedWrap) savedWrap.style.display = 'none';
            if (inputWrap) inputWrap.style.display = 'block';
            if (inputEl) {
                inputEl.value = '';
                inputEl.focus();
            }
        });
    });

    document.querySelectorAll('[data-cancel-replace]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var inputWrap = document.getElementById(btn.getAttribute('data-cancel-replace'));
            var savedWrap = document.getElementById(btn.getAttribute('data-saved-target'));
            var inputEl = document.getElementById(btn.getAttribute('data-input-id'));
            if (inputEl) inputEl.value = '';
            if (inputWrap) inputWrap.style.display = 'none';
            if (savedWrap) savedWrap.style.display = 'flex';
        });
    });

    // Copy buttons
    document.querySelectorAll('[data-copy-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var target = document.getElementById(btn.getAttribute('data-copy-target'));
            var label = btn.getAttribute('data-copy-label') || 'Value';
            if (!target) return;
            var text = (target.textContent || '').trim();
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    if (window.TitNotify) window.TitNotify.success(label + ' copied to clipboard.');
                }).catch(function () {
                    if (window.TitNotify) window.TitNotify.info(text);
                });
            } else if (window.TitNotify) {
                window.TitNotify.info(text);
            }
        });
    });

    // Sandbox / Live switch with themed confirmation when switching to Live
    var sandboxInput = document.getElementById('payhereIsSandboxInput');
    var btnSandbox = document.getElementById('btnModeSandbox');
    var btnLive = document.getElementById('btnModeLive');

    function setSandboxState(useSandbox) {
        if (!sandboxInput || !btnSandbox || !btnLive) return;
        sandboxInput.value = useSandbox ? '1' : '0';
        btnSandbox.classList.toggle('is-active', useSandbox);
        btnSandbox.setAttribute('aria-pressed', useSandbox ? 'true' : 'false');
        btnLive.classList.toggle('is-active', !useSandbox);
        btnLive.setAttribute('aria-pressed', !useSandbox ? 'true' : 'false');
    }

    if (btnSandbox) {
        btnSandbox.addEventListener('click', function () {
            setSandboxState(true);
        });
    }

    if (btnLive) {
        btnLive.addEventListener('click', function () {
            if (sandboxInput && sandboxInput.value === '0') {
                return;
            }
            if (window.TitNotify && typeof window.TitNotify.confirm === 'function') {
                window.TitNotify.confirm({
                    title: 'Switch PayHere to Live Mode?',
                    text: 'Students will be routed to the live PayHere checkout and real payments will be charged to your merchant account.',
                    confirmText: 'Enable Live Mode',
                    cancelText: 'Stay in Sandbox',
                    danger: false
                }).then(function (ok) {
                    if (ok) {
                        setSandboxState(false);
                    }
                });
            } else {
                setSandboxState(false);
            }
        });
    }

    // Local configuration check button
    var btnCheck = document.getElementById('btnCheckPayHereConfig');
    var checkForm = document.getElementById('payhereCheckForm');
    if (btnCheck && checkForm) {
        btnCheck.addEventListener('click', function () {
            checkForm.submit();
        });
    }

    // Clear / Disable button with themed danger confirmation
    var btnClear = document.getElementById('btnClearPayHere');
    var clearForm = document.getElementById('payhereClearForm');
    if (btnClear && clearForm) {
        btnClear.addEventListener('click', function () {
            if (window.TitNotify && typeof window.TitNotify.confirm === 'function') {
                window.TitNotify.confirm({
                    title: 'Clear PayHere Credentials?',
                    text: 'Online card payments will be disabled for your students until you configure PayHere again. Bank transfer and cash options will remain available.',
                    confirmText: 'Clear Credentials',
                    cancelText: 'Cancel',
                    danger: true
                }).then(function (ok) {
                    if (ok) clearForm.submit();
                });
            }
        });
    }
})();
</script>
@endpush

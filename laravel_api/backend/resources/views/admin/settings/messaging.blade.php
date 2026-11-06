@extends('layouts.admin')

@section('title', 'Messaging & E-mail Settings')

@push('styles')
<style>
    .msg-chip {
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
    .msg-chip-dot {
        width: 0.55rem;
        height: 0.55rem;
        border-radius: 50%;
        flex-shrink: 0;
        background: var(--adm-text-2);
    }
    .msg-chip--verified { border-color: var(--adm-success); }
    .msg-chip--verified .msg-chip-dot { background: var(--adm-success); }
    .msg-chip--configured { border-color: var(--adm-accent); }
    .msg-chip--configured .msg-chip-dot { background: var(--adm-accent); }
    .msg-chip--failing { border-color: var(--adm-danger); }
    .msg-chip--failing .msg-chip-dot { background: var(--adm-danger); }
    .msg-chip--fallback { border-color: var(--adm-warning); }
    .msg-chip--fallback .msg-chip-dot { background: var(--adm-warning); }

    .msg-failing-banner {
        background: var(--adm-input-bg);
        border: 1px solid var(--adm-danger);
        border-left: 4px solid var(--adm-danger);
        border-radius: 0.75rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1.25rem;
        color: var(--adm-text);
    }
    .msg-failing-title {
        font-weight: 700;
        color: var(--adm-danger-light);
        margin-bottom: 0.25rem;
        font-size: 0.95rem;
    }

    .msg-control {
        min-height: 44px;
        background: var(--adm-input-bg) !important;
        color: var(--adm-text) !important;
        border: 1px solid var(--adm-border) !important;
        border-radius: 0.625rem;
    }

    .msg-secret-saved {
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
    .msg-secret-mask {
        font-family: monospace;
        font-size: 0.95rem;
        letter-spacing: 0.06em;
        color: var(--adm-text);
    }

    .msg-btn-secondary {
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
    .msg-btn-secondary:hover,
    .msg-btn-secondary:focus {
        border-color: var(--adm-accent);
        color: var(--adm-text);
    }

    .msg-btn-danger {
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

    .msg-btn-primary {
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

    .msg-actions-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1.25rem;
        padding-top: 1.25rem;
        border-top: 1px solid var(--adm-border);
    }
    .msg-actions-left {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .msg-test-panel {
        margin-top: 1.25rem;
        padding: 1rem;
        border-radius: 0.75rem;
        background: var(--adm-surface-2);
        border: 1px solid var(--adm-border);
    }

    .msg-templates-box {
        border: 1px solid var(--adm-border);
        border-radius: 0.75rem;
        background: var(--adm-surface-2);
        padding: 0.875rem 1rem;
        margin-top: 0.5rem;
    }

    .msg-test-inline input {
        flex: 1 1 200px;
    }

    @media (max-width: 575.98px) {
        .msg-actions-bar,
        .msg-actions-left,
        .msg-test-inline {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
        }
        .msg-test-inline input {
            flex: 0 0 auto;
            width: 100%;
        }
        .msg-actions-bar .btn,
        .msg-actions-bar button,
        .msg-test-inline button {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
@php
    $mailStatus = $mailIntegration?->status ?? 'unconfigured';
    $mailSettings = is_array($mailIntegration?->settings) ? $mailIntegration->settings : [];
    $mailIsConfiguredRow = $mailIntegration !== null && $mailStatus !== 'unconfigured';
    $hasMailPassword = !empty($mailMasked['password']);

    if ($mailStatus === 'verified') {
        $mailChipClass = 'msg-chip--verified';
        $mailChipLabel = 'Verified';
    } elseif ($mailStatus === 'configured') {
        $mailChipClass = 'msg-chip--configured';
        $mailChipLabel = 'Configured';
    } elseif ($mailStatus === 'failing') {
        $mailChipClass = 'msg-chip--failing';
        $mailChipLabel = 'Failing';
    } elseif ($mailUsingPlatformFallback) {
        $mailChipClass = 'msg-chip--fallback';
        $mailChipLabel = 'Using Platform Mailer';
    } else {
        $mailChipClass = '';
        $mailChipLabel = 'Unconfigured';
    }

    $waStatus = $whatsappIntegration?->status ?? 'unconfigured';
    $waSettings = is_array($whatsappIntegration?->settings) ? $whatsappIntegration->settings : [];
    $waTemplates = is_array($waSettings['templates'] ?? null) ? $waSettings['templates'] : [];
    $waIsConfiguredRow = $whatsappIntegration !== null && $waStatus !== 'unconfigured';
    $hasWaToken = !empty($whatsappMasked['access_token']);

    if ($waStatus === 'verified') {
        $waChipClass = 'msg-chip--verified';
        $waChipLabel = 'Verified';
    } elseif ($waStatus === 'configured') {
        $waChipClass = 'msg-chip--configured';
        $waChipLabel = 'Configured';
    } elseif ($waStatus === 'failing') {
        $waChipClass = 'msg-chip--failing';
        $waChipLabel = 'Failing';
    } elseif ($whatsappUsingPlatformFallback) {
        $waChipClass = 'msg-chip--fallback';
        $waChipLabel = 'Using Platform Default';
    } else {
        $waChipClass = '';
        $waChipLabel = 'Unconfigured';
    }

    $templateLabels = [
        'welcome' => 'Student Welcome',
        'payment_reminder' => 'Monthly Fee Reminder',
        'payment_request_received' => 'Offline Payment Received',
        'payment_success' => 'Payment Confirmation',
        'payment_receipt' => 'Payment Receipt Link',
        'admin_approved' => 'Registration Approved',
        'general_update' => 'General Notice / Test',
        'otp_verification' => 'Verification Code (OTP)',
    ];
@endphp

<div class="row page-titles mx-0 mb-4">
    <div class="col-sm-7 p-md-0">
        <div class="welcome-text">
            <h4 style="color: var(--adm-text);">Messaging &amp; E-mail</h4>
            <p class="mb-0" style="color: var(--adm-text-2);">Configure your institute's outgoing SMTP e-mail server and Meta WhatsApp Cloud API credentials.</p>
        </div>
    </div>
    <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-none d-sm-flex align-items-center">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
            <li class="breadcrumb-item active"><span>Messaging &amp; E-mail</span></li>
        </ol>
    </div>
</div>

@if($errors->any())
<div class="msg-failing-banner" role="alert">
    <div class="msg-failing-title">Please review the highlighted fields</div>
    <ul class="mb-0 pl-3" style="font-size: 0.875rem; color: var(--adm-text);">
        @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="row">
    {{-- Card 1: Outgoing E-mail (SMTP) --}}
    <div class="col-xl-6 col-lg-12">
        <div class="card" id="email-settings-card" data-testid="mail-form-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                <div>
                    <h4 class="card-title mb-1" style="color: var(--adm-text);">Outgoing E-mail (SMTP)</h4>
                    <small style="color: var(--adm-text-2);">Send receipts, verification codes, and alerts from your own domain.</small>
                </div>
                <span class="msg-chip {{ $mailChipClass }}" data-testid="mail-status-chip">
                    <span class="msg-chip-dot" aria-hidden="true"></span>
                    <span>{{ $mailChipLabel }}</span>
                </span>
            </div>
            <div class="card-body">
                @if($mailStatus === 'failing' && !empty($mailIntegration?->last_error))
                <div class="msg-failing-banner" role="alert" data-testid="mail-failing-banner">
                    <div class="msg-failing-title">SMTP delivery failure</div>
                    <div style="font-size: 0.875rem; color: var(--adm-text-2);">{{ $mailIntegration->last_error }}</div>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.messaging.mail.update') }}" autocomplete="off">
                    @csrf

                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label for="mail_host" style="color: var(--adm-text); font-weight: 600;">SMTP Host <span style="color: var(--adm-danger-light);">*</span></label>
                            <input
                                type="text"
                                id="mail_host"
                                name="host"
                                class="form-control msg-control"
                                value="{{ old('host', $mailSettings['host'] ?? '') }}"
                                placeholder="e.g. smtp.gmail.com"
                                required
                            >
                        </div>
                        <div class="form-group col-md-5">
                            <label for="mail_port" style="color: var(--adm-text); font-weight: 600;">Port <span style="color: var(--adm-danger-light);">*</span></label>
                            <input
                                type="number"
                                id="mail_port"
                                name="port"
                                class="form-control msg-control"
                                value="{{ old('port', $mailSettings['port'] ?? 587) }}"
                                min="1"
                                max="65535"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-5">
                            <label for="mail_encryption" style="color: var(--adm-text); font-weight: 600;">Encryption</label>
                            @php $enc = strtolower((string) old('encryption', $mailSettings['encryption'] ?? 'tls')); @endphp
                            <select id="mail_encryption" name="encryption" class="form-control msg-control">
                                <option value="tls" {{ $enc === 'tls' ? 'selected' : '' }}>TLS (Port 587)</option>
                                <option value="ssl" {{ $enc === 'ssl' ? 'selected' : '' }}>SSL (Port 465)</option>
                                <option value="none" {{ $enc === 'none' ? 'selected' : '' }}>None</option>
                            </select>
                        </div>
                        <div class="form-group col-md-7">
                            <label for="mail_username" style="color: var(--adm-text); font-weight: 600;">SMTP Username</label>
                            <input
                                type="text"
                                id="mail_username"
                                name="username"
                                class="form-control msg-control"
                                value="{{ old('username', $mailSettings['username'] ?? '') }}"
                                placeholder="e.g. noreply@yourinstitute.lk"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="mail_password" style="color: var(--adm-text); font-weight: 600;">SMTP Password / App Password</label>

                        @if($hasMailPassword)
                            <div class="msg-secret-saved" id="mailPasswordSavedWrap" data-testid="mail-password-masked">
                                <div>
                                    <span class="msg-secret-mask">{{ $mailMasked['password'] }}</span>
                                    <small class="d-block" style="color: var(--adm-text-2);">Leave unchanged to keep your saved password.</small>
                                </div>
                                <button
                                    type="button"
                                    class="msg-btn-secondary"
                                    data-replace-target="mailPasswordInputWrap"
                                    data-saved-target="mailPasswordSavedWrap"
                                    data-input-id="mail_password"
                                    data-testid="replace-mail-password-btn"
                                >Replace</button>
                            </div>
                        @endif

                        <div id="mailPasswordInputWrap" style="{{ $hasMailPassword ? 'display: none;' : '' }}">
                            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                <input
                                    type="password"
                                    id="mail_password"
                                    name="password"
                                    class="form-control msg-control"
                                    style="flex: 1 1 220px;"
                                    value=""
                                    autocomplete="new-password"
                                    placeholder="{{ $hasMailPassword ? 'Enter new SMTP password' : 'Enter SMTP or Gmail App Password' }}"
                                >
                                @if($hasMailPassword)
                                    <button
                                        type="button"
                                        class="msg-btn-secondary"
                                        data-cancel-replace="mailPasswordInputWrap"
                                        data-saved-target="mailPasswordSavedWrap"
                                        data-input-id="mail_password"
                                    >Keep Existing</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="mail_from_address" style="color: var(--adm-text); font-weight: 600;">From Address <span style="color: var(--adm-danger-light);">*</span></label>
                            <input
                                type="email"
                                id="mail_from_address"
                                name="from_address"
                                class="form-control msg-control"
                                value="{{ old('from_address', $mailSettings['from_address'] ?? '') }}"
                                placeholder="noreply@yourinstitute.lk"
                                required
                            >
                        </div>
                        <div class="form-group col-md-6">
                            <label for="mail_from_name" style="color: var(--adm-text); font-weight: 600;">From Name <span style="color: var(--adm-danger-light);">*</span></label>
                            <input
                                type="text"
                                id="mail_from_name"
                                name="from_name"
                                class="form-control msg-control"
                                value="{{ old('from_name', $mailSettings['from_name'] ?? $institute->name) }}"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label for="mail_reply_to" style="color: var(--adm-text); font-weight: 600;">Reply-To Address</label>
                        <input
                            type="email"
                            id="mail_reply_to"
                            name="reply_to"
                            class="form-control msg-control"
                            value="{{ old('reply_to', $mailSettings['reply_to'] ?? $contactReplyToDefault) }}"
                            placeholder="info@yourinstitute.lk"
                        >
                        <small style="color: var(--adm-text-2);">When using the platform mailer, e-mails automatically show your institute name and this Reply-To address.</small>
                    </div>

                    <div class="msg-actions-bar">
                        <div class="msg-actions-left">
                            <button type="submit" class="btn btn-primary msg-btn-primary" data-testid="mail-save-btn">
                                Save E-mail Settings
                            </button>
                            <button type="button" class="msg-btn-secondary" id="btnSendTestMail" data-testid="mail-test-btn">
                                Send Test E-mail
                            </button>
                        </div>

                        @if($mailIsConfiguredRow)
                            <button type="button" class="msg-btn-danger" id="btnClearMail" data-testid="mail-clear-btn">
                                Clear Custom SMTP
                            </button>
                        @endif
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.settings.messaging.mail.test') }}" id="mailTestForm" class="d-none">
                    @csrf
                </form>
                @if($mailIsConfiguredRow)
                    <form method="POST" action="{{ route('admin.settings.messaging.mail.clear') }}" id="mailClearForm" class="d-none">
                        @csrf
                    </form>
                @endif

                <div class="mt-3" style="font-size: 0.8125rem; color: var(--adm-text-2);">
                    Test e-mails are sent to your administrator address: <strong style="color: var(--adm-text);">{{ $adminEmail ?: 'Not set' }}</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Card 2: WhatsApp (Meta Cloud API) --}}
    <div class="col-xl-6 col-lg-12">
        <div class="card" id="whatsapp-settings-card" data-testid="whatsapp-form-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                <div>
                    <h4 class="card-title mb-1" style="color: var(--adm-text);">WhatsApp (Meta Cloud API)</h4>
                    <small style="color: var(--adm-text-2);">Send approved WhatsApp template alerts from your institute's own business number.</small>
                </div>
                <span class="msg-chip {{ $waChipClass }}" data-testid="whatsapp-status-chip">
                    <span class="msg-chip-dot" aria-hidden="true"></span>
                    <span>{{ $waChipLabel }}</span>
                </span>
            </div>
            <div class="card-body">
                @if($waStatus === 'failing' && !empty($whatsappIntegration?->last_error))
                <div class="msg-failing-banner" role="alert" data-testid="whatsapp-failing-banner">
                    <div class="msg-failing-title">WhatsApp delivery failure</div>
                    <div style="font-size: 0.875rem; color: var(--adm-text-2);">{{ $whatsappIntegration->last_error }}</div>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.settings.messaging.whatsapp.update') }}" autocomplete="off">
                    @csrf

                    <div class="form-group">
                        <label for="whatsapp_access_token" style="color: var(--adm-text); font-weight: 600;">Permanent Access Token <span style="color: var(--adm-danger-light);">*</span></label>

                        @if($hasWaToken)
                            <div class="msg-secret-saved" id="waTokenSavedWrap" data-testid="whatsapp-token-masked">
                                <div>
                                    <span class="msg-secret-mask">{{ $whatsappMasked['access_token'] }}</span>
                                    <small class="d-block" style="color: var(--adm-text-2);">Leave unchanged to keep your saved token.</small>
                                </div>
                                <button
                                    type="button"
                                    class="msg-btn-secondary"
                                    data-replace-target="waTokenInputWrap"
                                    data-saved-target="waTokenSavedWrap"
                                    data-input-id="whatsapp_access_token"
                                    data-testid="replace-whatsapp-token-btn"
                                >Replace</button>
                            </div>
                        @endif

                        <div id="waTokenInputWrap" style="{{ $hasWaToken ? 'display: none;' : '' }}">
                            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                <input
                                    type="password"
                                    id="whatsapp_access_token"
                                    name="access_token"
                                    class="form-control msg-control"
                                    style="flex: 1 1 220px;"
                                    value=""
                                    autocomplete="new-password"
                                    placeholder="{{ $hasWaToken ? 'Enter new Meta System User token' : 'Paste your Meta Cloud API System User token' }}"
                                    {{ $hasWaToken ? '' : 'required' }}
                                >
                                @if($hasWaToken)
                                    <button
                                        type="button"
                                        class="msg-btn-secondary"
                                        data-cancel-replace="waTokenInputWrap"
                                        data-saved-target="waTokenSavedWrap"
                                        data-input-id="whatsapp_access_token"
                                    >Keep Existing</button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group col-md-7">
                            <label for="whatsapp_phone_number_id" style="color: var(--adm-text); font-weight: 600;">Phone Number ID <span style="color: var(--adm-danger-light);">*</span></label>
                            <input
                                type="text"
                                id="whatsapp_phone_number_id"
                                name="phone_number_id"
                                class="form-control msg-control"
                                value="{{ old('phone_number_id', $waSettings['phone_number_id'] ?? '') }}"
                                placeholder="e.g. 109876543210987"
                                required
                            >
                        </div>
                        <div class="form-group col-md-5">
                            <label for="whatsapp_api_version" style="color: var(--adm-text); font-weight: 600;">Graph API Version <span style="color: var(--adm-danger-light);">*</span></label>
                            <input
                                type="text"
                                id="whatsapp_api_version"
                                name="api_version"
                                class="form-control msg-control"
                                value="{{ old('api_version', $waSettings['api_version'] ?? 'v21.0') }}"
                                placeholder="v21.0"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label style="color: var(--adm-text); font-weight: 600; display: block;">Template Name Overrides <small style="color: var(--adm-text-2);">(Optional)</small></label>
                        <small class="d-block mb-2" style="color: var(--adm-text-2);">Leave blank to use standard template names, or enter the exact approved template names from your Meta Business Manager.</small>

                        <div class="msg-templates-box">
                            <div class="form-row">
                                @foreach($defaultTemplates as $eventKey => $defaultTplName)
                                    <div class="form-group col-md-6 mb-2">
                                        <label for="wa_tpl_{{ $eventKey }}" style="color: var(--adm-text); font-size: 0.8125rem; font-weight: 600;">
                                            {{ $templateLabels[$eventKey] ?? ucfirst(str_replace('_', ' ', $eventKey)) }}
                                        </label>
                                        <input
                                            type="text"
                                            id="wa_tpl_{{ $eventKey }}"
                                            name="templates[{{ $eventKey }}]"
                                            class="form-control msg-control"
                                            value="{{ old('templates.' . $eventKey, $waTemplates[$eventKey] ?? '') }}"
                                            placeholder="{{ $defaultTplName }}"
                                        >
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="msg-actions-bar">
                        <div class="msg-actions-left">
                            <button type="submit" class="btn btn-primary msg-btn-primary" data-testid="whatsapp-save-btn">
                                Save WhatsApp Settings
                            </button>
                        </div>

                        @if($waIsConfiguredRow)
                            <button type="button" class="msg-btn-danger" id="btnClearWhatsApp" data-testid="whatsapp-clear-btn">
                                Clear WhatsApp
                            </button>
                        @endif
                    </div>
                </form>

                @if($waIsConfiguredRow)
                    <form method="POST" action="{{ route('admin.settings.messaging.whatsapp.clear') }}" id="whatsappClearForm" class="d-none">
                        @csrf
                    </form>
                @endif

                <div class="msg-test-panel">
                    <form method="POST" action="{{ route('admin.settings.messaging.whatsapp.test') }}" autocomplete="off">
                        @csrf
                        <label for="whatsapp_test_to" style="color: var(--adm-text); font-weight: 600;">Send Test WhatsApp Message</label>
                        <small class="d-block mb-2" style="color: var(--adm-text-2);">Enter a recipient number in international E.164 format (limited to 3 test sends per hour).</small>
                        <div class="d-flex flex-wrap msg-test-inline" style="gap: 0.625rem;">
                            <input
                                type="tel"
                                id="whatsapp_test_to"
                                name="test_to"
                                class="form-control msg-control"
                                value="{{ old('test_to', '') }}"
                                placeholder="+94771234567"
                                required
                            >
                            <button type="submit" class="msg-btn-secondary" data-testid="whatsapp-test-btn">
                                Send Test Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    @if(($mailStatus === 'failing' && !empty($mailIntegration?->last_error)) || ($waStatus === 'failing' && !empty($whatsappIntegration?->last_error)))
    document.addEventListener('DOMContentLoaded', function () {
        if (window.TitNotify) {
            @if($mailStatus === 'failing' && !empty($mailIntegration?->last_error))
            window.TitNotify.warning(@json('SMTP issue: ' . $mailIntegration->last_error));
            @endif
            @if($waStatus === 'failing' && !empty($whatsappIntegration?->last_error))
            window.TitNotify.warning(@json('WhatsApp issue: ' . $whatsappIntegration->last_error));
            @endif
        }
    });
    @endif

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

    var btnTestMail = document.getElementById('btnSendTestMail');
    var mailTestForm = document.getElementById('mailTestForm');
    if (btnTestMail && mailTestForm) {
        btnTestMail.addEventListener('click', function () {
            mailTestForm.submit();
        });
    }

    var btnClearMail = document.getElementById('btnClearMail');
    var mailClearForm = document.getElementById('mailClearForm');
    if (btnClearMail && mailClearForm) {
        btnClearMail.addEventListener('click', function () {
            if (window.TitNotify && typeof window.TitNotify.confirm === 'function') {
                window.TitNotify.confirm({
                    title: 'Clear Custom SMTP Settings?',
                    text: 'Your custom SMTP credentials will be removed and outgoing e-mails will revert to the platform mailer with your institute name.',
                    confirmText: 'Clear SMTP Settings',
                    cancelText: 'Cancel',
                    danger: true
                }).then(function (ok) {
                    if (ok) mailClearForm.submit();
                });
            }
        });
    }

    var btnClearWa = document.getElementById('btnClearWhatsApp');
    var waClearForm = document.getElementById('whatsappClearForm');
    if (btnClearWa && waClearForm) {
        btnClearWa.addEventListener('click', function () {
            if (window.TitNotify && typeof window.TitNotify.confirm === 'function') {
                window.TitNotify.confirm({
                    title: 'Clear WhatsApp Credentials?',
                    text: 'WhatsApp notifications will be skipped for your institute until new Meta Cloud API credentials are saved. E-mail notifications will continue.',
                    confirmText: 'Clear WhatsApp',
                    cancelText: 'Cancel',
                    danger: true
                }).then(function (ok) {
                    if (ok) waClearForm.submit();
                });
            }
        });
    }
})();
</script>
@endpush

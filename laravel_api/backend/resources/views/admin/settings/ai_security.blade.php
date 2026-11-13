@extends('layouts.admin')

@section('title', 'AI & Security Settings')

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
    .msg-btn-danger:hover,
    .msg-btn-danger:focus {
        background: var(--adm-danger);
        color: #ffffff;
    }

    .ai-stat-box {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.875rem 1.125rem;
        border-radius: 0.625rem;
        background: var(--adm-input-bg);
        border: 1px solid var(--adm-border);
        margin-bottom: 1.25rem;
    }
    .ai-stat-label {
        font-size: 0.875rem;
        color: var(--adm-text-2);
    }
    .ai-stat-value {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--adm-text);
    }
</style>
@endpush

@section('content')
@php
    // Gemini status chip
    $geminiStatus = $geminiIntegration?->status ?? 'unconfigured';
    $geminiIsConfiguredRow = $geminiIntegration !== null && $geminiStatus !== 'unconfigured';
    $hasGeminiKey = !empty($geminiMasked['api_key']);

    if ($geminiStatus === 'verified') {
        $geminiChipClass = 'msg-chip--verified';
        $geminiChipLabel = 'Verified';
    } elseif ($geminiStatus === 'configured') {
        $geminiChipClass = 'msg-chip--configured';
        $geminiChipLabel = 'Configured';
    } elseif ($geminiStatus === 'failing') {
        $geminiChipClass = 'msg-chip--failing';
        $geminiChipLabel = 'Failing';
    } elseif ($geminiUsingPlatformFallback) {
        $geminiChipClass = 'msg-chip--fallback';
        $geminiChipLabel = 'Using Platform Default';
    } else {
        $geminiChipClass = '';
        $geminiChipLabel = 'Unconfigured';
    }

    // reCAPTCHA status chip
    $recaptchaStatus = $recaptchaIntegration?->status ?? 'unconfigured';
    $recaptchaIsConfiguredRow = $recaptchaIntegration !== null && $recaptchaStatus !== 'unconfigured';
    $hasRecaptchaSecret = !empty($recaptchaMasked['secret_key']);

    if ($recaptchaStatus === 'verified') {
        $recaptchaChipClass = 'msg-chip--verified';
        $recaptchaChipLabel = 'Verified';
    } elseif ($recaptchaStatus === 'configured') {
        $recaptchaChipClass = 'msg-chip--configured';
        $recaptchaChipLabel = 'Configured';
    } elseif ($recaptchaStatus === 'failing') {
        $recaptchaChipClass = 'msg-chip--failing';
        $recaptchaChipLabel = 'Failing';
    } elseif ($recaptchaUsingPlatformFallback) {
        $recaptchaChipClass = 'msg-chip--fallback';
        $recaptchaChipLabel = 'Using Platform Default';
    } else {
        $recaptchaChipClass = '';
        $recaptchaChipLabel = 'Unconfigured';
    }

    // Google status chip
    $googleStatus = $googleIntegration?->status ?? 'unconfigured';
    $googleIsConfiguredRow = $googleIntegration !== null && $googleStatus !== 'unconfigured';
    $hasGoogleSecret = !empty($googleMasked['client_secret']);

    if ($googleStatus === 'verified') {
        $googleChipClass = 'msg-chip--verified';
        $googleChipLabel = 'Verified';
    } elseif ($googleStatus === 'configured') {
        $googleChipClass = 'msg-chip--configured';
        $googleChipLabel = 'Configured';
    } elseif ($googleStatus === 'failing') {
        $googleChipClass = 'msg-chip--failing';
        $googleChipLabel = 'Failing';
    } elseif ($googleUsingPlatformFallback) {
        $googleChipClass = 'msg-chip--fallback';
        $googleChipLabel = 'Using Platform Default';
    } else {
        $googleChipClass = '';
        $googleChipLabel = 'Unconfigured';
    }
@endphp

<div class="row page-titles mx-0 mb-4">
    <div class="col-sm-7 p-md-0">
        <div class="welcome-text">
            <h4 style="color: var(--adm-text);">AI &amp; Security</h4>
            <p class="mb-0" style="color: var(--adm-text-2);">Configure your institute's Google Gemini API key, Google reCAPTCHA v3 protection, and Google Sign-in OAuth credentials.</p>
        </div>
    </div>
    <div class="col-sm-5 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-none d-sm-flex align-items-center">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
            <li class="breadcrumb-item active"><span>AI &amp; Security</span></li>
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
    {{-- Card 1: AI Assistant (Gemini) --}}
    <div class="col-xl-6 col-lg-12">
        <div class="card" id="gemini-settings-card" data-testid="gemini-form-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                <div>
                    <h4 class="card-title mb-1" style="color: var(--adm-text);">AI Assistant (Google Gemini)</h4>
                    <small style="color: var(--adm-text-2);">Powers student study assistant, public chatbot, OCR ingestion, and embeddings.</small>
                </div>
                <span class="msg-chip {{ $geminiChipClass }}" data-testid="gemini-status-chip">
                    <span class="msg-chip-dot" aria-hidden="true"></span>
                    <span>{{ $geminiChipLabel }}</span>
                </span>
            </div>
            <div class="card-body">
                @if($geminiStatus === 'failing' && !empty($geminiIntegration?->last_error))
                <div class="msg-failing-banner" role="alert" data-testid="gemini-failing-banner">
                    <div class="msg-failing-title">Gemini API error</div>
                    <div style="font-size: 0.875rem; color: var(--adm-text-2);">{{ $geminiIntegration->last_error }}</div>
                </div>
                @endif

                <div class="ai-stat-box" data-testid="gemini-usage-counter">
                    <div>
                        <div class="ai-stat-label">AI Questions Today</div>
                        <div style="font-size: 0.8125rem; color: var(--adm-text-2);">Resets automatically at midnight.</div>
                    </div>
                    <div class="ai-stat-value">
                        <span>{{ $questionsToday }}</span> / <span style="color: var(--adm-text-2);">{{ $dailyCap }}</span>
                    </div>
                </div>

                <form method="POST" action="{{ route('admin.settings.ai-security.gemini.update') }}" autocomplete="off">
                    @csrf

                    <div class="form-group">
                        <label for="gemini_api_key" style="color: var(--adm-text); font-weight: 600;">Gemini API Key <span style="color: var(--adm-danger-light);">*</span></label>

                        @if($hasGeminiKey)
                            <div class="msg-secret-saved" id="geminiKeySavedWrap" data-testid="gemini-key-masked">
                                <div>
                                    <span class="msg-secret-mask">{{ $geminiMasked['api_key'] }}</span>
                                    <small class="d-block" style="color: var(--adm-text-2);">Leave unchanged to keep your saved API key.</small>
                                </div>
                                <button
                                    type="button"
                                    class="msg-btn-secondary"
                                    data-replace-target="geminiKeyInputWrap"
                                    data-saved-target="geminiKeySavedWrap"
                                    data-input-id="gemini_api_key"
                                    data-testid="replace-gemini-key-btn"
                                >Replace</button>
                            </div>
                        @endif

                        <div id="geminiKeyInputWrap" style="{{ $hasGeminiKey ? 'display: none;' : '' }}">
                            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                <input
                                    type="password"
                                    id="gemini_api_key"
                                    name="api_key"
                                    class="form-control msg-control"
                                    style="flex: 1 1 220px;"
                                    value=""
                                    autocomplete="new-password"
                                    placeholder="{{ $hasGeminiKey ? 'Enter new Gemini API key' : 'AIzaSy...' }}"
                                >
                                @if($hasGeminiKey)
                                    <button
                                        type="button"
                                        class="msg-btn-secondary"
                                        data-cancel-target="geminiKeyInputWrap"
                                        data-saved-target="geminiKeySavedWrap"
                                        data-input-id="gemini_api_key"
                                    >Cancel</button>
                                @endif
                            </div>
                            <small class="form-text mt-1" style="color: var(--adm-text-2);">Obtain an API key from Google AI Studio (starts with <code>AIza</code>).</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="gemini_daily_cap" style="color: var(--adm-text); font-weight: 600;">Visitor Daily Question Cap <span style="color: var(--adm-danger-light);">*</span></label>
                        <input
                            type="number"
                            id="gemini_daily_cap"
                            name="daily_cap"
                            class="form-control msg-control"
                            value="{{ old('daily_cap', $dailyCap) }}"
                            min="50"
                            max="100000"
                            required
                        >
                        <small class="form-text" style="color: var(--adm-text-2);">Maximum public AI questions per day (minimum 50). Registered students are governed separately.</small>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between pt-2" style="gap: 0.75rem;">
                        <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="min-height: 44px; min-width: 140px; font-weight: 600;" data-testid="save-gemini-btn">
                                Save Gemini Key
                            </button>

                            @if($geminiIsConfiguredRow || $geminiUsingPlatformFallback)
                                <button type="button" class="msg-btn-secondary" id="btnTestGemini" data-testid="gemini-test-btn">
                                    <i class="flaticon-381-check" aria-hidden="true"></i>
                                    Test Key
                                </button>
                            @endif
                        </div>

                        @if($geminiIsConfiguredRow)
                            <button type="button" class="msg-btn-danger" id="btnClearGemini" data-testid="gemini-clear-btn">
                                Clear Key
                            </button>
                        @endif
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.settings.ai-security.gemini.test') }}" id="geminiTestForm" class="d-none">
                    @csrf
                </form>
                @if($geminiIsConfiguredRow)
                    <form method="POST" action="{{ route('admin.settings.ai-security.gemini.clear') }}" id="geminiClearForm" class="d-none">
                        @csrf
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- Card 2: Google reCAPTCHA v3 --}}
    <div class="col-xl-6 col-lg-12">
        <div class="card" id="recaptcha-settings-card" data-testid="recaptcha-form-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                <div>
                    <h4 class="card-title mb-1" style="color: var(--adm-text);">Google reCAPTCHA v3</h4>
                    <small style="color: var(--adm-text-2);">Protects public registration, login, and chatbot forms from abuse.</small>
                </div>
                <span class="msg-chip {{ $recaptchaChipClass }}" data-testid="recaptcha-status-chip">
                    <span class="msg-chip-dot" aria-hidden="true"></span>
                    <span>{{ $recaptchaChipLabel }}</span>
                </span>
            </div>
            <div class="card-body">
                @if($recaptchaStatus === 'failing' && !empty($recaptchaIntegration?->last_error))
                <div class="msg-failing-banner" role="alert" data-testid="recaptcha-failing-banner">
                    <div class="msg-failing-title">reCAPTCHA verification error</div>
                    <div style="font-size: 0.875rem; color: var(--adm-text-2);">{{ $recaptchaIntegration->last_error }}</div>
                </div>
                @endif

                <div class="mb-3" style="font-size: 0.8125rem; color: var(--adm-text-2); background: var(--adm-input-bg); padding: 0.75rem 1rem; border-radius: 0.625rem; border: 1px solid var(--adm-border);">
                    <i class="flaticon-381-info mr-1" aria-hidden="true"></i>
                    Without custom keys, public registration and login operate under strict fallback rate limits (5 submits / 10 min), and the public AI assistant is disabled.
                </div>

                <form method="POST" action="{{ route('admin.settings.ai-security.recaptcha.update') }}" autocomplete="off">
                    @csrf

                    <div class="form-group">
                        <label for="recaptcha_site_key" style="color: var(--adm-text); font-weight: 600;">Site Key <span style="color: var(--adm-danger-light);">*</span></label>
                        <input
                            type="text"
                            id="recaptcha_site_key"
                            name="site_key"
                            class="form-control msg-control"
                            value="{{ old('site_key', $siteKey) }}"
                            placeholder="6L..."
                            required
                        >
                        <small class="form-text" style="color: var(--adm-text-2);">Public key delivered to browsers via runtime settings.</small>
                    </div>

                    <div class="form-group">
                        <label for="recaptcha_secret_key" style="color: var(--adm-text); font-weight: 600;">Secret Key <span style="color: var(--adm-danger-light);">*</span></label>

                        @if($hasRecaptchaSecret)
                            <div class="msg-secret-saved" id="recaptchaSecretSavedWrap" data-testid="recaptcha-secret-masked">
                                <div>
                                    <span class="msg-secret-mask">{{ $recaptchaMasked['secret_key'] }}</span>
                                    <small class="d-block" style="color: var(--adm-text-2);">Leave unchanged to keep your saved secret key.</small>
                                </div>
                                <button
                                    type="button"
                                    class="msg-btn-secondary"
                                    data-replace-target="recaptchaSecretInputWrap"
                                    data-saved-target="recaptchaSecretSavedWrap"
                                    data-input-id="recaptcha_secret_key"
                                    data-testid="replace-recaptcha-secret-btn"
                                >Replace</button>
                            </div>
                        @endif

                        <div id="recaptchaSecretInputWrap" style="{{ $hasRecaptchaSecret ? 'display: none;' : '' }}">
                            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                <input
                                    type="password"
                                    id="recaptcha_secret_key"
                                    name="secret_key"
                                    class="form-control msg-control"
                                    style="flex: 1 1 220px;"
                                    value=""
                                    autocomplete="new-password"
                                    placeholder="{{ $hasRecaptchaSecret ? 'Enter new Secret Key' : '6L...' }}"
                                >
                                @if($hasRecaptchaSecret)
                                    <button
                                        type="button"
                                        class="msg-btn-secondary"
                                        data-cancel-target="recaptchaSecretInputWrap"
                                        data-saved-target="recaptchaSecretSavedWrap"
                                        data-input-id="recaptcha_secret_key"
                                    >Cancel</button>
                                @endif
                            </div>
                            <small class="form-text mt-1" style="color: var(--adm-text-2);">Server-side secret key (starts with <code>6L</code>). Never exposed to browsers.</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="recaptcha_score_threshold" style="color: var(--adm-text); font-weight: 600;">Score Threshold <span style="color: var(--adm-danger-light);">*</span></label>
                        <input
                            type="number"
                            id="recaptcha_score_threshold"
                            name="score_threshold"
                            class="form-control msg-control"
                            value="{{ old('score_threshold', $scoreThreshold) }}"
                            min="0.1"
                            max="1.0"
                            step="0.05"
                            required
                        >
                        <small class="form-text" style="color: var(--adm-text-2);">Scores range from 0.0 (bot) to 1.0 (human). Standard default is 0.5.</small>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between pt-2" style="gap: 0.75rem;">
                        <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                            <button type="submit" class="btn btn-primary" style="min-height: 44px; min-width: 140px; font-weight: 600;" data-testid="save-recaptcha-btn">
                                Save reCAPTCHA
                            </button>

                            @if($recaptchaIsConfiguredRow || $recaptchaUsingPlatformFallback)
                                <button type="button" class="msg-btn-secondary" id="btnTestRecaptcha" data-testid="recaptcha-test-btn">
                                    <i class="flaticon-381-check" aria-hidden="true"></i>
                                    Test Keys
                                </button>
                            @endif
                        </div>

                        @if($recaptchaIsConfiguredRow)
                            <button type="button" class="msg-btn-danger" id="btnClearRecaptcha" data-testid="recaptcha-clear-btn">
                                Clear Keys
                            </button>
                        @endif
                    </div>
                </form>

                <form method="POST" action="{{ route('admin.settings.ai-security.recaptcha.test') }}" id="recaptchaTestForm" class="d-none">
                    @csrf
                </form>
                @if($recaptchaIsConfiguredRow)
                    <form method="POST" action="{{ route('admin.settings.ai-security.recaptcha.clear') }}" id="recaptchaClearForm" class="d-none">
                        @csrf
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Card 3: Google Sign-in (OAuth 2.0) --}}
    <div class="col-xl-6 col-lg-12">
        <div class="card" id="google-settings-card" data-testid="google-form-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                <div>
                    <h4 class="card-title mb-1" style="color: var(--adm-text);">Google Sign-in (OAuth 2.0)</h4>
                    <small style="color: var(--adm-text-2);">Allow students and staff to sign in using their Google account.</small>
                </div>
                <span class="msg-chip {{ $googleChipClass }}" data-testid="google-status-chip">
                    <span class="msg-chip-dot" aria-hidden="true"></span>
                    <span>{{ $googleChipLabel }}</span>
                </span>
            </div>
            <div class="card-body">
                @if($googleStatus === 'failing' && !empty($googleIntegration?->last_error))
                <div class="msg-failing-banner" role="alert" data-testid="google-failing-banner">
                    <div class="msg-failing-title">Google OAuth error</div>
                    <div style="font-size: 0.875rem; color: var(--adm-text-2);">{{ $googleIntegration->last_error }}</div>
                </div>
                @endif

                <div class="form-group">
                    <label style="color: var(--adm-text); font-weight: 600;">Authorized Redirect URI (Copy to Google Console)</label>
                    <div class="d-flex" style="gap: 0.5rem;">
                        <input
                            type="text"
                            id="google_redirect_uri"
                            class="form-control msg-control"
                            value="{{ $googleRedirectUri }}"
                            readonly
                            style="font-family: monospace; font-size: 0.875rem;"
                            data-testid="google-redirect-uri"
                        >
                        <button
                            type="button"
                            class="msg-btn-secondary"
                            id="btnCopyRedirectUri"
                            data-testid="copy-google-redirect-uri-btn"
                            title="Copy to clipboard"
                        >
                            Copy
                        </button>
                    </div>
                    <small class="form-text" style="color: var(--adm-text-2);">Add this exact callback URL under "Authorized redirect URIs" in your Google Cloud OAuth Client credentials.</small>
                </div>

                <form method="POST" action="{{ route('admin.settings.ai-security.google.update') }}" autocomplete="off">
                    @csrf

                    <div class="form-group">
                        <label for="google_client_id" style="color: var(--adm-text); font-weight: 600;">Client ID <span style="color: var(--adm-danger-light);">*</span></label>
                        <input
                            type="text"
                            id="google_client_id"
                            name="client_id"
                            class="form-control msg-control"
                            value="{{ old('client_id', $googleClientId) }}"
                            placeholder="...apps.googleusercontent.com"
                            required
                        >
                        <small class="form-text" style="color: var(--adm-text-2);">Client ID ending with <code>.apps.googleusercontent.com</code>.</small>
                    </div>

                    <div class="form-group">
                        <label for="google_client_secret" style="color: var(--adm-text); font-weight: 600;">Client Secret <span style="color: var(--adm-danger-light);">*</span></label>

                        @if($hasGoogleSecret)
                            <div class="msg-secret-saved" id="googleSecretSavedWrap" data-testid="google-secret-masked">
                                <div>
                                    <span class="msg-secret-mask">{{ $googleMasked['client_secret'] }}</span>
                                    <small class="d-block" style="color: var(--adm-text-2);">Leave unchanged to keep your saved client secret.</small>
                                </div>
                                <button
                                    type="button"
                                    class="msg-btn-secondary"
                                    data-replace-target="googleSecretInputWrap"
                                    data-saved-target="googleSecretSavedWrap"
                                    data-input-id="google_client_secret"
                                    data-testid="replace-google-secret-btn"
                                >Replace</button>
                            </div>
                        @endif

                        <div id="googleSecretInputWrap" style="{{ $hasGoogleSecret ? 'display: none;' : '' }}">
                            <div class="d-flex flex-wrap" style="gap: 0.5rem;">
                                <input
                                    type="password"
                                    id="google_client_secret"
                                    name="client_secret"
                                    class="form-control msg-control"
                                    style="flex: 1 1 220px;"
                                    value=""
                                    autocomplete="new-password"
                                    placeholder="{{ $hasGoogleSecret ? 'Enter new Client Secret' : 'GOCSPX-...' }}"
                                >
                                @if($hasGoogleSecret)
                                    <button
                                        type="button"
                                        class="msg-btn-secondary"
                                        data-cancel-target="googleSecretInputWrap"
                                        data-saved-target="googleSecretSavedWrap"
                                        data-input-id="google_client_secret"
                                    >Cancel</button>
                                @endif
                            </div>
                            <small class="form-text mt-1" style="color: var(--adm-text-2);">Client Secret (starts with <code>GOCSPX-</code>). Never exposed to browsers.</small>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between pt-2" style="gap: 0.75rem;">
                        <button type="submit" class="btn btn-primary" style="min-height: 44px; min-width: 140px; font-weight: 600;" data-testid="save-google-btn">
                            Save Google Credentials
                        </button>

                        @if($googleIsConfiguredRow)
                            <button type="button" class="msg-btn-danger" id="btnClearGoogle" data-testid="google-clear-btn">
                                Clear Credentials
                            </button>
                        @endif
                    </div>
                </form>

                @if($googleIsConfiguredRow)
                    <form method="POST" action="{{ route('admin.settings.ai-security.google.clear') }}" id="googleClearForm" class="d-none">
                        @csrf
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    // Secret replace / cancel toggles
    document.querySelectorAll('[data-replace-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var inputWrap = document.getElementById(btn.getAttribute('data-replace-target'));
            var savedWrap = document.getElementById(btn.getAttribute('data-saved-target'));
            var inputId = btn.getAttribute('data-input-id');
            if (inputWrap) inputWrap.style.display = 'block';
            if (savedWrap) savedWrap.style.display = 'none';
            if (inputId) {
                var inp = document.getElementById(inputId);
                if (inp) {
                    inp.focus();
                }
            }
        });
    });

    document.querySelectorAll('[data-cancel-target]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var inputWrap = document.getElementById(btn.getAttribute('data-cancel-target'));
            var savedWrap = document.getElementById(btn.getAttribute('data-saved-target'));
            var inputId = btn.getAttribute('data-input-id');
            if (inputWrap) inputWrap.style.display = 'none';
            if (savedWrap) savedWrap.style.display = 'flex';
            if (inputId) {
                var inp = document.getElementById(inputId);
                if (inp) inp.value = '';
            }
        });
    });

    // Copy redirect URI
    var btnCopy = document.getElementById('btnCopyRedirectUri');
    var uriInput = document.getElementById('google_redirect_uri');
    if (btnCopy && uriInput) {
        btnCopy.addEventListener('click', function () {
            if (navigator.clipboard && uriInput.value) {
                navigator.clipboard.writeText(uriInput.value).then(function () {
                    btnCopy.textContent = 'Copied!';
                    setTimeout(function () { btnCopy.textContent = 'Copy'; }, 2000);
                });
            } else {
                uriInput.select();
                document.execCommand('copy');
                btnCopy.textContent = 'Copied!';
                setTimeout(function () { btnCopy.textContent = 'Copy'; }, 2000);
            }
        });
    }

    // Test Gemini
    var btnTestGemini = document.getElementById('btnTestGemini');
    var geminiTestForm = document.getElementById('geminiTestForm');
    if (btnTestGemini && geminiTestForm) {
        btnTestGemini.addEventListener('click', function () {
            geminiTestForm.submit();
        });
    }

    // Clear Gemini
    var btnClearGemini = document.getElementById('btnClearGemini');
    var geminiClearForm = document.getElementById('geminiClearForm');
    if (btnClearGemini && geminiClearForm) {
        btnClearGemini.addEventListener('click', function () {
            if (window.TitNotify && typeof window.TitNotify.confirm === 'function') {
                window.TitNotify.confirm({
                    title: 'Clear Gemini AI Key?',
                    text: 'AI Assistant features (chat, study questions, OCR) will be disabled for your institute until a new key is added.',
                    confirmText: 'Clear Key',
                    cancelText: 'Cancel',
                    danger: true
                }).then(function (ok) {
                    if (ok) geminiClearForm.submit();
                });
            } else if (confirm('Clear Gemini AI Key? AI Assistant features will be disabled.')) {
                geminiClearForm.submit();
            }
        });
    }

    // Test reCAPTCHA
    var btnTestRecaptcha = document.getElementById('btnTestRecaptcha');
    var recaptchaTestForm = document.getElementById('recaptchaTestForm');
    if (btnTestRecaptcha && recaptchaTestForm) {
        btnTestRecaptcha.addEventListener('click', function () {
            recaptchaTestForm.submit();
        });
    }

    // Clear reCAPTCHA
    var btnClearRecaptcha = document.getElementById('btnClearRecaptcha');
    var recaptchaClearForm = document.getElementById('recaptchaClearForm');
    if (btnClearRecaptcha && recaptchaClearForm) {
        btnClearRecaptcha.addEventListener('click', function () {
            if (window.TitNotify && typeof window.TitNotify.confirm === 'function') {
                window.TitNotify.confirm({
                    title: 'Clear reCAPTCHA Credentials?',
                    text: 'reCAPTCHA protection will be removed. Strict fallback rate limiting will apply to public forms, and public AI assistant will be disabled.',
                    confirmText: 'Clear reCAPTCHA',
                    cancelText: 'Cancel',
                    danger: true
                }).then(function (ok) {
                    if (ok) recaptchaClearForm.submit();
                });
            } else if (confirm('Clear reCAPTCHA credentials? Strict fallback throttling will apply.')) {
                recaptchaClearForm.submit();
            }
        });
    }

    // Clear Google
    var btnClearGoogle = document.getElementById('btnClearGoogle');
    var googleClearForm = document.getElementById('googleClearForm');
    if (btnClearGoogle && googleClearForm) {
        btnClearGoogle.addEventListener('click', function () {
            if (window.TitNotify && typeof window.TitNotify.confirm === 'function') {
                window.TitNotify.confirm({
                    title: 'Clear Google Sign-in Credentials?',
                    text: 'Google Sign-in will be disabled on your institute login and registration pages.',
                    confirmText: 'Clear Credentials',
                    cancelText: 'Cancel',
                    danger: true
                }).then(function (ok) {
                    if (ok) googleClearForm.submit();
                });
            } else if (confirm('Clear Google Sign-in credentials? Google sign-in will be disabled.')) {
                googleClearForm.submit();
            }
        });
    }
})();
</script>
@endpush


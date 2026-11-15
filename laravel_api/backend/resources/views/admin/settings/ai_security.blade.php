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

    .adm-readonly {
        background: var(--adm-input-bg, rgba(255, 255, 255, 0.05));
        color: var(--adm-text, #e2e8f0);
        border: 1px solid var(--adm-border, rgba(255, 255, 255, 0.12));
        border-radius: 0.625rem;
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
        line-height: 1.5;
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

                    <div class="form-group mb-3">
                        <label style="color: var(--adm-text); font-weight: 600;">Integration Mode <span style="color: var(--adm-danger-light);">*</span></label>
                        <div class="d-flex flex-column mt-1" style="gap: 0.5rem;">
                            <label class="d-flex align-items-center mb-0" style="gap: 0.5rem; cursor: pointer;">
                                <input
                                    type="radio"
                                    name="gemini_mode"
                                    value="platform"
                                    id="gemini_mode_platform"
                                    {{ $geminiMode === 'platform' ? 'checked' : '' }}
                                    data-testid="gemini-mode-platform"
                                >
                                <span style="color: var(--adm-text); font-size: 0.9rem;">
                                    <strong>Platform default</strong> (quota {{ $platformDailyQuota }} / day)
                                </span>
                            </label>
                            <label class="d-flex align-items-center mb-0" style="gap: 0.5rem; cursor: pointer;">
                                <input
                                    type="radio"
                                    name="gemini_mode"
                                    value="custom"
                                    id="gemini_mode_custom"
                                    {{ $geminiMode === 'custom' ? 'checked' : '' }}
                                    data-testid="gemini-mode-custom"
                                >
                                <span style="color: var(--adm-text); font-size: 0.9rem;">
                                    <strong>Your own key</strong>
                                </span>
                            </label>
                        </div>
                        <small class="form-text" style="color: var(--adm-text-2);">Institutes on plans allowing AI features can use the shared platform assistant or provide their own Gemini API key.</small>
                    </div>

                    <div class="form-group" id="geminiCustomKeySection" style="{{ $geminiMode === 'custom' ? '' : 'display: none;' }}">
                        <label for="gemini_api_key" style="color: var(--adm-text); font-weight: 600;">Gemini API Key</label>

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
                                Save Settings
                            </button>

                            @if($geminiIsConfiguredRow || $geminiUsingPlatformFallback)
                                <button type="button" class="msg-btn-secondary" id="btnTestGemini" data-testid="gemini-test-btn">
                                    <i class="flaticon-381-check" aria-hidden="true"></i>
                                    Test Connection
                                </button>
                            @endif
                        </div>

                        @if($geminiIsConfiguredRow)
                            <button type="button" class="msg-btn-danger" id="btnClearGemini" data-testid="gemini-clear-btn">
                                Clear Custom Key
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

    {{-- Card 2: Google reCAPTCHA v3 (Platform Managed) --}}
    <div class="col-xl-6 col-lg-12">
        <div class="card" id="recaptcha-settings-card" data-testid="recaptcha-form-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                <div>
                    <h4 class="card-title mb-1" style="color: var(--adm-text);">Google reCAPTCHA v3</h4>
                    <small style="color: var(--adm-text-2);">Platform-managed anti-abuse protection for student registration and login.</small>
                </div>
                <span class="msg-chip {{ $recaptchaActive ? 'msg-chip--verified' : 'msg-chip--fallback' }}" data-testid="recaptcha-status-chip">
                    <span class="msg-chip-dot" aria-hidden="true"></span>
                    <span>{{ $recaptchaActive ? 'Active' : 'Unconfigured' }}</span>
                </span>
            </div>
            <div class="card-body">
                <div class="adm-readonly mb-3" data-testid="recaptcha-status-line">
                    @if($recaptchaActive)
                        <span class="d-inline-flex align-items-center" style="gap: 0.5rem; color: var(--adm-text);">
                            <i class="flaticon-381-shield-2" style="color: var(--adm-success);" aria-hidden="true"></i>
                            Protection by the platform: <strong style="color: var(--adm-success);">Active</strong>
                        </span>
                    @else
                        <span class="d-inline-flex align-items-center" style="gap: 0.5rem; color: var(--adm-text);">
                            <i class="flaticon-381-warning" style="color: var(--adm-warning);" aria-hidden="true"></i>
                            Protection by the platform: <strong style="color: var(--adm-warning);">Not configured – ask the platform owner</strong>
                        </span>
                    @endif
                </div>

                <div class="p-3" style="background: var(--adm-input-bg); border-radius: 0.625rem; border: 1px solid var(--adm-border); color: var(--adm-text-2); font-size: 0.85rem; line-height: 1.6;">
                    <strong style="color: var(--adm-text);">Platform Managed Integration</strong><br>
                    Google reCAPTCHA v3 is managed centrally by the platform super-administrator to secure registration and login forms against automated bots. The domain-bound site key and server secret are provisioned by the platform owner.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    {{-- Card 3: Google Sign-in (OAuth 2.0) (Platform Managed) --}}
    <div class="col-xl-6 col-lg-12">
        <div class="card" id="google-settings-card" data-testid="google-form-card">
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                <div>
                    <h4 class="card-title mb-1" style="color: var(--adm-text);">Google Sign-in (OAuth 2.0)</h4>
                    <small style="color: var(--adm-text-2);">Allow students and staff to sign in with their Google account.</small>
                </div>
                <span class="msg-chip {{ $googleActive ? 'msg-chip--verified' : 'msg-chip--fallback' }}" data-testid="google-status-chip">
                    <span class="msg-chip-dot" aria-hidden="true"></span>
                    <span>{{ $googleActive ? 'Active' : 'Unconfigured' }}</span>
                </span>
            </div>
            <div class="card-body">
                <div class="adm-readonly mb-3" data-testid="google-status-line">
                    @if($googleActive)
                        <span class="d-inline-flex align-items-center" style="gap: 0.5rem; color: var(--adm-text);">
                            <i class="flaticon-381-user-9" style="color: var(--adm-success);" aria-hidden="true"></i>
                            Sign-in with Google: <strong style="color: var(--adm-success);">Active</strong>
                        </span>
                    @else
                        <span class="d-inline-flex align-items-center" style="gap: 0.5rem; color: var(--adm-text);">
                            <i class="flaticon-381-warning" style="color: var(--adm-warning);" aria-hidden="true"></i>
                            Protection by the platform: <strong style="color: var(--adm-warning);">Not configured – ask the platform owner</strong>
                        </span>
                    @endif
                </div>

                <div class="p-3" style="background: var(--adm-input-bg); border-radius: 0.625rem; border: 1px solid var(--adm-border); color: var(--adm-text-2); font-size: 0.85rem; line-height: 1.6;">
                    <strong style="color: var(--adm-text);">Platform Managed Integration</strong><br>
                    Google OAuth client credentials and authorized redirect URIs are managed per-domain by the platform super-administrator. To enable or update Google Sign-in for this institute, please contact the platform owner.
                </div>
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

    // Gemini mode toggling
    var modePlatformRadio = document.getElementById('gemini_mode_platform');
    var modeCustomRadio = document.getElementById('gemini_mode_custom');
    var customKeySection = document.getElementById('geminiCustomKeySection');

    function updateGeminiModeUi() {
        if (!customKeySection) return;
        if (modeCustomRadio && modeCustomRadio.checked) {
            customKeySection.style.display = '';
        } else {
            customKeySection.style.display = 'none';
        }
    }

    if (modePlatformRadio && modeCustomRadio) {
        modePlatformRadio.addEventListener('change', updateGeminiModeUi);
        modeCustomRadio.addEventListener('change', updateGeminiModeUi);
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
                    title: 'Clear Custom Gemini Key?',
                    text: 'Institute will switch back to using the shared platform AI assistant default.',
                    confirmText: 'Clear Key',
                    cancelText: 'Cancel',
                    danger: true
                }).then(function (ok) {
                    if (ok) geminiClearForm.submit();
                });
            } else {
                geminiClearForm.submit();
            }
        });
    }
})();
</script>
@endpush


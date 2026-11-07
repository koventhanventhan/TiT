@php
    /** @var \App\Filament\SuperAdmin\Resources\InstituteResource\Pages\ViewInstitute $this */
    /** @var \App\Models\Institute $institute */
    $institute = $this->getRecord();
    $checklist = $institute->setupChecklist();
    $plan = $institute->plan;
    $loginUrl = $this->adminLoginUrl;
    $integrationsUrl = $this->integrationsOverviewUrl;
    $editUrl = \App\Filament\SuperAdmin\Resources\InstituteResource::getUrl('edit', ['record' => $institute]);
    $hasOneTimePassword = !empty($this->oneTimeAdminPassword);
@endphp

<div
    x-data="{
        savedCredentials: @js(!$hasOneTimePassword),
        copiedKey: null,
        copyVal(key, text) {
            if (navigator.clipboard && text) {
                navigator.clipboard.writeText(text);
            }
            this.copiedKey = key;
            setTimeout(() => { if (this.copiedKey === key) this.copiedKey = null; }, 2000);
        }
    }"
    class="inst-ready-root"
>
    <style>
        .inst-ready-root {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            width: 100%;
            max-width: 100%;
            box-sizing: border-box;
        }
        .inst-ready-banner {
            border-radius: 0.75rem;
            border: 1px solid #86efac;
            background: #f0fdf4;
            color: #14532d;
            padding: 1.125rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        html.dark .inst-ready-banner {
            border-color: #166534;
            background: #052e16;
            color: #dcfce7;
        }
        .inst-cred-box {
            border-radius: 0.75rem;
            border: 1px solid #fcd34d;
            background: #fffbeb;
            color: #78350f;
            padding: 1.125rem 1.25rem;
            display: flex;
            flex-direction: column;
            gap: 0.875rem;
        }
        html.dark .inst-cred-box {
            border-color: #b45309;
            background: #451a03;
            color: #fef3c7;
        }
        .inst-code-pill {
            display: inline-block;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.875rem;
            padding: 0.375rem 0.625rem;
            border-radius: 0.5rem;
            background: #ffffff;
            color: #0f172a;
            border: 1px solid #cbd5e1;
            word-break: break-all;
        }
        html.dark .inst-code-pill {
            background: #0f172a;
            color: #f8fafc;
            border-color: #475569;
        }
        .inst-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            min-width: 44px;
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            box-sizing: border-box;
            gap: 0.375rem;
        }
        .inst-btn:hover {
            background: #f1f5f9;
        }
        html.dark .inst-btn {
            background: #1e293b;
            color: #f8fafc;
            border-color: #475569;
        }
        html.dark .inst-btn:hover {
            background: #334155;
        }
        .inst-btn-primary {
            background: #15803d;
            border-color: #15803d;
            color: #ffffff;
        }
        .inst-btn-primary:hover {
            background: #166534;
        }
        html.dark .inst-btn-primary {
            background: #22c55e;
            border-color: #22c55e;
            color: #052e16;
        }
        .inst-btn-disabled {
            opacity: 0.55;
            pointer-events: none;
        }
        .inst-check-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.875rem 0;
            border-bottom: 1px solid rgba(148, 163, 184, 0.25);
        }
        .inst-check-row:last-child {
            border-bottom: none;
        }
        .inst-tick {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 0.875rem;
            flex-shrink: 0;
        }
        .inst-tick-ok {
            background: #dcfce7;
            color: #14532d;
        }
        html.dark .inst-tick-ok {
            background: #14532d;
            color: #dcfce7;
        }
        .inst-tick-warn {
            background: #fef3c7;
            color: #78350f;
        }
        html.dark .inst-tick-warn {
            background: #78350f;
            color: #fef3c7;
        }
        .inst-tick-miss {
            background: #fee2e2;
            color: #7f1d1d;
        }
        html.dark .inst-tick-miss {
            background: #7f1d1d;
            color: #fee2e2;
        }
        .inst-muted {
            font-size: 0.8125rem;
            color: #475569;
        }
        html.dark .inst-muted {
            color: #cbd5e1;
        }
    </style>

    {{-- Green summary card --}}
    <div class="inst-ready-banner">
        <div style="display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem;">
            <div>
                <div style="font-size: 1.125rem; font-weight: 700;">
                    @if ($this->justProvisioned)
                        ✓ {{ $institute->name }} is ready
                    @else
                        {{ $institute->name }}
                    @endif
                </div>
                <div style="font-size: 0.875rem; margin-top: 0.25rem;">
                    Status: <strong>{{ ucfirst($institute->status) }}</strong>
                    &bull; Student ID format: <strong>{{ $institute->getIndexPrefix() }}/{{ $institute->getIndexFormat() === 'batch' ? date('y') . '/1001' : '08/001' }}</strong>
                </div>
            </div>
        </div>

        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.625rem;">
            <span style="font-size: 0.875rem; font-weight: 600;">Admin sign-in link:</span>
            <span class="inst-code-pill">{{ $loginUrl }}</span>
            <button
                type="button"
                class="inst-btn"
                x-on:click="copyVal('loginUrl', @js($loginUrl))"
            >
                <span x-show="copiedKey !== 'loginUrl'">Copy link</span>
                <span x-cloak x-show="copiedKey === 'loginUrl'">Copied</span>
            </button>
        </div>
    </div>

    {{-- One-time credentials card (never stored in session flash or logs; disappears on refresh) --}}
    @if ($hasOneTimePassword)
        <div class="inst-cred-box" data-testid="one-time-credentials-box">
            <div style="font-size: 1rem; font-weight: 700;">
                Save these one-time sign-in details now
            </div>
            <div style="font-size: 0.875rem;">
                For security, this password is shown only once on this screen. If you refresh or close this page, it will never be shown again (you can generate a new one later using Reset admin password).
            </div>

            <div style="display: flex; flex-direction: column; gap: 0.625rem;">
                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                    <span style="min-width: 7.5rem; font-size: 0.875rem; font-weight: 600;">Admin e-mail:</span>
                    <span class="inst-code-pill" data-testid="one-time-admin-email">{{ $this->oneTimeAdminEmail }}</span>
                    <button
                        type="button"
                        class="inst-btn"
                        x-on:click="copyVal('adminEmail', @js($this->oneTimeAdminEmail))"
                    >
                        <span x-show="copiedKey !== 'adminEmail'">Copy e-mail</span>
                        <span x-cloak x-show="copiedKey === 'adminEmail'">Copied</span>
                    </button>
                </div>

                <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                    <span style="min-width: 7.5rem; font-size: 0.875rem; font-weight: 600;">One-time password:</span>
                    <span class="inst-code-pill" data-testid="one-time-admin-password">{{ $this->oneTimeAdminPassword }}</span>
                    <button
                        type="button"
                        class="inst-btn"
                        x-on:click="copyVal('adminPass', @js($this->oneTimeAdminPassword))"
                    >
                        <span x-show="copiedKey !== 'adminPass'">Copy password</span>
                        <span x-cloak x-show="copiedKey === 'adminPass'">Copied</span>
                    </button>
                </div>
            </div>

            <label style="display: inline-flex; align-items: center; gap: 0.625rem; min-height: 44px; cursor: pointer; font-size: 0.875rem; font-weight: 600;">
                <input
                    type="checkbox"
                    x-model="savedCredentials"
                    data-testid="saved-credentials-checkbox"
                    style="width: 1.25rem; height: 1.25rem; cursor: pointer;"
                />
                <span>I have saved it in a safe place</span>
            </label>
        </div>
    @endif

    {{-- Setup checklist --}}
    <x-filament::section>
        <x-slot name="heading">
            Setup checklist
        </x-slot>

        <div>
            {{-- 1. Website address --}}
            <div class="inst-check-row">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem; min-width: 0;">
                    @if ($checklist['domain_status'] === 'verified')
                        <span class="inst-tick inst-tick-ok" aria-label="Completed">✓</span>
                    @elseif ($checklist['domain_status'] === 'unverified')
                        <span class="inst-tick inst-tick-warn" aria-label="Pending verification">!</span>
                    @else
                        <span class="inst-tick inst-tick-miss" aria-label="Missing">✗</span>
                    @endif
                    <div>
                        <div style="font-weight: 600; font-size: 0.9375rem;">Website address</div>
                        <div class="inst-muted">
                            @if ($checklist['domain_status'] === 'verified')
                                Primary address <strong>{{ $checklist['primary_domain'] }}</strong> is verified and pointing to this server.
                            @elseif ($checklist['domain_status'] === 'unverified')
                                <strong>{{ $checklist['primary_domain'] }}</strong> is saved (DNS not verified yet). Point its A record and cPanel alias, then click Check DNS now.
                            @else
                                No custom website address added yet. Add one when the institute's domain is ready.
                            @endif
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    @if ($checklist['domain_status'] === 'unverified')
                        <button type="button" class="inst-btn" wire:click="mountAction('checkDns')">
                            Check DNS now
                        </button>
                    @endif
                    <button type="button" class="inst-btn" wire:click="mountAction('addDomain')">
                        Add domain
                    </button>
                </div>
            </div>

            {{-- 2. Plan --}}
            <div class="inst-check-row">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem; min-width: 0;">
                    @if ($checklist['has_plan'])
                        <span class="inst-tick inst-tick-ok" aria-label="Completed">✓</span>
                    @else
                        <span class="inst-tick inst-tick-miss" aria-label="Missing">✗</span>
                    @endif
                    <div>
                        <div style="font-weight: 600; font-size: 0.9375rem;">Subscription plan</div>
                        <div class="inst-muted">
                            @if ($plan)
                                <strong>{{ $plan->name }}</strong>
                                &bull; Students: {{ $plan->formattedLimit('max_students') }}
                                &bull; Teachers: {{ $plan->formattedLimit('max_teachers') }}
                                @if ($institute->expires_at)
                                    &bull; Valid until {{ $institute->expires_at->format('M d, Y') }}
                                @endif
                            @else
                                No subscription plan assigned yet.
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <a href="{{ $editUrl }}" class="inst-btn">
                        {{ $plan ? 'Change plan' : 'Select plan' }}
                    </a>
                </div>
            </div>

            {{-- 3. Administrator account --}}
            <div class="inst-check-row">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem; min-width: 0;">
                    @if ($checklist['has_admin'])
                        <span class="inst-tick inst-tick-ok" aria-label="Completed">✓</span>
                    @else
                        <span class="inst-tick inst-tick-miss" aria-label="Missing">✗</span>
                    @endif
                    <div>
                        <div style="font-weight: 600; font-size: 0.9375rem;">First administrator</div>
                        <div class="inst-muted">
                            @if ($checklist['has_admin'])
                                Administrator account ready: <strong>{{ $checklist['admin_email'] }}</strong>
                            @else
                                No administrator account has been created for this institute yet.
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    @if ($checklist['has_admin'])
                        <button type="button" class="inst-btn" wire:click="mountAction('resetAdminPassword')">
                            Reset admin password
                        </button>
                    @else
                        <button type="button" class="inst-btn" wire:click="mountAction('createAdmin')">
                            Create admin
                        </button>
                    @endif
                </div>
            </div>

            {{-- 4. Online payments --}}
            <div class="inst-check-row">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem; min-width: 0;">
                    @if (!empty($checklist['providers']['payhere']))
                        <span class="inst-tick inst-tick-ok" aria-label="Completed">✓</span>
                    @else
                        <span class="inst-tick inst-tick-miss" aria-label="Not configured">✗</span>
                    @endif
                    <div>
                        <div style="font-weight: 600; font-size: 0.9375rem;">Online payments (PayHere)</div>
                        <div class="inst-muted">
                            @if (!empty($checklist['providers']['payhere']))
                                PayHere merchant account is configured for this institute.
                            @else
                                Not configured yet. Have the institute administrator sign in and open <strong>Settings &rarr; Online Payments</strong>.
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    <a href="{{ $integrationsUrl }}" class="inst-btn">
                        Set up online payments
                    </a>
                </div>
            </div>

            {{-- 5. WhatsApp & e-mail --}}
            <div class="inst-check-row">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem; min-width: 0;">
                    @php
                        $waOk = !empty($checklist['providers']['whatsapp']);
                        $mailOk = !empty($checklist['providers']['mail']);
                    @endphp
                    @if ($waOk && $mailOk)
                        <span class="inst-tick inst-tick-ok" aria-label="Completed">✓</span>
                    @elseif ($waOk || $mailOk)
                        <span class="inst-tick inst-tick-warn" aria-label="Partially configured">!</span>
                    @else
                        <span class="inst-tick inst-tick-miss" aria-label="Not configured">✗</span>
                    @endif
                    <div>
                        <div style="font-weight: 600; font-size: 0.9375rem;">WhatsApp &amp; e-mail messaging</div>
                        <div class="inst-muted">
                            WhatsApp: <strong>{{ $waOk ? 'Configured' : 'Not configured' }}</strong>
                            &bull; E-mail: <strong>{{ $mailOk ? 'Custom sender configured' : 'Using default platform sender' }}</strong>.
                            Have the institute administrator open <strong>Settings &rarr; Messaging</strong> to connect their own accounts.
                        </div>
                    </div>
                </div>

                <div>
                    <a href="{{ $integrationsUrl }}" class="inst-btn">
                        Set up WhatsApp &amp; e-mail
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>

    {{-- Next-step action buttons --}}
    <x-filament::section>
        <x-slot name="heading">
            Next steps
        </x-slot>

        <div style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center;">
            <button type="button" class="inst-btn" wire:click="mountAction('addDomain')">
                Add domain
            </button>

            <a
                href="{{ $integrationsUrl }}"
                class="inst-btn"
                x-bind:class="!savedCredentials ? 'inst-btn-disabled' : ''"
            >
                Set up online payments
            </a>

            <a
                href="{{ $integrationsUrl }}"
                class="inst-btn"
                x-bind:class="!savedCredentials ? 'inst-btn-disabled' : ''"
            >
                Set up WhatsApp &amp; e-mail
            </a>

            <a
                href="{{ $loginUrl }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inst-btn inst-btn-primary"
                x-bind:class="!savedCredentials ? 'inst-btn-disabled' : ''"
                data-testid="open-institute-admin-btn"
            >
                Open this institute's admin &rarr;
            </a>
        </div>

        @if ($hasOneTimePassword)
            <div x-show="!savedCredentials" class="inst-muted" style="margin-top: 0.625rem;">
                Tick <strong>"I have saved it in a safe place"</strong> above to unlock navigation links.
            </div>
        @endif
    </x-filament::section>
</div>


<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        <p style="font-size: 0.875rem; opacity: 0.85; margin: 0;">
            Read-only operational status for every institute's PayHere, WhatsApp, E-mail, Gemini, Zoom, reCAPTCHA, and Google integrations. Secret credentials are encrypted per institute and are never displayed here.
        </p>

        @if ($this->feedbackMessage)
            <x-filament::section>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; font-weight: 600;">
                    <span>{{ $this->feedbackMessage }}</span>
                </div>
            </x-filament::section>
        @endif

        @foreach ($this->institutesMatrix as $inst)
            <x-filament::section>
                <x-slot name="heading">
                    #{{ $inst['id'] }} — {{ $inst['name'] }} ({{ $inst['primary_domain'] }})
                </x-slot>

                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                        <thead>
                            <tr style="text-align: left; border-bottom: 1px solid rgba(148, 163, 184, 0.25);">
                                <th style="padding: 0.625rem 0.75rem;">Provider</th>
                                <th style="padding: 0.625rem 0.75rem;">Status</th>
                                <th style="padding: 0.625rem 0.75rem;">Source</th>
                                <th style="padding: 0.625rem 0.75rem;">Last Verified</th>
                                <th style="padding: 0.625rem 0.75rem;">Last Error (Sanitised)</th>
                                <th style="padding: 0.625rem 0.75rem; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ([
                                'payhere' => 'PayHere',
                                'whatsapp' => 'WhatsApp',
                                'mail' => 'E-mail (SMTP)',
                                'gemini' => 'AI Assistant (Gemini)',
                                'zoom' => 'Zoom Meetings',
                                'recaptcha' => 'Google reCAPTCHA v3',
                                'google' => 'Google Sign-in (OAuth)',
                            ] as $providerKey => $providerLabel)
                                @php
                                    $p = $inst['providers'][$providerKey];
                                    $badgeColor = match ($p['status']) {
                                        'verified' => 'success',
                                        'configured' => 'info',
                                        'failing' => 'danger',
                                        'using platform fallback' => 'warning',
                                        default => 'gray',
                                    };
                                @endphp
                                <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.15);">
                                    <td style="padding: 0.625rem 0.75rem; font-weight: 600;">{{ $providerLabel }}</td>
                                    <td style="padding: 0.625rem 0.75rem;">
                                        <x-filament::badge :color="$badgeColor">
                                            {{ $p['status'] }}
                                        </x-filament::badge>
                                    </td>
                                    <td style="padding: 0.625rem 0.75rem;">{{ $p['source'] }}</td>
                                    <td style="padding: 0.625rem 0.75rem;">{{ $p['last_verified_at'] ?? '—' }}</td>
                                    <td style="padding: 0.625rem 0.75rem; max-width: 320px; word-break: break-word;">
                                        {{ $p['last_error'] ?? '—' }}
                                    </td>
                                    <td style="padding: 0.625rem 0.75rem; text-align: right;">
                                        @if ($p['has_db_credentials'])
                                            @php $rowKey = $inst['id'] . ':' . $providerKey; @endphp
                                            @if ($this->pendingClearKey === $rowKey)
                                                <div style="display: inline-flex; gap: 0.375rem; align-items: center;">
                                                    <x-filament::button
                                                        color="danger"
                                                        size="xs"
                                                        wire:click="clearProviderCredentials({{ $inst['id'] }}, '{{ $providerKey }}')"
                                                    >
                                                        Confirm clear
                                                    </x-filament::button>
                                                    <x-filament::button
                                                        color="gray"
                                                        size="xs"
                                                        wire:click="cancelClear"
                                                    >
                                                        Cancel
                                                    </x-filament::button>
                                                </div>
                                            @else
                                                <x-filament::button
                                                    color="danger"
                                                    size="xs"
                                                    wire:click="requestClear({{ $inst['id'] }}, '{{ $providerKey }}')"
                                                >
                                                    Clear credentials
                                                </x-filament::button>
                                            @endif
                                        @else
                                            <span style="opacity: 0.6;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>

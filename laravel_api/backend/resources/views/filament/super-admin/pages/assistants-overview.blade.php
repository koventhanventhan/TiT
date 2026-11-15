<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 1.25rem;">
        {{-- Platform-level Switch Status (Read-only badges with sentence: "set in the server .env") --}}
        <x-filament::section>
            <x-slot name="heading">
                Platform-Wide Assistant Configuration
            </x-slot>

            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div style="display: flex; align-items: center; gap: 1.5rem; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-weight: 600; font-size: 0.875rem;">RAG_PUBLIC_ENABLED:</span>
                        @if ($this->platformSwitches['rag_public_enabled'])
                            <x-filament::badge color="success">
                                ENABLED
                            </x-filament::badge>
                        @else
                            <x-filament::badge color="danger">
                                DISABLED
                            </x-filament::badge>
                        @endif
                    </div>

                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="font-weight: 600; font-size: 0.875rem;">RAG_ENABLED:</span>
                        @if ($this->platformSwitches['rag_enabled'])
                            <x-filament::badge color="success">
                                ENABLED
                            </x-filament::badge>
                        @else
                            <x-filament::badge color="danger">
                                DISABLED
                            </x-filament::badge>
                        @endif
                    </div>
                </div>

                <p style="font-size: 0.8125rem; opacity: 0.75; margin: 0;">
                    Platform switches are read-only and set in the server .env.
                </p>
            </div>
        </x-filament::section>

        @if ($this->feedbackMessage)
            <x-filament::section>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.875rem; font-weight: 600; color: #16a34a;">
                    <span>{{ $this->feedbackMessage }}</span>
                </div>
            </x-filament::section>
        @endif

        {{-- Institutes Matrix Table --}}
        <x-filament::section>
            <x-slot name="heading">
                Institutes AI Assistant Matrix ({{ count($this->institutesMatrix) }})
            </x-slot>

            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
                    <thead>
                        <tr style="text-align: left; border-bottom: 1px solid rgba(148, 163, 184, 0.25);">
                            <th style="padding: 0.625rem 0.75rem;">Institute</th>
                            <th style="padding: 0.625rem 0.75rem;">Gemini</th>
                            <th style="padding: 0.625rem 0.75rem;">reCAPTCHA</th>
                            <th style="padding: 0.625rem 0.75rem;">Plan Allows AI</th>
                            <th style="padding: 0.625rem 0.75rem;">Contact Details</th>
                            <th style="padding: 0.625rem 0.75rem;">Public Assistant</th>
                            <th style="padding: 0.625rem 0.75rem;">7-Day Queries</th>
                            <th style="padding: 0.625rem 0.75rem; text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->institutesMatrix as $inst)
                            @php
                                $geminiColor = match ($inst['gemini']) {
                                    'db' => 'success',
                                    'env' => 'warning',
                                    default => 'danger',
                                };
                                $recaptchaColor = match ($inst['recaptcha']) {
                                    'db' => 'success',
                                    'env' => 'warning',
                                    default => 'danger',
                                };
                            @endphp
                            <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.15);">
                                <td style="padding: 0.625rem 0.75rem; font-weight: 600;">
                                    #{{ $inst['id'] }} — {{ $inst['name'] }}
                                    <div style="font-size: 0.75rem; font-weight: normal; opacity: 0.75;">
                                        {{ $inst['primary_domain'] }}
                                    </div>
                                </td>

                                <td style="padding: 0.625rem 0.75rem;">
                                    <x-filament::badge :color="$geminiColor">
                                        {{ $inst['gemini'] }}
                                    </x-filament::badge>
                                </td>

                                <td style="padding: 0.625rem 0.75rem;">
                                    <x-filament::badge :color="$recaptchaColor">
                                        {{ $inst['recaptcha'] }}
                                    </x-filament::badge>
                                </td>

                                <td style="padding: 0.625rem 0.75rem;">
                                    @if ($inst['plan_allows_ai'])
                                        <x-filament::badge color="success">YES</x-filament::badge>
                                    @else
                                        <x-filament::badge color="danger">NO</x-filament::badge>
                                    @endif
                                </td>

                                <td style="padding: 0.625rem 0.75rem;">
                                    @if ($inst['contacts_present'])
                                        <x-filament::badge color="success">Complete</x-filament::badge>
                                    @else
                                        <x-filament::badge color="warning">
                                            Incomplete
                                        </x-filament::badge>
                                    @endif
                                </td>

                                <td style="padding: 0.625rem 0.75rem;">
                                    @if ($inst['public_assistant_enabled'])
                                        <x-filament::badge color="success">ON</x-filament::badge>
                                    @else
                                        <x-filament::badge color="gray">OFF</x-filament::badge>
                                    @endif
                                </td>

                                <td style="padding: 0.625rem 0.75rem; font-weight: 600;">
                                    {{ $inst['queries_count'] }}
                                </td>

                                <td style="padding: 0.625rem 0.75rem; text-align: right;">
                                    <div style="display: inline-flex; gap: 0.5rem; align-items: center; justify-content: flex-end;">
                                        <x-filament::button
                                            tag="a"
                                            :href="$inst['open_url']"
                                            color="gray"
                                            size="xs"
                                        >
                                            Open institute
                                        </x-filament::button>

                                        @if ($inst['public_assistant_enabled'])
                                            @if ($this->pendingTurnOffInstituteId === $inst['id'])
                                                <div style="display: inline-flex; gap: 0.25rem; align-items: center;">
                                                    <x-filament::button
                                                        color="danger"
                                                        size="xs"
                                                        wire:click="confirmTurnOff({{ $inst['id'] }})"
                                                    >
                                                        Confirm OFF
                                                    </x-filament::button>
                                                    <x-filament::button
                                                        color="gray"
                                                        size="xs"
                                                        wire:click="cancelTurnOff"
                                                    >
                                                        Cancel
                                                    </x-filament::button>
                                                </div>
                                            @else
                                                <x-filament::button
                                                    color="danger"
                                                    size="xs"
                                                    wire:click="requestTurnOff({{ $inst['id'] }})"
                                                >
                                                    Turn off public assistant
                                                </x-filament::button>
                                            @endif
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>

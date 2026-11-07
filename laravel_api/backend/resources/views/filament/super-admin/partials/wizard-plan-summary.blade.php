@php
    /** @var \App\Filament\SuperAdmin\Resources\InstituteResource\Pages\CreateInstitute $this */
    $planId = data_get($this->data, 'subscription_plan_id');
    $plan = $planId ? \App\Models\SubscriptionPlan::find($planId) : null;
    $expiryPreset = $this->expiryPresetMode ?? '30d';
@endphp

<div class="wiz-plan-wrap">
    <style>
        .wiz-plan-wrap {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            box-sizing: border-box;
            max-width: 100%;
        }
        .wiz-plan-card {
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a;
            padding: 1rem 1.125rem;
        }
        html.dark .wiz-plan-card {
            border-color: #334155;
            background: #0f172a;
            color: #f1f5f9;
        }
        .wiz-plan-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.75rem;
            margin-top: 0.75rem;
        }
        .wiz-plan-stat {
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            padding: 0.625rem 0.75rem;
        }
        html.dark .wiz-plan-stat {
            border-color: #475569;
            background: #1e293b;
        }
        .wiz-plan-stat-label {
            font-size: 0.75rem;
            color: #475569;
        }
        html.dark .wiz-plan-stat-label {
            color: #cbd5e1;
        }
        .wiz-plan-stat-val {
            font-size: 1rem;
            font-weight: 700;
            margin-top: 0.125rem;
        }
        .wiz-quick-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            min-width: 44px;
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            font-size: 0.8125rem;
            font-weight: 600;
            cursor: pointer;
        }
        html.dark .wiz-quick-btn {
            border-color: #475569;
            background: #1e293b;
            color: #f8fafc;
        }
        .wiz-quick-btn-active {
            border-color: #1d4ed8;
            background: #dbeafe;
            color: #1e3a8a;
        }
        html.dark .wiz-quick-btn-active {
            border-color: #60a5fa;
            background: #1e3a8a;
            color: #eff6ff;
        }
    </style>

    @if ($plan)
        <div class="wiz-plan-card" data-testid="wizard-plan-summary-card">
            <div style="font-weight: 700; font-size: 0.9375rem;">
                {{ $plan->name }} — Plan summary
            </div>

            <div class="wiz-plan-grid">
                <div class="wiz-plan-stat">
                    <div class="wiz-plan-stat-label">Student limit</div>
                    <div class="wiz-plan-stat-val">{{ $plan->formattedLimit('max_students') }}</div>
                </div>

                <div class="wiz-plan-stat">
                    <div class="wiz-plan-stat-label">Teacher limit</div>
                    <div class="wiz-plan-stat-val">{{ $plan->formattedLimit('max_teachers') }}</div>
                </div>
            </div>

            @php
                $features = is_array($plan->features) ? array_filter($plan->features) : [];
            @endphp
            @if (!empty($features))
                <div style="margin-top: 0.75rem; font-size: 0.8125rem;">
                    <strong>Included features:</strong>
                    <ul style="margin: 0.375rem 0 0 1.25rem; padding: 0;">
                        @foreach ($features as $key => $val)
                            <li>
                                @if (is_string($key) && !is_numeric($key))
                                    {{ ucfirst(str_replace('_', ' ', $key)) }}: {{ is_bool($val) ? ($val ? 'Included' : 'No') : $val }}
                                @else
                                    {{ $val }}
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    <div>
        <div style="font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.375rem;">
            Quick validity duration:
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
            <button
                type="button"
                wire:click="applyExpiryPreset('30d')"
                class="wiz-quick-btn {{ $expiryPreset === '30d' ? 'wiz-quick-btn-active' : '' }}"
                data-testid="expiry-preset-30d"
            >
                30 days (trial)
            </button>
            <button
                type="button"
                wire:click="applyExpiryPreset('1y')"
                class="wiz-quick-btn {{ $expiryPreset === '1y' ? 'wiz-quick-btn-active' : '' }}"
                data-testid="expiry-preset-1y"
            >
                1 year
            </button>
            <button
                type="button"
                wire:click="applyExpiryPreset('custom')"
                class="wiz-quick-btn {{ $expiryPreset === 'custom' ? 'wiz-quick-btn-active' : '' }}"
                data-testid="expiry-preset-custom"
            >
                Custom
            </button>
        </div>
    </div>
</div>


@php
    /** @var \App\Filament\SuperAdmin\Resources\InstituteResource\Pages\CreateInstitute $this */
    $selectedPreset = strtolower((string) data_get($this->data, 'theme_preset', 'royal'));
    $presets = [
        'royal' => [
            'title' => 'Royal',
            'subtitle' => 'Classic deep indigo and crisp white accents',
            'swatch' => '#3730a3',
        ],
        'ocean' => [
            'title' => 'Ocean',
            'subtitle' => 'Clean coastal blue and cool slate surfaces',
            'swatch' => '#0369a1',
        ],
        'emerald' => [
            'title' => 'Emerald',
            'subtitle' => 'Fresh academic green with warm neutral cards',
            'swatch' => '#047857',
        ],
    ];
@endphp

<div class="wiz-presets-wrap">
    <style>
        .wiz-presets-wrap {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            box-sizing: border-box;
            max-width: 100%;
        }
        .wiz-presets-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.75rem;
        }
        .wiz-preset-card {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
            gap: 0.5rem;
            min-height: 88px;
            min-width: 44px;
            padding: 0.875rem 1rem;
            border-radius: 0.75rem;
            border: 2px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            text-align: left;
            cursor: pointer;
            box-sizing: border-box;
            width: 100%;
        }
        .wiz-preset-card:hover {
            border-color: #64748b;
        }
        html.dark .wiz-preset-card {
            border-color: #334155;
            background: #0f172a;
            color: #f8fafc;
        }
        html.dark .wiz-preset-card:hover {
            border-color: #94a3b8;
        }
        .wiz-preset-card-selected {
            border-color: #1d4ed8;
            background: #eff6ff;
            color: #0f172a;
        }
        html.dark .wiz-preset-card-selected {
            border-color: #60a5fa;
            background: #172554;
            color: #f8fafc;
        }
        .wiz-preset-sub {
            font-size: 0.8125rem;
            color: #475569;
            line-height: 1.4;
        }
        html.dark .wiz-preset-sub {
            color: #cbd5e1;
        }
    </style>

    <div style="font-size: 0.875rem; font-weight: 600;">
        Color theme
    </div>

    <div class="wiz-presets-grid" role="radiogroup" aria-label="Color theme">
        @foreach ($presets as $key => $meta)
            @php
                $isSelected = $selectedPreset === $key;
            @endphp
            <button
                type="button"
                role="radio"
                aria-checked="{{ $isSelected ? 'true' : 'false' }}"
                wire:click="selectThemePreset('{{ $key }}')"
                class="wiz-preset-card {{ $isSelected ? 'wiz-preset-card-selected' : '' }}"
                data-testid="preset-card-{{ $key }}"
            >
                <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; gap: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <span style="display: inline-block; width: 1.25rem; height: 1.25rem; border-radius: 9999px; background: {{ $meta['swatch'] }}; border: 1px solid rgba(15,23,42,0.2);"></span>
                        <span style="font-weight: 700; font-size: 0.9375rem;">{{ $meta['title'] }}</span>
                    </div>
                    @if ($isSelected)
                        <span style="font-size: 0.75rem; font-weight: 700;">✓ Selected</span>
                    @endif
                </div>
                <div class="wiz-preset-sub">
                    {{ $meta['subtitle'] }}
                </div>
            </button>
        @endforeach
    </div>
</div>


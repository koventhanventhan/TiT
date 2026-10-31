@extends('layouts.admin')

@section('title', 'Appearance Settings')

@push('styles')
<style>
    .theme-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .theme-card {
        border: 2px solid var(--adm-border);
        border-radius: var(--t-radius-card, 16px);
        background: var(--adm-surface);
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        position: relative;
        display: flex;
        flex-direction: column;
        color: var(--adm-text);
    }

    .theme-card:hover {
        border-color: var(--adm-accent);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
    }

    .theme-card.active {
        border-color: var(--adm-accent, #EB8153);
        box-shadow: 0 0 0 2px var(--adm-accent, #EB8153), 0 8px 24px rgba(0, 0, 0, 0.25);
    }

    .theme-preview-mockup {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid var(--adm-border, #473f72);
        height: 150px;
        display: flex;
        margin-bottom: 1rem;
    }

    .theme-mockup-sidebar {
        width: 48px;
        padding: 8px 6px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .theme-mockup-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        padding: 8px;
        gap: 6px;
    }

    .theme-mockup-header {
        height: 22px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 6px;
    }

    .theme-mockup-body {
        flex: 1;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }

    .theme-mockup-stat {
        border-radius: 4px;
        padding: 6px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .swatch-group {
        display: flex;
        gap: 6px;
        margin-top: 0.5rem;
    }

    .swatch {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 1px solid rgba(255, 255, 255, 0.15);
    }

    .theme-badge-active {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 9999px;
        background: rgba(34, 197, 94, 0.15);
        color: #4ade80;
        border: 1px solid rgba(34, 197, 94, 0.3);
    }
</style>
@endpush

@section('content')
<div class="row page-titles mx-0">
    <div class="col-sm-6 p-md-0">
        <div class="welcome-text">
            <h4 style="color: var(--adm-text);">Appearance & Theme</h4>
            <p class="mb-0" style="color: var(--adm-text-2);">Select 1 of 4 dark SaaS design presets for your institute console.</p>
        </div>
    </div>
    <div class="col-sm-6 p-md-0 justify-content-sm-end mt-2 mt-sm-0 d-flex">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.settings.index') }}">Settings</a></li>
            <li class="breadcrumb-item active"><a href="javascript:void(0)">Appearance</a></li>
        </ol>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <strong>Success!</strong> {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
@endif

<div class="card">
    <div class="card-header">
        <h4 class="card-title">Institute Theme Preset</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.appearance.update') }}">
            @csrf

            <div class="theme-grid">
                @php
                    $presetsData = file_exists(resource_path('themes/presets.json'))
                        ? json_decode(file_get_contents(resource_path('themes/presets.json')), true)
                        : [];

                    $availablePresets = [
                        'royal' => [
                            'name' => 'Classic Violet (original)',
                            'tagline' => 'Original Dark Purple & Warm Orange Accent',
                        ],
                        'ocean' => [
                            'name' => 'Ocean Blue',
                            'tagline' => 'Deep Navy Console & Vivid Cyan Accent',
                        ],
                        'emerald' => [
                            'name' => 'Emerald Fresh',
                            'tagline' => 'Forest Green Console & Fresh Mint Accent',
                        ],
                        'midnight' => [
                            'name' => 'Midnight Dark',
                            'tagline' => 'Obsidian Black Console & Indigo Glow',
                        ]
                    ];

                    foreach ($availablePresets as $k => $info) {
                        $pMeta = $presetsData[$k] ?? [];
                        foreach ($pMeta as $tokenKey => $tokenVal) {
                            $availablePresets[$k][$tokenKey] = $tokenVal;
                        }
                    }
                @endphp

                @foreach($availablePresets as $key => $p)
                @php $isSelected = ($currentPreset === $key); @endphp
                <label class="theme-card {{ $isSelected ? 'active' : '' }}" for="preset_{{ $key }}">
                    <!-- Live Mini UI Mockup of the Real Admin Console -->
                    <div class="theme-preview-mockup" style="background: {{ $p['adm_bg'] }}; border: 1px solid {{ $p['adm_border'] }};">
                        <!-- Mini Sidebar -->
                        <div class="theme-mockup-sidebar" style="background: {{ $p['adm_sidebar'] }}; border-right: 1px solid {{ $p['adm_border'] }};">
                            <div style="width: 14px; height: 14px; border-radius: 3px; background: {{ $p['adm_accent'] }}; margin-bottom: 4px;"></div>
                            <div style="width: 26px; height: 5px; border-radius: 2px; background: rgba(255,255,255,0.12); border-left: 2px solid {{ $p['adm_accent'] }};"></div>
                            <div style="width: 22px; height: 4px; border-radius: 2px; background: {{ $p['adm_text_2'] }}; opacity: 0.5;"></div>
                            <div style="width: 24px; height: 4px; border-radius: 2px; background: {{ $p['adm_text_2'] }}; opacity: 0.5;"></div>
                        </div>

                        <!-- Mini Main Workspace -->
                        <div class="theme-mockup-main">
                            <!-- Mini Topbar Header -->
                            <div class="theme-mockup-header" style="background: {{ $p['adm_sidebar'] }}; border-bottom: 1px solid {{ $p['adm_border'] }};">
                                <div style="width: 44px; height: 7px; border-radius: 10px; background: {{ $p['adm_input_bg'] }}; border: 1px solid {{ $p['adm_border'] }};"></div>
                                <div style="width: 12px; height: 12px; border-radius: 50%; background: {{ $p['adm_accent'] }};"></div>
                            </div>

                            <!-- Mini Content with 2 Stat Cards (Visible Numbers!) -->
                            <div class="theme-mockup-body">
                                <div class="theme-mockup-stat" style="background: {{ $p['adm_surface'] }}; border: 1px solid {{ $p['adm_border'] }};">
                                    <span style="font-size: 7px; color: {{ $p['adm_text_2'] }}; line-height: 1;">Students</span>
                                    <span style="font-size: 11px; font-weight: 700; color: {{ $p['adm_text'] }}; line-height: 1.2;">1,240</span>
                                </div>
                                <div class="theme-mockup-stat" style="background: {{ $p['adm_surface'] }}; border: 1px solid {{ $p['adm_border'] }};">
                                    <span style="font-size: 7px; color: {{ $p['adm_text_2'] }}; line-height: 1;">Revenue</span>
                                    <span style="font-size: 11px; font-weight: 700; color: {{ $p['adm_text'] }}; line-height: 1.2;">85.5K</span>
                                </div>
                            </div>

                            <!-- Mini Controls (Button + Badge) -->
                            <div style="display: flex; gap: 6px; align-items: center;">
                                <div style="height: 16px; padding: 0 6px; border-radius: 3px; background: {{ $p['adm_btn'] }}; color: {{ $p['adm_btn_text'] }}; font-size: 8px; font-weight: 700; display: flex; align-items: center; justify-content: center;">
                                    Save
                                </div>
                                <div style="height: 16px; padding: 0 6px; border-radius: 999px; background: rgba(34, 197, 94, 0.15); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; font-size: 7.5px; font-weight: 600; display: flex; align-items: center;">
                                    Active
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Header & Radio Selection -->
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center">
                            <input type="radio" id="preset_{{ $key }}" name="theme_preset" value="{{ $key }}" {{ $isSelected ? 'checked' : '' }} style="margin-right: 8px; width: 18px; height: 18px; accent-color: var(--adm-accent, #EB8153);" onchange="document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active')); this.closest('.theme-card').classList.add('active');">
                            <strong style="color: var(--adm-text);">{{ $p['name'] }}</strong>
                        </div>
                        @if($isSelected)
                            <span class="theme-badge-active"><i class="fa fa-check"></i> Active</span>
                        @endif
                    </div>

                    <small style="color: var(--adm-text-2);" class="mb-2">{{ $p['tagline'] }}</small>

                    <!-- Color Swatches: bg, surface, sidebar, accent -->
                    <div class="swatch-group">
                        <span class="swatch" style="background: {{ $p['adm_bg'] }};" title="Page BG: {{ $p['adm_bg'] }}"></span>
                        <span class="swatch" style="background: {{ $p['adm_surface'] }};" title="Surface: {{ $p['adm_surface'] }}"></span>
                        <span class="swatch" style="background: {{ $p['adm_sidebar'] }};" title="Header/Sidebar: {{ $p['adm_sidebar'] }}"></span>
                        <span class="swatch" style="background: {{ $p['adm_accent'] }};" title="Accent: {{ $p['adm_accent'] }}"></span>
                    </div>
                </label>
                @endforeach
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4 py-2" style="font-weight: 600;">
                    <i class="fa fa-save mr-2"></i> Save Appearance
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

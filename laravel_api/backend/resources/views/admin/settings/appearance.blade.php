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
        border: 2px solid var(--t-border, #e2e8f0);
        border-radius: var(--t-radius-card, 16px);
        background: var(--t-surface, #ffffff);
        padding: 1.25rem;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .theme-card:hover {
        border-color: var(--t-primary, #4f0bd9);
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .theme-card.active {
        border-color: var(--t-primary, #4f0bd9);
        box-shadow: 0 0 0 2px var(--t-primary, #4f0bd9), 0 8px 24px rgba(0, 0, 0, 0.08);
    }

    .theme-preview-mockup {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid rgba(0, 0, 0, 0.1);
        height: 140px;
        display: flex;
        margin-bottom: 1rem;
        background: #f8fafc;
    }

    .theme-mockup-sidebar {
        width: 44px;
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
        height: 18px;
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
        justify-content: space-between;
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
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .theme-badge-active {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 2px 8px;
        border-radius: 9999px;
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #bbf7d0;
    }
</style>
@endpush

@section('content')
<div class="row page-titles mx-0">
    <div class="col-sm-6 p-md-0">
        <div class="welcome-text">
            <h4>Appearance & Theme</h4>
            <p class="mb-0">Select 1 of 4 SaaS design presets for your institute console and public frontend.</p>
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
        <h4 class="card-title text-white">Institute Theme Preset</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.settings.appearance.update') }}">
            @csrf

            <div class="theme-grid">
                @php
                    $availablePresets = [
                        'royal' => [
                            'name' => 'Royal Violet',
                            'tagline' => 'Rich Indigo & Royal Purple',
                            'primary' => '#4f0bd9',
                            'sidebar' => '#1a103c',
                            'bg' => '#f8fafc',
                            'surface' => '#ffffff',
                            'accent' => '#4308b8',
                            'is_dark' => false
                        ],
                        'ocean' => [
                            'name' => 'Ocean Blue',
                            'tagline' => 'Deep Navy & Ocean Cyan',
                            'primary' => '#0b5fd9',
                            'sidebar' => '#0b2447',
                            'bg' => '#f8fafc',
                            'surface' => '#ffffff',
                            'accent' => '#094bb0',
                            'is_dark' => false
                        ],
                        'emerald' => [
                            'name' => 'Emerald Fresh',
                            'tagline' => 'Forest Slate & Vivid Emerald',
                            'primary' => '#047857',
                            'sidebar' => '#052e2b',
                            'bg' => '#f8fafc',
                            'surface' => '#ffffff',
                            'accent' => '#065f46',
                            'is_dark' => false
                        ],
                        'midnight' => [
                            'name' => 'Midnight Dark',
                            'tagline' => 'Obsidian Black & Violet Glow',
                            'primary' => '#4f46e5',
                            'sidebar' => '#0b1020',
                            'bg' => '#0b1020',
                            'surface' => '#151b2e',
                            'accent' => '#4338ca',
                            'is_dark' => true
                        ]
                    ];
                @endphp

                @foreach($availablePresets as $key => $p)
                @php $isSelected = ($currentPreset === $key); @endphp
                <label class="theme-card {{ $isSelected ? 'active' : '' }}" for="preset_{{ $key }}">
                    <!-- Live Mini UI Mockup -->
                    <div class="theme-preview-mockup" style="background: {{ $p['bg'] }};">
                        <!-- Mini Sidebar -->
                        <div class="theme-mockup-sidebar" style="background: {{ $p['sidebar'] }};">
                            <div style="width: 14px; height: 14px; border-radius: 3px; background: {{ $p['primary'] }}; margin-bottom: 4px;"></div>
                            <div style="width: 24px; height: 4px; border-radius: 2px; background: rgba(255,255,255,0.4);"></div>
                            <div style="width: 20px; height: 4px; border-radius: 2px; background: rgba(255,255,255,0.2);"></div>
                            <div style="width: 26px; height: 4px; border-radius: 2px; background: rgba(255,255,255,0.2);"></div>
                        </div>

                        <!-- Mini Main Workspace -->
                        <div class="theme-mockup-main">
                            <!-- Mini Topbar -->
                            <div class="theme-mockup-header" style="background: {{ $p['surface'] }}; border: 1px solid rgba(0,0,0,0.06);">
                                <div style="width: 40px; height: 6px; border-radius: 2px; background: {{ $p['is_dark'] ? '#94a3b8' : '#cbd5e1' }};"></div>
                                <div style="width: 12px; height: 12px; border-radius: 50%; background: {{ $p['primary'] }};"></div>
                            </div>

                            <!-- Mini Content -->
                            <div class="theme-mockup-body">
                                <div class="theme-mockup-stat" style="background: {{ $p['surface'] }}; border: 1px solid rgba(0,0,0,0.06);">
                                    <div style="width: 28px; height: 5px; border-radius: 2px; background: {{ $p['is_dark'] ? '#64748b' : '#94a3b8' }};"></div>
                                    <div style="width: 16px; height: 8px; border-radius: 2px; background: {{ $p['primary'] }};"></div>
                                </div>
                                <div class="theme-mockup-stat" style="background: {{ $p['surface'] }}; border: 1px solid rgba(0,0,0,0.06);">
                                    <div style="width: 32px; height: 5px; border-radius: 2px; background: {{ $p['is_dark'] ? '#64748b' : '#94a3b8' }};"></div>
                                    <div style="width: 22px; height: 8px; border-radius: 2px; background: #15803d;"></div>
                                </div>
                            </div>

                            <!-- Mini Button Row -->
                            <div style="display: flex; gap: 4px; align-items: center;">
                                <div style="height: 14px; padding: 0 6px; border-radius: 3px; background: {{ $p['primary'] }}; display: flex; align-items: center;">
                                    <div style="width: 20px; height: 3px; background: #ffffff; border-radius: 1px;"></div>
                                </div>
                                <div style="height: 14px; padding: 0 4px; border-radius: 3px; border: 1px solid {{ $p['primary'] }}; display: flex; align-items: center;">
                                    <div style="width: 16px; height: 3px; background: {{ $p['primary'] }}; border-radius: 1px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Header & Radio Selection -->
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center">
                            <input type="radio" id="preset_{{ $key }}" name="theme_preset" value="{{ $key }}" {{ $isSelected ? 'checked' : '' }} style="margin-right: 8px; width: 18px; height: 18px; accent-color: var(--t-primary, #4f0bd9);" onchange="document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('active')); this.closest('.theme-card').classList.add('active');">
                            <strong>{{ $p['name'] }}</strong>
                        </div>
                        @if($isSelected)
                            <span class="theme-badge-active"><i class="fa fa-check"></i> Active</span>
                        @endif
                    </div>

                    <small class="text-muted mb-2">{{ $p['tagline'] }}</small>

                    <!-- Color Swatches -->
                    <div class="swatch-group">
                        <span class="swatch" style="background: {{ $p['primary'] }};" title="Primary: {{ $p['primary'] }}"></span>
                        <span class="swatch" style="background: {{ $p['sidebar'] }};" title="Sidebar: {{ $p['sidebar'] }}"></span>
                        <span class="swatch" style="background: {{ $p['surface'] }};" title="Surface: {{ $p['surface'] }}"></span>
                        <span class="swatch" style="background: {{ $p['accent'] }};" title="Hover/Accent: {{ $p['accent'] }}"></span>
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


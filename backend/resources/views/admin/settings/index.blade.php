@extends('layouts.admin')

@section('title', 'Frontend Settings')

@push('styles')
<style>
    .content-body {
        margin-top: 0 !important;
        padding-top: 1.25rem;
    }

    .card {
        border-radius: 0.75rem;
        box-shadow: 0 0.25rem 1.25rem rgba(0, 0, 0, 0.2);
        margin-bottom: 1.5625rem;
        background: rgba(43, 37, 72, 0.4) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .card-header {
        border-bottom: 1.0px solid rgba(255, 255, 255, 0.1);
        background: transparent !important;
    }

    .card-title {
        color: #fff !important;
        font-weight: 600;
    }

    .class-edit-section, .profile-tab .custom-tab-1 {
        background: rgba(255, 255, 255, 0.03);
        border: 1.0px solid rgba(255,255,255,0.1);
        border-radius: 0.75rem;
        padding: 1.5625rem;
        margin-bottom: 1.875rem;
        color: #fff;
        position: relative;
        transition: all 0.3s ease;
    }

    .class-edit-section:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(235, 129, 83, 0.3);
    }

    .class-edit-section h5, .profile-tab .nav-tabs .nav-link.active {
        color: #EB8153 !important;
        font-weight: 600;
        margin-bottom: 1.25rem;
        border-bottom: 1.0px solid rgba(255,255,255,0.1) !important;
        padding-bottom: 0.625rem;
        border-color: transparent transparent #EB8153 !important;
    }
    
    .profile-tab .nav-tabs .nav-link {
        color: rgba(255,255,255,0.7);
        border: none;
        border-bottom: 1.0px solid transparent;
    }
    
    .profile-tab .nav-tabs .nav-link:hover {
        color: #EB8153;
        border-bottom: 1.0px solid rgba(235, 129, 83, 0.5);
    }

    .form-control, .bootstrap-select .dropdown-toggle, textarea {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        border-radius: 0.5rem !important;
    }

    .form-control:focus, textarea:focus {
        border-color: #EB8153 !important;
        box-shadow: 0 0 0 0.2rem rgba(235, 129, 83, 0.25) !important;
    }

    label {
        color: rgba(255, 255, 255, 0.7) !important;
        font-weight: 500;
        margin-bottom: 0.5rem;
    }

    .help-text {
        font-size: 0.75rem;
        color: rgba(255,255,255,0.5);
        margin-top: 0.25rem;
    }

    /* Prevent overrides from bootstrap text-dark/text-muted classes */
    form h5.text-dark {
         color: #EB8153 !important;
    }
    small.text-muted {
         color: rgba(255,255,255,0.6) !important;
    }


    .btn-info.btn-xs, .btn-primary {
        background-color: #EB8153;
        border-color: #EB8153;
        color: #fff;
        border-radius: 0.375rem;
        padding: 0.3125rem 0.75rem;
    }

    .btn-info.btn-xs:hover, .btn-primary:hover {
        background-color: #d96e42;
        border-color: #d96e42;
        transform: translateY(-0.125rem);
        box-shadow: 0 0.25rem 0.75rem rgba(235, 129, 83, 0.4) !important;
    }
    

    hr {
        border-top: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .text-primary {
        color: #EB8153 !important;
    }

    /* Interactive Logo Preview */
    .clickable-logo-preview {
        position: relative;
        transition: all 0.3s ease;
        overflow: hidden;
        background: rgba(0,0,0,0.2) !important;
        border: 1.0px solid rgba(255,255,255,0.1);
        border-radius: 0.5rem;
        height: 6.25rem;
        width: 6.25rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .clickable-logo-preview:hover {
        border-color: #EB8153 !important;
        box-shadow: 0 0.25rem 0.75rem rgba(0,0,0,0.3);
    }
    .logo-preview-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
        font-weight: 600;
    }
    .clickable-logo-preview:hover .logo-preview-overlay {
        opacity: 1;
    }
    .empty-logo:hover {
        background: rgba(43, 37, 72, 0.8) !important;
        color: #EB8153 !important;
    }

    .testimonial-item, .love-us-testimonial-item {
        background: #3b3363;
        border: 1.0px solid rgba(255,255,255,0.1);
        border-radius: 0.75rem;
        padding: 1.25rem;
        margin-bottom: 1.25rem;
        position: relative;
        transition: all 0.3s;
        color: #fff;
    }
    .testimonial-item:hover, .love-us-testimonial-item:hover {
        box-shadow: 0 0.5rem 0.9375rem rgba(0,0,0,0.2);
        border-color: #EB8153;
    }
    .remove-testimonial {
        position: absolute;
        top: 0.9375rem;
        right: 0.9375rem;
        color: #ff5e5e;
        cursor: pointer;
        z-index: 10;
        background: rgba(255,255,255,0.1);
        width: 1.5625rem;
        height: 1.5625rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
    }
    .remove-testimonial:hover {
        background: #ff5e5e;
        color: #fff;
    }
    .testimonial-item label, .love-us-testimonial-item label {
        font-size: 0.6875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: rgba(255,255,255,0.6);
        margin-bottom: 0.3125rem;
        display: block;
    }
    .t-image-preview-wrapper {
        width: 5rem;
        height: 5rem;
        border-radius: 0.625rem;
        overflow: hidden;
        background: rgba(255,255,255,0.05);
        border: 0.125rem dashed rgba(255,255,255,0.2);
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
    }
    .t-image-preview-wrapper:hover {
        border-color: #EB8153;
    }
    .t-image-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .t-image-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
        font-size: 1.25rem;
    }
    .t-image-preview-wrapper:hover .t-image-overlay {
        opacity: 1;
    }
    .form-control-sm {
        background: rgba(255,255,255,0.05) !important;
        border: 1.0px solid rgba(255,255,255,0.1) !important;
        color: #fff !important;
    }
    .form-control-sm:focus {
        border-color: #EB8153 !important;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Frontend Settings</h4>
            <div>
                <a href="{{ config('services.frontend_url') }}/" target="_blank" class="btn btn-primary btn-sm">
                    View Home Page
                </a>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-body">
                <div class="profile-tab">
                    <div class="custom-tab-1">
                        <ul class="nav nav-tabs">

                             <li class="nav-item"><a href="#hero" data-toggle="tab" class="nav-link active show">Hero Section</a></li>
                             <li class="nav-item"><a href="#topbar_header" data-toggle="tab" class="nav-link">Topbar & Header</a></li>
                             <li class="nav-item"><a href="#stats" data-toggle="tab" class="nav-link">Stats</a></li>
                            <li class="nav-item"><a href="#classes_list" data-toggle="tab" class="nav-link">Classes Section</a></li>
                             <li class="nav-item"><a href="#whychooseus" data-toggle="tab" class="nav-link">Why Choose Us</a></li>
                               <li class="nav-item"><a href="#mobileapp" data-toggle="tab" class="nav-link">Mobile App Section</a></li>
                             <li class="nav-item"><a href="#onboarding" data-toggle="tab" class="nav-link">Onboarding Section</a></li>
                             <li class="nav-item"><a href="#contact_social" data-toggle="tab" class="nav-link">Contact & Social</a></li>
                               <li class="nav-item"><a href="#testimonials" data-toggle="tab" class="nav-link">Testimonials Section</a></li>
                               <li class="nav-item"><a href="#footer" data-toggle="tab" class="nav-link">Footer & General</a></li>
                        </ul>
                        <div class="tab-content">
                            <!-- Hero Section -->
                             <div id="hero" class="tab-pane fade active show">
                                <div class="pt-4">
                                    <form action="{{ route('admin.settings.store') }}" method="POST">
                                        @csrf
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Hero Title</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="hero_title" class="form-control" value="{{ \App\Models\SiteSetting::get('hero_title', 'Experience the Future of') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Hero Title Gradient Part</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="hero_title_gradient" class="form-control" value="{{ \App\Models\SiteSetting::get('hero_title_gradient', ' Quality Online Learning') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Hero Description</label>
                                            <div class="col-sm-9">
                                                <textarea name="hero_description" class="form-control" rows="4">{{ \App\Models\SiteSetting::get('hero_description', 'Top-notch online tutoring from qualified tutors at the comfort of your home. Join thousands of students achieving academic excellence with personalized learning.') }}</textarea>
                                            </div>
                                        </div>

                                        <hr style="border-color: rgba(255,255,255,0.1); margin: 2rem 0 1.5rem;">
                                        <h5 class="text-primary mb-3" style="font-weight: 600;">Hero Background &amp; Slider Mode</h5>

                                        @php
                                            $heroBgMode = \App\Models\SiteSetting::get('hero_bg_mode', 'default');
                                            $heroBgImg = \App\Models\SiteSetting::get('hero_bg_image', '');
                                            $heroSliderImgs = json_decode(\App\Models\SiteSetting::get('hero_slider_images', '[]'), true) ?: [];
                                            $heroOpacity = \App\Models\SiteSetting::get('hero_overlay_opacity', '0.55');
                                            $heroInterval = \App\Models\SiteSetting::get('hero_slider_interval', '5');
                                            $heroOverlayColor = \App\Models\SiteSetting::get('hero_overlay_color', '#0a0f1e');
                                        @endphp

                                        {{-- Mode Selection --}}
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Background Mode</label>
                                            <div class="col-sm-9">
                                                <select name="hero_bg_mode" id="hero_bg_mode" class="form-control" onchange="toggleHeroBgMode(this.value)">
                                                    <option value="default" {{ $heroBgMode === 'default' ? 'selected' : '' }}>Mode 1: Default Animated Shapes &amp; Gradients</option>
                                                    <option value="single" {{ $heroBgMode === 'single' ? 'selected' : '' }}>Mode 2: Single Background Image</option>
                                                    <option value="slider" {{ $heroBgMode === 'slider' ? 'selected' : '' }}>Mode 3: Multi-Image Background Slider (Carousel)</option>
                                                </select>
                                                <small class="text-muted d-block mt-1">Select how you want the Hero background to display. You can switch between Default, Single Image, or Slider anytime.</small>
                                            </div>
                                        </div>

                                        {{-- Single Background Image Panel --}}
                                        <div id="hero_single_panel" class="form-group row" style="{{ $heroBgMode === 'single' ? '' : 'display:none;' }}">
                                            <label class="col-sm-3 col-form-label">Single Hero Image</label>
                                            <div class="col-sm-9">
                                                <div class="image-picker-container" style="height: 10rem; width: 100%; max-width: 480px; position: relative; background: rgba(0,0,0,0.2); border: 2px dashed rgba(255,255,255,0.15); border-radius: 0.75rem; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden;" onclick="this.querySelector('input[type=file]').click()">
                                                    <div class="upload-loading" style="display:none; position: absolute; inset:0; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; z-index: 5;"><div class="spinner-border text-primary"></div></div>
                                                    <div class="image-picker-placeholder" style="{{ $heroBgImg ? 'display:none;' : '' }}">
                                                        <i class="la la-image" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i> Select / Upload Image
                                                    </div>
                                                    <img src="{{ $heroBgImg }}" class="image-picker-preview" style="width: 100%; height: 100%; object-fit: cover; {{ $heroBgImg ? '' : 'display:none;' }}">
                                                    <input type="file" style="display:none;" accept="image/*" onchange="uploadHeroImage(this, null, 'hero_bg_image_input')">
                                                </div>
                                                <input type="hidden" name="hero_bg_image" id="hero_bg_image_input" value="{{ $heroBgImg }}">
                                                <button type="button" class="btn btn-danger btn-sm mt-2" style="{{ $heroBgImg ? '' : 'display:none;' }}" onclick="removeHeroSingleImage(this)"><i class="la la-trash"></i> Remove Image</button>
                                            </div>
                                        </div>

                                        {{-- Slider Manager Panel --}}
                                        <div id="hero_slider_panel" style="{{ $heroBgMode === 'slider' ? '' : 'display:none;' }}">
                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Slide Autoplay Speed</label>
                                                <div class="col-sm-4">
                                                    <div class="input-group">
                                                        <input type="number" name="hero_slider_interval" class="form-control" value="{{ $heroInterval }}" min="2" max="30">
                                                        <div class="input-group-append"><span class="input-group-text">Seconds</span></div>
                                                    </div>
                                                    <small class="text-muted">Seconds before automatically transitioning to the next slide.</small>
                                                </div>
                                            </div>

                                            <div class="form-group row">
                                                <label class="col-sm-3 col-form-label">Slider Images</label>
                                                <div class="col-sm-9">
                                                    <div id="hero_slider_container">
                                                        @foreach($heroSliderImgs as $sIdx => $sImg)
                                                            <div class="dynamic-slider-row mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; padding: 1rem; position: relative;">
                                                                <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 0.75rem; right: 0.75rem;" onclick="this.closest('.dynamic-slider-row').remove(); syncHeroSlider();"><i class="la la-trash"></i> Remove</button>
                                                                <div class="row align-items-center">
                                                                    <div class="col-md-5">
                                                                        <div class="image-picker-container" style="height: 7rem; width: 100%; position: relative; background: rgba(0,0,0,0.2); border: 1px dashed rgba(255,255,255,0.15); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden;" onclick="this.querySelector('input[type=file]').click()">
                                                                            <div class="upload-loading" style="display:none; position: absolute; inset:0; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; z-index: 5;"><div class="spinner-border text-primary"></div></div>
                                                                            <div class="image-picker-placeholder" style="{{ !empty($sImg['image']) ? 'display:none;' : '' }}">
                                                                                <i class="la la-image"></i> Select Slide Image
                                                                            </div>
                                                                            <img src="{{ is_array($sImg) ? ($sImg['image'] ?? '') : $sImg }}" class="image-picker-preview" style="width: 100%; height: 100%; object-fit: cover; {{ !empty($sImg['image']) || !empty($sImg) ? '' : 'display:none;' }}">
                                                                            <input type="file" style="display:none;" accept="image/*" onchange="uploadHeroImage(this, null, null, true)">
                                                                        </div>
                                                                        <input type="hidden" class="slider-img-url" value="{{ is_array($sImg) ? ($sImg['image'] ?? '') : $sImg }}">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label style="font-size: 0.8rem; color: rgba(255,255,255,0.7);">Optional Caption / Title</label>
                                                                        <input type="text" class="form-control form-control-sm slider-img-title" value="{{ is_array($sImg) ? ($sImg['title'] ?? '') : '' }}" placeholder="e.g. Modern Virtual Classroom" oninput="syncHeroSlider()">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <button type="button" class="btn btn-info btn-sm" onclick="addHeroSlideRow()"><i class="la la-plus"></i> + Add Slide Image</button>
                                                    <input type="hidden" name="hero_slider_images" id="hero_slider_images_json" value="{{ \App\Models\SiteSetting::get('hero_slider_images', '[]') }}">
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Overlay Opacity & Color Tint Settings --}}
                                        <div id="hero_overlay_settings" style="{{ $heroBgMode === 'default' ? 'display:none;' : '' }}">
                                            <hr style="border-color: rgba(255,255,255,0.08); margin: 1.5rem 0;">
                                            <div class="form-group row align-items-center">
                                                <label class="col-sm-3 col-form-label">Overlay Opacity (Darkness)</label>
                                                <div class="col-sm-6">
                                                    <input type="range" name="hero_overlay_opacity" id="hero_overlay_opacity_range" class="form-control-range" min="0" max="1" step="0.05" value="{{ $heroOpacity }}" oninput="document.getElementById('opacity_val_label').innerText = Math.round(this.value * 100) + '%'">
                                                    <small class="text-muted">Controls darkness overlay so foreground text and buttons remain 100% readable over images.</small>
                                                </div>
                                                <div class="col-sm-3">
                                                    <span id="opacity_val_label" class="badge badge-primary" style="font-size: 1rem; padding: 0.5rem 0.85rem;">{{ round(((float)$heroOpacity) * 100) }}%</span>
                                                </div>
                                            </div>
                                            <div class="form-group row align-items-center">
                                                <label class="col-sm-3 col-form-label">Overlay Tint Color</label>
                                                <div class="col-sm-4">
                                                    <input type="color" name="hero_overlay_color" class="form-control" style="height: 42px; width: 100px; padding: 4px;" value="{{ $heroOverlayColor }}">
                                                </div>
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary mt-4" onclick="syncHeroSlider()"><i class="la la-save"></i> Save Hero Changes</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Topbar & Header Section -->
                            <div id="topbar_header" class="tab-pane fade">
                                <div class="pt-4">
                                    <form action="{{ route('admin.settings.store') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <h5 class="mb-3 text-primary">Topbar Visibility Toggles</h5>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label>Show Facebook</label>
                                                <select name="topbar_show_fb" class="form-control mb-2">
                                                    <option value="yes" {{ \App\Models\SiteSetting::get('topbar_show_fb', 'yes') == 'yes' ? 'selected' : '' }}>On</option>
                                                    <option value="no" {{ \App\Models\SiteSetting::get('topbar_show_fb', 'yes') == 'no' ? 'selected' : '' }}>Off</option>
                                                </select>
                                                <input type="text" name="social_facebook" class="form-control" placeholder="Facebook URL" value="{{ \App\Models\SiteSetting::get('social_facebook', '#facebook') }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label>Show Instagram</label>
                                                <select name="topbar_show_insta" class="form-control mb-2">
                                                    <option value="yes" {{ \App\Models\SiteSetting::get('topbar_show_insta', 'yes') == 'yes' ? 'selected' : '' }}>On</option>
                                                    <option value="no" {{ \App\Models\SiteSetting::get('topbar_show_insta', 'yes') == 'no' ? 'selected' : '' }}>Off</option>
                                                </select>
                                                <input type="text" name="social_instagram" class="form-control" placeholder="Instagram URL" value="{{ \App\Models\SiteSetting::get('social_instagram', '#instagram') }}">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label>Show YouTube</label>
                                                <select name="topbar_show_youtube" class="form-control mb-2">
                                                    <option value="yes" {{ \App\Models\SiteSetting::get('topbar_show_youtube', 'yes') == 'yes' ? 'selected' : '' }}>On</option>
                                                    <option value="no" {{ \App\Models\SiteSetting::get('topbar_show_youtube', 'yes') == 'no' ? 'selected' : '' }}>Off</option>
                                                </select>
                                                <input type="text" name="social_youtube" class="form-control" placeholder="YouTube URL" value="{{ \App\Models\SiteSetting::get('social_youtube', '#youtube') }}">
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label>Show Support Email & Email Address</label>
                                                <div class="d-flex gap-2 align-items-center">
                                                    <select name="topbar_show_email" class="form-control" style="width: 7.5rem; flex-shrink: 0;">
                                                        <option value="yes" {{ \App\Models\SiteSetting::get('topbar_show_email', 'yes') == 'yes' ? 'selected' : '' }}>On</option>
                                                        <option value="no" {{ \App\Models\SiteSetting::get('topbar_show_email', 'yes') == 'no' ? 'selected' : '' }}>Off</option>
                                                    </select>
                                                    <input type="email" name="footer_email" class="form-control" placeholder="Support Email" value="{{ \App\Models\SiteSetting::get('footer_email', 'info@edulearn.lk') }}">
                                                </div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label>Language Selector Visibility</label>
                                                <select name="topbar_show_lang" class="form-control">
                                                    <option value="yes" {{ \App\Models\SiteSetting::get('topbar_show_lang', 'yes') == 'yes' ? 'selected' : '' }}>On</option>
                                                    <option value="no" {{ \App\Models\SiteSetting::get('topbar_show_lang', 'yes') == 'no' ? 'selected' : '' }}>Off</option>
                                                </select>
                                            </div>
                                        </div>

                                        <hr>
                                        <h5 class="mb-3 text-primary">Admin Dashboard Identity</h5>
                                        <div class="row mb-4">
                                            <div class="col-md-12 mb-3">
                                                <label>Admin Dashboard Company Name</label>
                                                <input type="text" name="admin_company_name" class="form-control" placeholder="e.g. TiT Dashboard" value="{{ \App\Models\SiteSetting::get('admin_company_name', 'Zenix') }}">
                                                <small class="text-muted">This name appears in the top-left corner of the admin panel.</small>
                                            </div>
                                            <!-- <div class="col-md-6 mb-3">
                                                <label class="d-block">Admin Dashboard Logo</label>
                                                <div class="clickable-logo-preview mb-2" id="admin-logo-preview-container">
                                                    @if(\App\Models\SiteSetting::get('admin_logo'))
                                                        <img src="{{ asset(\App\Models\SiteSetting::get('admin_logo')) }}" alt="Admin Logo" id="admin-logo-preview-img" style="max-height: 5rem; max-width: 100%;">
                                                        <div class="logo-preview-overlay" onclick="removeLogo('admin')">Remove Logo</div>
                                                    @else
                                                        <div class="empty-logo" onclick="document.querySelector('input[name=\'admin_logo\']').click()">No Logo</div>
                                                    @endif
                                                </div>
                                                <input type="file" name="admin_logo" class="form-control-file mb-2" onchange="previewLogo(this, 'admin')">
                                                <input type="hidden" name="remove_admin_logo" id="remove_admin_logo" value="0">
                                            </div> -->
                                            






                                            <div class="col-md-6 mb-3">
                                                <label class="d-block">Frontend Website Logo</label>
                                                <div class="clickable-logo-preview mb-2" id="frontend-logo-preview-container">
                                                    @if(\App\Models\SiteSetting::get('frontend_logo'))
                                                        <img src="{{ asset(\App\Models\SiteSetting::get('frontend_logo')) }}" alt="Frontend Logo" id="frontend-logo-preview-img" style="max-height: 5rem; max-width: 100%;">
                                                        <div class="logo-preview-overlay" onclick="removeLogo('frontend')">Remove Logo</div>
                                                    @else
                                                        <div class="empty-logo" onclick="document.querySelector('input[name=\'frontend_logo\']').click()">No Logo</div>
                                                    @endif
                                                </div>
                                                <input type="file" name="frontend_logo" class="form-control-file mb-2" onchange="previewLogo(this, 'frontend')">
                                                <input type="hidden" name="remove_frontend_logo" id="remove_frontend_logo" value="0">
                                                <small class="text-muted d-block">This logo will appear on the main website header.</small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="d-block">Site Favicon (Tab Icon)</label>
                                                <div class="clickable-logo-preview mb-2" id="favicon-logo-preview-container">
                                                    @if(\App\Models\SiteSetting::get('site_favicon'))
                                                        <img src="{{ asset(\App\Models\SiteSetting::get('site_favicon')) }}" alt="Favicon" id="favicon-logo-preview-img" style="max-height: 3rem; max-width: 3rem;">
                                                        <div class="logo-preview-overlay" onclick="removeLogo('favicon')">Remove</div>
                                                    @else
                                                        <div class="empty-logo" onclick="document.querySelector('input[name=\'site_favicon\']').click()">No Icon</div>
                                                    @endif
                                                </div>
                                                <input type="file" name="site_favicon" class="form-control-file mb-2" onchange="previewLogo(this, 'favicon')">
                                                <input type="hidden" name="remove_favicon_logo" id="remove_favicon_logo" value="0">
                                                <small class="text-muted d-block">Best size: 32x32 or 16x16 pixels.</small>
                                            </div>
                                        </div>

                                        
                                        <hr>
                                        <h5 class="mb-3 text-primary">Navigation Labels</h5>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label>Home Link</label>
                                                <input type="text" name="nav_home" class="form-control" value="{{ \App\Models\SiteSetting::get('nav_home', 'Home') }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>About Link</label>
                                                <input type="text" name="nav_about" class="form-control" value="{{ \App\Models\SiteSetting::get('nav_about', 'About Us') }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Classes Link</label>
                                                <input type="text" name="nav_classes" class="form-control" value="{{ \App\Models\SiteSetting::get('nav_classes', 'Our Classes') }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Learning Suite Link</label>
                                                <input type="text" name="nav_learning_suite" class="form-control" value="{{ \App\Models\SiteSetting::get('nav_learning_suite', 'Learning Suite') }}">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label>Contact Link</label>
                                                <input type="text" name="nav_contact" class="form-control" value="{{ \App\Models\SiteSetting::get('nav_contact', 'Contact Us') }}">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-primary mt-3">Save Header Changes</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div id="stats" class="tab-pane fade">
                                <div class="pt-4">
                                    <form action="{{ route('admin.settings.store') }}" method="POST">
                                        @csrf
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Years Experience</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="stats_years" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_years', 10) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Total Students</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="stats_students" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_students', 10000) }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Expert Tutors</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="stats_tutors" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_tutors', 200) }}">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary mt-3">Save Stats Changes</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Classes Section -->
                            <div id="classes_list" class="tab-pane fade">
                                <div class="pt-4">
                                    <form action="{{ route('admin.settings.store') }}" method="POST">
                                        @csrf
                                        <h5 class="mb-3 text-primary">Header Settings</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Title</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="classes_title" class="form-control" value="{{ \App\Models\SiteSetting::get('classes_title', 'Explore & Enroll') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Subtitle</label>
                                            <div class="col-sm-9">
                                                <textarea name="classes_subtitle" class="form-control" rows="3">{{ \App\Models\SiteSetting::get('classes_subtitle', 'Online Tuition for all subjects - Grade 1 to Advanced Level. Group or one-on-one? We got you!') }}</textarea>
                                            </div>
                                        </div>

                                        <hr>
                                        <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                                            Course Types Manager
                                            <button type="button" id="add-class-type" class="btn btn-info btn-xs">+ Add Type</button>
                                        </h5>

                                        <div id="classes-repeater">
                                            <!-- Items will be injected here by JS -->
                                        </div>

                                        @php
                                            $typesJson = \App\Models\SiteSetting::get('classes_types', '[]');
                                            $typesArr = json_decode($typesJson, true);
                                            if (empty($typesArr)) {
                                                $typesArr = [
                                                    [
                                                        'title' => 'Direct Class',
                                                        'description' => \App\Models\SiteSetting::get('classes_direct_description', 'Face-to-face academic support.'),
                                                        'color' => '#EB8153',
                                                        'stars' => 5,
                                                        'features' => \App\Models\SiteSetting::get('classes_direct_features', "Expert Tutors\nSmall Groups"),
                                                        'subjects' => \App\Models\SiteSetting::get('classes_direct_subjects', 'Maths, Science'),
                                                        'image' => '',
                                                        'format' => 'Face-to-Face'
                                                    ],
                                                    [
                                                        'title' => 'Online Class',
                                                        'description' => \App\Models\SiteSetting::get('classes_online_description', 'Live interactive sessions.'),
                                                        'color' => '#667eea',
                                                        'stars' => 5,
                                                        'features' => \App\Models\SiteSetting::get('classes_online_features', "Live Records\nDigital Resources"),
                                                        'subjects' => \App\Models\SiteSetting::get('classes_online_subjects', 'Physics, Chemistry'),
                                                        'image' => '',
                                                        'format' => 'Live Online'
                                                    ]
                                                ];
                                                $typesJson = json_encode($typesArr);
                                            }
                                        @endphp
                                        <textarea name="classes_types" id="classes_types_hidden" class="d-none">{{ $typesJson }}</textarea>

                                        <div class="alert alert-info py-2 mt-3" style="font-size: 0.8125rem;">
                                            <b>Note:</b> These are the cards shown in the "Explore & Enroll" section.
                                        </div>

                                        <button type="submit" class="btn btn-primary mt-3">Save Classes Changes</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Why Choose Us Section -->
                            <div id="whychooseus" class="tab-pane fade">
                                <div class="pt-4">
                                    <form action="{{ route('admin.settings.store') }}" method="POST">
                                        @csrf
                                        <h5 class="mb-3 text-primary">Header Settings</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Title</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="why_title" class="form-control" value="{{ \App\Models\SiteSetting::get('why_title', 'Why EduLearn?') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Subtitle</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="why_subtitle" class="form-control" value="{{ \App\Models\SiteSetting::get('why_subtitle', 'Quality Assured Online Learning 👨🏻‍🎓') }}">
                                            </div>
                                        </div>

                                        <hr>
                                        <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                                            Reasons/Features Manager (Right Cards)
                                            <button type="button" id="add-why-item" class="btn btn-info btn-xs">+ Add Reason</button>
                                        </h5>
                                        <div id="why-repeater">
                                            <!-- Items will be injected here by JS -->
                                        </div>
                                        <textarea name="why_reasons" id="why_reasons_hidden" class="d-none">{{ \App\Models\SiteSetting::get('why_reasons', '[]') }}</textarea>

                                        <hr>
                                        <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                                            Benefits Manager (Left Bullet Points)
                                            <button type="button" id="add-why-benefit" class="btn btn-info btn-xs">+ Add Benefit</button>
                                        </h5>
                                        <div id="why-benefits-repeater">
                                            <!-- Benefits will be injected here by JS -->
                                        </div>
                                        <textarea name="why_benefits" id="why_benefits_hidden" class="d-none">{{ \App\Models\SiteSetting::get('why_benefits', '[]') }}</textarea>

                                        <button type="submit" class="btn btn-primary mt-3">Save Why Choose Us Changes</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Mobile App Section -->
                            <div id="mobileapp" class="tab-pane fade">
                                <div class="pt-4">
                                    <form action="{{ route('admin.settings.store') }}" method="POST">
                                        @csrf
                                        <h5 class="mb-3 text-primary">App Details</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Main Title</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="mobile_title" class="form-control" value="{{ \App\Models\SiteSetting::get('mobile_title', 'Learn Anytime, Anywhere..!') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Subtitle</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="mobile_subtitle" class="form-control" value="{{ \App\Models\SiteSetting::get('mobile_subtitle', 'EduLearn Mobile App') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Description</label>
                                            <div class="col-sm-9">
                                                <textarea name="mobile_description" class="form-control" rows="4">{{ \App\Models\SiteSetting::get('mobile_description', 'Take your learning on the go with the EduLearn Mobile App, available on both iOS and Android. Access live classes, class recordings, exams, and progress updates seamlessly from your mobile device. Stay connected, stay updated, and unlock a world of learning at your fingertips—anytime, anywhere!') }}</textarea>
                                            </div>
                                        </div>

                                        <hr>
                                        <h5 class="mb-3 text-primary">Download Links & Floating Labels</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">App Store Link</label>
                                            <div class="col-sm-6">
                                                <input type="text" name="mobile_app_store_link" class="form-control" value="{{ \App\Models\SiteSetting::get('mobile_app_store_link', '#') }}">
                                            </div>
                                            <div class="col-sm-3">
                                                <select name="mobile_app_store_show" class="form-control">
                                                    <option value="on" {{ \App\Models\SiteSetting::get('mobile_app_store_show', 'on') === 'on' ? 'selected' : '' }}>Show (On)</option>
                                                    <option value="off" {{ \App\Models\SiteSetting::get('mobile_app_store_show', 'on') === 'off' ? 'selected' : '' }}>Hide (Off)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Google Play Link</label>
                                            <div class="col-sm-6">
                                                <input type="text" name="mobile_play_store_link" class="form-control" value="{{ \App\Models\SiteSetting::get('mobile_play_store_link', '#') }}">
                                            </div>
                                            <div class="col-sm-3">
                                                <select name="mobile_play_store_show" class="form-control">
                                                    <option value="on" {{ \App\Models\SiteSetting::get('mobile_play_store_show', 'on') === 'on' ? 'selected' : '' }}>Show (On)</option>
                                                    <option value="off" {{ \App\Models\SiteSetting::get('mobile_play_store_show', 'on') === 'off' ? 'selected' : '' }}>Hide (Off)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Satisfaction Card Label</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="mobile_card_satisfaction" class="form-control" value="{{ \App\Models\SiteSetting::get('mobile_card_satisfaction', '99% Student Satisfaction') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Live Sessions Card Label</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="mobile_card_sessions" class="form-control" value="{{ \App\Models\SiteSetting::get('mobile_card_sessions', '24/7 Live Sessions') }}">
                                            </div>
                                        </div>
                                        <hr>
                                        <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                                            App Screens Manager (Phone Mockup)
                                            <button type="button" id="add-mobile-screen" class="btn btn-info btn-xs">+ Add Screen</button>
                                        </h5>
                                        <div id="mobile-screens-repeater">
                                            <!-- Screens will be injected here by JS -->
                                        </div>
                                        <textarea name="mobile_screens" id="mobile_screens_hidden" class="d-none">{{ \App\Models\SiteSetting::get('mobile_screens', '[]') }}</textarea>
                                        <hr>
                                        <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                                            Mobile App Features
                                            <button type="button" id="add-mobile-feature" class="btn btn-info btn-xs">+ Add Feature</button>
                                        </h5>
                                        <div id="mobile-features-repeater">
                                            <!-- Features will be injected here by JS -->
                                        </div>
                                        <textarea name="mobile_features" id="mobile_features_hidden" class="d-none">{{ \App\Models\SiteSetting::get('mobile_features', '[]') }}</textarea>
                                        <button type="submit" class="btn btn-primary mt-3">Save Mobile App Changes</button>
                                    </form>
                                </div>
                            </div>

                             <!-- Onboarding Section -->
                             <div id="onboarding" class="tab-pane fade">
                                 <div class="pt-4">
                                     <form action="{{ route('admin.settings.store') }}" method="POST">
                                         @csrf
                                         <h5 class="mb-3 text-primary">Header & Description</h5>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">Section Title</label>
                                             <div class="col-sm-9">
                                                 <input type="text" name="onboarding_title" class="form-control" value="{{ \App\Models\SiteSetting::get('onboarding_title', 'Onboarding Process') }}">
                                             </div>
                                         </div>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">Section Subtitle</label>
                                             <div class="col-sm-9">
                                                 <textarea name="onboarding_subtitle" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('onboarding_subtitle', 'Follow our simple steps to join EduLearn Online Tuition 📚') }}</textarea>
                                             </div>
                                         </div>

                                         <hr>
                                         <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                                             Onboarding Steps Manager
                                             <button type="button" id="add-onboarding-step" class="btn btn-info btn-xs">+ Add New Step</button>
                                         </h5>

                                         <div id="onboarding-repeater">
                                             <!-- Steps will be injected here by JS -->
                                         </div>

                                         <textarea name="onboarding_steps" id="onboarding_steps_hidden" class="d-none">{{ \App\Models\SiteSetting::get('onboarding_steps', '[]') }}</textarea>

                                         <button type="submit" class="btn btn-primary mt-3">Save Onboarding Changes</button>
                                     </form>
                                 </div>
                             </div>

                             <!-- Contact & Social Section -->
                             <div id="contact_social" class="tab-pane fade">
                                 <div class="pt-4">
                                     <form action="{{ route('admin.settings.store') }}" method="POST">
                                         @csrf
                                         <h5 class="mb-3 text-primary">Main Contact Info</h5>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">Contact Email</label>
                                             <div class="col-sm-9">
                                                 <input type="email" name="footer_email" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_email', 'info@edulearn.lk') }}">
                                                 <small class="text-muted">Used in Topbar, Footer, and Contact Page</small>
                                             </div>
                                         </div>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">Contact Phone</label>
                                             <div class="col-sm-9">
                                                 <input type="text" name="footer_phone" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_phone', '+94 114 477 488') }}">
                                             </div>
                                         </div>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">Location Address</label>
                                             <div class="col-sm-9">
                                                 <input type="text" name="contact_location" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_location', 'Colombo, Sri Lanka') }}">
                                             </div>
                                         </div>

                                         <hr>
                                         <h5 class="mb-3 text-primary">Social Media Links</h5>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">Facebook</label>
                                             <div class="col-sm-9">
                                                 <input type="text" name="social_facebook" class="form-control" value="{{ \App\Models\SiteSetting::get('social_facebook', '#facebook') }}">
                                             </div>
                                         </div>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">Instagram</label>
                                             <div class="col-sm-9">
                                                 <input type="text" name="social_instagram" class="form-control" value="{{ \App\Models\SiteSetting::get('social_instagram', '#instagram') }}">
                                             </div>
                                         </div>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">YouTube</label>
                                             <div class="col-sm-9">
                                                 <input type="text" name="social_youtube" class="form-control" value="{{ \App\Models\SiteSetting::get('social_youtube', '#youtube') }}">
                                             </div>
                                         </div>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">Twitter/X</label>
                                             <div class="col-sm-9">
                                                 <input type="text" name="social_twitter" class="form-control" value="{{ \App\Models\SiteSetting::get('social_twitter', '#twitter') }}">
                                             </div>
                                         </div>
                                         <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">LinkedIn</label>
                                             <div class="col-sm-9">
                                                 <input type="text" name="social_linkedin" class="form-control" value="{{ \App\Models\SiteSetting::get('social_linkedin', '#linkedin') }}">
                                             </div>
                                         </div>

                                         <button type="submit" class="btn btn-primary mt-3">Save Contact Changes</button>
                                     </form>
                                 </div>
                             </div>

                            <!-- Testimonials Section -->
                            <div id="testimonials" class="tab-pane fade">
                                <div class="pt-4">
                                    <form action="{{ route('admin.settings.store') }}" method="POST">
                                        @csrf
                                        <h5 class="mb-3 text-primary">Header & Description</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Section Title</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="love_us_title" class="form-control" value="{{ \App\Models\SiteSetting::get('love_us_title', 'Students & Parents Love Us') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Section Subtitle</label>
                                            <div class="col-sm-9">
                                                <textarea name="love_us_subtitle" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('love_us_subtitle', 'Join thousands of satisfied students and parents who trust TiT Online Education') }}</textarea>
                                            </div>
                                        </div>

                                        <hr>
                                        <h5 class="mb-3 text-primary">Key Statistics</h5>
                                        <div class="row">
                                            @for($i = 1; $i <= 4; $i++)
                                            <div class="col-md-6 mb-3">
                                                <div class="card p-3">
                                                    <h6 style="color: #fff;">Stat {{ $i }}</h6>
                                                    <div class="form-group">
                                                        <label>Number</label>
                                                        <input type="text" name="love_us_stat{{ $i }}_number" class="form-control" value="{{ \App\Models\SiteSetting::get('love_us_stat'.$i.'_number', '') }}">
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Label</label>
                                                        <input type="text" name="love_us_stat{{ $i }}_label" class="form-control" value="{{ \App\Models\SiteSetting::get('love_us_stat'.$i.'_label', '') }}">
                                                    </div>
                                                </div>
                                            </div>
                                            @endfor
                                        </div>

                                        <hr>
                                        <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                                            Testimonials Manager
                                            <button type="button" id="add-testimonial" class="btn btn-info btn-xs">+ Add New</button>
                                        </h5>

                                        <div id="testimonials-repeater">
                                            <!-- Testimonials will be injected here by JS -->
                                        </div>

                                        <textarea name="love_us_testimonials" id="love_us_testimonials_hidden" class="d-none">{{ \App\Models\SiteSetting::get('love_us_testimonials', '[]') }}</textarea>

                                        <button type="submit" class="btn btn-primary mt-3">Save Testimonials Changes</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Footer & General -->
                            <div id="footer" class="tab-pane fade">
                                <div class="pt-4">
                                    <form action="{{ route('admin.settings.store') }}" method="POST">
                                        @csrf
                                        <h5 class="mb-3 text-primary">Footer Identity</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Footer Logo Text</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_logo_text" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_logo_text', 'TiT') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Footer Tagline</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_tagline" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_tagline', 'Your Education..! Our Priority..!') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Footer Description</label>
                                            <div class="col-sm-9">
                                                <textarea name="footer_description" class="form-control" rows="3">{{ \App\Models\SiteSetting::get('footer_description', "Sri Lanka's trusted leader in online tuition. We ensure student success through personalized learning and comprehensive support.") }}</textarea>
                                            </div>
                                        </div>

                                        <hr>
                                        <h5 class="mb-3 text-primary">Legal Links</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Privacy Policy Link</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_privacy_link" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_privacy_link', '#privacy') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Terms & Conditions Link</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_terms_link" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_terms_link', '#terms') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Refund Policy Link</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_refund_link" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_refund_link', '#refund') }}">
                                            </div>
                                        </div>

                                        <hr>
                                        <h5 class="mb-3 text-primary">Quick Links</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Gallery Link</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_gallery_link" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_gallery_link', '#gallery') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Apply as Tutor Link</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_tutor_link" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_tutor_link', '#tutor') }}">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                             <label class="col-sm-3 col-form-label">Register Student Link</label>
                                             <div class="col-sm-9">
                                                 <input type="text" name="footer_register_link" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_register_link', '#register') }}">
                                             </div>
                                         </div>

                                         <hr>
                                         <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                                            Student Toolkit Items
                                            <button type="button" id="add-toolkit-item" class="btn btn-info btn-xs">+ Add Item</button>
                                         </h5>
                                         <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Toolkit Title</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_toolkit_title" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_toolkit_title', 'Student Toolkit') }}">
                                            </div>
                                         </div>
                                         <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Toolkit Subtitle</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_toolkit_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_toolkit_desc', 'Essential resources for your learning journey') }}">
                                            </div>
                                         </div>
                                         <div id="toolkit-repeater">
                                            <!-- Toolkit items will be injected here by JS -->
                                         </div>
                                         <textarea name="footer_toolkit_items" id="footer_toolkit_items_hidden" class="d-none">{{ \App\Models\SiteSetting::get('footer_toolkit_items', '[]') }}</textarea>

                                        <hr>
                                        <h5 class="mb-3 text-primary">Copyright</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-3 col-form-label">Copyright Text</label>
                                            <div class="col-sm-9">
                                                <input type="text" name="footer_copyright" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_copyright', 'Copyrights © 2025 TiT. All rights reserved by TiT Online Education (PVT) Ltd.') }}">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary mt-3">Save Footer Changes</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Testimonials Repeater Logic ---
        const hiddenTextarea = document.getElementById('love_us_testimonials_hidden');
        const addButton = document.getElementById('add-testimonial');
        let testimonials = [];
        try { testimonials = JSON.parse(hiddenTextarea.value || '[]'); } catch (e) { testimonials = []; }

        function syncTestimonials() {
            const items = [];
            document.querySelectorAll('.love-us-testimonial-item').forEach(el => {
                items.push({
                    name: el.querySelector('.t-name').value,
                    role: el.querySelector('.t-role').value,
                    student: el.querySelector('.t-student').value,
                    rating: el.querySelector('.t-rating').value,
                    comment: el.querySelector('.t-comment').value,
                    image: el.querySelector('.t-image').value
                });
            });
            hiddenTextarea.value = JSON.stringify(items);
        }

        function createTestimonialItem(data = {}) {
            const id = Date.now() + Math.random().toString(36).substr(2, 9);
            const html = `
                <div class="love-us-testimonial-item" id="item-${id}">
                    <span class="remove-testimonial" onclick="document.getElementById('item-${id}').remove(); window.syncTestimonials();">
                        <i class="flaticon-381-close"></i>
                    </span>
                    <div class="row align-items-center">
                        <div class="col-md-2 text-center">
                            <label>Photo</label>
                            <div class="t-image-preview-wrapper mx-auto" onclick="document.getElementById('file-${id}').click()">
                                <img src="${data.image || 'https://via.placeholder.com/150'}" class="t-image-preview" id="preview-${id}">
                                <div class="t-image-overlay">
                                    <i class="fa fa-camera"></i>
                                </div>
                            </div>
                            <input type="file" id="file-${id}" class="d-none" accept="image/*" onchange="window.uploadTestimonialImage(this, '${id}')">
                            <input type="hidden" class="t-image" value="${data.image || ''}">
                            <div class="upload-status mt-1" id="status-${id}" style="font-size: 0.625rem;"></div>
                        </div>
                        <div class="col-md-10">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label>Full Name</label>
                                    <input type="text" class="form-control form-control-sm t-name" value="${data.name || ''}" placeholder="e.g. John Doe" oninput="window.syncTestimonials()">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label>Role</label>
                                    <input type="text" class="form-control form-control-sm t-role" value="${data.role || ''}" placeholder="e.g. Parent" oninput="window.syncTestimonials()">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label>Student/Grade Info</label>
                                    <input type="text" class="form-control form-control-sm t-student" value="${data.student || ''}" placeholder="e.g. Grade 10 Student" oninput="window.syncTestimonials()">
                                </div>
                                <div class="col-md-2 mb-2">
                                    <label>Rating (1-5)</label>
                                    <input type="number" class="form-control form-control-sm t-rating" value="${data.rating || 5}" min="1" max="5" oninput="window.syncTestimonials()">
                                </div>
                                <div class="col-md-10">
                                    <label>Review Comment</label>
                                    <textarea class="form-control form-control-sm t-comment" rows="2" placeholder="Write the review here..." oninput="window.syncTestimonials()">${data.comment || ''}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            document.getElementById('testimonials-repeater').insertAdjacentHTML('beforeend', html);
        }

        // --- Onboarding Repeater Logic ---
        const onboardingHidden = document.getElementById('onboarding_steps_hidden');
        const addOnboardingBtn = document.getElementById('add-onboarding-step');
        const onboardingRepeater = document.getElementById('onboarding-repeater');
        let onboardingSteps = [];
        try { onboardingSteps = JSON.parse(onboardingHidden.value || '[]'); } catch (e) { onboardingSteps = []; }

        function syncOnboarding() {
            const items = [];
            document.querySelectorAll('.onboarding-item').forEach(el => {
                items.push({
                    id: el.querySelector('.o-number').value,
                    title: el.querySelector('.o-title').value,
                    description: el.querySelector('.o-description').value,
                    icon: el.querySelector('.o-icon').value
                });
            });
            onboardingHidden.value = JSON.stringify(items);
        }

        function createOnboardingItem(data = {}) {
            const id = Date.now() + Math.random().toString(36).substr(2, 9);
            const commonIcons = [
                {val: 'https://cdn.lordicon.com/wjyqkiew.json', label: 'Registration (Pencil)'},
                {val: 'https://cdn.lordicon.com/zpxybbhl.json', label: 'Consultation (User)'},
                {val: 'https://cdn.lordicon.com/dxjqoygy.json', label: 'Learning (Books)'},
                {val: 'https://cdn.lordicon.com/yqzmiobz.json', label: 'Checkmark'},
                {val: 'https://cdn.lordicon.com/osuxyevn.json', label: 'Instruction'}
            ];
            let iconOptions = '<option value="">-- Select Icon --</option>';
            let isCustomIcon = data.icon && !commonIcons.some(i => i.val === data.icon);
            commonIcons.forEach(i => {
                iconOptions += `<option value="${i.val}" ${data.icon === i.val ? 'selected' : ''}>${i.label}</option>`;
            });
            iconOptions += `<option value="custom" ${isCustomIcon ? 'selected' : ''}>Custom URL...</option>`;

            const html = `
                <div class="testimonial-item onboarding-item" id="onb-${id}">
                    <span class="remove-testimonial" onclick="document.getElementById('onb-${id}').remove(); window.syncOnboarding();">
                        <i class="flaticon-381-close"></i>
                    </span>
                    <div class="row">
                        <div class="col-md-2 mb-2">
                            <label>Step # (e.g. 01)</label>
                            <input type="text" class="form-control form-control-sm o-number" value="${data.id || ''}" placeholder="01" oninput="window.syncOnboarding()">
                        </div>
                        <div class="col-md-5 mb-2">
                            <label>Step Title</label>
                            <input type="text" class="form-control form-control-sm o-title" value="${data.title || ''}" placeholder="e.g. Register" oninput="window.syncOnboarding()">
                        </div>
                        <div class="col-md-5 mb-2">
                            <label>LordIcon Selection</label>
                            <select class="form-control form-control-sm o-icon-select" onchange="const inp = document.getElementById('o-icon-custom-${id}'); if(this.value === 'custom'){ inp.classList.remove('d-none'); } else { inp.classList.add('d-none'); inp.value = this.value; window.syncOnboarding(); }">
                                ${iconOptions}
                            </select>
                            <input type="text" id="o-icon-custom-${id}" class="form-control form-control-sm o-icon mt-1 ${isCustomIcon ? '' : 'd-none'}" value="${data.icon || 'https://cdn.lordicon.com/wjyqkiew.json'}" placeholder="Paste LordIcon JSON URL here" oninput="window.syncOnboarding()">
                        </div>
                        <div class="col-md-12">
                            <label>Description</label>
                            <textarea class="form-control form-control-sm o-description" rows="2" placeholder="Describe the step..." oninput="window.syncOnboarding()">${data.description || ''}</textarea>
                        </div>
                    </div>
                </div>
            `;
            onboardingRepeater.insertAdjacentHTML('beforeend', html);
        }

        // --- Why Choose Us Repeater Logic ---
        const whyHidden = document.getElementById('why_reasons_hidden');
        const addWhyBtn = document.getElementById('add-why-item');
        const whyRepeater = document.getElementById('why-repeater');
        let whyReasons = [];
        try { whyReasons = JSON.parse(whyHidden.value || '[]'); } catch (e) { whyReasons = []; }

        function syncWhy() {
            const items = [];
            document.querySelectorAll('.why-item').forEach(el => {
                items.push({
                    title: el.querySelector('.w-title').value,
                    subtitle: el.querySelector('.w-subtitle').value,
                    description: el.querySelector('.w-description').value,
                    gradient: el.querySelector('.w-gradient').value,
                    icon: el.querySelector('.w-icon').value
                });
            });
            whyHidden.value = JSON.stringify(items);
        }

        function createWhyItem(data = {}) {
            const id = Date.now() + Math.random().toString(36).substr(2, 9);
            const gradients = ['gradient-1', 'gradient-2', 'gradient-3'];
            let gradientOptions = '';
            gradients.forEach(g => {
                gradientOptions += `<option value="${g}" ${data.gradient === g ? 'selected' : ''}>${g.replace('-', ' ')}</option>`;
            });

            const commonIcons = [
                {val: 'https://cdn.lordicon.com/wxnxiano.json', label: 'படிப்பிற்கு (Books)'},
                {val: 'https://cdn.lordicon.com/nocovwne.json', label: 'பரிசிற்கு (Award)'},
                {val: 'https://cdn.lordicon.com/yqzmiobz.json', label: 'சரியான குறி (Checkmark)'},
                {val: 'https://cdn.lordicon.com/osuxyevn.json', label: 'ஆசிரியருக்கு (Instruction)'},
                {val: 'https://cdn.lordicon.com/qhviklyi.json', label: 'பணத்திற்கு (Pricing)'},
                {val: 'https://cdn.lordicon.com/hrjifpbq.json', label: 'உதவிக்கு (Support)'}
            ];
            let iconOptions = '<option value="">-- Select Icon --</option>';
            let isCustomIcon = data.icon && !commonIcons.some(i => i.val === data.icon);
            commonIcons.forEach(i => {
                iconOptions += `<option value="${i.val}" ${data.icon === i.val ? 'selected' : ''}>${i.label}</option>`;
            });
            iconOptions += `<option value="custom" ${isCustomIcon ? 'selected' : ''}>Custom URL...</option>`;

            const html = `
                <div class="testimonial-item why-item" id="why-${id}">
                    <span class="remove-testimonial" onclick="document.getElementById('why-${id}').remove(); window.syncWhy();">
                        <i class="flaticon-381-close"></i>
                    </span>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label>Title</label>
                            <input type="text" class="form-control form-control-sm w-title" value="${data.title || ''}" placeholder="e.g. Quality Learning" oninput="window.syncWhy()">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label>Subtitle</label>
                            <input type="text" class="form-control form-control-sm w-subtitle" value="${data.subtitle || ''}" placeholder="e.g. Proven Results" oninput="window.syncWhy()">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label>Gradient</label>
                            <select class="form-control form-control-sm w-gradient" onchange="window.syncWhy()">
                                ${gradientOptions}
                            </select>
                        </div>
                         <div class="col-md-3 mb-2">
                            <label>LordIcon Selection</label>
                            <select class="form-control form-control-sm w-icon-select" onchange="const inp = document.getElementById('w-icon-custom-${id}'); if(this.value === 'custom'){ inp.classList.remove('d-none'); } else { inp.classList.add('d-none'); inp.value = this.value; window.syncWhy(); }">
                                ${iconOptions}
                            </select>
                            <input type="text" id="w-icon-custom-${id}" class="form-control form-control-sm w-icon mt-1 ${isCustomIcon ? '' : 'd-none'}" value="${data.icon || ''}" placeholder="Paste LordIcon JSON URL here" oninput="window.syncWhy()">
                        </div>
                        <div class="col-md-12">
                            <label>Description</label>
                            <textarea class="form-control form-control-sm w-description" rows="2" placeholder="Describe this reason..." oninput="window.syncWhy()">${data.description || ''}</textarea>
                        </div>
                    </div>
                </div>
            `;
            whyRepeater.insertAdjacentHTML('beforeend', html);
        }

        // --- Why Benefits Repeater logic ---
        const whyBenefitsHidden = document.getElementById('why_benefits_hidden');
        const addWhyBenefitBtn = document.getElementById('add-why-benefit');
        const whyBenefitsRepeater = document.getElementById('why-benefits-repeater');
        let whyBenefitsArr = [];
        try { whyBenefitsArr = JSON.parse(whyBenefitsHidden.value || '[]'); } catch (e) { whyBenefitsArr = []; }

        function createWhyBenefitItem(data = {}) {
            const id = Date.now() + Math.random().toString(36).substr(2, 9);
            const html = `
                <div class="testimonial-item why-benefit-item" id="wb-${id}">
                    <span class="remove-testimonial" onclick="document.getElementById('wb-${id}').remove(); window.syncWhyBenefits();">
                        <i class="flaticon-381-close"></i>
                    </span>
                    <div class="row">
                        <div class="col-md-12">
                            <label>Benefit Text</label>
                            <input type="text" class="form-control form-control-sm wb-text" value="${data.text || ''}" placeholder="e.g. Personalized Learning" oninput="window.syncWhyBenefits()">
                        </div>
                    </div>
                </div>
            `;
            whyBenefitsRepeater.insertAdjacentHTML('beforeend', html);
        }

        function syncWhyBenefits() {
            const items = [];
            document.querySelectorAll('.why-benefit-item').forEach(el => {
                items.push({
                    text: el.querySelector('.wb-text').value
                });
            });
            whyBenefitsHidden.value = JSON.stringify(items);
        }

        window.syncWhyBenefits = syncWhyBenefits;
        document.getElementById('why-benefits-repeater').innerHTML = ''; // Prevent duplicates on hot-reload
        if (whyBenefitsArr.length > 0) whyBenefitsArr.forEach(b => createWhyBenefitItem(b));
        addWhyBenefitBtn.addEventListener('click', () => { createWhyBenefitItem(); syncWhyBenefits(); });

        // --- Classes Repeater Logic ---
        const classesHidden = document.getElementById('classes_types_hidden');
        const addClassBtn = document.getElementById('add-class-type');
        const classesRepeater = document.getElementById('classes-repeater');
        let classesTypes = [];
        try { classesTypes = JSON.parse(classesHidden.value || '[]'); } catch (e) { classesTypes = []; }

        function syncClasses() {
            const items = [];
            document.querySelectorAll('.class-type-item').forEach(el => {
                const featEl = el.querySelector('.c-features');
                const subjEl = el.querySelector('.c-subjects');
                items.push({
                    title: el.querySelector('.c-title').value,
                    description: el.querySelector('.c-description').value,
                    color: el.querySelector('.c-color').value,
                    image: el.querySelector('.c-image').value,
                    stars: el.querySelector('.c-stars').value,
                    features: featEl ? featEl.value : '',
                    subjects: subjEl ? subjEl.value : ''
                });
            });
            classesHidden.value = JSON.stringify(items);
        }

        function createClassTypeItem(data = {}) {
            const id = Date.now() + Math.random().toString(36).substr(2, 9);
            const colors = [
                {val: '#EB8153', label: 'Orange (Direct)'},
                {val: '#667eea', label: 'Blue (Online)'},
                {val: '#764ba2', label: 'Purple'},
                {val: '#2ecc71', label: 'Green'},
                {val: '#e74c3c', label: 'Red'}
            ];
            let colorOptions = '';
            colors.forEach(c => {
                colorOptions += `<option value="${c.val}" ${data.color === c.val ? 'selected' : ''}>${c.label}</option>`;
            });

            const html = `
                <div class="testimonial-item class-type-item mb-4" id="cls-${id}" style="border: 1px solid rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; position: relative; background: rgba(255,255,255,0.02);">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="text-white mb-0">Class Configuration</h6>
                        <button type="button" class="btn btn-danger btn-xs" onclick="window.removeClassType('${id}')">
                            <i class="fa fa-trash mr-1"></i> 
                        </button>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Course Image</label>
                            <div class="clickable-logo-preview mb-2" onclick="document.getElementById('img-input-${id}').click()" style="width: 100%; height: 6.25rem;">
                                <img src="${data.image || ''}" id="preview-cls-${id}" style="max-height: 100%; max-width: 100%; ${data.image ? '' : 'display:none;'}">
                                <div class="logo-preview-overlay" style="${data.image ? 'opacity:0;' : 'opacity:1;'}">
                                    <i class="fa fa-camera"></i> ${data.image ? 'Change' : 'Upload'}
                                </div>
                            </div>
                            <input type="file" id="img-input-${id}" class="d-none" onchange="window.uploadClassImage(this, '${id}')" accept="image/*">
                            <input type="hidden" class="c-image" value="${data.image || ''}">
                            <div id="status-cls-${id}" class="help-text text-center"></div>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <label>Course Title</label>
                                    <input type="text" class="form-control form-control-sm c-title" value="${data.title || ''}" placeholder="e.g. Sri Lankan Syllabus" oninput="window.syncClasses()">
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>Color Theme</label>
                                    <select class="form-control form-control-sm c-color" onchange="window.syncClasses()">
                                        ${colorOptions}
                                    </select>
                                </div>
                                <div class="col-md-3 mb-2">
                                    <label>Star Rating</label>
                                    <select class="form-control form-control-sm c-stars" onchange="window.syncClasses()">
                                        <option value="5" ${data.stars == 5 ? 'selected' : ''}>5 Stars</option>
                                        <option value="4" ${data.stars == 4 ? 'selected' : ''}>4 Stars</option>
                                        <option value="3" ${data.stars == 3 ? 'selected' : ''}>3 Stars</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-2">
                                    <label>Description</label>
                                    <textarea class="form-control form-control-sm c-description" rows="2" placeholder="Describe this course type..." oninput="window.syncClasses()">${data.description || ''}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            classesRepeater.insertAdjacentHTML('beforeend', html);
        }

        window.uploadClassImage = function(input, id) {
            if (!input.files || !input.files[0]) return;
            const statusEl = document.getElementById(`status-cls-${id}`);
            const previewEl = document.getElementById(`preview-cls-${id}`);
            const hiddenInput = document.querySelector(`#cls-${id} .c-image`);
            const overlay = document.querySelector(`#cls-${id} .logo-preview-overlay`);
            
            // Delete existing image
            let existingImagePath = hiddenInput.value;
            if (existingImagePath && existingImagePath.includes('uploads/settings/')) {
                const fd = new FormData();
                fd.append('_token', '{{ csrf_token() }}');
                fd.append('image_path', existingImagePath);
                fetch('{{ route("admin.settings.delete-image") }}', {
                    method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).catch(e => console.error(e));
            }

            const formData = new FormData();
            formData.append('image', input.files[0]);
            formData.append('_token', '{{ csrf_token() }}');
            statusEl.innerHTML = '<span class="text-info">Uploading...</span>';
            fetch('{{ route("admin.settings.upload") }}', {
                method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    previewEl.src = data.path; 
                    previewEl.style.display = 'block';
                    hiddenInput.value = data.path;
                    overlay.style.opacity = '0';
                    statusEl.innerHTML = '<span class="text-success"><i class="fa fa-check-circle"></i> Uploaded</span>';
                    window.syncClasses();
                } else { alert(data.message || 'Upload failed'); statusEl.innerHTML = ''; }
            }).catch(e => { console.error(e); statusEl.innerHTML = ''; });
        };

        window.syncTestimonials = syncTestimonials;
        window.syncOnboarding = syncOnboarding;
        window.syncWhy = syncWhy;
        window.syncClasses = syncClasses;

        window.removeClassType = function(id) {
            if(confirm('Delete this class type?')){
                const el = document.getElementById('cls-' + id);
                const imgPath = el.querySelector('.c-image').value;
                if (imgPath && imgPath.includes('uploads/settings/')) {
                    const fd = new FormData();
                    fd.append('_token', '{{ csrf_token() }}');
                    fd.append('image_path', imgPath);
                    fetch('{{ route("admin.settings.delete-image") }}', {
                        method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).catch(e => console.error(e));
                }
                el.remove(); 
                window.syncClasses();
            }
        };

        window.uploadTestimonialImage = function(input, id) {
            if (!input.files || !input.files[0]) return;
            const statusEl = document.getElementById(`status-${id}`);
            const previewEl = document.getElementById(`preview-${id}`);
            const hiddenInput = document.querySelector(`#item-${id} .t-image`);
            const formData = new FormData();
            formData.append('image', input.files[0]);
            formData.append('_token', '{{ csrf_token() }}');
            statusEl.innerHTML = '<span class="text-info">Uploading...</span>';
            fetch('{{ route("admin.settings.upload") }}', {
                method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    previewEl.src = data.path; hiddenInput.value = data.path;
                    statusEl.innerHTML = '<span class="text-success"><i class="fa fa-check-circle"></i> Uploaded</span>';
                    window.syncTestimonials();
                } else { alert(data.message || 'Upload failed'); }
            }).catch(e => console.error(e));
        };

        // Initial renders
        document.getElementById('testimonials-repeater').innerHTML = '';
        if (testimonials.length > 0) testimonials.forEach(t => createTestimonialItem(t));
        
        document.getElementById('onboarding-repeater').innerHTML = '';
        if (onboardingSteps.length > 0) onboardingSteps.forEach(s => createOnboardingItem(s));
        
        document.getElementById('why-repeater').innerHTML = '';
        if (whyReasons.length > 0) whyReasons.forEach(r => createWhyItem(r));
        
        document.getElementById('classes-repeater').innerHTML = '';
        if (classesTypes.length > 0) classesTypes.forEach(c => createClassTypeItem(c));

        addButton.addEventListener('click', () => { createTestimonialItem(); syncTestimonials(); });
        addOnboardingBtn.addEventListener('click', () => { createOnboardingItem(); syncOnboarding(); });
        addWhyBtn.addEventListener('click', () => { createWhyItem(); syncWhy(); });
        addClassBtn.addEventListener('click', () => { createClassTypeItem(); syncClasses(); });

        // --- Mobile Features Repeater Logic ---
        const mobileHidden = document.getElementById('mobile_features_hidden');
        const addMobileBtn = document.getElementById('add-mobile-feature');
        const mobileRepeater = document.getElementById('mobile-features-repeater');
        let mobileFeatures = [];
        try { mobileFeatures = JSON.parse(mobileHidden.value || '[]'); } catch (e) { mobileFeatures = []; }

        function syncMobileFeatures() {
            const items = [];
            document.querySelectorAll('.mobile-feature-item').forEach(el => {
                items.push({
                    text: el.querySelector('.mf-text').value
                });
            });
            mobileHidden.value = JSON.stringify(items);
        }

        function createMobileFeatureItem(data = {}) {
            const id = Date.now() + Math.random().toString(36).substr(2, 9);
            const html = `
                <div class="testimonial-item mobile-feature-item" id="mf-${id}">
                    <span class="remove-testimonial" onclick="document.getElementById('mf-${id}').remove(); window.syncMobileFeatures();">
                        <i class="flaticon-381-close"></i>
                    </span>
                    <div class="row">
                        <div class="col-md-12">
                            <label>Feature Text</label>
                            <input type="text" class="form-control form-control-sm mf-text" value="${data.text || ''}" placeholder="e.g. Free Learning Materials" oninput="window.syncMobileFeatures()">
                        </div>
                    </div>
                </div>
            `;
            mobileRepeater.insertAdjacentHTML('beforeend', html);
        }

        // --- Mobile Screens Repeater Logic ---
        const mobileScreensHidden = document.getElementById('mobile_screens_hidden');
        const addMobileScreenBtn = document.getElementById('add-mobile-screen');
        const mobileScreensRepeater = document.getElementById('mobile-screens-repeater');
        let mobileScreens = [];
        try { mobileScreens = JSON.parse(mobileScreensHidden.value || '[]'); } catch (e) { mobileScreens = []; }

        function syncMobileScreens() {
            const items = [];
            document.querySelectorAll('.mobile-screen-item').forEach(el => {
                items.push({
                    title: el.querySelector('.ms-title').value,
                    icon: el.querySelector('.ms-icon').value
                });
            });
            mobileScreensHidden.value = JSON.stringify(items);
        }

        function createMobileScreenItem(data = {}) {
            const id = Date.now() + Math.random().toString(36).substr(2, 9);
            const html = `
                <div class="testimonial-item mobile-screen-item" id="ms-${id}">
                    <span class="remove-testimonial" onclick="document.getElementById('ms-${id}').remove(); window.syncMobileScreens();">
                        <i class="flaticon-381-close"></i>
                    </span>
                    <div class="row">
                        <div class="col-md-8">
                            <label>Screen Title</label>
                            <input type="text" class="form-control form-control-sm ms-title" value="${data.title || ''}" placeholder="e.g. Live Classes" oninput="window.syncMobileScreens()">
                        </div>
                        <div class="col-md-4">
                            <label>Icon/Emoji</label>
                            <input type="text" class="form-control form-control-sm ms-icon" value="${data.icon || '📚'}" placeholder="Emoji or Icon" oninput="window.syncMobileScreens()">
                        </div>
                    </div>
                </div>
            `;
            mobileScreensRepeater.insertAdjacentHTML('beforeend', html);
        }

        window.syncMobileScreens = syncMobileScreens;
        document.getElementById('mobile-screens-repeater').innerHTML = ''; // Prevent duplicates
        if (mobileScreens.length > 0) mobileScreens.forEach(s => createMobileScreenItem(s));
        addMobileScreenBtn.addEventListener('click', () => { createMobileScreenItem(); syncMobileScreens(); });

        // --- Student Toolkit Repeater Logic ---
        const toolkitHidden = document.getElementById('footer_toolkit_items_hidden');
        const addToolkitBtn = document.getElementById('add-toolkit-item');
        const toolkitRepeater = document.getElementById('toolkit-repeater');
        let toolkitItems = [];
        try { toolkitItems = JSON.parse(toolkitHidden.value || '[]'); } catch (e) { toolkitItems = []; }

        function syncToolkit() {
            const items = [];
            document.querySelectorAll('.toolkit-item').forEach(el => {
                items.push({
                    title: el.querySelector('.tk-title').value,
                    tag: el.querySelector('.tk-tag').value,
                    description: el.querySelector('.tk-description').value,
                    color: el.querySelector('.tk-color').value,
                    icon: el.querySelector('.tk-icon').value
                });
            });
            toolkitHidden.value = JSON.stringify(items);
        }

        function createToolkitItem(data = {}) {
            const id = Date.now() + Math.random().toString(36).substr(2, 9);
            const html = `
                <div class="testimonial-item toolkit-item" id="tk-${id}">
                    <span class="remove-testimonial" onclick="document.getElementById('tk-${id}').remove(); window.syncToolkit();">
                        <i class="flaticon-381-close"></i>
                    </span>
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label>Title</label>
                            <input type="text" class="form-control form-control-sm tk-title" value="${data.title || ''}" placeholder="e.g. Past Papers" oninput="window.syncToolkit()">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Tag</label>
                            <input type="text" class="form-control form-control-sm tk-tag" value="${data.tag || ''}" placeholder="e.g. Academic" oninput="window.syncToolkit()">
                        </div>
                        <div class="col-md-12 mb-2">
                            <label>Description</label>
                            <textarea class="form-control form-control-sm tk-description" rows="2" placeholder="Describe this tool..." oninput="window.syncToolkit()">${data.description || ''}</textarea>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>Icon Color (Hex)</label>
                            <input type="text" class="form-control form-control-sm tk-color" value="${data.color || '#4f0bd9'}" oninput="window.syncToolkit()">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label>LordIcon URL (Optional)</label>
                            <input type="text" class="form-control form-control-sm tk-icon" value="${data.icon || ''}" placeholder="e.g. https://cdn.lordicon.com/fkdkvhlp.json" oninput="window.syncToolkit()">
                        </div>
                    </div>
                </div>
            `;
            toolkitRepeater.insertAdjacentHTML('beforeend', html);
        }

        window.syncMobileFeatures = syncMobileFeatures;
        window.syncToolkit = syncToolkit;

        if (mobileFeatures.length > 0) mobileFeatures.forEach(f => createMobileFeatureItem(f));
        if (toolkitItems.length > 0) toolkitItems.forEach(i => createToolkitItem(i));

        addMobileBtn.addEventListener('click', () => { createMobileFeatureItem(); syncMobileFeatures(); });
        addToolkitBtn.addEventListener('click', () => { createToolkitItem(); syncToolkit(); });

        window.previewLogo = function(input, type) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.getElementById(`${type}-logo-preview-container`);
                    container.innerHTML = `
                        <img src="${e.target.result}" alt="${type} Logo" style="max-height: 5rem; max-width: 100%;">
                        <div class="logo-preview-overlay" onclick="removeLogo('${type}')">Remove Logo</div>
                    `;
                    document.getElementById(`remove_${type}_logo`).value = '0';
                }
                reader.readAsDataURL(input.files[0]);
            }
        };

        // ── Hero Background & Slider Management ──
        window.toggleHeroBgMode = function(mode) {
            const singlePanel = document.getElementById('hero_single_panel');
            const sliderPanel = document.getElementById('hero_slider_panel');
            const overlaySettings = document.getElementById('hero_overlay_settings');

            if (mode === 'single') {
                if (singlePanel) singlePanel.style.display = 'flex';
                if (sliderPanel) sliderPanel.style.display = 'none';
                if (overlaySettings) overlaySettings.style.display = 'block';
            } else if (mode === 'slider') {
                if (singlePanel) singlePanel.style.display = 'none';
                if (sliderPanel) sliderPanel.style.display = 'block';
                if (overlaySettings) overlaySettings.style.display = 'block';
            } else {
                // Default
                if (singlePanel) singlePanel.style.display = 'none';
                if (sliderPanel) sliderPanel.style.display = 'none';
                if (overlaySettings) overlaySettings.style.display = 'none';
            }
        };

        window.removeHeroSingleImage = function(btn) {
            const container = btn.closest('.col-sm-9').querySelector('.image-picker-container');
            const preview = container.querySelector('.image-picker-preview');
            const placeholder = container.querySelector('.image-picker-placeholder');
            const inputField = document.getElementById('hero_bg_image_input');

            if (preview) { preview.src = ''; preview.style.display = 'none'; }
            if (placeholder) { placeholder.style.display = 'block'; }
            if (inputField) { inputField.value = ''; }
            btn.style.display = 'none';
        };

        window.uploadHeroImage = async function(input, previewId, targetInputId, isSlider = false) {
            const file = input.files[0];
            if (!file) return;

            const container = input.closest('.image-picker-container');
            const loader = container.querySelector('.upload-loading');
            const preview = container.querySelector('.image-picker-preview');
            const placeholder = container.querySelector('.image-picker-placeholder');

            if (loader) loader.style.display = 'flex';

            const formData = new FormData();
            formData.append('image', file);

            try {
                const response = await fetch('{{ route("admin.settings.upload") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    if (preview) {
                        preview.src = data.path;
                        preview.style.display = 'block';
                    }
                    if (placeholder) {
                        placeholder.style.display = 'none';
                    }
                    if (targetInputId) {
                        const targetInput = document.getElementById(targetInputId);
                        if (targetInput) targetInput.value = data.path;
                    }
                    if (isSlider) {
                        const hiddenInput = container.closest('.row').querySelector('.slider-img-url');
                        if (hiddenInput) hiddenInput.value = data.path;
                        window.syncHeroSlider();
                    } else {
                        const removeBtn = container.closest('.col-sm-9').querySelector('button.btn-danger');
                        if (removeBtn) removeBtn.style.display = 'inline-block';
                    }
                } else {
                    alert(data.message || 'Upload failed');
                }
            } catch (error) {
                console.error('Error uploading image:', error);
                alert('An error occurred during upload');
            } finally {
                if (loader) loader.style.display = 'none';
                input.value = '';
            }
        };

        window.addHeroSlideRow = function() {
            const container = document.getElementById('hero_slider_container');
            if (!container) return;

            const html = `
                <div class="dynamic-slider-row mb-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 0.75rem; padding: 1rem; position: relative;">
                    <button type="button" class="btn btn-danger btn-xs" style="position: absolute; top: 0.75rem; right: 0.75rem;" onclick="this.closest('.dynamic-slider-row').remove(); window.syncHeroSlider();"><i class="la la-trash"></i> Remove</button>
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <div class="image-picker-container" style="height: 7rem; width: 100%; position: relative; background: rgba(0,0,0,0.2); border: 1px dashed rgba(255,255,255,0.15); border-radius: 0.5rem; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden;" onclick="this.querySelector('input[type=file]').click()">
                                <div class="upload-loading" style="display:none; position: absolute; inset:0; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; z-index: 5;"><div class="spinner-border text-primary"></div></div>
                                <div class="image-picker-placeholder">
                                    <i class="la la-image"></i> Select Slide Image
                                </div>
                                <img src="" class="image-picker-preview" style="width: 100%; height: 100%; object-fit: cover; display:none;">
                                <input type="file" style="display:none;" accept="image/*" onchange="uploadHeroImage(this, null, null, true)">
                            </div>
                            <input type="hidden" class="slider-img-url" value="">
                        </div>
                        <div class="col-md-6">
                            <label style="font-size: 0.8rem; color: rgba(255,255,255,0.7);">Optional Caption / Title</label>
                            <input type="text" class="form-control form-control-sm slider-img-title" placeholder="e.g. Virtual Classroom Session" oninput="window.syncHeroSlider()">
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        };

        window.syncHeroSlider = function() {
            const items = [];
            document.querySelectorAll('.dynamic-slider-row').forEach(row => {
                const urlEl = row.querySelector('.slider-img-url');
                const titleEl = row.querySelector('.slider-img-title');
                const imgUrl = urlEl ? urlEl.value : '';
                const title = titleEl ? titleEl.value : '';
                if (imgUrl) {
                    items.push({ image: imgUrl, title: title });
                }
            });
            const jsonField = document.getElementById('hero_slider_images_json');
            if (jsonField) jsonField.value = JSON.stringify(items);
        };
    });
</script>
@endpush

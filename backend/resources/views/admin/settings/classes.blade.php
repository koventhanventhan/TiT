@extends('layouts.admin')

@section('title', 'Classes Settings')

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

    .class-edit-section {
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

    .class-edit-section h5 {
        color: #EB8153 !important;
        font-weight: 600;
        margin-bottom: 1.25rem;
        border-bottom: 1.0px solid rgba(255,255,255,0.1);
        padding-bottom: 0.625rem;
    }

    .form-control, .bootstrap-select .dropdown-toggle {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        border-radius: 0.5rem !important;
    }

    .form-control:focus {
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

    .btn-info.btn-xs {
        background-color: #EB8153;
        border-color: #EB8153;
        color: #fff;
        border-radius: 0.375rem;
        padding: 0.3125rem 0.75rem;
    }

    .btn-info.btn-xs:hover {
        background-color: #d96e42;
        border-color: #d96e42;
    }

    hr {
        border-top: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .text-primary {
        color: #EB8153 !important;
    }

    .btn-primary:hover {
        transform: translateY(-0.125rem);
        box-shadow: 0 0.25rem 0.75rem rgba(102, 126, 234, 0.4) !important;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Classes Settings</h4>
            <div>
                <a href="{{ config('services.frontend_url') }}/classes" target="_blank" class="btn btn-primary btn-sm">
                    View Classes Page
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
                <form action="{{ route('admin.settings.store') }}" method="POST">
                    @csrf
                    <h5 class="text-primary mb-4">Header Content</h5>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Main Title</label>
                                <input type="text" name="classes_title" class="form-control" value="{{ \App\Models\SiteSetting::get('classes_title', 'Explore & Enroll') }}">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Subtitle</label>
                                <textarea name="classes_subtitle" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('classes_subtitle', 'Online Tuition for all subjects - Grade 1 to Advanced Level. Group or one-on-one? We got you!') }}</textarea>
                            </div>
                        </div>
                    </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="text-primary mb-4">Online Classes Expert Layout Settings (ocl-)</h5>
                    
                    <div class="row">
                        <!-- Left Text Settings -->
                        <div class="col-md-7">
                            <div class="class-edit-section">
                                <h6 class="mb-3">Hero Text Content</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Tagline</label>
                                            <input type="text" name="ocl_tagline" class="form-control" value="{{ \App\Models\SiteSetting::get('ocl_tagline', 'TiT KALVI NILAYAM – JAFFNA') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Title Line 1 (Dark)</label>
                                            <input type="text" name="ocl_title_line1" class="form-control" value="{{ \App\Models\SiteSetting::get('ocl_title_line1', 'ONLINE') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Title Line 2 (Gold)</label>
                                            <input type="text" name="ocl_title_line2" class="form-control" value="{{ \App\Models\SiteSetting::get('ocl_title_line2', 'CLASSES') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Tamil Subtitle</label>
                                            <input type="text" name="ocl_subtitle_tamil" class="form-control" value="{{ \App\Models\SiteSetting::get('ocl_subtitle_tamil', 'இணையவழி வகுப்புகள்') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Small Secondary Text</label>
                                            <input type="text" name="ocl_sub_text" class="form-control" value="{{ \App\Models\SiteSetting::get('ocl_sub_text', 'தரம் 01 முதல் A/L வரை') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Hero Highlights (One per line)</label>
                                            <textarea name="ocl_highlights" class="form-control" rows="3">{{ \App\Models\SiteSetting::get('ocl_highlights', "LIVE CLASSES\nRECORDED CLASSES\nSTUDY MATERIAL") }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>CTA Button Text</label>
                                            <input type="text" name="ocl_cta_text" class="form-control" value="{{ \App\Models\SiteSetting::get('ocl_cta_text', 'இப்போது சேருங்கள்') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Media Settings -->
                        <div class="col-md-5">
                            <div class="class-edit-section">
                                <h6 class="mb-3">Hero Media</h6>
                                
                                <div class="form-group">
                                    <label>Main Laptop Image (ocl_hero_image)</label>
                                    @php $heroImg = \App\Models\SiteSetting::get('ocl_hero_image'); @endphp
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input image-upload" data-setting-key="ocl_hero_image" accept="image/*">
                                            <label class="custom-file-label">Choose image</label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger remove-image-btn" data-setting-key="ocl_hero_image" {{ $heroImg ? '' : 'style="display:none;"' }}>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2 preview-container" id="preview-ocl_hero_image">
                                        @if($heroImg)
                                            <img src="{{ Str::startsWith($heroImg, 'http') ? $heroImg : asset($heroImg) }}" class="img-thumbnail" style="max-height: 200px;">
                                        @endif
                                    </div>
                                    <input type="hidden" name="ocl_hero_image" id="input-ocl_hero_image" value="{{ $heroImg }}">
                                </div>

                                <div class="form-group mt-4">
                                    <label>Floating Badge Image (ocl_badge_image)</label>
                                    @php $badgeImg = \App\Models\SiteSetting::get('ocl_badge_image'); @endphp
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input image-upload" data-setting-key="ocl_badge_image" accept="image/*">
                                            <label class="custom-file-label">Choose image</label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger remove-image-btn" data-setting-key="ocl_badge_image" {{ $badgeImg ? '' : 'style="display:none;"' }}>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2 preview-container" id="preview-ocl_badge_image">
                                        @if($badgeImg)
                                            <img src="{{ Str::startsWith($badgeImg, 'http') ? $badgeImg : asset($badgeImg) }}" class="img-thumbnail" style="max-height: 100px;">
                                        @endif
                                    </div>
                                    <input type="hidden" name="ocl_badge_image" id="input-ocl_badge_image" value="{{ $badgeImg }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="class-edit-section">
                        <h6 class="mb-3">Grades Selector</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Section Title</label>
                                    <input type="text" name="ocl_grades_title" class="form-control" value="{{ \App\Models\SiteSetting::get('ocl_grades_title', 'தரம் தேர்வு செய்க') }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Grades (Comma separated)</label>
                                    <input type="text" name="ocl_grades" class="form-control" value="{{ \App\Models\SiteSetting::get('ocl_grades', '01,02,03,04,05,06,07,08,09,10,11,A/L') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="class-edit-section">
                        <h6 class="mb-3">Features, Stats & Subjects (Advanced JSON)</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Features Grid (JSON array)</label>
                                    <textarea name="ocl_features" class="form-control" rows="6">{{ \App\Models\SiteSetting::get('ocl_features', '[{"icon":"FiMonitor","title":"நேரலை வகுப்புகள்","description":"அனுபவமிக்க ஆசிரியர்களின் நேரலை வகுப்புகள்"},{"icon":"FiPlay","title":"வகுப்பு பதிவு","description":"பதிவு செய்யப்பட்ட வகுப்புகளை மீண்டும் பார்க்கலாம்"},{"icon":"FiFolder","title":"கல்வி பொருட்கள்","description":"PDF குறிப்புகள் & தேவையான படிப்பு பொருட்கள்"}]') }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Stats Bar (JSON array)</label>
                                    <textarea name="ocl_stats" class="form-control" rows="6">{{ \App\Models\SiteSetting::get('ocl_stats', '[{"icon":"FaGraduationCap","number":"3000+","label":"மாணவர்கள்"},{"icon":"FiUsers","number":"30+","label":"ஆசிரியர்கள்"},{"icon":"FiPlay","number":"500+","label":"வீடியோ வகுப்புகள்"},{"icon":"FiHeadphones","number":"24/7","label":"ஆதரவு"}]') }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Subjects Row (JSON array)</label>
                                    <textarea name="ocl_subjects" class="form-control" rows="6">{{ \App\Models\SiteSetting::get('ocl_subjects', '[{"icon":"FiBook","name":"தமிழ்"},{"icon":"FiGlobe","name":"ஆங்கிலம்"},{"icon":"FiEdit","name":"கணிதம்"},{"icon":"FiStar","name":"அறிவியல்"}]') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="text-primary mb-4">Direct Classes Expert Layout Settings (dcl-)</h5>
                    
                    <div class="row">
                        <!-- Left Text Settings -->
                        <div class="col-md-7">
                            <div class="class-edit-section">
                                <h6 class="mb-3">Hero Text Content</h6>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Tagline</label>
                                            <input type="text" name="dcl_tagline" class="form-control" value="{{ \App\Models\SiteSetting::get('dcl_tagline', 'TiT KALVI NILAYAM – JAFFNA') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Title Line 1 (Dark)</label>
                                            <input type="text" name="dcl_title_line1" class="form-control" value="{{ \App\Models\SiteSetting::get('dcl_title_line1', 'DIRECT') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Title Line 2 (Gold)</label>
                                            <input type="text" name="dcl_title_line2" class="form-control" value="{{ \App\Models\SiteSetting::get('dcl_title_line2', 'CLASSES') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Tamil Subtitle</label>
                                            <input type="text" name="dcl_subtitle_tamil" class="form-control" value="{{ \App\Models\SiteSetting::get('dcl_subtitle_tamil', 'நேரடி வகுப்புகள்') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Small Secondary Text</label>
                                            <input type="text" name="dcl_sub_text" class="form-control" value="{{ \App\Models\SiteSetting::get('dcl_sub_text', 'தரம் 01 முதல் A/L வரை') }}">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>Hero Highlights (One per line)</label>
                                            <textarea name="dcl_highlights" class="form-control" rows="3">{{ \App\Models\SiteSetting::get('dcl_highlights', "IN-PERSON CLASSES\nEXPERT TUTORS\nSTUDY MATERIAL") }}</textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <label>CTA Button Text</label>
                                            <input type="text" name="dcl_cta_text" class="form-control" value="{{ \App\Models\SiteSetting::get('dcl_cta_text', 'இப்போது சேருங்கள்') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Media Settings -->
                        <div class="col-md-5">
                            <div class="class-edit-section">
                                <h6 class="mb-3">Hero Media</h6>
                                
                                <div class="form-group">
                                    <label>Main Background Image (dcl_hero_image)</label>
                                    @php $dclHeroImg = \App\Models\SiteSetting::get('dcl_hero_image'); @endphp
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input image-upload" data-setting-key="dcl_hero_image" accept="image/*">
                                            <label class="custom-file-label">Choose image</label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger remove-image-btn" data-setting-key="dcl_hero_image" {{ $dclHeroImg ? '' : 'style="display:none;"' }}>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2 preview-container" id="preview-dcl_hero_image">
                                        @if($dclHeroImg)
                                            <img src="{{ Str::startsWith($dclHeroImg, 'http') ? $dclHeroImg : asset($dclHeroImg) }}" class="img-thumbnail" style="max-height: 200px;">
                                        @endif
                                    </div>
                                    <input type="hidden" name="dcl_hero_image" id="input-dcl_hero_image" value="{{ $dclHeroImg }}">
                                </div>

                                <div class="form-group mt-4">
                                    <label>Floating Badge Image (dcl_badge_image)</label>
                                    @php $dclBadgeImg = \App\Models\SiteSetting::get('dcl_badge_image'); @endphp
                                    <div class="input-group">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input image-upload" data-setting-key="dcl_badge_image" accept="image/*">
                                            <label class="custom-file-label">Choose image</label>
                                        </div>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-danger remove-image-btn" data-setting-key="dcl_badge_image" {{ $dclBadgeImg ? '' : 'style="display:none;"' }}>
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2 preview-container" id="preview-dcl_badge_image">
                                        @if($dclBadgeImg)
                                            <img src="{{ Str::startsWith($dclBadgeImg, 'http') ? $dclBadgeImg : asset($dclBadgeImg) }}" class="img-thumbnail" style="max-height: 100px;">
                                        @endif
                                    </div>
                                    <input type="hidden" name="dcl_badge_image" id="input-dcl_badge_image" value="{{ $dclBadgeImg }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="class-edit-section">
                        <h6 class="mb-3">Grades Selector</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Section Title</label>
                                    <input type="text" name="dcl_grades_title" class="form-control" value="{{ \App\Models\SiteSetting::get('dcl_grades_title', 'தரம் தேர்வு செய்க') }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Grades (Comma separated)</label>
                                    <input type="text" name="dcl_grades" class="form-control" value="{{ \App\Models\SiteSetting::get('dcl_grades', '01,02,03,04,05,06,07,08,09,10,11,A/L') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="class-edit-section">
                        <h6 class="mb-3">Features, Stats & Subjects (Advanced JSON)</h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Features Grid (JSON array)</label>
                                    <textarea name="dcl_features" class="form-control" rows="6">{{ \App\Models\SiteSetting::get('dcl_features', '[{"icon":"FiUsers","title":"சிறு குழுக்கள்","description":"சிறிய குழுக்கள் மூலம் ஆசிரியரின் தனிப்பட்ட கவனம்"},{"icon":"FiCheck","title":"நேரடி கற்றல்","description":"ஆசிரியர்களுடன் நேரடி தொடர்பு மற்றும் உரையாடல்"},{"icon":"FiFolder","title":"கற்றல் உபகரணங்கள்","description":"தேவையான அனைத்து பௌதீக கற்றல் பொருட்களும் வழங்கப்படும்"}]') }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Stats Bar (JSON array)</label>
                                    <textarea name="dcl_stats" class="form-control" rows="6">{{ \App\Models\SiteSetting::get('dcl_stats', '[{"icon":"FaGraduationCap","number":"2000+","label":"மாணவர்கள்"},{"icon":"FiUsers","number":"25+","label":"ஆசிரியர்கள்"},{"icon":"FiBook","number":"15+","label":"பாடநெறிகள்"},{"icon":"FiAward","number":"100%","label":"வெற்றி"}]') }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Subjects Row (JSON array)</label>
                                    <textarea name="dcl_subjects" class="form-control" rows="6">{{ \App\Models\SiteSetting::get('dcl_subjects', '[{"icon":"FiBook","name":"தமிழ்"},{"icon":"FiGlobe","name":"ஆங்கிலம்"},{"icon":"FiEdit","name":"கணிதம்"},{"icon":"FiStar","name":"அறிவியல்"},{"icon":"FiClock","name":"வரலாறு"},{"icon":"FiMapPin","name":"புவியியல்"}]') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center">
                        <button type="submit" class="btn btn-primary btn-lg px-5">Save All Classes Settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Fixed Image Upload logic for OCL Settings
        $(document).off('change', '.image-upload').on('change', '.image-upload', function() {
            let input = this;
            let file = input.files[0];
            let settingKey = $(this).data('setting-key');
            if (file) {
                let formData = new FormData();
                formData.append('image', file);
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                let label = $(this).next('.custom-file-label');
                let originalText = label.text();
                label.text('Uploading...');

                $.ajax({
                    url: "{{ route('admin.settings.upload') }}",
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            $('#input-' + settingKey).val(response.relative_path);
                            $('#preview-' + settingKey).html('<img src="' + response.path + '" class="img-thumbnail" style="max-height: 200px;">');
                            $('.remove-image-btn[data-setting-key="' + settingKey + '"]').show();
                            label.text('Choose image');
                            toastr.success('Image uploaded successfully');
                        } else {
                            label.text(originalText);
                            toastr.error(response.message || 'Upload failed');
                        }
                    },
                    error: function() {
                        label.text(originalText);
                        toastr.error('Connection error occurred');
                    }
                });
            }
        });

        // Remove Fixed Image
        $(document).off('click', '.remove-image-btn').on('click', '.remove-image-btn', function() {
            let settingKey = $(this).data('setting-key');
            let imagePath = $('#input-' + settingKey).val();

            if (imagePath && imagePath.includes('uploads/settings/')) {
                $.ajax({
                    url: "{{ route('admin.settings.delete-image') }}",
                    method: 'POST',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        image_path: imagePath
                    }
                });
            }
            $('#input-' + settingKey).val('');
            $('#preview-' + settingKey).html('');
            $(this).hide();
        });

    });
</script>
@endpush

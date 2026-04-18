@extends('layouts.admin')

@section('title', 'About Page Settings')

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

    .dynamic-row {
        background: rgba(255, 255, 255, 0.03);
        padding: 1.25rem;
        border-radius: 0.75rem;
        margin-bottom: 0.9375rem;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
        position: relative;
        transition: all 0.3s ease;
    }

    .dynamic-row:hover {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(235, 129, 83, 0.3);
    }

    .remove-row {
        position: absolute;
        top: 0.9375rem;
        right: 0.9375rem;
        color: #ff5e5e;
        cursor: pointer;
        font-size: 1.125rem;
        transition: transform 0.2s ease;
        z-index: 10;
    }

    .remove-row:hover {
        transform: scale(1.2);
        color: #ff4444;
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

    .text-muted {
        color: rgba(255, 255, 255, 0.5) !important;
    }
    .image-picker-container {
        position: relative;
        width: 100%;
        height: 9.375rem;
        background: rgba(0, 0, 0, 0.2);
        border: 0.125rem dashed rgba(255, 255, 255, 0.1);
        border-radius: 0.75rem;
        overflow: hidden;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .image-picker-container:hover {
        border-color: #EB8153;
        background: rgba(235, 129, 83, 0.05);
    }

    .image-picker-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    .image-picker-placeholder {
        text-align: center;
        color: rgba(255, 255, 255, 0.5);
    }

    .image-picker-placeholder i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .image-picker-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .image-picker-container:hover .image-picker-overlay {
        opacity: 1;
    }

    .upload-loading {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 5;
    }

    .upload-loading .spinner-border {
        width: 1.875rem;
        height: 1.875rem;
        color: #EB8153;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">About Page Settings</h4>
            <div>
                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}/about" target="_blank" class="btn btn-primary btn-sm">View About Page</a>
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

<form action="{{ route('admin.settings.store') }}" method="POST">
    @csrf
    
    {{-- Hero Section --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title">Hero Section</h5></div>
        <div class="card-body">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Hero Title</label>
                <div class="col-sm-9">
                    <input type="text" name="about_title" class="form-control" value="{{ \App\Models\SiteSetting::get('about_title', 'About TiT Online Education') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Hero Subtitle</label>
                <div class="col-sm-9">
                    <input type="text" name="about_subtitle" class="form-control" value="{{ \App\Models\SiteSetting::get('about_subtitle', "Sri Lanka's Premier Choice for Online Tuition 🎓") }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Hero Description</label>
                <div class="col-sm-9">
                    <textarea name="about_description" class="form-control" rows="3">{{ \App\Models\SiteSetting::get('about_description', "Sri Lanka's trusted leader in online tuition. We ensure student success through personalized learning and comprehensive parental support.") }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- Successful Journey Section --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">Successful Journey Timeline</h5>
            <button type="button" class="btn btn-info btn-xs" onclick="addRow('journey-container')">+ Add Year</button>
        </div>
        <div class="card-body">
            <div id="journey-container">
                @php
                    $journey = json_decode(\App\Models\SiteSetting::get('about_journey', '[]'), true);
                    if(empty($journey)) {
                        $journey = [
                            ['year' => '2024', 'achievement' => '10,000+ Students', 'description' => 'Reached a milestone...']
                        ];
                    }
                @endphp
                @foreach($journey as $item)
                    <div class="dynamic-row">
                        <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Year</label>
                                    <input type="text" name="journey_year[]" class="form-control" value="{{ $item['year'] }}">
                                </div>
                            </div>
                            <div class="col-md-9">
                                <div class="form-group">
                                    <label>Achievement</label>
                                    <input type="text" name="journey_achievement[]" class="form-control" value="{{ $item['achievement'] }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="journey_description[]" class="form-control" value="{{ $item['description'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <input type="hidden" name="about_journey" id="about_journey_json">
        </div>
    </div>

    {{-- Stats Section --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title">Statistics</h5></div>
        <div class="card-body">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Years of Experience</label>
                <div class="col-sm-9">
                    <input type="text" name="stats_years" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_years', '10+') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Happy Students</label>
                <div class="col-sm-9">
                    <input type="text" name="stats_students" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_students', '10K+') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Expert Tutors</label>
                <div class="col-sm-9">
                    <input type="text" name="stats_tutors" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_tutors', '200+') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Success Rate</label>
                <div class="col-sm-9">
                    <input type="text" name="stats_success_rate" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_success_rate', '98%') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Features Section --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">What Makes Us Different (Features)</h5>
            <button type="button" class="btn btn-info btn-xs" onclick="addRow('features-container')">+ Add Feature</button>
        </div>
        <div class="card-body">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Features Title</label>
                <div class="col-sm-9">
                    <input type="text" name="about_features_title" class="form-control" value="{{ \App\Models\SiteSetting::get('about_features_title', 'What Makes Us Different') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Features Subtitle</label>
                <div class="col-sm-9">
                    <input type="text" name="about_features_subtitle" class="form-control" value="{{ \App\Models\SiteSetting::get('about_features_subtitle', 'Quality Assured Online Learning with Proven Results') }}">
                </div>
            </div>
            <hr>
            <div id="features-container">
                @php
                    $features = json_decode(\App\Models\SiteSetting::get('about_features', '[]'), true);
                    if(empty($features)) {
                        $features = [
                            ['icon' => 'FiBookOpen', 'title' => 'Top-notch Online Classes', 'description' => 'Interactive live sessions...', 'image' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f']
                        ];
                    }
                @endphp
                @foreach($features as $feature)
                    <div class="dynamic-row">
                        <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Icon Code (e.g. FiBookOpen)</label>
                                    <input type="text" name="feature_icon[]" class="form-control" value="{{ $feature['icon'] }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="feature_title[]" class="form-control" value="{{ $feature['title'] }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="feature_description[]" class="form-control" rows="2">{{ $feature['description'] }}</textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Feature Image</label>
                                    <div class="image-picker-container" style="height: 7.5rem;" onclick="this.querySelector('input[type=file]').click()">
                                        <div class="upload-loading"><div class="spinner-border"></div></div>
                                        <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Change</div>
                                        <div class="image-picker-placeholder" style="{{ $feature['image'] ? 'display:none' : '' }}">
                                            <i class="la la-image"></i> Select
                                        </div>
                                        <img src="{{ $feature['image'] }}" class="image-picker-preview" style="{{ $feature['image'] ? '' : 'display:none' }}">
                                        <input type="file" style="display:none" accept="image/*" onchange="uploadImage(this, null, null)">
                                    </div>
                                    <input type="hidden" name="feature_image[]" value="{{ $feature['image'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <input type="hidden" name="about_features" id="about_features_json">
        </div>
    </div>

    {{-- Values Section --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">Core Values</h5>
            <button type="button" class="btn btn-info btn-xs" onclick="addRow('values-container')">+ Add Value</button>
        </div>
        <div class="card-body">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Values Title</label>
                <div class="col-sm-9">
                    <input type="text" name="about_values_title" class="form-control" value="{{ \App\Models\SiteSetting::get('about_values_title', 'Our Core Values') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Values Subtitle</label>
                <div class="col-sm-9">
                    <input type="text" name="about_values_subtitle" class="form-control" value="{{ \App\Models\SiteSetting::get('about_values_subtitle', 'The Principles That Guide Everything We Do') }}">
                </div>
            </div>
            <hr>
            <div id="values-container">
                @php
                    $values = json_decode(\App\Models\SiteSetting::get('about_values', '[]'), true);
                    if(empty($values)) {
                        $values = [
                            ['icon' => 'FiHeart', 'title' => 'Student-Centered', 'description' => 'Every decision we make...']
                        ];
                    }
                @endphp
                @foreach($values as $value)
                    <div class="dynamic-row">
                        <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Icon (e.g. FiHeart)</label>
                                    <input type="text" name="value_icon[]" class="form-control" value="{{ $value['icon'] }}">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="value_title[]" class="form-control" value="{{ $value['title'] }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="value_description[]" class="form-control" value="{{ $value['description'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <input type="hidden" name="about_values" id="about_values_json">
        </div>
    </div>

    {{-- Mission Section --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title">Mission Section</h5></div>
        <div class="card-body">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Mission Title</label>
                <div class="col-sm-9">
                    <input type="text" name="about_mission_title" class="form-control" value="{{ \App\Models\SiteSetting::get('about_mission_title', 'Our Mission') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Mission Description</label>
                <div class="col-sm-9">
                    <textarea name="about_mission_text" class="form-control" rows="5">{{ \App\Models\SiteSetting::get('about_mission_text', 'To democratize quality education by making world-class online tuition accessible to every student in Sri Lanka...') }}</textarea>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Mission Image</label>
                <div class="col-sm-9">
                    <div class="image-picker-container" onclick="document.getElementById('about_mission_image_file').click()">
                        <div class="upload-loading"><div class="spinner-border"></div></div>
                        <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Click to Upload</div>
                        @php $missionImage = \App\Models\SiteSetting::get('about_mission_image', 'https://images.unsplash.com/photo-1523240795612-9a054b0db644?w=800&h=600&fit=crop'); @endphp
                        <div class="image-picker-placeholder" style="{{ $missionImage ? 'display:none' : '' }}">
                            <i class="la la-image"></i> Select Image
                        </div>
                        <img id="about_mission_image_preview" src="{{ $missionImage }}" class="image-picker-preview" style="{{ $missionImage ? '' : 'display:none' }}">
                        <input type="file" id="about_mission_image_file" style="display:none" accept="image/*" onchange="uploadImage(this, 'about_mission_image_preview', 'about_mission_image_input')">
                    </div>
                    <input type="hidden" name="about_mission_image" id="about_mission_image_input" value="{{ $missionImage }}">
                </div>
            </div>
        </div>
    </div>

    {{-- CTA Section --}}
    <div class="card">
        <div class="card-header"><h5 class="card-title">CTA Section</h5></div>
        <div class="card-body">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">CTA Title</label>
                <div class="col-sm-9">
                    <input type="text" name="about_cta_title" class="form-control" value="{{ \App\Models\SiteSetting::get('about_cta_title', 'Ready to Start Your Learning Journey?') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">CTA Description</label>
                <div class="col-sm-9">
                    <textarea name="about_cta_desc" class="form-control" rows="3">{{ \App\Models\SiteSetting::get('about_cta_desc', 'Join thousands of students who are already achieving academic excellence with TiT Online Education.') }}</textarea>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">CTA Button 1 Text</label>
                <div class="col-sm-9">
                    <input type="text" name="about_cta_btn1" class="form-control" value="{{ \App\Models\SiteSetting::get('about_cta_btn1', 'Register Now') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">CTA Button 1 Link</label>
                <div class="col-sm-9">
                    <input type="text" name="about_cta_btn1_link" class="form-control" value="{{ \App\Models\SiteSetting::get('about_cta_btn1_link', '/register') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">CTA Button 2 Text</label>
                <div class="col-sm-9">
                    <input type="text" name="about_cta_btn2" class="form-control" value="{{ \App\Models\SiteSetting::get('about_cta_btn2', 'Contact Us') }}">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">CTA Button 2 Link</label>
                <div class="col-sm-9">
                    <input type="text" name="about_cta_btn2_link" class="form-control" value="{{ \App\Models\SiteSetting::get('about_cta_btn2_link', '/contact') }}">
                </div>
            </div>
        </div>
    </div>

    {{-- Teachers Section --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">Teachers Details</h5>
            <button type="button" class="btn btn-info btn-xs" onclick="addRow('teachers-container')">+ Add Teacher</button>
        </div>
        <div class="card-body">
            <div id="teachers-container">
                @php
                    $teachers = json_decode(\App\Models\SiteSetting::get('about_teachers', '[]'), true);
                    if(empty($teachers)) {
                        $teachers = [
                            ['name' => 'Dr. Kamal Perera', 'subject' => 'Mathematics', 'qualification' => 'Ph.D. in Mathematics', 'experience' => '15+ years', 'image' => 'https://via.placeholder.com/150']
                        ];
                    }
                @endphp
                @foreach($teachers as $index => $teacher)
                    <div class="dynamic-row">
                        <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="teacher_name[]" class="form-control" value="{{ $teacher['name'] }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Subject</label>
                                    <input type="text" name="teacher_subject[]" class="form-control" value="{{ $teacher['subject'] }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Qualification</label>
                                    <input type="text" name="teacher_qualification[]" class="form-control" value="{{ $teacher['qualification'] }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Experience</label>
                                    <input type="text" name="teacher_experience[]" class="form-control" value="{{ $teacher['experience'] }}">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Teacher Image</label>
                                    <div class="image-picker-container" onclick="this.querySelector('input[type=file]').click()">
                                        <div class="upload-loading"><div class="spinner-border"></div></div>
                                        <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Change</div>
                                        <div class="image-picker-placeholder" style="{{ $teacher['image'] ? 'display:none' : '' }}">
                                            <i class="la la-image"></i> Select
                                        </div>
                                        <img src="{{ $teacher['image'] }}" class="image-picker-preview" style="{{ $teacher['image'] ? '' : 'display:none' }}">
                                        <input type="file" style="display:none" accept="image/*" onchange="uploadImage(this, null, null)">
                                    </div>
                                    <input type="hidden" name="teacher_image[]" value="{{ $teacher['image'] }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <input type="hidden" name="about_teachers" id="about_teachers_json">
        </div>
    </div>

    {{-- Gallery Section --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">Gallery Images</h5>
            <button type="button" class="btn btn-info btn-xs" onclick="addRow('gallery-container')">+ Add Image</button>
        </div>
        <div class="card-body">
            <div class="form-group row">
                <label class="col-sm-3 col-form-label">Gallery Categories</label>
                <div class="col-sm-9">
                    <input type="text" name="about_gallery_categories" class="form-control" value="{{ \App\Models\SiteSetting::get('about_gallery_categories', 'Online Class Sessions, Student Success Stories, Teacher Training, Award Ceremony') }}" placeholder="Enter categories separated by commas">
                    <small class="text-muted">Separate categories with commas (e.g. Academy, Sports, Lab)</small>
                </div>
            </div>
            <hr>
            <div id="gallery-container">
                @php
                    $gallery = json_decode(\App\Models\SiteSetting::get('about_gallery', '[]'), true);
                @endphp
                @foreach($gallery as $item)
                    <div class="dynamic-row">
                        <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="gallery_category[]" class="form-control">
                                        @php
                                            $categories = explode(',', \App\Models\SiteSetting::get('about_gallery_categories', 'Online Class Sessions, Student Success Stories, Teacher Training, Award Ceremony'));
                                        @endphp
                                        @foreach($categories as $cat)
                                            @php $cat = trim($cat); @endphp
                                            <option value="{{ $cat }}" {{ ($item['category'] ?? '') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Title</label>
                                    <input type="text" name="gallery_title[]" class="form-control" value="{{ $item['title'] ?? '' }}">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Gallery Image</label>
                                    <div class="image-picker-container" style="height: 6.25rem;" onclick="this.querySelector('input[type=file]').click()">
                                        <div class="upload-loading"><div class="spinner-border"></div></div>
                                        <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Change</div>
                                        <div class="image-picker-placeholder" style="{{ ($item['image'] ?? '') ? 'display:none' : '' }}">
                                            <i class="la la-image"></i> Select
                                        </div>
                                        <img src="{{ $item['image'] ?? '' }}" class="image-picker-preview" style="{{ ($item['image'] ?? '') ? '' : 'display:none' }}">
                                        <input type="file" style="display:none" accept="image/*" onchange="uploadImage(this, null, null)">
                                    </div>
                                    <input type="hidden" name="gallery_image[]" value="{{ $item['image'] ?? '' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <input type="hidden" name="about_gallery" id="about_gallery_json">
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <button type="submit" class="btn btn-primary" onclick="prepareJsonData()">Save About Page Settings</button>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
    function addRow(containerId) {
        const container = document.getElementById(containerId);
        let html = '';
        
        if (containerId === 'teachers-container') {
            html = `
                <div class="dynamic-row">
                    <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group"><label>Name</label><input type="text" name="teacher_name[]" class="form-control"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group"><label>Subject</label><input type="text" name="teacher_subject[]" class="form-control"></div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group"><label>Qualification</label><input type="text" name="teacher_qualification[]" class="form-control"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group"><label>Experience</label><input type="text" name="teacher_experience[]" class="form-control"></div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Teacher Image</label>
                                <div class="image-picker-container" onclick="this.querySelector('input[type=file]').click()">
                                    <div class="upload-loading"><div class="spinner-border"></div></div>
                                    <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Click to Upload</div>
                                    <div class="image-picker-placeholder"><i class="la la-image"></i> Select</div>
                                    <img src="" class="image-picker-preview" style="display:none">
                                    <input type="file" style="display:none" accept="image/*" onchange="uploadImage(this, null, null)">
                                </div>
                                <input type="hidden" name="teacher_image[]">
                            </div>
                        </div>
                    </div>
                </div>`;
        } else if (containerId === 'features-container') {
             html = `
                <div class="dynamic-row">
                    <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group"><label>Icon Code</label><input type="text" name="feature_icon[]" class="form-control"></div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group"><label>Title</label><input type="text" name="feature_title[]" class="form-control"></div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group"><label>Description</label><textarea name="feature_description[]" class="form-control" rows="2"></textarea></div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Feature Image</label>
                                <div class="image-picker-container" style="height: 7.5rem;" onclick="this.querySelector('input[type=file]').click()">
                                    <div class="upload-loading"><div class="spinner-border"></div></div>
                                    <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Click to Upload</div>
                                    <div class="image-picker-placeholder"><i class="la la-image"></i> Select</div>
                                    <img src="" class="image-picker-preview" style="display:none">
                                    <input type="file" style="display:none" accept="image/*" onchange="uploadImage(this, null, null)">
                                </div>
                                <input type="hidden" name="feature_image[]">
                            </div>
                        </div>
                    </div>
                </div>`;
        } else if (containerId === 'journey-container') {
            html = `
                <div class="dynamic-row">
                    <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group"><label>Year</label><input type="text" name="journey_year[]" class="form-control"></div>
                        </div>
                        <div class="col-md-9">
                            <div class="form-group"><label>Achievement</label><input type="text" name="journey_achievement[]" class="form-control"></div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group"><label>Description</label><input type="text" name="journey_description[]" class="form-control"></div>
                        </div>
                    </div>
                </div>`;
        } else if (containerId === 'values-container') {
            html = `
                <div class="dynamic-row">
                    <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group"><label>Icon</label><input type="text" name="value_icon[]" class="form-control"></div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group"><label>Title</label><input type="text" name="value_title[]" class="form-control"></div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group"><label>Description</label><input type="text" name="value_description[]" class="form-control"></div>
                        </div>
                    </div>
                </div>`;
        } else if (containerId === 'gallery-container') {
            const categoriesInput = document.getElementsByName('about_gallery_categories')[0];
            const categories = categoriesInput ? categoriesInput.value.split(',').map(c => c.trim()) : ['Online Class Sessions', 'Student Success Stories', 'Teacher Training', 'Award Ceremony'];
            let optionsHtml = '';
            categories.forEach(cat => {
                optionsHtml += `<option value="${cat}">${cat}</option>`;
            });

            html = `
                <div class="dynamic-row">
                    <i class="la la-trash remove-row" onclick="this.parentElement.remove()"></i>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Category</label>
                                <select name="gallery_category[]" class="form-control">
                                    ${optionsHtml}
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group"><label>Title</label><input type="text" name="gallery_title[]" class="form-control"></div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Gallery Image</label>
                                <div class="image-picker-container" style="height: 6.25rem;" onclick="this.querySelector('input[type=file]').click()">
                                    <div class="upload-loading"><div class="spinner-border"></div></div>
                                    <div class="image-picker-overlay"><i class="la la-cloud-upload"></i> Click to Upload</div>
                                    <div class="image-picker-placeholder"><i class="la la-image"></i> Select</div>
                                    <img src="" class="image-picker-preview" style="display:none">
                                    <input type="file" style="display:none" accept="image/*" onchange="uploadImage(this, null, null)">
                                </div>
                                <input type="hidden" name="gallery_image[]">
                            </div>
                        </div>
                    </div>
                </div>`;
        }
        
        container.insertAdjacentHTML('beforeend', html);
    }

    function prepareJsonData() {
        // Process Teachers
        const teachers = [];
        const tNames = document.getElementsByName('teacher_name[]');
        const tSubjects = document.getElementsByName('teacher_subject[]');
        const tQuals = document.getElementsByName('teacher_qualification[]');
        const tExps = document.getElementsByName('teacher_experience[]');
        const tImages = document.getElementsByName('teacher_image[]');
        for (let i = 0; i < tNames.length; i++) {
            teachers.push({
                name: tNames[i].value,
                subject: tSubjects[i].value,
                qualification: tQuals[i].value,
                experience: tExps[i].value,
                image: tImages[i].value
            });
        }
        document.getElementById('about_teachers_json').value = JSON.stringify(teachers);

        // Process Features
        const features = [];
        const fIcons = document.getElementsByName('feature_icon[]');
        const fTitles = document.getElementsByName('feature_title[]');
        const fDescs = document.getElementsByName('feature_description[]');
        const fImages = document.getElementsByName('feature_image[]');
        for (let i = 0; i < fIcons.length; i++) {
            features.push({
                icon: fIcons[i].value,
                title: fTitles[i].value,
                description: fDescs[i].value,
                image: fImages[i].value
            });
        }
        document.getElementById('about_features_json').value = JSON.stringify(features);

        // Process Journey
        const journey = [];
        const jYears = document.getElementsByName('journey_year[]');
        const jAchievements = document.getElementsByName('journey_achievement[]');
        const jDescs = document.getElementsByName('journey_description[]');
        for (let i = 0; i < jYears.length; i++) {
            journey.push({
                year: jYears[i].value,
                achievement: jAchievements[i].value,
                description: jDescs[i].value
            });
        }
        document.getElementById('about_journey_json').value = JSON.stringify(journey);

        // Process Values
        const values = [];
        const vIcons = document.getElementsByName('value_icon[]');
        const vTitles = document.getElementsByName('value_title[]');
        const vDescs = document.getElementsByName('value_description[]');
        for (let i = 0; i < vIcons.length; i++) {
            values.push({
                icon: vIcons[i].value,
                title: vTitles[i].value,
                description: vDescs[i].value
            });
        }
        document.getElementById('about_values_json').value = JSON.stringify(values);

        // Process Gallery
        const gallery = [];
        const gCategories = document.getElementsByName('gallery_category[]');
        const gTitles = document.getElementsByName('gallery_title[]');
        const gImages = document.getElementsByName('gallery_image[]');
        for (let i = 0; i < gCategories.length; i++) {
            gallery.push({
                category: gCategories[i].value,
                title: gTitles[i].value,
                image: gImages[i].value
            });
        }
        document.getElementById('about_gallery_json').value = JSON.stringify(gallery);
    }
    async function uploadImage(input, previewId, targetInputId) {
        const file = input.files[0];
        if (!file) return;

        const container = input.closest('.image-picker-container');
        const loader = container.querySelector('.upload-loading');
        const preview = previewId ? document.getElementById(previewId) : container.querySelector('.image-picker-preview');
        const targetInput = targetInputId ? document.getElementById(targetInputId) : null;

        // Show loading
        loader.style.display = 'flex';

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
                    const placeholder = container.querySelector('.image-picker-placeholder');
                    if(placeholder) placeholder.style.display = 'none';
                    const overlay = container.querySelector('.image-picker-overlay');
                    if(overlay) overlay.innerHTML = '<i class="la la-cloud-upload"></i> Change';
                }
                if (targetInput) {
                    targetInput.value = data.path;
                }
                // If it's a dynamic row, update the sibling input
                const siblingInput = input.closest('.dynamic-row') ? input.closest('.dynamic-row').querySelector('input[type="hidden"][name*="image"]') : null;
                if (!targetInput && siblingInput) {
                    siblingInput.value = data.path;
                }
            } else {
                alert(data.message || 'Upload failed');
            }
        } catch (error) {
            console.error('Error uploading image:', error);
            alert('An error occurred during upload');
        } finally {
            loader.style.display = 'none';
        }
    }
</script>
@endpush

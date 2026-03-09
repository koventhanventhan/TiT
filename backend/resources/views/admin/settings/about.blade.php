<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>About Settings - {{ config('app.name') }}</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('admin-theme/vendor/chartist/css/chartist.min.css') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/vendor/owl-carousel/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/admin-responsive.css') }}" rel="stylesheet">
    <style>
        .content-body {
            margin-top: 0 !important;
            padding-top: 20px;
        }

        .card {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .header {
            background: #1f2937;
        }

        .header-profile .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-header .brand-logo {
            display: flex;
            align-items: center;
            padding-left: 20px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4) !important;
        }
    </style>
    <!-- Pusher and Notifications -->
    <link rel="stylesheet" href="{{ asset('admin-theme/vendor/toastr/css/toastr.min.css') }}">
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <script>
        window.PUSHER_KEY = "{{ env('PUSHER_APP_KEY', '4f9958ae0d1fc1808fb5') }}";
        window.PUSHER_CLUSTER = "{{ env('PUSHER_APP_CLUSTER', 'ap2') }}";
        @auth
            window.USER_ID = {{ auth()->id() }};
        @else
            window.USER_ID = null;
        @endauth
    </script>
</head>

<body>
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>

    <div id="main-wrapper">
        <div class="nav-header">
                        <a href="{{ route('admin.dashboard') }}" class="brand-logo">
                @if(isset($site_settings['admin_logo']))
                    <img src="{{ asset($site_settings['admin_logo']) }}" alt="Logo" style="max-height: 45px; max-width: 45px; object-fit: contain;">
                @else
                    <svg class="logo-abbr" width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect class="svg-logo-rect" width="50" height="50" rx="20" fill="#EB8153"/>
                        <path class="svg-logo-path" d="M17.5158 25.8619L19.8088 25.2475L14.8746 11.1774C14.5189 9.84988 15.8701 9.0998 16.8205 9.75055L33.0924 22.2055C33.7045 22.5589 33.8512 24.0717 32.6444 24.3951L30.3514 25.0095L35.2856 39.0796C35.6973 40.1334 34.4431 41.2455 33.3397 40.5064L17.0678 28.0515C16.2057 27.2477 16.5504 26.1205 17.5158 25.8619ZM18.685 14.2955L22.2224 24.6007L29.4633 22.6605L18.685 14.2955ZM31.4751 35.9615L27.8171 25.6886L20.5762 27.6288L31.4751 35.9615Z" fill="white"/>
                    </svg>
                @endif
                <span class="brand-title" style="font-size: 24px; font-weight: 700; margin-left:12px; color: #fff;">
                    {{ $site_settings['admin_company_name'] ?? 'Zenix' }}
                </span>
            </a>
            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span><span class="line"></span><span class="line"></span>
                </div>
            </div>
        </div>

        <div class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        <div class="header-left">
                            <div class="search_bar">
                                <form>
                                    <input class="form-control" type="search" placeholder="Find something here..." aria-label="Search">
                                    <span class="search_icon">
                                        <i class="mdi mdi-magnify"></i>
                                    </span>
                                </form>
                            </div>
                        </div>

                        <ul class="navbar-nav header-right">
                             <li class="nav-item" style="margin-right: 20px;">
                                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="btn btn-primary btn-sm"
                                   style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                          border: none;
                                          padding: 8px 12px;
                                          border-radius: 6px;
                                          color: white;
                                          font-weight: 500;
                                          text-decoration: none;
                                          display: inline-flex;
                                          align-items: center;
                                          gap: 5px;
                                          transition: all 0.3s ease;
                                          box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
                                          cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                    </svg>
                                    Home
                                </a>
                            </li>
                            
                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                                    <!-- <div class="header-info">
                                        <span style="color: #fff; font-weight: 600;"><strong>{{ Auth::user()->name }}</strong></span>
                                        <p class="fs-12 mb-0" style="color: rgba(255, 255, 255, 0.8);">{{ Auth::user()->email }}</p>
                                    </div> -->
                                    @if(Auth::user()->avatar)
                                        <img src="{{ asset(Auth::user()->avatar) }}" width="40" height="40" alt="" style="border-radius: 50%; object-fit: cover;">
                                    @else
                                        <div class="header-profile-initials" style="width: 40px; height: 40px; border-radius: 50%; background: #EB8153; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            {{ strtoupper(substr(Auth::user()->first_name ?: Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div class="dropdown-header text-left border-bottom pb-3 mb-2">
                                        <h6 class="mb-0 text-black">{{ Auth::user()->name }}</h6>
                                        <small class="text-muted">{{ Auth::user()->email }}</small>
                                    </div>
                                    <a href="{{ route('admin.profile.settings') }}" class="dropdown-item ai-icon">
                                        <i class="la la-cog text-primary mr-2"></i>
                                        <span class="ml-2">Settings</span>
                                    </a>
                                    <a href="{{ route('admin.profile.settings') }}?tab=calendar" class="dropdown-item ai-icon">
                                        <i class="la la-calendar text-primary mr-2"></i>
                                        <span class="ml-2">Calendar</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.logout') }}" class="mt-2 border-top pt-2">
                                        @csrf
                                        <button type="submit" class="dropdown-item ai-icon text-danger">
                                            <i class="la la-sign-out text-danger mr-2"></i>
                                            <span class="ml-2">Sign out</span>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        @include('admin.partials.sidebar')

        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="page-title d-flex justify-content-between align-items-center">
                            <h4 class="mb-0" style="font-size: 24px; font-weight: 600; color: #1f2937;">About Page Settings</h4>
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary btn-sm">
                                <i class="flaticon-381-back"></i> Back to Dashboard
                            </a>
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
                            <div class="card-header">
                                <h5 class="card-title mb-0">About Page Content</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.settings.store') }}" method="POST">
                                    @csrf
                                    <h5 class="mb-3 text-primary">Hero Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Page Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="about_title" class="form-control" value="{{ App\Models\SiteSetting::get('about_title', 'About TiT Online Education') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Subtitle</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="about_subtitle" class="form-control" value="{{ App\Models\SiteSetting::get('about_subtitle', 'Sri Lanka\'s Premier Choice for Online Tuition ðŸŽ“') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Description</label>
                                        <div class="col-sm-9">
                                            <textarea name="about_description" class="form-control" rows="4">{{ App\Models\SiteSetting::get('about_description', 'Sri Lanka\'s trusted leader in online tuition. We ensure student success through personalized learning and comprehensive parental support. Invest in your child\'s successful learning journey with TiT Online Education.') }}</textarea>
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Mission Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Mission Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="about_mission_title" class="form-control" value="{{ App\Models\SiteSetting::get('about_mission_title', 'Our Mission') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Mission Text</label>
                                        <div class="col-sm-9">
                                            <textarea name="about_mission_text" class="form-control" rows="5">{{ App\Models\SiteSetting::get('about_mission_text', 'To democratize quality education by making world-class online tuition accessible to every student in Sri Lanka. We believe that every child deserves the opportunity to excel academically, regardless of their location or background. Through innovative teaching methods, personalized learning paths, and dedicated support, we empower students to achieve their full potential and succeed in their academic journey.') }}</textarea>
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Features Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Features Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="about_features_title" class="form-control" value="{{ App\Models\SiteSetting::get('about_features_title', 'What Makes Us Different') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Features Subtitle</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="about_features_subtitle" class="form-control" value="{{ App\Models\SiteSetting::get('about_features_subtitle', 'Quality Assured Online Learning with Proven Results') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Values Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Values Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="about_values_title" class="form-control" value="{{ App\Models\SiteSetting::get('about_values_title', 'Our Core Values') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Values Subtitle</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="about_values_subtitle" class="form-control" value="{{ App\Models\SiteSetting::get('about_values_subtitle', 'The Principles That Guide Everything We Do') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">CTA Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">CTA Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="about_cta_title" class="form-control" value="{{ App\Models\SiteSetting::get('about_cta_title', 'Ready to Start Your Learning Journey?') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">CTA Description</label>
                                        <div class="col-sm-9">
                                            <textarea name="about_cta_description" class="form-control" rows="2">{{ App\Models\SiteSetting::get('about_cta_description', 'Join thousands of students who are already achieving academic excellence with TiT Online Education.') }}</textarea>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary mt-3">Save About Page Settings</button>
                                </form>

                                <!-- Successful Journey Repeater -->
                                <hr class="my-4">
                                <h5 class="mb-3 text-primary">Successful Journey Timeline</h5>
                                <div id="journey-repeater">
                                    <!-- Items will be added here by JS -->
                                </div>
                                <button type="button" class="btn btn-success btn-sm mt-2" id="add-journey-item">
                                    <i class="fa fa-plus"></i> Add Journey Item
                                </button>
                                <textarea name="about_journey" id="journey_hidden" style="display:none;">{{ App\Models\SiteSetting::get('about_journey', '[]') }}</textarea>

                                <!-- Teachers Repeater -->
                                <hr class="my-4">
                                <h5 class="mb-3 text-primary">Teacher Details</h5>
                                <div id="teachers-repeater">
                                    <!-- Items will be added here by JS -->
                                </div>
                                <button type="button" class="btn btn-success btn-sm mt-2" id="add-teacher-item">
                                    <i class="fa fa-plus"></i> Add Teacher
                                </button>
                                <textarea name="about_teachers" id="teachers_hidden" style="display:none;">{{ App\Models\SiteSetting::get('about_teachers', '[]') }}</textarea>

                                <!-- Gallery Images Repeater -->
                                <hr class="my-4">
                                <h5 class="mb-3 text-primary">Gallery Images</h5>
                                <div id="gallery-repeater">
                                    <!-- Items will be added here by JS -->
                                </div>
                                <button type="button" class="btn btn-success btn-sm mt-2" id="add-gallery-item">
                                    <i class="fa fa-plus"></i> Add Gallery Image
                                </button>
                                <textarea name="about_gallery" id="gallery_hidden" style="display:none;">{{ App\Models\SiteSetting::get('about_gallery', '[]') }}</textarea>

                                <form action="{{ route('admin.settings.store') }}" method="POST" id="repeater-form" class="mt-4">
                                    @csrf
                                    <input type="hidden" name="about_journey" id="journey_submit">
                                    <input type="hidden" name="about_teachers" id="teachers_submit">
                                    <input type="hidden" name="about_gallery" id="gallery_submit">
                                    <button type="submit" class="btn btn-primary">Save All Repeater Data</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright Â© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- Required vendors -->
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-search.js') }}"></script>

    <style>
        .repeater-item {
            background: #3b3363;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            position: relative;
        }
        .repeater-item:hover {
            border-color: #667eea;
        }
        .remove-item {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #dc3545;
            cursor: pointer;
            background: #fff;
            border: 1px solid #dc3545;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        .remove-item:hover {
            background: #dc3545;
            color: #fff;
        }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // --- Journey Repeater ---
        const journeyHidden = document.getElementById('journey_hidden');
        const journeyRepeater = document.getElementById('journey-repeater');
        const addJourneyBtn = document.getElementById('add-journey-item');
        let journeyItems = [];
        try { journeyItems = JSON.parse(journeyHidden.value || '[]'); } catch(e) { journeyItems = []; }

        function syncJourney() {
            const items = [];
            document.querySelectorAll('.journey-item').forEach(el => {
                items.push({
                    year: el.querySelector('.j-year').value,
                    achievement: el.querySelector('.j-achievement').value,
                    description: el.querySelector('.j-description').value
                });
            });
            journeyHidden.value = JSON.stringify(items);
            document.getElementById('journey_submit').value = JSON.stringify(items);
        }

        function createJourneyItem(data = {}) {
            const div = document.createElement('div');
            div.className = 'repeater-item journey-item';
            div.innerHTML = `
                <span class="remove-item" onclick="this.parentElement.remove(); syncJourney();">&times;</span>
                <div class="row">
                    <div class="col-md-3">
                        <label>Year</label>
                        <input type="text" class="form-control j-year" value="${data.year || ''}" placeholder="2024">
                    </div>
                    <div class="col-md-4">
                        <label>Achievement</label>
                        <input type="text" class="form-control j-achievement" value="${data.achievement || ''}" placeholder="10,000+ Students">
                    </div>
                    <div class="col-md-5">
                        <label>Description</label>
                        <input type="text" class="form-control j-description" value="${data.description || ''}" placeholder="Milestone description">
                    </div>
                </div>
            `;
            journeyRepeater.appendChild(div);
            div.querySelectorAll('input').forEach(inp => inp.addEventListener('input', syncJourney));
        }

        journeyItems.forEach(item => createJourneyItem(item));
        addJourneyBtn.addEventListener('click', () => { createJourneyItem(); syncJourney(); });
        window.syncJourney = syncJourney;
        syncJourney();

        // --- Teachers Repeater ---
        const teachersHidden = document.getElementById('teachers_hidden');
        const teachersRepeater = document.getElementById('teachers-repeater');
        const addTeacherBtn = document.getElementById('add-teacher-item');
        let teacherItems = [];
        try { teacherItems = JSON.parse(teachersHidden.value || '[]'); } catch(e) { teacherItems = []; }

        function syncTeachers() {
            const items = [];
            document.querySelectorAll('.teacher-item').forEach(el => {
                items.push({
                    name: el.querySelector('.t-name').value,
                    subject: el.querySelector('.t-subject').value,
                    qualification: el.querySelector('.t-qualification').value,
                    experience: el.querySelector('.t-experience').value,
                    image: el.querySelector('.t-image').value
                });
            });
            teachersHidden.value = JSON.stringify(items);
            document.getElementById('teachers_submit').value = JSON.stringify(items);
        }

        function createTeacherItem(data = {}) {
            const div = document.createElement('div');
            div.className = 'repeater-item teacher-item';
            const uniqueId = 'teacher_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            div.innerHTML = `
                <span class="remove-item" onclick="this.parentElement.remove(); syncTeachers();">&times;</span>
                <div class="row">
                    <div class="col-md-3">
                        <label>Name</label>
                        <input type="text" class="form-control t-name" value="${data.name || ''}" placeholder="Dr. John Doe">
                    </div>
                    <div class="col-md-2">
                        <label>Subject</label>
                        <input type="text" class="form-control t-subject" value="${data.subject || ''}" placeholder="Mathematics">
                    </div>
                    <div class="col-md-2">
                        <label>Qualification</label>
                        <input type="text" class="form-control t-qualification" value="${data.qualification || ''}" placeholder="Ph.D.">
                    </div>
                    <div class="col-md-2">
                        <label>Experience</label>
                        <input type="text" class="form-control t-experience" value="${data.experience || ''}" placeholder="15+ years">
                    </div>
                    <div class="col-md-3">
                        <label>Image</label>
                        <div class="input-group">
                            <input type="text" class="form-control t-image" value="${data.image || ''}" placeholder="Select image...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info btn-upload-teacher" data-target="${uniqueId}">
                                    <i class="fa fa-folder-open"></i>
                                </button>
                            </div>
                        </div>
                        <input type="file" id="${uniqueId}" class="d-none" accept="image/*">
                        ${data.image ? `<img src="${data.image}" class="mt-2 img-preview" style="max-width:60px;max-height:60px;border-radius:4px;">` : ''}
                    </div>
                </div>
            `;
            teachersRepeater.appendChild(div);
            div.querySelectorAll('input[type="text"]').forEach(inp => inp.addEventListener('input', syncTeachers));
            
            // File upload handler
            const fileInput = div.querySelector(`#${uniqueId}`);
            const uploadBtn = div.querySelector('.btn-upload-teacher');
            uploadBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const formData = new FormData();
                    formData.append('image', this.files[0]);
                    formData.append('_token', '{{ csrf_token() }}');
                    
                    fetch('{{ route("admin.settings.upload") }}', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const imgInput = div.querySelector('.t-image');
                            imgInput.value = data.path;
                            // Update or add preview
                            let preview = div.querySelector('.img-preview');
                            if (!preview) {
                                preview = document.createElement('img');
                                preview.className = 'mt-2 img-preview';
                                preview.style = 'max-width:60px;max-height:60px;border-radius:4px;';
                                div.querySelector('.input-group').after(preview);
                            }
                            preview.src = data.path;
                            syncTeachers();
                        } else {
                            alert('Upload failed: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(err => alert('Upload error: ' + err.message));
                }
            });
        }

        teacherItems.forEach(item => createTeacherItem(item));
        addTeacherBtn.addEventListener('click', () => { createTeacherItem(); syncTeachers(); });
        window.syncTeachers = syncTeachers;
        syncTeachers();

        // --- Gallery Repeater ---
        const galleryHidden = document.getElementById('gallery_hidden');
        const galleryRepeater = document.getElementById('gallery-repeater');
        const addGalleryBtn = document.getElementById('add-gallery-item');
        let galleryItems = [];
        try { galleryItems = JSON.parse(galleryHidden.value || '[]'); } catch(e) { galleryItems = []; }

        function syncGallery() {
            const items = [];
            document.querySelectorAll('.gallery-item').forEach(el => {
                items.push({
                    category: el.querySelector('.g-category').value,
                    image: el.querySelector('.g-image').value,
                    title: el.querySelector('.g-title').value
                });
            });
            galleryHidden.value = JSON.stringify(items);
            document.getElementById('gallery_submit').value = JSON.stringify(items);
        }

        function createGalleryItem(data = {}) {
            const div = document.createElement('div');
            div.className = 'repeater-item gallery-item';
            const uniqueId = 'gallery_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            div.innerHTML = `
                <span class="remove-item" onclick="this.parentElement.remove(); syncGallery();">&times;</span>
                <div class="row">
                    <div class="col-md-3">
                        <label>Category</label>
                        <select class="form-control g-category">
                            <option value="Online Class Sessions" ${data.category === 'Online Class Sessions' ? 'selected' : ''}>Online Class Sessions</option>
                            <option value="Student Success Stories" ${data.category === 'Student Success Stories' ? 'selected' : ''}>Student Success Stories</option>
                            <option value="Teacher Training" ${data.category === 'Teacher Training' ? 'selected' : ''}>Teacher Training</option>
                            <option value="Award Ceremony" ${data.category === 'Award Ceremony' ? 'selected' : ''}>Award Ceremony</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label>Image</label>
                        <div class="input-group">
                            <input type="text" class="form-control g-image" value="${data.image || ''}" placeholder="Select image...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info btn-upload-gallery" data-target="${uniqueId}">
                                    <i class="fa fa-folder-open"></i>
                                </button>
                            </div>
                        </div>
                        <input type="file" id="${uniqueId}" class="d-none" accept="image/*">
                        ${data.image ? `<img src="${data.image}" class="mt-2 img-preview" style="max-width:80px;max-height:60px;border-radius:4px;">` : ''}
                    </div>
                    <div class="col-md-4">
                        <label>Title</label>
                        <input type="text" class="form-control g-title" value="${data.title || ''}" placeholder="Image title">
                    </div>
                </div>
            `;
            galleryRepeater.appendChild(div);
            div.querySelectorAll('input[type="text"], select').forEach(inp => inp.addEventListener('input', syncGallery));
            div.querySelectorAll('select').forEach(sel => sel.addEventListener('change', syncGallery));
            
            // File upload handler
            const fileInput = div.querySelector(`#${uniqueId}`);
            const uploadBtn = div.querySelector('.btn-upload-gallery');
            uploadBtn.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const formData = new FormData();
                    formData.append('image', this.files[0]);
                    formData.append('_token', '{{ csrf_token() }}');
                    
                    fetch('{{ route("admin.settings.upload") }}', {
                        method: 'POST',
                        body: formData
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            const imgInput = div.querySelector('.g-image');
                            imgInput.value = data.path;
                            // Update or add preview
                            let preview = div.querySelector('.img-preview');
                            if (!preview) {
                                preview = document.createElement('img');
                                preview.className = 'mt-2 img-preview';
                                preview.style = 'max-width:80px;max-height:60px;border-radius:4px;';
                                div.querySelector('.input-group').after(preview);
                            }
                            preview.src = data.path;
                            syncGallery();
                        } else {
                            alert('Upload failed: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(err => alert('Upload error: ' + err.message));
                }
            });
        }

        galleryItems.forEach(item => createGalleryItem(item));
        addGalleryBtn.addEventListener('click', () => { createGalleryItem(); syncGallery(); });
        window.syncGallery = syncGallery;
        syncGallery();
    });
    </script>
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>
</body>

</html>

</html>





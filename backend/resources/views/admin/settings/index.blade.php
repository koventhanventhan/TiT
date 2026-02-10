<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Frontend Settings - {{ config('app.name') }}</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('admin-theme/vendor/chartist/css/chartist.min.css') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/vendor/owl-carousel/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
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
                <svg class="logo-abbr" width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect class="svg-logo-rect" width="50" height="50" rx="20" fill="#EB8153"/>
                    <path class="svg-logo-path" d="M17.5158 25.8619L19.8088 25.2475L14.8746 11.1774C14.5189 9.84988 15.8701 9.0998 16.8205 9.75055L33.0924 22.2055C33.7045 22.5589 33.8512 24.0717 32.6444 24.3951L30.3514 25.0095L35.2856 39.0796C35.6973 40.1334 34.4431 41.2455 33.3397 40.5064L17.0678 28.0515C16.2057 27.2477 16.5504 26.1205 17.5158 25.8619ZM18.685 14.2955L22.2224 24.6007L29.4633 22.6605L18.685 14.2955ZM31.4751 35.9615L27.8171 25.6886L20.5762 27.6288L31.4751 35.9615Z" fill="white"/>
                </svg>
                <svg class="brand-title" width="74" height="22" viewBox="0 0 74 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="svg-logo-path" d="M0.784 17.556L10.92 5.152H1.176V1.12H16.436V4.564L6.776 16.968H16.548V21H0.784V17.556ZM25.7399 21.28C24.0785 21.28 22.6599 20.9347 21.4839 20.244C20.3079 19.5533 19.4025 18.6387 18.7679 17.5C18.1519 16.3613 17.8439 15.1293 17.8439 13.804C17.8439 12.3853 18.1519 11.088 18.7679 9.912C19.3839 8.736 20.2799 7.79333 21.4559 7.084C22.6319 6.37467 24.0599 6.02 25.7399 6.02C27.4012 6.02 28.8199 6.37467 29.9959 7.084C31.1719 7.79333 32.0585 8.72667 32.6559 9.884C33.2719 11.0413 33.5799 12.2827 33.5799 13.608C33.5799 14.1493 33.5425 14.6253 33.4679 15.036H22.6039C22.6785 16.0253 23.0332 16.7813 23.6679 17.304C24.3212 17.808 25.0585 18.06 25.8799 18.06C26.5332 18.06 27.1585 17.9013 27.7559 17.584C28.3532 17.2667 28.7639 16.8373 28.9879 16.296L32.7959 17.36C32.2172 18.5173 31.3119 19.46 30.0799 20.188C28.8665 20.916 27.4199 21.28 25.7399 21.28ZM22.4919 12.292H28.8759C28.7825 11.3587 28.4372 10.6213 27.8399 10.08C27.2612 9.52 26.5425 9.24 25.6839 9.24C24.8252 9.24 24.0972 9.52 23.4999 10.08C22.9212 10.64 22.5852 11.3773 22.4919 12.292ZM49.7783 21H45.2983V12.74C45.2983 11.7693 45.1116 11.0693 44.7383 10.64C44.3836 10.192 43.9076 9.968 43.3103 9.968C42.6943 9.968 42.069 10.2107 41.4343 10.696C40.7996 11.1813 40.3516 11.8067 40.0903 12.572V21H35.6103V6.3H39.6423V8.764C40.1836 7.90533 40.949 7.23333 41.9383 6.748C42.9276 6.26267 44.0663 6.02 45.3543 6.02C46.3063 6.02 47.0716 6.19733 47.6503 6.552C48.2476 6.888 48.6956 7.336 48.9943 7.896C49.3116 8.43733 49.517 9.03467 49.6103 9.688C49.7223 10.3413 49.7783 10.976 49.7783 11.592V21ZM52.7548 4.62V0.559999H57.2348V4.62H52.7548ZM52.7548 21V6.3H57.2348V21H52.7548ZM63.4657 6.3L66.0697 10.444L66.3497 10.976L66.6297 10.444L69.2337 6.3H73.8537L68.9257 13.608L73.9657 21H69.3457L66.6017 16.884L66.3497 16.352L66.0977 16.884L63.3537 21H58.7337L63.7737 13.692L58.8457 6.3H63.4657Z" fill="black"/>
                </svg>
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
                                    <div class="header-info">
                                        <span style="color: #fff; font-weight: 600;"><strong>{{ Auth::user()->name }}</strong></span>
                                        <p class="fs-12 mb-0" style="color: rgba(255, 255, 255, 0.8);">{{ Auth::user()->email }}</p>
                                    </div>
                                    <img src="{{ asset('admin-theme/images/profile/pic1.jpg') }}" width="20" alt="" style="border-radius: 50%;">
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="#" class="dropdown-item ai-icon">
                                        <svg id="icon-user1" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <span class="ml-2">Profile </span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item ai-icon">
                                            <svg id="icon-logout" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                            <span class="ml-2">Logout </span>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        <div class="deznav">
            <div class="deznav-scroll">
                <ul class="metismenu" id="menu">
                    <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                            <i class="flaticon-381-networking"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        </ul>
                    </li>
                    <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                            <i class="flaticon-381-user-7"></i>
                            <span class="nav-text">Users</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="#">All Users</a></li>
                            <li><a href="#">Add User</a></li>
                        </ul>
                    </li>
                    <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                            <i class="flaticon-381-notepad"></i>
                            <span class="nav-text">Student Entries</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="{{ route('admin.students.index') }}">All Students</a></li>
                            <li><a href="{{ route('admin.students.create') }}">Add Student</a></li>
                        </ul>
                    </li>
                    <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                            <i class="flaticon-381-user-7"></i>
                            <span class="nav-text">Teachers</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="{{ route('admin.teachers.index') }}">All Teachers</a></li>
                            <li><a href="{{ route('admin.teachers.create') }}">Add Teacher</a></li>
                        </ul>
                    </li>
                    <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                            <i class="flaticon-381-settings-2"></i>
                            <span class="nav-text">Settings</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="{{ route('admin.settings.index') }}">Frontend Settings</a></li>
                            <li><a href="{{ route('admin.settings.about') }}">About Page</a></li>
                            <li><a href="{{ route('admin.settings.contact') }}">Contact Page</a></li>
                            <li><a href="{{ route('admin.settings.learning') }}">Learning Site Page</a></li>
                            <li><a href="{{ route('admin.settings.classes') }}">Classes Page</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="page-title d-flex justify-content-between align-items-center">
                            <h4 class="mb-0" style="font-size: 24px; font-weight: 600; color: #1f2937;">Frontend Settings</h4>
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
                            <div class="card-body">
                                <div class="profile-tab">
                                    <div class="custom-tab-1">
                                        <ul class="nav nav-tabs">
                                            <li class="nav-item"><a href="#hero" data-toggle="tab" class="nav-link active show">Hero Section</a></li>
                                            <li class="nav-item"><a href="#stats" data-toggle="tab" class="nav-link">Stats</a></li>
                                             <li class="nav-item"><a href="#testimonials" data-toggle="tab" class="nav-link">Testimonials Section</a></li>
                                             <li class="nav-item"><a href="#onboarding" data-toggle="tab" class="nav-link">Onboarding Section</a></li>
                                             <li class="nav-item"><a href="#mobileapp" data-toggle="tab" class="nav-link">Mobile App Section</a></li>
                                             <li class="nav-item"><a href="#whychooseus" data-toggle="tab" class="nav-link">Why Choose Us</a></li>
                                             <li class="nav-item"><a href="#classes_list" data-toggle="tab" class="nav-link">Classes Section</a></li>
                                             <li class="nav-item"><a href="#contact_social" data-toggle="tab" class="nav-link">Contact & Social</a></li>
                                             <li class="nav-item"><a href="#sections" data-toggle="tab" class="nav-link">Sections Text</a></li>
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
                                                                <input type="text" name="hero_title" class="form-control" value="{{ App\Models\SiteSetting::get('hero_title', 'Experience the Future of') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Hero Title Gradient Part</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="hero_title_gradient" class="form-control" value="{{ App\Models\SiteSetting::get('hero_title_gradient', ' Quality Online Learning') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Hero Description</label>
                                                            <div class="col-sm-9">
                                                                <textarea name="hero_description" class="form-control" rows="4">{{ App\Models\SiteSetting::get('hero_description', 'Top-notch online tutoring from qualified tutors at the comfort of your home. Join thousands of students achieving academic excellence with personalized learning.') }}</textarea>
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary mt-3">Save Hero Changes</button>
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
                                                                <input type="number" name="stats_years" class="form-control" value="{{ App\Models\SiteSetting::get('stats_years', 10) }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Total Students</label>
                                                            <div class="col-sm-9">
                                                                <input type="number" name="stats_students" class="form-control" value="{{ App\Models\SiteSetting::get('stats_students', 10000) }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Expert Tutors</label>
                                                            <div class="col-sm-9">
                                                                <input type="number" name="stats_tutors" class="form-control" value="{{ App\Models\SiteSetting::get('stats_tutors', 200) }}">
                                                            </div>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary mt-3">Save Stats Changes</button>
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
                                                                <input type="text" name="love_us_title" class="form-control" value="{{ App\Models\SiteSetting::get('love_us_title', 'Students & Parents Love Us') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Section Subtitle</label>
                                                            <div class="col-sm-9">
                                                                <textarea name="love_us_subtitle" class="form-control" rows="2">{{ App\Models\SiteSetting::get('love_us_subtitle', 'Join thousands of satisfied students and parents who trust TiT Online Education') }}</textarea>
                                                            </div>
                                                        </div>

                                                        <hr>
                                                        <h5 class="mb-3 text-primary">Key Statistics</h5>
                                                        <div class="row">
                                                            @for($i = 1; $i <= 4; $i++)
                                                            <div class="col-md-6 mb-3">
                                                                <div class="card p-3" style="border: 1px solid #eee;">
                                                                    <h6>Stat {{ $i }}</h6>
                                                                    <div class="form-group">
                                                                        <label>Number</label>
                                                                        <input type="text" name="love_us_stat{{ $i }}_number" class="form-control" value="{{ App\Models\SiteSetting::get('love_us_stat'.$i.'_number', '') }}">
                                                                    </div>
                                                                    <div class="form-group">
                                                                        <label>Label</label>
                                                                        <input type="text" name="love_us_stat{{ $i }}_label" class="form-control" value="{{ App\Models\SiteSetting::get('love_us_stat'.$i.'_label', '') }}">
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

                                                        <!-- Hidden textarea to store JSON for submission -->
                                                        <textarea name="love_us_testimonials" id="love_us_testimonials_hidden" class="d-none">{{ App\Models\SiteSetting::get('love_us_testimonials', '[]') }}</textarea>

                                                        <div class="alert alert-info py-2 mt-3" style="font-size: 13px;">
                                                            <b>Tip:</b> Click "Add New" to add a testimonial. Changes are synced automatically to the hidden field.
                                                        </div>

                                                        <button type="submit" class="btn btn-primary mt-3">Save Testimonials Changes</button>
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
                                                                 <input type="text" name="onboarding_title" class="form-control" value="{{ App\Models\SiteSetting::get('onboarding_title', 'Onboarding Process') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Section Subtitle</label>
                                                             <div class="col-sm-9">
                                                                 <textarea name="onboarding_subtitle" class="form-control" rows="2">{{ App\Models\SiteSetting::get('onboarding_subtitle', 'Follow our simple steps to join EduLearn Online Tuition 📚') }}</textarea>
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

                                                         <!-- Hidden textarea to store JSON for submission -->
                                                         <textarea name="onboarding_steps" id="onboarding_steps_hidden" class="d-none">{{ App\Models\SiteSetting::get('onboarding_steps', '[]') }}</textarea>

                                                         <div class="alert alert-info py-2 mt-3" style="font-size: 13px;">
                                                             <b>Tip:</b> Add the steps in the order you want them to appear.
                                                         </div>

                                                         <button type="submit" class="btn btn-primary mt-3">Save Onboarding Changes</button>
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
                                                                 <input type="text" name="mobile_title" class="form-control" value="{{ App\Models\SiteSetting::get('mobile_title', 'Learn Anytime, Anywhere..!') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Subtitle</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="mobile_subtitle" class="form-control" value="{{ App\Models\SiteSetting::get('mobile_subtitle', 'EduLearn Mobile App') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Description</label>
                                                             <div class="col-sm-9">
                                                                 <textarea name="mobile_description" class="form-control" rows="4">{{ App\Models\SiteSetting::get('mobile_description', 'Take your learning on the go with the EduLearn Mobile App, available on both iOS and Android. Access live classes, class recordings, exams, and progress updates seamlessly from your mobile device. Stay connected, stay updated, and unlock a world of learning at your fingertips—anytime, anywhere!') }}</textarea>
                                                             </div>
                                                         </div>

                                                         <hr>
                                                         <h5 class="mb-3 text-primary">Download Links</h5>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">App Store Link</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="mobile_app_store_link" class="form-control" value="{{ App\Models\SiteSetting::get('mobile_app_store_link', '#') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Google Play Link</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="mobile_play_store_link" class="form-control" value="{{ App\Models\SiteSetting::get('mobile_play_store_link', '#') }}">
                                                             </div>
                                                         </div>

                                                         <button type="submit" class="btn btn-primary mt-3">Save Mobile App Changes</button>
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
                                                                 <input type="text" name="why_title" class="form-control" value="{{ App\Models\SiteSetting::get('why_title', 'Why EduLearn?') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Subtitle</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="why_subtitle" class="form-control" value="{{ App\Models\SiteSetting::get('why_subtitle', 'Quality Assured Online Learning 👨🏻‍🎓') }}">
                                                             </div>
                                                         </div>

                                                         <hr>
                                                         <h5 class="mb-3 text-primary d-flex justify-content-between align-items-center">
                                                             Reasons/Features Manager
                                                             <button type="button" id="add-why-item" class="btn btn-info btn-xs">+ Add Reason</button>
                                                         </h5>

                                                         <div id="why-repeater">
                                                             <!-- Items will be injected here by JS -->
                                                         </div>

                                                         <!-- Hidden textarea to store JSON for submission -->
                                                         <textarea name="why_reasons" id="why_reasons_hidden" class="d-none">{{ App\Models\SiteSetting::get('why_reasons', '[]') }}</textarea>

                                                         <div class="alert alert-info py-2 mt-3" style="font-size: 13px;">
                                                             <b>Note:</b> These are the 3 cards shown in the "Why EduLearn?" section.
                                                         </div>

                                                         <button type="submit" class="btn btn-primary mt-3">Save Why Choose Us Changes</button>
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
                                                                 <input type="text" name="classes_title" class="form-control" value="{{ App\Models\SiteSetting::get('classes_title', 'Explore & Enroll') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Subtitle</label>
                                                             <div class="col-sm-9">
                                                                 <textarea name="classes_subtitle" class="form-control" rows="3">{{ App\Models\SiteSetting::get('classes_subtitle', 'Online Tuition for all subjects - Grade 1 to Advanced Level. Group or one-on-one? We got you!') }}</textarea>
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

                                                         <!-- Hidden textarea to store JSON for submission -->
                                                         <textarea name="classes_types" id="classes_types_hidden" class="d-none">{{ App\Models\SiteSetting::get('classes_types', '[]') }}</textarea>

                                                         <div class="alert alert-info py-2 mt-3" style="font-size: 13px;">
                                                             <b>Note:</b> These are the cards shown in the "Explore & Enroll" section.
                                                         </div>

                                                         <button type="submit" class="btn btn-primary mt-3">Save Classes Changes</button>
                                                     </form>
                                                 </div>
                                             </div>

                                             <!-- Sections Text -->
                                            <div id="sections" class="tab-pane fade">
                                                <div class="pt-4">
                                                    <div class="alert alert-info">
                                                        Generic section texts are now managed in their respective specialized tabs (Hero, Classes, Why Choose Us, etc.).
                                                    </div>
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
                                                                 <input type="email" name="footer_email" class="form-control" value="{{ App\Models\SiteSetting::get('footer_email', 'info@edulearn.lk') }}">
                                                                 <small class="text-muted">Used in Topbar, Footer, and Contact Page</small>
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Contact Phone</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="footer_phone" class="form-control" value="{{ App\Models\SiteSetting::get('footer_phone', '+94 114 477 488') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Location Address</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="contact_location" class="form-control" value="{{ App\Models\SiteSetting::get('contact_location', 'Colombo, Sri Lanka') }}">
                                                             </div>
                                                         </div>

                                                         <hr>
                                                         <h5 class="mb-3 text-primary">Social Media Links</h5>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Facebook</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="social_facebook" class="form-control" value="{{ App\Models\SiteSetting::get('social_facebook', '#facebook') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Instagram</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="social_instagram" class="form-control" value="{{ App\Models\SiteSetting::get('social_instagram', '#instagram') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">YouTube</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="social_youtube" class="form-control" value="{{ App\Models\SiteSetting::get('social_youtube', '#youtube') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">Twitter/X</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="social_twitter" class="form-control" value="{{ App\Models\SiteSetting::get('social_twitter', '#twitter') }}">
                                                             </div>
                                                         </div>
                                                         <div class="form-group row">
                                                             <label class="col-sm-3 col-form-label">LinkedIn</label>
                                                             <div class="col-sm-9">
                                                                 <input type="text" name="social_linkedin" class="form-control" value="{{ App\Models\SiteSetting::get('social_linkedin', '#linkedin') }}">
                                                             </div>
                                                         </div>

                                                         <button type="submit" class="btn btn-primary mt-3">Save Contact Changes</button>
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
                                                                <input type="text" name="footer_logo_text" class="form-control" value="{{ App\Models\SiteSetting::get('footer_logo_text', 'TiT') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Footer Tagline</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="footer_tagline" class="form-control" value="{{ App\Models\SiteSetting::get('footer_tagline', 'Your Education..! Our Priority..!') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Footer Description</label>
                                                            <div class="col-sm-9">
                                                                <textarea name="footer_description" class="form-control" rows="3">{{ App\Models\SiteSetting::get('footer_description', "Sri Lanka's trusted leader in online tuition. We ensure student success through personalized learning and comprehensive support.") }}</textarea>
                                                            </div>
                                                        </div>

                                                        <hr>
                                                        <h5 class="mb-3 text-primary">Legal Links</h5>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Privacy Policy Link</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="footer_privacy_link" class="form-control" value="{{ App\Models\SiteSetting::get('footer_privacy_link', '#privacy') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Terms & Conditions Link</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="footer_terms_link" class="form-control" value="{{ App\Models\SiteSetting::get('footer_terms_link', '#terms') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Refund Policy Link</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="footer_refund_link" class="form-control" value="{{ App\Models\SiteSetting::get('footer_refund_link', '#refund') }}">
                                                            </div>
                                                        </div>

                                                        <hr>
                                                        <h5 class="mb-3 text-primary">Quick Links</h5>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Gallery Link</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="footer_gallery_link" class="form-control" value="{{ App\Models\SiteSetting::get('footer_gallery_link', '#gallery') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Apply as Tutor Link</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="footer_tutor_link" class="form-control" value="{{ App\Models\SiteSetting::get('footer_tutor_link', '#tutor') }}">
                                                            </div>
                                                        </div>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Register Student Link</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="footer_register_link" class="form-control" value="{{ App\Models\SiteSetting::get('footer_register_link', '#register') }}">
                                                            </div>
                                                        </div>

                                                        <hr>
                                                        <h5 class="mb-3 text-primary">Copyright</h5>
                                                        <div class="form-group row">
                                                            <label class="col-sm-3 col-form-label">Copyright Text</label>
                                                            <div class="col-sm-9">
                                                                <input type="text" name="footer_copyright" class="form-control" value="{{ App\Models\SiteSetting::get('footer_copyright', 'Copyrights © 2025 TiT. All rights reserved by TiT Online Education (PVT) Ltd.') }}">
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
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- Required vendors -->
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
    <style>
        .testimonial-item {
            background: #3b3363;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            transition: all 0.3s;
            color: #fff;
        }
        .testimonial-item:hover {
            box-shadow: 0 8px 15px rgba(0,0,0,0.2);
            border-color: #EB8153;
        }
        .remove-testimonial {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #ff5e5e;
            cursor: pointer;
            z-index: 10;
            background: rgba(255,255,255,0.1);
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }
        .remove-testimonial:hover {
            background: #ff5e5e;
            color: #fff;
        }
        .testimonial-item label {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: rgba(255,255,255,0.6);
            margin-bottom: 5px;
            display: block;
        }
        .t-image-preview-wrapper {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            overflow: hidden;
            background: rgba(255,255,255,0.05);
            border: 2px dashed rgba(255,255,255,0.2);
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
            font-size: 20px;
        }
        .t-image-preview-wrapper:hover .t-image-overlay {
            opacity: 1;
        }
        .form-control-sm {
            background: rgba(255,255,255,0.05) !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: #fff !important;
        }
        .form-control-sm:focus {
            border-color: #EB8153 !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // --- Testimonials Repeater Logic ---
            const hiddenTextarea = document.getElementById('love_us_testimonials_hidden');
            const addButton = document.getElementById('add-testimonial');
            let testimonials = [];
            try { testimonials = JSON.parse(hiddenTextarea.value || '[]'); } catch (e) { testimonials = []; }

            function syncTestimonials() {
                const items = [];
                document.querySelectorAll('.testimonial-item').forEach(el => {
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
                    <div class="testimonial-item" id="item-${id}">
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
                                <div class="upload-status mt-1" id="status-${id}" style="font-size: 10px;"></div>
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
                        number: el.querySelector('.o-number').value,
                        title: el.querySelector('.o-title').value,
                        description: el.querySelector('.o-description').value
                    });
                });
                onboardingHidden.value = JSON.stringify(items);
            }

            function createOnboardingItem(data = {}) {
                const id = Date.now() + Math.random().toString(36).substr(2, 9);
                const html = `
                    <div class="testimonial-item onboarding-item" id="onb-${id}">
                        <span class="remove-testimonial" onclick="document.getElementById('onb-${id}').remove(); window.syncOnboarding();">
                            <i class="flaticon-381-close"></i>
                        </span>
                        <div class="row">
                            <div class="col-md-2 mb-2">
                                <label>Step Number</label>
                                <input type="text" class="form-control form-control-sm o-number" value="${data.number || ''}" placeholder="e.g. 01" oninput="window.syncOnboarding()">
                            </div>
                            <div class="col-md-10 mb-2">
                                <label>Step Title</label>
                                <input type="text" class="form-control form-control-sm o-title" value="${data.title || ''}" placeholder="e.g. Register" oninput="window.syncOnboarding()">
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
                        gradient: el.querySelector('.w-gradient').value
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

                const html = `
                    <div class="testimonial-item why-item" id="why-${id}">
                        <span class="remove-testimonial" onclick="document.getElementById('why-${id}').remove(); window.syncWhy();">
                            <i class="flaticon-381-close"></i>
                        </span>
                        <div class="row">
                            <div class="col-md-5 mb-2">
                                <label>Title</label>
                                <input type="text" class="form-control form-control-sm w-title" value="${data.title || ''}" placeholder="e.g. Quality Learning" oninput="window.syncWhy()">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Subtitle</label>
                                <input type="text" class="form-control form-control-sm w-subtitle" value="${data.subtitle || ''}" placeholder="e.g. Proven Results" oninput="window.syncWhy()">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>Gradient Color</label>
                                <select class="form-control form-control-sm w-gradient" onchange="window.syncWhy()">
                                    ${gradientOptions}
                                </select>
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

            // --- Classes Repeater Logic ---
            const classesHidden = document.getElementById('classes_types_hidden');
            const addClassBtn = document.getElementById('add-class-type');
            const classesRepeater = document.getElementById('classes-repeater');
            let classesTypes = [];
            try { classesTypes = JSON.parse(classesHidden.value || '[]'); } catch (e) { classesTypes = []; }

            function syncClasses() {
                const items = [];
                document.querySelectorAll('.class-type-item').forEach(el => {
                    items.push({
                        title: el.querySelector('.c-title').value,
                        description: el.querySelector('.c-description').value,
                        color: el.querySelector('.c-color').value
                    });
                });
                classesHidden.value = JSON.stringify(items);
            }

            function createClassTypeItem(data = {}) {
                const id = Date.now() + Math.random().toString(36).substr(2, 9);
                const colors = ['gradient-1', 'gradient-2', 'gradient-3'];
                let colorOptions = '';
                colors.forEach(c => {
                    colorOptions += `<option value="${c}" ${data.color === c ? 'selected' : ''}>${c.replace('-', ' ')}</option>`;
                });

                const html = `
                    <div class="testimonial-item class-type-item" id="cls-${id}">
                        <span class="remove-testimonial" onclick="document.getElementById('cls-${id}').remove(); window.syncClasses();">
                            <i class="flaticon-381-close"></i>
                        </span>
                        <div class="row">
                            <div class="col-md-8 mb-2">
                                <label>Course Title</label>
                                <input type="text" class="form-control form-control-sm c-title" value="${data.title || ''}" placeholder="e.g. Sri Lankan Syllabus" oninput="window.syncClasses()">
                            </div>
                            <div class="col-md-4 mb-2">
                                <label>Color Theme</label>
                                <select class="form-control form-control-sm c-color" onchange="window.syncClasses()">
                                    ${colorOptions}
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label>Description</label>
                                <textarea class="form-control form-control-sm c-description" rows="2" placeholder="Describe this course type..." oninput="window.syncClasses()">${data.description || ''}</textarea>
                            </div>
                        </div>
                    </div>
                `;
                classesRepeater.insertAdjacentHTML('beforeend', html);
            }

            window.syncTestimonials = syncTestimonials;
            window.syncOnboarding = syncOnboarding;
            window.syncWhy = syncWhy;
            window.syncClasses = syncClasses;

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
            if (testimonials.length > 0) testimonials.forEach(t => createTestimonialItem(t));
            if (onboardingSteps.length > 0) onboardingSteps.forEach(s => createOnboardingItem(s));
            if (whyReasons.length > 0) whyReasons.forEach(r => createWhyItem(r));
            if (classesTypes.length > 0) classesTypes.forEach(c => createClassTypeItem(c));

            addButton.addEventListener('click', () => { createTestimonialItem(); syncTestimonials(); });
            addOnboardingBtn.addEventListener('click', () => { createOnboardingItem(); syncOnboarding(); });
            addWhyBtn.addEventListener('click', () => { createWhyItem(); syncWhy(); });
            addClassBtn.addEventListener('click', () => { createClassTypeItem(); syncClasses(); });
        });
    </script>
</body>
</html>

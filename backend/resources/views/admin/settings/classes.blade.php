<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Classes Settings - {{ config('app.name') }}</title>
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

        .header {
            background: #1f2937;
        }

        .header-profile .nav-link {
            display: flex;
            align-items: center;
            gap: 0.625rem;
        }

        .nav-header .brand-logo {
            display: flex;
            align-items: center;
            padding-left: 1.25rem;
        }

        .btn-primary:hover {
            transform: translateY(-0.125rem);
            box-shadow: 0 0.25rem 0.75rem rgba(102, 126, 234, 0.4) !important;
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
                    <img src="{{ asset($site_settings['admin_logo']) }}" alt="Logo" style="max-height: 2.8125rem; max-width: 2.8125rem; object-fit: contain;">
                @else
                    <svg class="logo-abbr" width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect class="svg-logo-rect" width="50" height="50" rx="20" fill="#EB8153"/>
                        <path class="svg-logo-path" d="M17.5158 25.8619L19.8088 25.2475L14.8746 11.1774C14.5189 9.84988 15.8701 9.0998 16.8205 9.75055L33.0924 22.2055C33.7045 22.5589 33.8512 24.0717 32.6444 24.3951L30.3514 25.0095L35.2856 39.0796C35.6973 40.1334 34.4431 41.2455 33.3397 40.5064L17.0678 28.0515C16.2057 27.2477 16.5504 26.1205 17.5158 25.8619ZM18.685 14.2955L22.2224 24.6007L29.4633 22.6605L18.685 14.2955ZM31.4751 35.9615L27.8171 25.6886L20.5762 27.6288L31.4751 35.9615Z" fill="white"/>
                    </svg>
                @endif
                <span class="brand-title" style="font-size: 1.5rem; font-weight: 700; margin-left:0.75rem; color: #fff;">
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
                             <li class="nav-item" style="margin-right: 1.25rem;">
                                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="btn btn-primary btn-sm"
                                   style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                          border: none;
                                          padding: 0.5rem 0.75rem;
                                          border-radius: 0.375rem;
                                          color: white;
                                          font-weight: 500;
                                          text-decoration: none;
                                          display: inline-flex;
                                          align-items: center;
                                          gap: 0.3125rem;
                                          transition: all 0.3s ease;
                                          box-shadow: 0 0.125rem 0.5rem rgba(102, 126, 234, 0.3);
                                          cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                                    </svg>
                                    Home
                                </a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link ai-icon" href="{{ route('admin.messages.index') }}" title="Messages" style="position: relative;">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M22.1667 5.83331H5.83333C4.54467 5.83331 3.5 6.878 3.5 8.16665V19.8333C3.5 21.122 4.54467 22.1666 5.83333 22.1666H22.1667C23.4553 22.1666 24.5 21.122 24.5 19.8333V8.16665C24.5 6.878 23.4553 5.83331 22.1667 5.83331Z" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3.5 8.16665L14 15.1666L24.5 8.16665" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <div class="pulse-css d-none" id="message-pulse" style="width: 1.125rem; height: 1.125rem; background: #EB8153; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 0.0px; right: -0.3125rem; box-shadow: 0 0 0 0.125rem #fff;">
                                        <span id="message-count" class="text-white d-none" style="font-size: 0.625rem; font-weight: bold; line-height: 1;">0</span>
                                    </div>
                                </a>
                            </li>

                            <li class="nav-item dropdown notification_dropdown">
                                <a class="nav-link ai-icon" href="#" role="button" data-toggle="dropdown">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M22.75 23.0417H5.25C4.84174 23.0417 4.44973 22.8791 4.16142 22.5891C3.87311 22.2991 3.71128 21.9058 3.71245 21.4958C3.71245 18.8033 4.75412 16.2133 6.65 14.3942V9.33333C6.65 6.65906 7.71235 4.09451 9.6033 2.2033C11.4945 0.31235 14.0591 -0.75 16.7333 -0.75C19.4076 -0.75 21.9721 0.31235 23.8633 2.2033C25.7543 4.09451 26.8167 6.65906 26.8167 9.33333V14.3942C28.7125 16.2133 29.7541 18.8033 29.7541 21.4958C29.7553 21.9058 29.5935 22.2991 29.3052 22.5891C29.0169 22.8791 28.6249 23.0417 28.2167 23.0417H22.75ZM7.11667 20.125H26.3417C26.0465 18.2808 25.1017 16.6067 23.6654 15.405C23.2798 15.0842 23.0567 14.6067 23.0567 14.1033V9.33333C23.0567 7.65363 22.3894 6.04272 21.2017 4.855C20.014 3.66728 18.403 3 16.7233 3C15.0436 3 13.4327 3.66728 12.245 4.855C11.0573 6.04272 10.39 7.65363 10.39 9.33333V14.1033C10.39 14.6067 10.1669 15.0842 9.78125 15.405C8.34493 16.6067 7.40013 18.2808 7.105 20.125H7.11667ZM16.7233 27.25C15.6558 27.25 14.6158 26.8833 13.7783 26.205C13.4358 25.9258 13.3758 25.42 13.6458 25.0667C13.9167 24.7133 14.4142 24.6533 14.7667 24.9325C15.305 25.3675 16.0075 25.5992 16.7233 25.5992C17.4392 25.5992 18.1417 25.3675 18.68 24.9325C19.0325 24.6533 19.53 24.7133 19.8008 25.0667C20.0717 25.42 20.0117 25.9258 19.6683 26.205C18.8308 26.8833 17.7908 27.25 16.7233 27.25Z" fill="#3D4461"/>
                                    </svg>
                                    <div class="pulse-css d-none" id="notification-pulse" style="width: 1.125rem; height: 1.125rem; background: #EB8153; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 0.0px; right: -0.3125rem; box-shadow: 0 0 0 0.125rem #fff;">
                                        <span id="notification-count" class="text-white d-none" style="font-size: 0.625rem; font-weight: bold; line-height: 1;">0</span>
                                    </div>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div id="DZ_W_Notification1" class="set-height widget-media dz-scroll p-3">
                                        <ul class="timeline" id="notification-list">
                                            <li class="text-center py-3">No new notifications</li>
                                        </ul>
                                    </div>
                                    <a class="all-notification" href="{{ route('admin.notifications.index') }}">See all notifications <i class="ti-arrow-right"></i></a>
                                </div>
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
                                        <div class="header-profile-initials" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: #EB8153; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
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
                            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #fff;">Classes Settings</h4>
                            <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}/classes" target="_blank" class="btn btn-primary btn-sm">
                                View Classes Page
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

                                    <hr class="my-4">

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h5 class="text-primary mb-0">Class Types & Categories</h5>
                                        <button type="button" class="btn btn-info btn-sm" id="add-class-type">
                                            <i class="fa fa-plus mr-2"></i> Add New Class Type
                                        </button>
                                    </div>

                                    <div id="class-types-container">
                                        @php
                                            $types = json_decode(\App\Models\SiteSetting::get('classes_types', '[]'), true);
                                            
                                            // Migration logic if empty
                                            if (empty($types)) {
                                                $types = [
                                                    [
                                                        'id' => uniqid(),
                                                        'title' => 'Direct Physical Class',
                                                        'description' => \App\Models\SiteSetting::get('classes_direct_description', 'Comprehensive face-to-face learning experience with expert tutors in a physical classroom setting.'),
                                                        'duration' => \App\Models\SiteSetting::get('classes_direct_duration', 'Flexible schedules'),
                                                        'price' => \App\Models\SiteSetting::get('classes_direct_price', 'Affordable rates'),
                                                        'format' => \App\Models\SiteSetting::get('classes_direct_format', 'Small Groups'),
                                                        'features' => \App\Models\SiteSetting::get('classes_direct_features', "Small group sessions\nDirect teacher interaction\nPhysical learning materials\nIn-person assessments\nFocus and discipline"),
                                                        'subjects' => \App\Models\SiteSetting::get('classes_direct_subjects', 'Mathematics, Science, English, Sinhala, Tamil, History, Geography, Commerce, ICT, Art'),
                                                        'color' => '#EB8153',
                                                        'image' => '',
                                                        'stars' => 5
                                                    ],
                                                    [
                                                        'id' => uniqid(),
                                                        'title' => 'Online Live Class',
                                                        'description' => \App\Models\SiteSetting::get('classes_online_description', 'Convenient live interactive sessions accessible from anywhere with high-quality digital resources.'),
                                                        'duration' => \App\Models\SiteSetting::get('classes_online_duration', 'Flexible schedules'),
                                                        'price' => \App\Models\SiteSetting::get('classes_online_price', 'Competitive pricing'),
                                                        'format' => \App\Models\SiteSetting::get('classes_online_format', 'Group & One-on-One'),
                                                        'features' => \App\Models\SiteSetting::get('classes_online_features', "Interactive live classes\nRecorded lesson access\nDigital study materials\nOnline quizzes/exams\nFlexible learning from home"),
                                                        'subjects' => \App\Models\SiteSetting::get('classes_online_subjects', 'Mathematics, Physics, Chemistry, Biology, English, Business Studies, Economics, Accounting, ICT, Computer Science'),
                                                        'color' => '#667eea',
                                                        'image' => '',
                                                        'stars' => 5
                                                    ]
                                                ];
                                            }
                                        @endphp

                                        @foreach($types as $index => $type)
                                            <div class="class-type-item mb-4" data-index="{{ $index }}">
                                                <div class="class-edit-section">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <input type="text" name="classes_types[{{ $index }}][title]" class="form-control font-weight-bold" value="{{ $type['title'] ?? 'New Class Type' }}" style="font-size: 1.1rem; border: none !important; background: transparent !important; padding-left: 0;">
                                                        <button type="button" class="btn btn-danger btn-xs remove-class-type">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </div>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <div class="form-group">
                                                                <label>Description</label>
                                                                <textarea name="classes_types[{{ $index }}][description]" class="form-control" rows="3">{{ $type['description'] ?? '' }}</textarea>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Duration</label>
                                                                        <input type="text" name="classes_types[{{ $index }}][duration]" class="form-control" value="{{ $type['duration'] ?? '' }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Pricing Label</label>
                                                                        <input type="text" name="classes_types[{{ $index }}][price]" class="form-control" value="{{ $type['price'] ?? '' }}">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Format / Students</label>
                                                                        <input type="text" name="classes_types[{{ $index }}][format]" class="form-control" value="{{ $type['format'] ?? '' }}">
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="form-group">
                                                                        <label>Color Theme</label>
                                                                        <div class="d-flex align-items-center gap-2">
                                                                            <input type="color" name="classes_types[{{ $index }}][color]" class="form-control p-1" value="{{ $type['color'] ?? '#EB8153' }}" style="width: 3.125rem; height: 2.1875rem;">
                                                                            <span class="ml-2 text-muted small">{{ $type['color'] ?? '#EB8153' }}</span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Star Rating</label>
                                                                <select name="classes_types[{{ $index }}][stars]" class="form-control">
                                                                    @for($i = 1; $i <= 5; $i++)
                                                                        <option value="{{ $i }}" {{ ($type['stars'] ?? 5) == $i ? 'selected' : '' }}>{{ $i }} Stars</option>
                                                                    @endfor
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <div class="form-group text-center">
                                                                <label>Card Image</label>
                                                                <div class="image-preview-container mb-2" style="background: rgba(0,0,0,0.3); border: 2.0px dashed rgba(255,255,255,0.1); border-radius: 0.5rem; min-height: 15.625rem; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                                                    <img src="{{ !empty($type['image']) ? asset($type['image']) : '' }}" class="img-fluid" style="{{ !empty($type['image']) ? 'display: block;' : 'display: none;' }} max-height: 15.625rem;">
                                                                    <div class="no-image-placeholder" style="{{ !empty($type['image']) ? 'display: none;' : 'display: block;' }}">
                                                                        <i class="fa fa-image fa-3x mb-2 text-muted"></i>
                                                                        <p class="small text-muted">No image uploaded</p>
                                                                    </div>
                                                                    <button type="button" class="btn btn-danger btn-xs position-absolute remove-image" style="top: 0.625rem; right: 0.625rem; {{ !empty($type['image']) ? '' : 'display: none;' }}">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </div>
                                                                <input type="hidden" name="classes_types[{{ $index }}][image]" value="{{ $type['image'] ?? '' }}" class="image-path-input">
                                                                <button type="button" class="btn btn-info btn-xs btn-block upload-image-btn">
                                                                    <i class="fa fa-upload mr-1"></i> Upload Image
                                                                </button>
                                                                <input type="file" class="d-none dynamic-image-input" accept="image/*">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="row">
                                                        <!-- <div class="col-md-6">
                                                            <div class="form-group text-left">
                                                                <label>Features (One per line)</label>
                                                                <textarea name="classes_types[{{ $index }}][features]" class="form-control" rows="5">{{ $type['features'] ?? '' }}</textarea>
                                                            </div>
                                                        </div> -->
                                                        <!-- <div class="col-md-6">
                                                            <div class="form-group text-left">
                                                                <label>Available Subjects (Comma separated)</label>
                                                                <textarea name="classes_types[{{ $index }}][subjects]" class="form-control" rows="5">{{ $type['subjects'] ?? '' }}</textarea>
                                                            </div>
                                                        </div> -->
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-4 text-center">
                                        <button type="submit" class="btn btn-primary btn-lg px-5">Save All Classes Settings</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright @ {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- Required vendors -->
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-search.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>

    <script>
        $(document).ready(function() {
            // Function to re-index items after removal
            function reIndexItems() {
                $('#class-types-container .class-type-item').each(function(index) {
                    $(this).attr('data-index', index);
                    $(this).find('[name^="classes_types"]').each(function() {
                        let name = $(this).attr('name');
                        $(this).attr('name', name.replace(/classes_types\[\d+\]/, 'classes_types[' + index + ']'));
                    });
                });
            }

            // Add new class type
            $('#add-class-type').click(function() {
                let index = $('#class-types-container .class-type-item').length;
                let template = `
                    <div class="class-type-item mb-4" data-index="${index}">
                        <div class="class-edit-section">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <input type="text" name="classes_types[${index}][title]" class="form-control font-weight-bold" value="New Class Type" style="font-size: 1.1rem; border: none !important; background: transparent !important; padding-left: 0;">
                                <button type="button" class="btn btn-danger btn-xs remove-class-type">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="classes_types[${index}][description]" class="form-control" rows="3"></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Duration</label>
                                                <input type="text" name="classes_types[${index}][duration]" class="form-control" value="Flexible schedules">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Pricing Label</label>
                                                <input type="text" name="classes_types[${index}][price]" class="form-control" value="Affordable rates">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Format / Students</label>
                                                <input type="text" name="classes_types[${index}][format]" class="form-control" value="Small Groups">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Color Theme</label>
                                                <div class="d-flex align-items-center gap-2">
                                                    <input type="color" name="classes_types[${index}][color]" class="form-control p-1" value="#EB8153" style="width: 3.125rem; height: 2.1875rem;">
                                                    <span class="ml-2 text-muted small">#EB8153</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Star Rating</label>
                                        <select name="classes_types[${index}][stars]" class="form-control">
                                            <option value="1">1 Stars</option>
                                            <option value="2">2 Stars</option>
                                            <option value="3">3 Stars</option>
                                            <option value="4">4 Stars</option>
                                            <option value="5" selected>5 Stars</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group text-center">
                                        <label>Card Image</label>
                                        <div class="image-preview-container mb-2" style="background: rgba(0,0,0,0.3); border: 2.0px dashed rgba(255,255,255,0.1); border-radius: 0.5rem; min-height: 15.625rem; display: flex; align-items: center; justify-content: center; overflow: hidden; position: relative;">
                                            <img src="" class="img-fluid" style="display: none; max-height: 15.625rem;">
                                            <div class="no-image-placeholder">
                                                <i class="fa fa-image fa-3x mb-2 text-muted"></i>
                                                <p class="small text-muted">No image uploaded</p>
                                            </div>
                                            <button type="button" class="btn btn-danger btn-xs position-absolute remove-image" style="top: 0.625rem; right: 0.625rem; display: none;">
                                                <i class="fa fa-times"></i>
                                            </button>
                                        </div>
                                        <input type="hidden" name="classes_types[${index}][image]" value="" class="image-path-input">
                                        <button type="button" class="btn btn-info btn-xs btn-block upload-image-btn">
                                            <i class="fa fa-upload mr-1"></i> Upload Image
                                        </button>
                                        <input type="file" class="d-none dynamic-image-input" accept="image/*">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group text-left">
                                        <label>Features (One per line)</label>
                                        <textarea name="classes_types[${index}][features]" class="form-control" rows="5"></textarea>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group text-left">
                                        <label>Available Subjects (Comma separated)</label>
                                        <textarea name="classes_types[${index}][subjects]" class="form-control" rows="5"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                $('#class-types-container').append(template);
            });

            // Remove class type
            $(document).on('click', '.remove-class-type', function() {
                if ($('#class-types-container .class-type-item').length > 1) {
                    if (confirm('Are you sure you want to remove this class type?')) {
                        $(this).closest('.class-type-item').remove();
                        reIndexItems();
                    }
                } else {
                    alert('You must have at least one class type.');
                }
            });

            // Image Upload Trigger
            $(document).on('click', '.upload-image-btn', function() {
                $(this).closest('.form-group').find('.dynamic-image-input').click();
            });

            // AJAX Image Upload
            $(document).on('change', '.dynamic-image-input', function() {
                let input = this;
                let container = $(this).closest('.form-group');
                let file = input.files[0];
                
                if (file) {
                    let formData = new FormData();
                    formData.append('image', file);
                    formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

                    // Show loading state on button
                    let uploadBtn = container.find('.upload-image-btn');
                    let originalText = uploadBtn.html();
                    uploadBtn.html('<i class="fa fa-spinner fa-spin mr-1"></i> Uploading...').prop('disabled', true);

                    $.ajax({
                        url: "{{ route('admin.settings.upload') }}",
                        method: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                container.find('.image-path-input').val(response.relative_path);
                                container.find('.image-preview-container img').attr('src', response.path).show();
                                container.find('.no-image-placeholder').hide();
                                container.find('.remove-image').show();
                                toastr.success('Image uploaded successfully');
                            } else {
                                toastr.error(response.message || 'Upload failed');
                            }
                        },
                        error: function() {
                            toastr.error('Connection error occurred');
                        },
                        complete: function() {
                            uploadBtn.html(originalText).prop('disabled', false);
                        }
                    });
                }
            });

            // Remove Image
            $(document).on('click', '.remove-image', function() {
                let container = $(this).closest('.form-group');
                container.find('.image-path-input').val('');
                container.find('.image-preview-container img').attr('src', '').hide();
                container.find('.no-image-placeholder').show();
                $(this).hide();
            });

            // Update color hex display
            $(document).on('input', 'input[type="color"]', function() {
                $(this).next('span').text($(this).val());
            });
        });
    </script>
</body>

</html>





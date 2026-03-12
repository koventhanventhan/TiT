<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Student Entry - {{ config('app.name') }}</title>
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
        .form-group label {
            font-weight: 600;
            color: #ffffff;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            padding: 10px 15px;
        }
        [data-theme-version="dark"] .form-control {
            background-color: transparent !important;
            border-color: #eb8153 !important;
            color: #ffffff !important;
        }
        [data-theme-version="dark"] .form-control option {
            background-color: #1a152e !important;
            color: #ffffff !important;
        }
        [data-theme-version="dark"] .form-control::placeholder {
            color: #938787;
        }
        [data-theme-version="dark"] .form-group label {
            color: #ffffff !important;
        }
        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        .radio-group {
            display: flex;
            gap: 14px;
            margin-top: 8px;
        }
        .radio-label {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 12px 20px;
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 12px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(99, 102, 241, 0.1);
            backdrop-filter: blur(10px);
            flex: 1;
            min-width: 120px;
            justify-content: center;
        }
        .radio-label:hover {
            border-color: rgba(139, 92, 246, 0.6);
            background: rgba(99, 102, 241, 0.2);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }
        .radio-label span {
            color: #6366f1;
            font-weight: 500;
            font-size: 14px;
            transition: color 0.3s ease;
        }
        .radio-label input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #8b5cf6;
            margin: 0;
        }
        .radio-label input[type="radio"]:checked + span {
            color: #4f46e5;
            font-weight: 600;
        }
        .radio-label.checked,
        .radio-label:has(input[type="radio"]:checked) {
            border-color: rgba(139, 92, 246, 0.8);
            background: linear-gradient(135deg, rgba(139, 92, 246, 0.25) 0%, rgba(99, 102, 241, 0.2) 100%);
            box-shadow: 
                0 0 0 3px rgba(139, 92, 246, 0.15),
                0 4px 12px rgba(139, 92, 246, 0.3);
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
                                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 8px 20px; border-radius: 6px; color: white; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3); cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
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
                            <h4 class="mb-0" style="font-size: 24px; font-weight: 600; color: #1f2937;">Edit Student Entry</h4>
                            <a href="{{ route('admin.students.index') }}" class="btn btn-secondary btn-sm">
                                <i class="flaticon-381-back"></i> Back to List
                            </a>
                        </div>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">Student Information</h4>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.students.update', $student->id) }}" id="studentEditForm">
                                    @csrf
                                    @method('PUT')

                                    @php
                                        // Pre-decode subjects for easy checking
                                        $currentSubjects = [];
                                        if ($student->selected_subjects) {
                                            if (is_array($student->selected_subjects)) {
                                                $currentSubjects = $student->selected_subjects;
                                            } else {
                                                $decoded = json_decode($student->selected_subjects, true);
                                                $currentSubjects = is_array($decoded) ? $decoded : array_map('trim', explode(',', (string)$student->selected_subjects));
                                            }
                                        }
                                        
                                        // Subject Master Lists
                                        $subjectsGrade1to5 = ['à®¤à®®à®¿à®´à¯', 'à®†à®™à¯à®•à®¿à®²à®®à¯', 'à®šà¯‚à®´à®±à¯Â­à®±à®¾à®Ÿà®²à¯', 'à®šà®®à®¯à®®à¯', 'à®šà®¿à®™à¯à®•à®³à®®à¯', 'à®ªà¯à®²à®®à¯ˆà®ªà¯à®ªà®°à®¿à®šà®¿à®²à¯ à®µà®•à¯à®ªà¯à®ªà¯à®•à®³à¯'];
                                        $subjectsGrade6to11 = ['à®¤à®®à®¿à®´à¯', 'à®†à®™à¯à®•à®¿à®²à®®à¯', 'à®•à®£à®¿à®¤à®®à¯', 'à®µà®°à®²à®¾à®±à¯', 'à®šà®®à®¯à®®à¯', 'à®µà®¿à®žà¯à®žà®¾à®©à®®à¯', 'à®•à¯à®Ÿà®¿à®¯à®¿à®¯à®²à¯ à®•à®²à¯à®µà®¿', 'à®ªà¯à®µà®¿à®¯à®¿à®¯à®²à¯', 'à®šà®¿à®™à¯à®•à®³à®®à¯', 'ICT', 'à®šà¯à®•à®¾à®¤à®¾à®°à®®à¯ à®‰à®³à¯à®•à®²à¯à®µà®¿à®¯à¯à®®à¯', 'à®µà®£à®¿à®•à®•à¯ à®•à®²à¯à®µà®¿', 'à®‡à®²à®•à¯à®•à®¿à®¯à®®à¯ (à®¤à®®à®¿à®´à¯)'];
                                        $subjectsArtsStream = ['à®¤à®®à®¿à®´à¯', 'à®µà®°à®²à®¾à®±à¯', 'à®ªà¯à®µà®¿à®¯à®¿à®¯à®²à¯', 'ICT', 'à®…à®°à®šà®¿à®¯à®²à¯ à®µà®¿à®žà¯à®žà®¾à®©à®®à¯', 'à®‡à®¨à¯à®¤à¯ à®¨à®¾à®•à®°à®¿à®•à®®à¯', 'à®®à®©à¯ˆà®ªà¯à®ªà¯Šà®°à¯à®³à®¿à®¯à®²à¯', 'à®Šà®Ÿà®•à®•à¯ à®•à®²à¯à®µà®¿', 'à®¨à®Ÿà®©à®®à¯', 'à®¨à®¾à®Ÿà®•à®®à¯', 'à®šà®¿à®¤à¯à®¤à®¿à®°à®®à¯', 'à®šà®™à¯à®•à¯€à®¤à®®à¯', 'à®•à®¿à®±à®¿à®¸à¯à®¤à®µ à®¨à®¾à®•à®°à®¿à®•à®®à¯', 'à®…à®³à®µà¯ˆà®¯à®¿à®¯à®²à¯'];
                                        $subjectsBioMathsStream = ['à®‡à®£à¯ˆà®¨à¯à®¤ à®•à®£à®¿à®¤à®®à¯', 'à®‰à®¯à®¿à®°à®¿à®¯à®²à¯', 'à®ªà¯†à®³à®¤à®¿à®•à®µà®¿à®¯à®²à¯', 'à®‡à®°à®šà®¾à®¯à®©à®µà®¿à®¯à®²à¯', 'ICT'];
                                    @endphp

                                    <div class="row">
                                        <!-- Full Name -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>Full Name / à®®à¯à®´à¯à®ªà¯ à®ªà¯†à®¯à®°à¯ <span class="text-danger">*</span></label>
                                                <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $student->full_name) }}" required>
                                            </div>
                                        </div>

                                        <!-- Phone Number -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>Phone / WhatsApp <span class="text-danger">*</span></label>
                                                <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $student->phone_number) }}" required>
                                            </div>
                                        </div>

                                        <!-- Email Address -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>Email Address <span class="text-danger">*</span></label>
                                                <input type="email" name="email" class="form-control" value="{{ old('email', $student->email) }}" required>
                                            </div>
                                        </div>

                                        <!-- Password -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>Password / à®•à®Ÿà®µà¯à®šà¯à®šà¯Šà®²à¯ <small class="text-muted">(Leave blank to keep current)</small></label>
                                                <div style="position: relative;">
                                                    <input type="text" name="password" id="editPassword" class="form-control" value="{{ $student->plain_password }}" placeholder="{{ $student->plain_password ? '' : 'Enter new password' }}" minlength="8" style="padding-right: 50px;">
                                                    <button type="button" onclick="togglePassword('editPassword', 'editEyeIcon')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6366f1; font-size: 18px; padding: 5px;">
                                                        <span id="editEyeIcon">ðŸ™ˆ</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Date of Birth -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>Date of Birth / à®ªà®¿à®±à®¨à¯à®¤ à®¤à®¿à®•à®¤à®¿ <span class="text-danger">*</span></label>
                                                <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', is_string($student->date_of_birth) ? $student->date_of_birth : (optional($student->date_of_birth)->format('Y-m-d') ?? '')) }}" required max="{{ date('Y-m-d') }}">
                                            </div>
                                        </div>

                                        <!-- Gender -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>Gender / à®ªà®¾à®²à®¿à®©à®®à¯ <span class="text-danger">*</span></label>
                                                <select name="gender" class="form-control" required>
                                                    <option value="">Select Gender</option>
                                                    <option value="male" {{ old('gender', $student->gender) === 'male' ? 'selected' : '' }}>Male / à®†à®£à¯</option>
                                                    <option value="female" {{ old('gender', $student->gender) === 'female' ? 'selected' : '' }}>Female / à®ªà¯†à®£à¯</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- School Name -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>School Name / à®ªà®¾à®Ÿà®šà®¾à®²à¯ˆà®¯à®¿à®©à¯ à®ªà¯†à®¯à®°à¯ <span class="text-danger">*</span></label>
                                                <input type="text" name="school_name" class="form-control" value="{{ old('school_name', $student->school_name) }}" required>
                                            </div>
                                        </div>

                                        <!-- Medium of Learning -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>Medium of Learning / à®•à®±à¯à®•à¯ˆ à®®à¯Šà®´à®¿ à®®à¯‚à®²à®®à¯ <span class="text-danger">*</span></label>
                                                <select name="medium" class="form-control" required>
                                                    <option value="">Select Medium</option>
                                                    <option value="tamil" {{ old('medium', $student->medium) === 'tamil' ? 'selected' : '' }}>Tamil / à®¤à®®à®¿à®´à¯</option>
                                                    <option value="english" {{ old('medium', $student->medium) === 'english' ? 'selected' : '' }}>English / à®†à®™à¯à®•à®¿à®²à®®à¯</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Online Experience -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>Online Experience / à®†à®©à¯à®²à¯ˆà®©à¯ à®µà®•à¯à®ªà¯à®ªà¯ à®…à®©à¯à®ªà®µà®®à¯ <span class="text-danger">*</span></label>
                                                <select name="online_experience" class="form-control" required>
                                                    <option value="">Select Experience</option>
                                                    <option value="1" {{ old('online_experience', $student->online_experience) == 1 ? 'selected' : '' }}>Yes / à®†à®®à¯</option>
                                                    <option value="0" {{ old('online_experience', $student->online_experience) == 0 ? 'selected' : '' }}>No / à®‡à®²à¯à®²à¯ˆ</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Current Grade -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                 <label>Current Grade (2026) / à®¤à®±à¯à®ªà¯‹à®¤à¯ˆà®¯ à®¤à®°à®®à¯ (2026) <span class="text-danger">*</span></label>
                                                 <select name="current_grade" id="current_grade" class="form-control" required>
                                                     <option value="">Select Grade</option>
                                                     @foreach([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13] as $grade)
                                                         <option value="à®¤à®°à®®à¯ {{ $grade }} / Grade {{ $grade }}" {{ old('current_grade', $student->current_grade) == "à®¤à®°à®®à¯ $grade / Grade $grade" ? 'selected' : '' }}>à®¤à®°à®®à¯ {{ $grade }} / Grade {{ $grade }}</option>
                                                     @endforeach
                                                 </select>
                                            </div>
                                        </div>

                                        <!-- Device Used -->
                                        <div class="col-md-6 mb-3">
                                            <div class="form-group">
                                                <label>Device Used for Online Classes / à®†à®©à¯à®²à¯ˆà®©à¯ à®µà®•à¯à®ªà¯à®ªà®¿à®±à¯à®•à¯ à®ªà®¯à®©à¯à®ªà®Ÿà¯à®¤à¯à®¤à¯à®®à¯ à®šà®¾à®¤à®©à®®à¯ <span class="text-danger">*</span></label>
                                                <select name="device_used" class="form-control" required>
                                                    <option value="">Select Device</option>
                                                    @foreach(['Mobile', 'Tablet', 'Laptop', 'Desktop'] as $device)
                                                        <option value="{{ $device }}" {{ old('device_used', $student->device_used) == $device ? 'selected' : '' }}>{{ $device }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Stream (Conditional for A/L) -->
                                        <div class="col-md-6 mb-3" id="stream_container" style="display: none;">
                                            <div class="form-group">
                                                 <label>Stream / à®ªà®¿à®°à®¿à®µà¯ (A/L) <span class="text-danger">*</span></label>
                                                 <select name="stream" id="stream" class="form-control">
                                                     <option value="">Select Stream</option>
                                                     <option value="arts" {{ old('stream', $student->stream) === 'arts' ? 'selected' : '' }}>A/L â€“ ARTS / à®•à®²à¯ˆ</option>
                                                     <option value="bio_maths" {{ old('stream', $student->stream) === 'bio_maths' ? 'selected' : '' }}>A/L â€“ BIO & MATHS / à®‰à®¯à®¿à®°à®¿à®¯à®²à¯ & à®•à®£à®¿à®¤à®®à¯</option>
                                                 </select>
                                            </div>
                                        </div>

                                        <div class="col-md-12 mb-4">
                                             <div class="form-group">
                                                 <label class="d-block mb-3" style="font-size: 16px; color: #ffab2d; font-weight: 700;">Select Subjects / à®ªà®¾à®Ÿà®™à¯à®•à®³à¯ˆà®¤à¯ à®¤à¯‡à®°à¯à®¨à¯à®¤à¯†à®Ÿà¯à®•à¯à®•à®µà¯à®®à¯ <span class="text-danger">*</span></label>
                                                 
                                                 {{-- Subjects for Grade 1-5 --}}
                                                 <div class="subject-section" id="subjects_1_5" style="display: none;">
                                                     <div class="row">
                                                         @foreach($subjectsGrade1to5 as $subject)
                                                         <div class="col-md-4 col-6 mb-2">
                                                             <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                                 <input type="checkbox" name="selected_subjects[]" value="{{ $subject }}" style="width: 18px; height: 18px; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject, $currentSubjects) ? 'checked' : '' }}>
                                                                 <span>{{ $subject }}</span>
                                                             </label>
                                                         </div>
                                                         @endforeach
                                                     </div>
                                                 </div>

                                                 {{-- Subjects for Grade 6-11 --}}
                                                 <div class="subject-section" id="subjects_6_11" style="display: none;">
                                                     <div class="row">
                                                         @foreach($subjectsGrade6to11 as $subject)
                                                         <div class="col-md-4 col-6 mb-2">
                                                             <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                                 <input type="checkbox" name="selected_subjects[]" value="{{ $subject }}" style="width: 18px; height: 18px; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject, $currentSubjects) ? 'checked' : '' }}>
                                                                 <span>{{ $subject }}</span>
                                                             </label>
                                                         </div>
                                                         @endforeach
                                                     </div>
                                                 </div>

                                                 {{-- Subjects for Arts --}}
                                                 <div class="subject-section" id="subjects_arts" style="display: none;">
                                                     <div class="row">
                                                         @foreach($subjectsArtsStream as $subject)
                                                         <div class="col-md-4 col-6 mb-2">
                                                             <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                                 <input type="checkbox" name="selected_subjects[]" value="{{ $subject }}" style="width: 18px; height: 18px; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject, $currentSubjects) ? 'checked' : '' }}>
                                                                 <span>{{ $subject }}</span>
                                                             </label>
                                                         </div>
                                                         @endforeach
                                                     </div>
                                                 </div>

                                                 {{-- Subjects for Bio/Maths --}}
                                                 <div class="subject-section" id="subjects_bio_maths" style="display: none;">
                                                     <div class="row">
                                                         @foreach($subjectsBioMathsStream as $subject)
                                                         <div class="col-md-4 col-6 mb-2">
                                                             <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px 12px; border-radius: 6px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.15); margin: 0; font-weight: 500; color: #e0e0e0; transition: all 0.2s;">
                                                                 <input type="checkbox" name="selected_subjects[]" value="{{ $subject }}" style="width: 18px; height: 18px; accent-color: #ffab2d; cursor: pointer;" {{ in_array($subject, $currentSubjects) ? 'checked' : '' }}>
                                                                 <span>{{ $subject }}</span>
                                                             </label>
                                                         </div>
                                                         @endforeach
                                                     </div>
                                                 </div>
                                             </div>
                                        </div>
                                    </div>

                                    <div class="form-group mt-4">
                                        <button type="submit" class="btn btn-primary" style="background: #ffab2d; border-color: #ffab2d; color: #000; font-weight: 700;">
                                            <i class="flaticon-381-save"></i> UPDATE STUDENT DETAILS
                                        </button>
                                        <a href="{{ route('admin.students.index') }}" class="btn btn-dark ml-2">
                                            CANCEL
                                        </a>
                                    </div>
                                </form>

                                <div class="payment-management-section mt-5 border-top pt-4">
                                    <h4 class="mb-4" style="color: #ffab2d; font-weight: 700;"><i class="flaticon-381-layer-1 mr-2"></i> MONTHLY PAYMENT STATUS</h4>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="p-4 rounded" style="background: rgba(255, 171, 45, 0.1); border-left: 5px solid #ffab2d; height: 100%;">
                                                <h5 class="mb-3" style="color: #fff;">Quick Payment (Current Month)</h5>
                                                <p class="mb-3">Mark student as paid for <strong>{{ now()->format('F Y') }}</strong></p>
                                                <form action="{{ route('admin.students.mark-paid', $student->id) }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="year_month" value="{{ now()->format('Y-m-d') }}">
                                                    <button type="submit" class="btn btn-success font-weight-bold btn-block" style="border-radius: 8px;">
                                                        MARK PAID: {{ strtoupper(now()->format('F Y')) }}
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <div class="p-4 rounded shadow-sm" style="background: rgba(255, 255, 255, 0.05); border: 1px dashed rgba(255, 255, 255, 0.2); height: 100%;">
                                                <h5 class="mb-3" style="color: #fff;">Custom Payment Month</h5>
                                                <form action="{{ route('admin.students.mark-paid', $student->id) }}" method="POST">
                                                    @csrf
                                                    <div class="form-group">
                                                        <label class="small text-muted">Select Date / à®¤à®¿à®•à®¤à®¿à®¯à¯ˆà®¤à¯ à®¤à¯‡à®°à¯à®¨à¯à®¤à¯†à®Ÿà¯à®•à¯à®•à®µà¯à®®à¯</label>
                                                        <div class="input-group">
                                                            <input type="date" name="year_month" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                                            <div class="input-group-append">
                                                                <button type="submit" class="btn btn-info" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px;">UPDATE</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </form>
                                                <small class="text-muted d-block mt-2">Select a date to mark payment / à®•à®Ÿà¯à®Ÿà®£à®¤à¯à®¤à¯ˆ à®•à¯à®±à®¿à®•à¯à®• à®’à®°à¯ à®¤à®¿à®•à®¤à®¿à®¯à¯ˆà®¤à¯ à®¤à¯‡à®°à¯à®¨à¯à®¤à¯†à®Ÿà¯à®•à¯à®•à®µà¯à®®à¯</small>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Payment History Table --}}
                                    @if(isset($payments) && $payments->count() > 0)
                                    <div class="mt-5 border-top pt-4">
                                        <h4 class="mb-4" style="color: #4caf50; font-weight: 700;"><i class="flaticon-381-notepad mr-2"></i> PAYMENT HISTORY / à®•à®Ÿà¯à®Ÿà®£ à®µà®°à®²à®¾à®±à¯</h4>
                                        <div class="table-responsive">
                                            <table class="table table-bordered" style="color: #fff;">
                                                <thead style="background: rgba(76, 175, 80, 0.2);">
                                                    <tr>
                                                        <th style="color: #4caf50; font-weight: 700;">#</th>
                                                        <th style="color: #4caf50; font-weight: 700;">Month / à®®à®¾à®¤à®®à¯</th>
                                                        <th style="color: #4caf50; font-weight: 700;">Paid Date / à®•à®Ÿà¯à®Ÿà®¿à®¯ à®¤à®¿à®•à®¤à®¿</th>
                                                        <th style="color: #4caf50; font-weight: 700;">Amount / à®¤à¯Šà®•à¯ˆ</th>
                                                        <th style="color: #4caf50; font-weight: 700;">Status / à®¨à®¿à®²à¯ˆ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($payments as $index => $payment)
                                                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                                                        <td>{{ $index + 1 }}</td>
                                                        <td><strong>{{ $payment->year_month }}</strong></td>
                                                        <td>{{ $payment->paid_at ? $payment->paid_at->format('Y-m-d') : '-' }}</td>
                                                        <td>Rs. {{ number_format($payment->amount, 2) }}</td>
                                                        <td>
                                                            @if($payment->status === 'paid')
                                                                <span class="badge" style="background: #4caf50; color: #fff; padding: 5px 12px; border-radius: 20px;">PAID âœ…</span>
                                                            @else
                                                                <span class="badge" style="background: #f44336; color: #fff; padding: 5px 12px; border-radius: 20px;">{{ strtoupper($payment->status) }}</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    @else
                                    <div class="mt-4 text-center p-4" style="background: rgba(255,255,255,0.05); border-radius: 8px;">
                                        <p class="mb-0" style="color: #938787;">No payment records yet / à®‡à®¤à¯à®µà®°à¯ˆ à®•à®Ÿà¯à®Ÿà®£ à®ªà®¤à®¿à®µà¯à®•à®³à¯ à®‡à®²à¯à®²à¯ˆ</p>
                                    </div>
                                    @endif
                                </div>
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

    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-search.js') }}"></script>
    <script>
        $(document).ready(function() {
            const gradeSelect = $('#current_grade');
            const streamSelect = $('#stream');
            const streamContainer = $('#stream_container');
            
            function updateSubjectSections() {
                const gradeValue = gradeSelect.val() || "";
                const streamValue = streamSelect.val() || "";
                
                // Extract number from "à®¤à®°à®®à¯ X / Grade X"
                const match = gradeValue.match(/Grade\s*(\d+)/i);
                const gradeNum = match ? parseInt(match[1]) : null;
                
                // Hide all sections first and DISABLE their checkboxes
                $('.subject-section').hide().find('input[type="checkbox"]').prop('disabled', true);
                streamContainer.hide();
                
                if (gradeNum) {
                    if (gradeNum >= 1 && gradeNum <= 5) {
                        $('#subjects_1_5').fadeIn().find('input[type="checkbox"]').prop('disabled', false);
                    } else if (gradeNum >= 6 && gradeNum <= 11) {
                        $('#subjects_6_11').fadeIn().find('input[type="checkbox"]').prop('disabled', false);
                    } else if (gradeNum >= 12 && gradeNum <= 13) {
                        streamContainer.fadeIn();
                        if (streamValue === 'arts') {
                            $('#subjects_arts').fadeIn().find('input[type="checkbox"]').prop('disabled', false);
                        } else if (streamValue === 'bio_maths') {
                            $('#subjects_bio_maths').fadeIn().find('input[type="checkbox"]').prop('disabled', false);
                        }
                    }
                }
            }
            
            gradeSelect.on('change', function() {
                // If not 12-13, clear stream
                const gradeValue = $(this).val() || "";
                const match = gradeValue.match(/Grade\s*(\d+)/i);
                const gradeNum = match ? parseInt(match[1]) : null;
                
                if (gradeNum < 12) {
                    streamSelect.val('');
                }
                updateSubjectSections();
            });
            
            streamSelect.on('change', updateSubjectSections);
            
            // Initial call
            updateSubjectSections();
        });

        // Toggle password visibility
        function togglePassword(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = 'ðŸ™ˆ';
            } else {
                input.type = 'password';
                icon.textContent = 'ðŸ‘ï¸';
            }
        }
    </script>
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>
</body>

</html>





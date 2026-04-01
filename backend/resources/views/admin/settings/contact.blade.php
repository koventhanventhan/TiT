<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Contact Settings - {{ config('app.name') }}</title>
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
                            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #fff;">Contact Settings</h4>
                            <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}/contact" target="_blank" class="btn btn-primary btn-sm">
                                View Contact Page
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
                                    <h5 class="mb-3 text-primary">Hero Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Hero Badge</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_hero_badge" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hero_badge', 'Contact Us') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Hero Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_hero_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hero_title', "Let's Start a Conversation") }}">
                                            <small class="text-muted">Use <span>Conversation</span> for gradient effect</small>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Hero Description</label>
                                        <div class="col-sm-9">
                                            <textarea name="contact_hero_desc" class="form-control" rows="3">{{ \App\Models\SiteSetting::get('contact_hero_desc', "Have questions about our courses? Want to enroll? We're here to help. Reach out to us through any channel below.") }}</textarea>
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Visual Stats (Cards)</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Stat 1 (e.g. 24/7 Support)</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="contact_stat1_label" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_stat1_label', '24/7 Support') }}" placeholder="Label">
                                        </div>
                                        <div class="col-sm-5">
                                            <input type="text" name="contact_stat1_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_stat1_desc', 'Always available') }}" placeholder="Description">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Stat 2 (e.g. 10000 Students)</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="stats_students" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_students', '10000') }}" placeholder="Value/Label">
                                        </div>
                                        <div class="col-sm-5">
                                            <input type="text" name="contact_stat2_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_stat2_desc', 'Trust us') }}" placeholder="Description">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Stat 3 (e.g. 98% Satisfaction)</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="stats_success_rate" class="form-control" value="{{ \App\Models\SiteSetting::get('stats_success_rate', '98%') }}" placeholder="Value/Label">
                                        </div>
                                        <div class="col-sm-5">
                                            <input type="text" name="contact_stat3_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_stat3_desc', 'Satisfaction Rate') }}" placeholder="Description">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Contact Form Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Form Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_form_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_form_title', 'Send Us a Message') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Form Description</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_form_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_form_desc', "Fill out the form below and we'll respond within 24 hours.") }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Labels (Name/Email/Phone)</label>
                                        <div class="col-sm-3">
                                            <input type="text" name="contact_label_name" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_name', 'Full Name') }}" placeholder="Name Label">
                                        </div>
                                        <div class="col-sm-3">
                                            <input type="text" name="contact_label_email" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_email', 'Email') }}" placeholder="Email Label">
                                        </div>
                                        <div class="col-sm-3">
                                            <input type="text" name="contact_label_phone" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_phone', 'Phone') }}" placeholder="Phone Label">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Labels (Subject/Message)</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="contact_label_subject" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_subject', 'Subject') }}" placeholder="Subject Label">
                                        </div>
                                        <div class="col-sm-5">
                                            <input type="text" name="contact_label_message" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_label_message', 'Message') }}" placeholder="Message Label">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Button Text</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_form_button" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_form_button', 'Send Message') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Contact Details & Info</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Email Address</label>
                                        <div class="col-sm-9">
                                            <input type="email" name="footer_email" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_email', 'info@edulearn.lk') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Phone Number</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_phone" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_phone', '+94 767206279') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Location Address</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_location" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_location', 'Alaveddy, Sri Lanka') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Support Hours</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_hours_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_title', 'Support Hours') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Mon - Fri</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="contact_hours_label_weekdays" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_label_weekdays', 'Monday - Friday') }}" placeholder="Label">
                                        </div>
                                        <div class="col-sm-5">
                                            <input type="text" name="contact_hours_weekdays" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_weekdays', '9:00 AM - 6:00 PM') }}" placeholder="Time">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Saturday</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="contact_hours_label_saturday" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_label_saturday', 'Saturday') }}" placeholder="Label">
                                        </div>
                                        <div class="col-sm-5">
                                            <input type="text" name="contact_hours_saturday" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_saturday', '9:00 AM - 2:00 PM') }}" placeholder="Time">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Sunday</label>
                                        <div class="col-sm-4">
                                            <input type="text" name="contact_hours_label_sunday" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_label_sunday', 'Sunday') }}" placeholder="Label">
                                        </div>
                                        <div class="col-sm-5">
                                            <input type="text" name="contact_hours_sunday" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_hours_sunday', 'Closed') }}" placeholder="Time">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Social Media & Connect</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Section Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_social_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_social_title', 'Connect With Us') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">WhatsApp URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_whatsapp" class="form-control" value="{{ \App\Models\SiteSetting::get('social_whatsapp', '#') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">TikTok URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_tiktok" class="form-control" value="{{ \App\Models\SiteSetting::get('social_tiktok', '#') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Facebook URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_facebook" class="form-control" value="{{ \App\Models\SiteSetting::get('social_facebook', '#') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">YouTube URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_youtube" class="form-control" value="{{ \App\Models\SiteSetting::get('social_youtube', '#') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Instagram URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_instagram" class="form-control" value="{{ \App\Models\SiteSetting::get('social_instagram', '#') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Urgent Help Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_urgent_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_urgent_title', 'Need Urgent Help?') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Description</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_urgent_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_urgent_desc', 'Call our support line directly for immediate assistance.') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Button Text</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_urgent_button" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_urgent_button', 'Call Now') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Map Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_map_title" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_map_title', 'Our Location') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Description</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_map_desc" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_map_desc', 'Find us on the map or visit our office directly.') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Map Pin Name</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_map_pin_name" class="form-control" value="{{ \App\Models\SiteSetting::get('contact_map_pin_name', 'TiT Education') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Google Maps Embed Link (src)</label>
                                        <div class="col-sm-9">
                                            <textarea name="contact_map_embed_link" class="form-control" rows="3" placeholder="Paste the 'src' portion of the Google Maps iframe here">{{ \App\Models\SiteSetting::get('contact_map_embed_link', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3931.964709012591!2d80.00635637583144!3d9.769053576964463!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3affab53cf231405%3A0x2f4a747312823206!2stit%20alaveddy!5e0!3m2!1sen!2slk!4v1773396885086!5m2!1sen!2slk') }}</textarea>
                                            <small class="text-info"><strong>Note:</strong> You can paste the entire iframe code OR just the link. The system will automatically extract the link for you.</small>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary mt-3">Save Contact Settings</button>
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
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>
</body>

</html>





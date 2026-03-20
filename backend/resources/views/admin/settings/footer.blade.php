<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Footer Settings - {{ config('app.name') }}</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/admin-responsive.css') }}" rel="stylesheet">
    <style>
        .content-body {
            margin-top: 0 !important;
            padding-top: 20px;
        }

        .card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
            margin-bottom: 25px;
            background: rgba(43, 37, 72, 0.4) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-header {
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: transparent !important;
        }

        .card-title {
            color: #fff !important;
            font-weight: 600;
        }

        .class-edit-section {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
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
            margin-bottom: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            padding-bottom: 10px;
        }

        .form-control, .bootstrap-select .dropdown-toggle {
            background: rgba(0, 0, 0, 0.2) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #fff !important;
            border-radius: 8px !important;
        }

        .form-control:focus {
            border-color: #EB8153 !important;
            box-shadow: 0 0 0 0.2rem rgba(235, 129, 83, 0.25) !important;
        }

        label {
            color: rgba(255, 255, 255, 0.7) !important;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .help-text {
            font-size: 12px;
            color: rgba(255,255,255,0.5);
            margin-top: 4px;
        }

        .btn-info.btn-xs {
            background-color: #EB8153;
            border-color: #EB8153;
            color: #fff;
            border-radius: 6px;
            padding: 5px 12px;
        }

        .btn-info.btn-xs:hover {
            background-color: #d96e42;
            border-color: #d96e42;
        }

        hr {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
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
    <link rel="stylesheet" href="{{ asset('admin-theme/vendor/toastr/css/toastr.min.css') }}">
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
                @php $admin_identity = \App\Models\SiteSetting::where('group', 'admin_identity')->pluck('value', 'key'); @endphp
                @if(isset($admin_identity['admin_logo']))
                    <img src="{{ asset($admin_identity['admin_logo']) }}" alt="Logo" style="max-height: 45px; max-width: 45px; object-fit: contain;">
                @else
                    <svg class="logo-abbr" width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect class="svg-logo-rect" width="50" height="50" rx="20" fill="#EB8153"/>
                        <path class="svg-logo-path" d="M17.5158 25.8619L19.8088 25.2475L14.8746 11.1774C14.5189 9.84988 15.8701 9.0998 16.8205 9.75055L33.0924 22.2055C33.7045 22.5589 33.8512 24.0717 32.6444 24.3951L30.3514 25.0095L35.2856 39.0796C35.6973 40.1334 34.4431 41.2455 33.3397 40.5064L17.0678 28.0515C16.2057 27.2477 16.5504 26.1205 17.5158 25.8619ZM18.685 14.2955L22.2224 24.6007L29.4633 22.6605L18.685 14.2955ZM31.4751 35.9615L27.8171 25.6886L20.5762 27.6288L31.4751 35.9615Z" fill="white"/>
                    </svg>
                @endif
                <span class="brand-title" style="font-size: 24px; font-weight: 700; margin-left:12px; color: #fff;">
                    {{ $admin_identity['admin_company_name'] ?? 'Zenix' }}
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
                        <div class="header-left"></div>
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

                            <li class="nav-item">
                                <a class="nav-link ai-icon" href="{{ route('admin.messages.index') }}" title="Messages" style="position: relative;">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M22.1667 5.83331H5.83333C4.54467 5.83331 3.5 6.878 3.5 8.16665V19.8333C3.5 21.122 4.54467 22.1666 5.83333 22.1666H22.1667C23.4553 22.1666 24.5 21.122 24.5 19.8333V8.16665C24.5 6.878 23.4553 5.83331 22.1667 5.83331Z" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3.5 8.16665L14 15.1666L24.5 8.16665" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <div class="pulse-css d-none" id="message-pulse" style="width: 18px; height: 18px; background: #EB8153; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 0px; right: -5px; box-shadow: 0 0 0 2px #fff;">
                                        <span id="message-count" class="text-white d-none" style="font-size: 10px; font-weight: bold; line-height: 1;">0</span>
                                    </div>
                                </a>
                            </li>

                            <li class="nav-item dropdown notification_dropdown">
                                <a class="nav-link ai-icon" href="#" role="button" data-toggle="dropdown">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M22.75 23.0417H5.25C4.84174 23.0417 4.44973 22.8791 4.16142 22.5891C3.87311 22.2991 3.71128 21.9058 3.71245 21.4958C3.71245 18.8033 4.75412 16.2133 6.65 14.3942V9.33333C6.65 6.65906 7.71235 4.09451 9.6033 2.2033C11.4945 0.31235 14.0591 -0.75 16.7333 -0.75C19.4076 -0.75 21.9721 0.31235 23.8633 2.2033C25.7543 4.09451 26.8167 6.65906 26.8167 9.33333V14.3942C28.7125 16.2133 29.7541 18.8033 29.7541 21.4958C29.7553 21.9058 29.5935 22.2991 29.3052 22.5891C29.0169 22.8791 28.6249 23.0417 28.2167 23.0417H22.75ZM7.11667 20.125H26.3417C26.0465 18.2808 25.1017 16.6067 23.6654 15.405C23.2798 15.0842 23.0567 14.6067 23.0567 14.1033V9.33333C23.0567 7.65363 22.3894 6.04272 21.2017 4.855C20.014 3.66728 18.403 3 16.7233 3C15.0436 3 13.4327 3.66728 12.245 4.855C11.0573 6.04272 10.39 7.65363 10.39 9.33333V14.1033C10.39 14.6067 10.1669 15.0842 9.78125 15.405C8.34493 16.6067 7.40013 18.2808 7.105 20.125H7.11667ZM16.7233 27.25C15.6558 27.25 14.6158 26.8833 13.7783 26.205C13.4358 25.9258 13.3758 25.42 13.6458 25.0667C13.9167 24.7133 14.4142 24.6533 14.7667 24.9325C15.305 25.3675 16.0075 25.5992 16.7233 25.5992C17.4392 25.5992 18.1417 25.3675 18.68 24.9325C19.0325 24.6533 19.53 24.7133 19.8008 25.0667C20.0717 25.42 20.0117 25.9258 19.6683 26.205C18.8308 26.8833 17.7908 27.25 16.7233 27.25Z" fill="#3D4461"/>
                                    </svg>
                                    <div class="pulse-css d-none" id="notification-pulse" style="width: 18px; height: 18px; background: #EB8153; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 0px; right: -5px; box-shadow: 0 0 0 2px #fff;">
                                        <span id="notification-count" class="text-white d-none" style="font-size: 10px; font-weight: bold; line-height: 1;">0</span>
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
                                    @if(Auth::user()->avatar)
                                        <img src="{{ asset(Auth::user()->avatar) }}" width="40" height="40" alt="" style="border-radius: 50%; object-fit: cover;">
                                    @else
                                        <div style="width: 40px; height: 40px; border-radius: 50%; background: #EB8153; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <form method="POST" action="{{ route('admin.logout') }}">
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
                            <h4 class="mb-0" style="font-size: 24px; font-weight: 600; color: #fff;">Footer Settings</h4>
                            <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}" target="_blank" class="btn btn-primary btn-sm">
                                View Site
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
                                    <h5 class="mb-3 text-primary">Footer Branding & About</h5>
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
                                    <h5 class="mb-3 text-primary">Legal Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Section Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_legal_title" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_legal_title', 'Legal') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Privacy Policy Label</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_privacy_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_privacy_label', 'Privacy Policy') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Terms & Conditions Label</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_terms_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_terms_label', 'Terms & Conditions') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Refund Policy Label</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_refund_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_refund_label', 'Refund Policy') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Quick Links Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Section Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_quick_links_title" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_quick_links_title', 'Quick Links') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Gallery Label</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_gallery_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_gallery_label', 'Gallery') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Apply as a Tutor Label</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_tutor_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_tutor_label', 'Apply as a Tutor') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Register as a Student Label</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_student_label" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_student_label', 'Register as a student') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Contact Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Section Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_contact_title" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_contact_title', 'Contact') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Email Address</label>
                                        <div class="col-sm-9">
                                            <input type="email" name="footer_email" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_email', 'info@edulearn.lk') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Phone Number</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_phone" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_phone', '+94 114 477 488') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Copyright & Socials</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Copyright Text</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_copyright" class="form-control" value="{{ \App\Models\SiteSetting::get('footer_copyright', '© 2025 TiT. All rights reserved by TIT Online Education (PVT) Ltd.') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Facebook URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_facebook" class="form-control" value="{{ \App\Models\SiteSetting::get('social_facebook', '#') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Instagram URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_instagram" class="form-control" value="{{ \App\Models\SiteSetting::get('social_instagram', '#') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">YouTube URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_youtube" class="form-control" value="{{ \App\Models\SiteSetting::get('social_youtube', '#') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Twitter URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_twitter" class="form-control" value="{{ \App\Models\SiteSetting::get('social_twitter', '#') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">LinkedIn URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="social_linkedin" class="form-control" value="{{ \App\Models\SiteSetting::get('social_linkedin', '#') }}">
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary mt-3">Save Footer Settings</button>
                                </form>
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

    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-search.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>
</body>

</html>

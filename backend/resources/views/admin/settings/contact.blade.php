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
                            <h4 class="mb-0" style="font-size: 24px; font-weight: 600; color: #1f2937;">Contact Settings</h4>
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
                                <form action="{{ route('admin.settings.store') }}" method="POST">
                                    @csrf
                                    <h5 class="mb-3 text-primary">Hero Section</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Hero Title</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_hero_title" class="form-control" value="{{ App\Models\SiteSetting::get('contact_hero_title', 'Get In Touch') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Hero Subtitle</label>
                                        <div class="col-sm-9">
                                            <textarea name="contact_hero_subtitle" class="form-control" rows="2">{{ App\Models\SiteSetting::get('contact_hero_subtitle', "Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.") }}</textarea>
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Top Bar Settings</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Show Social Icons</label>
                                        <div class="col-sm-9">
                                            <select name="topbar_show_social" class="form-control">
                                                <option value="yes" {{ App\Models\SiteSetting::get('topbar_show_social', 'yes') == 'yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="no" {{ App\Models\SiteSetting::get('topbar_show_social', 'yes') == 'no' ? 'selected' : '' }}>No</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Show Email Address</label>
                                        <div class="col-sm-9">
                                            <select name="topbar_show_email" class="form-control">
                                                <option value="yes" {{ App\Models\SiteSetting::get('topbar_show_email', 'yes') == 'yes' ? 'selected' : '' }}>Yes</option>
                                                <option value="no" {{ App\Models\SiteSetting::get('topbar_show_email', 'yes') == 'no' ? 'selected' : '' }}>No</option>
                                            </select>
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Contact Details</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Email Address</label>
                                        <div class="col-sm-9">
                                            <input type="email" name="footer_email" class="form-control" value="{{ App\Models\SiteSetting::get('footer_email', 'info@titonline.lk') }}">
                                            <small class="form-text text-muted">Displayed in header, footer, and contact page</small>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Phone Number</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_phone" class="form-control" value="{{ App\Models\SiteSetting::get('footer_phone', '+94 11 234 5678') }}">
                                            <small class="form-text text-muted">Displayed in footer and contact page</small>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Website URL</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_website" class="form-control" value="{{ App\Models\SiteSetting::get('contact_website', 'www.titonline.lk') }}">
                                            <small class="form-text text-muted">Displayed on contact page</small>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Location Address</label>
                                        <div class="col-sm-9">
                                            <textarea name="contact_location" class="form-control" rows="2">{{ App\Models\SiteSetting::get('contact_location', 'Colombo, Sri Lanka') }}</textarea>
                                            <small class="form-text text-muted">Full address displayed on contact page</small>
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Support Hours</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Weekdays</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_hours_weekdays" class="form-control" value="{{ App\Models\SiteSetting::get('contact_hours_weekdays', 'Monday - Friday (9:00 AM - 6:00 PM)') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Saturday</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_hours_saturday" class="form-control" value="{{ App\Models\SiteSetting::get('contact_hours_saturday', 'Saturday (9:00 AM - 2:00 PM)') }}">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Sunday</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="contact_hours_sunday" class="form-control" value="{{ App\Models\SiteSetting::get('contact_hours_sunday', 'Sunday (Closed)') }}">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Social Media Links</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Facebook URL</label>
                                        <div class="col-sm-9">
                                            <input type="url" name="social_facebook" class="form-control" value="{{ App\Models\SiteSetting::get('social_facebook', '#') }}" placeholder="https://facebook.com/...">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Instagram URL</label>
                                        <div class="col-sm-9">
                                            <input type="url" name="social_instagram" class="form-control" value="{{ App\Models\SiteSetting::get('social_instagram', '#') }}" placeholder="https://instagram.com/...">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">YouTube URL</label>
                                        <div class="col-sm-9">
                                            <input type="url" name="social_youtube" class="form-control" value="{{ App\Models\SiteSetting::get('social_youtube', '#') }}" placeholder="https://youtube.com/...">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Twitter/X URL</label>
                                        <div class="col-sm-9">
                                            <input type="url" name="social_twitter" class="form-control" value="{{ App\Models\SiteSetting::get('social_twitter', '#') }}" placeholder="https://twitter.com/...">
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">LinkedIn URL</label>
                                        <div class="col-sm-9">
                                            <input type="url" name="social_linkedin" class="form-control" value="{{ App\Models\SiteSetting::get('social_linkedin', '#') }}" placeholder="https://linkedin.com/...">
                                        </div>
                                    </div>

                                    <hr>
                                    <h5 class="mb-3 text-primary">Footer Settings</h5>
                                    <div class="form-group row">
                                        <label class="col-sm-3 col-form-label">Copyright Text</label>
                                        <div class="col-sm-9">
                                            <input type="text" name="footer_copyright" class="form-control" value="{{ App\Models\SiteSetting::get('footer_copyright', 'Â© 2024 TiT Online. All rights reserved.') }}">
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
    
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>
</body>

</html>





<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - {{ config('app.name') }}</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('admin-theme/vendor/chartist/css/chartist.min.css') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/vendor/owl-carousel/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/admin-responsive.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('admin-theme/vendor/toastr/css/toastr.min.css') }}">
    <style>
        html {
            font-size: 16px;
        }
        .content-body {
            margin-top: 0 !important;
            padding-top: 1.25rem;
        }
        .card {
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
            margin-bottom: 1.25rem;
        }
    </style>
    @stack('styles')
    
    <!-- Pusher and Notifications -->
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

        @include('admin.partials.header')
        @include('admin.partials.sidebar')

        <div class="content-body">
            <div class="container-fluid">
                <!-- Notifications / Alerts Section -->
                @if(($pendingApprovals ?? 0) > 0 || ($pendingPayments ?? 0) > 0)
                <div class="row">
                    <div class="col-12">
                        @if(($pendingApprovals ?? 0) > 0)
                        <div class="alert alert-warning mb-2 p-3" role="alert" style="border-radius: 0.5rem; border: 1px solid rgba(255, 169, 0, 0.3);">
                            <div class="d-flex align-items-start">
                                <div class="mr-3 mt-1">
                                    <i class="flaticon-381-warning text-warning" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mt-1 mb-1 font-weight-bold text-warning">Attention!</h6>
                                    <p class="mb-2 text-dark" style="font-size: 0.85rem; line-height: 1.4;">You have <strong>{{ $pendingApprovals }}</strong> students waiting for confirmation.</p>
                                    <a href="{{ route('admin.students.index') }}" class="btn btn-warning btn-sm px-3 py-1">View All</a>
                                </div>
                                <div>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: static; padding: 0;">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                        @if(($pendingPayments ?? 0) > 0)
                        <div class="alert alert-danger mb-3 p-3" role="alert" style="border-radius: 0.5rem; border: 1px solid rgba(255, 92, 117, 0.3);">
                            <div class="d-flex align-items-start">
                                <div class="mr-3 mt-1">
                                    <i class="flaticon-381-television text-danger" style="font-size: 1.5rem;"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mt-1 mb-1 font-weight-bold text-danger">Payment Notice</h6>
                                    <p class="mb-2 text-dark" style="font-size: 0.85rem; line-height: 1.4;">There are <strong>{{ $pendingPayments }}</strong> pending payment confirmations.</p>
                                    <a href="#" class="btn btn-danger btn-sm px-3 py-1">Review Payments</a>
                                </div>
                                <div>
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: static; padding: 0;">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                @yield('content')
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
    @stack('scripts')
</body>

</html>

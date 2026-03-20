<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Dashboard - {{ config('app.name') }}</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('admin-theme/vendor/chartist/css/chartist.min.css') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/vendor/owl-carousel/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/admin-responsive.css') }}" rel="stylesheet">
    <style>
        /* Dashboard Alignment Fixes */
        .content-body {
            margin-top: 0 !important;
            padding-top: 20px;
        }
        
        .card {
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .card-header-1 {
            padding: 20px 25px;
            border-bottom: 1px solid #e5e5e5;
            background: #3b3363;
        }
        
        .card-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .card-body {
            padding: 25px;
        }
        
        .container-fluid {
            padding-left: 25px;
            padding-right: 25px;
        }
        
        .row {
            margin-left: -15px;
            margin-right: -15px;
        }
        
        .row > [class*="col-"] {
            padding-left: 15px;
            padding-right: 15px;
        }
        
        /* Widget Stat Cards Alignment */
        .widget-stat {
            margin-bottom: 20px;
        }
        
        .widget-stat .card-body {
            padding: 20px;
        }
        
        .widget-stat .media {
            align-items: center;
        }
        
        .widget-stat .media-body {
            flex: 1;
        }
        
        /* Table Alignment */
        .table-responsive {
            overflow-x: auto;
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead th {
            border-bottom: 2px solid #e5e5e5;
            font-weight: 600;
            color: #1f2937;
            padding: 15px;
            vertical-align: middle;
        }
        
        .table tbody td {
            padding: 15px;
            vertical-align: middle;
        }
        
        /* Badge Alignment */
        .badge {
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 500;
            border-radius: 4px;
        }
        
        /* Header Search Bar Styling - Restore Original Design */
        .header {
            background: #1f2937;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .search_bar {
            position: relative;
            flex: 1;
            max-width: 600px;
        }
        
        .search_bar form {
            position: relative;
            width: 100%;
        }
        
        .search_bar .form-control {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: none;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search_bar .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }
        
        .search_bar .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            outline: none;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.3);
        }
        
        .search_bar .search_icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            cursor: pointer;
            z-index: 10;
            pointer-events: none;
            background: none !important;
            padding: 0 !important;
            height: auto !important;
            border-radius: 0 !important;
        }
        
        .header-left .search_bar .search_icon {
            background: none !important;
            height: auto !important;
            padding: 0 !important;
            border-top-left-radius: 0 !important;
            border-bottom-left-radius: 0 !important;
        }
        
        .search_bar .search_icon i {
            font-size: 20px;
        }
        
        /* Header Profile Styling */
        .header-profile .header-info {
            text-align: right;
        }
        
        .header-profile .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Home Button Styling */
        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4) !important;
        }

        /* Responsive Fixes */
        @media (max-width: 768px) {
            .container-fluid {
                padding-left: 15px;
                padding-right: 15px;
            }
            
            .card-body {
                padding: 15px;
            }
            
            .header-left {
                gap: 15px;
            }
            
            .search_bar {
                max-width: 100%;
            }

            .header-right .btn-primary {
                padding: 6px 16px;
                font-size: 13px;
            }

            .header-right .btn-primary svg {
                width: 16px;
                height: 16px;
            }
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
    <!--*******************
        Preloader start
    ********************-->
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>
    <!--*******************
        Preloader end
    ********************-->

    <!--**********************************
        Main wrapper start
    ***********************************-->
    <div id="main-wrapper">

        <!--**********************************
            Nav header start
        ***********************************-->
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
        <!--**********************************
            Nav header end
        ***********************************-->

        <!--**********************************
            Header start
        ***********************************-->
        @include('admin.partials.header')
        <!--**********************************
            Header end ti-comment-alt
        ***********************************-->

        <!--**********************************
            Sidebar start
        ***********************************-->
        @include('admin.partials.sidebar')
        <!--**********************************
            Sidebar end
        ***********************************-->

        <!--**********************************
            Content body start
        ***********************************-->
        <div class="content-body">
            <div class="container-fluid">
                <!-- Notifications / Alerts Section -->
                @if(($pendingApprovals ?? 0) > 0 || ($pendingPayments ?? 0) > 0)
                <div class="row">
                    <div class="col-12">
                        @if(($pendingApprovals ?? 0) > 0)
                        <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-2" role="alert" style="border-radius: 8px;">
                            <i class="flaticon-381-warning mr-3" style="font-size: 20px;"></i>
                            <strong>Attention!</strong> &nbsp; You have <strong>{{ $pendingApprovals }}</strong> students waiting for confirmation.
                            <a href="{{ route('admin.students.index') }}" class="ml-auto text-dark font-weight-bold"><u>View All</u></a>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif
                        @if(($pendingPayments ?? 0) > 0)
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-3" role="alert" style="border-radius: 8px;">
                            <i class="flaticon-381-television mr-3" style="font-size: 20px;"></i>
                            <strong>Payment Notice:</strong> &nbsp; There are <strong>{{ $pendingPayments }}</strong> pending payment confirmations.
                            <a href="#" class="ml-auto text-dark font-weight-bold"><u>Review Payments</u></a>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Summary Cards Row -->
                <div class="row">
                    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="mr-3 bgl-primary text-primary" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(102, 126, 234, 0.1);">
                                        <i class="flaticon-381-user-7" style="font-size: 28px;"></i>
                                    </span>
                                    <div class="media-body">
                                        <p class="mb-1" style="font-size: 13px; color: #6b7280;">Total Students</p>
                                        <h4 class="mb-0" style="font-size: 24px; font-weight: 700;">{{ number_format($totalStudents ?? 0) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="mr-3 bgl-info text-info" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(6, 182, 212, 0.1);">
                                        <i class="flaticon-381-user-8" style="font-size: 28px;"></i>
                                    </span>
                                    <div class="media-body">
                                        <p class="mb-1" style="font-size: 13px; color: #6b7280;">Total Teachers</p>
                                        <h4 class="mb-0" style="font-size: 24px; font-weight: 700;">{{ number_format($totalTeachers ?? 0) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="mr-3 bgl-warning text-warning" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(235, 129, 83, 0.1);">
                                        <i class="flaticon-381-bookmark" style="font-size: 28px;"></i>
                                    </span>
                                    <div class="media-body">
                                        <p class="mb-1" style="font-size: 13px; color: #6b7280;">Total Courses</p>
                                        <h4 class="mb-0" style="font-size: 24px; font-weight: 700;">{{ number_format($totalCourses ?? 0) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="mr-3 bgl-danger text-danger" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(239, 68, 68, 0.1);">
                                        <i class="flaticon-381-video-camera-1" style="font-size: 28px;"></i>
                                    </span>
                                    <div class="media-body">
                                        <p class="mb-1" style="font-size: 13px; color: #6b7280;">Active Classes (Today)</p>
                                        <h4 class="mb-0" style="font-size: 24px; font-weight: 700;">{{ $activeClassesToday ?? 0 }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-xxl-4 col-lg-6 col-sm-6">
                        <div class="widget-stat card">
                            <div class="card-body p-4">
                                <div class="media ai-icon">
                                    <span class="mr-3 bgl-success text-success" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: rgba(34, 197, 94, 0.1);">
                                        <i class="flaticon-381-diamond" style="font-size: 28px;"></i>
                                    </span>
                                    <div class="media-body">
                                        <p class="mb-1" style="font-size: 13px; color: #6b7280;">Total Revenue</p>
                                        <h4 class="mb-0" style="font-size: 24px; font-weight: 700;">LKR {{ number_format($totalRevenue ?? 0, 2) }}</h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row">
                    <div class="col-xl-8 col-lg-12">
                        <div class="card">
                            <div class="card-header-1">
                                <h4 class="card-title text-white">Student Registration Trends (Last 7 Days)</h4>
                            </div>
                            <div class="card-body">
                                <div id="registrationActivityChart" class="ct-chart ct-golden-section" style="height: 300px;"></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-12">
                        <div class="card">
                            <div class="card-header-1">
                                <h4 class="card-title text-white">Academic Breakdown</h4>
                            </div>
                            <div class="card-body p-0">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 18px 25px;">
                                        <span><i class="flaticon-381-calendar-1 mr-3 text-primary"></i> Grade 12 Students</span>
                                        <span class="badge badge-primary light badge-pill">{{ \App\Models\User::where('role', 'user')->where('current_grade', '12')->count() }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 18px 25px;">
                                        <span><i class="flaticon-381-calendar-1 mr-3 text-info"></i> Grade 13 Students</span>
                                        <span class="badge badge-info light badge-pill">{{ \App\Models\User::where('role', 'user')->where('current_grade', '13')->count() }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 18px 25px;">
                                        <span><i class="flaticon-381-star-1 mr-3 text-warning"></i> Arts Stream</span>
                                        <span class="badge badge-warning light badge-pill">{{ \App\Models\User::where('role', 'user')->where('stream', 'arts')->count() }}</span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center" style="padding: 18px 25px;">
                                        <span><i class="flaticon-381-heart mr-3 text-danger"></i> Bio/Maths Stream</span>
                                        <span class="badge badge-danger light badge-pill">{{ \App\Models\User::where('role', 'user')->where('stream', 'bio_maths')->count() }}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Recent Activity Section -->
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header-1">
                                <h4 class="card-title text-white">Recent Student Registrations</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-responsive-md">
                                        <thead>
                                            <tr>
                                                <th style="font-weight: 600;">Student</th>
                                                <th style="font-weight: 600;">Academic</th>
                                                <th style="font-weight: 600;">Status</th>
                                                <th style="font-weight: 600;">Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach(($recentStudents ?? []) as $student)
                                            <tr>
                                                <td>
                                                    <div style="font-weight: 500;">{{ $student->full_name ?? $student->name }}</div>
                                                    <small class="text-muted">{{ $student->email }}</small>
                                                </td>
                                                <td>
                                                    <small>Grade {{ $student->current_grade ?? 'N/A' }}</small><br>
                                                    <small class="text-info">{{ strtoupper($student->stream ?? 'N/A') }}</small>
                                                </td>
                                                <td>
                                                    @if($student->admin_confirmed_at)
                                                        <span class="badge badge-xs badge-success">Confirmed</span>
                                                    @else
                                                        <span class="badge badge-xs badge-warning">Pending</span>
                                                    @endif
                                                </td>
                                                <td><small>{{ $student->created_at->format('M d') }}</small></td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Classes Section -->
                    <div class="col-xl-6">
                        <div class="card">
                            <div class="card-header-1">
                                <h4 class="card-title text-white">Upcoming / Active Classes</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-responsive-md">
                                        <thead>
                                            <tr>
                                                <th style="font-weight: 600;">Subject</th>
                                                <th style="font-weight: 600;">Grade</th>
                                                <th style="font-weight: 600;">Scheduled At</th>
                                                <th style="font-weight: 600;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse(($upcomingClasses ?? []) as $class)
                                            <tr>
                                                <td style="font-weight: 500;">{{ $class->subject }}</td>
                                                <td>Grade {{ $class->grade }}</td>
                                                <td><small>{{ $class->scheduled_at->format('M d, h:i A') }}</small></td>
                                                <td>
                                                    @if($class->scheduled_at->isPast() && $class->scheduled_at->addMinutes($class->duration)->isFuture())
                                                        <span class="badge badge-xs badge-danger blink">LIVE</span>
                                                    @else
                                                        <a href="{{ $class->join_url }}" target="_blank" class="btn btn-primary btn-xs sharp"><i class="fa fa-video-camera"></i></a>
                                                    @endif
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="4" class="text-center py-4">No upcoming classes found</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications / Alerts Area (Bottom) -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header-1">
                                <h4 class="card-title text-white">Action Center</h4>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="p-3 text-center border-right">
                                            <h3 class="text-primary">{{ $pendingApprovals ?? 0 }}</h3>
                                            <span class="text-muted">Wait Confirmation</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 text-center border-right">
                                            <h3 class="text-warning">{{ $pendingPayments ?? 0 }}</h3>
                                            <span class="text-muted">Payment Issues</span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="p-3 text-center">
                                            <h3 class="text-info">{{ \App\Models\LearningMaterial::count() }}</h3>
                                            <span class="text-muted">Study Materials</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Registration Activity Chart
                new Chartist.Bar('#registrationActivityChart', {
                    labels: {!! json_encode($chartLabels ?? []) !!},
                    series: [{!! json_encode($chartData ?? []) !!}]
                }, {
                    low: 0,
                    showArea: true,
                    fullWidth: true,
                    axisY: {
                        onlyInteger: true,
                        offset: 20
                    }
                });
            });
        </script>
        <style>
            @keyframes blink {
                0% { opacity: 1; }
                50% { opacity: 0.4; }
                100% { opacity: 1; }
            }
            .blink {
                animation: blink 1s linear infinite;
            }
        </style>
        <!--**********************************
            Content body end
        ***********************************-->

        <!--**********************************
            Footer start
        ***********************************-->
        <div class="footer">
            <div class="copyright">
                <p>Copyright Â© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
        <!--**********************************
            Footer end
        ***********************************-->
    </div>
    <!--**********************************
        Main wrapper end
    ***********************************-->

    <!--**********************************
        Scripts
    ***********************************-->
    <!-- Required vendors -->
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-search.js') }}"></script>
    <script src="{{ asset('admin-theme/js/dashboard/dashboard-1.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>
</body>
</html>






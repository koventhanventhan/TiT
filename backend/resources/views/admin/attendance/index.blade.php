<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Attendance Management - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/admin-responsive.css') }}" rel="stylesheet">
    <style>
        .content-body {
            margin-top: 0 !important;
            padding-top: 1.25rem;
        }
        .card {
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
        }
        .att-table th {
            background-color: #f8fafc;
        }
    </style>
    @include('admin.partials.pagination-styles')
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
                                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 0.5rem 1.25rem; border-radius: 0.375rem; color: white; font-weight: 500; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.3s ease; box-shadow: 0 0.125rem 0.5rem rgba(102, 126, 234, 0.3); cursor: pointer;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle;">
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
                            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Attendance Monitoring</h4>
                            <a href="{{ route('admin.zoom.index') }}" class="btn btn-secondary btn-sm">
                                <i class="flaticon-381-video-camera"></i> View Classes
                            </a>
                        </div>
                    </div>
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
                    </div>
                @endif

                @forelse($schedules as $s)
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h4 class="card-title text-primary"><strong>{{ $s->title }}</strong></h4>
                        <span class="badge badge-outline-dark fs-12">{{ $s->scheduled_at->format('M d, Y @ H:i') }}</span>
                    </div>
                    <div class="card-body px-0">
                        <div class="table-responsive">
                            <table class="table table-responsive-md att-table mb-0">
                                <thead>
                                    <tr>
                                        <th class="pl-4" style="width:3.125rem;">#</th>
                                        <th>Attendee Name</th>
                                        <th>Role Type</th>
                                        <th>Current Status</th>
                                        <th class="text-right pr-4">Manual Adjustment</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($s->attendances as $index => $att)
                                    <tr>
                                        <td class="pl-4"><strong>{{ $index + 1 }}</strong></td>
                                        <td>{{ $att->user->full_name ?? $att->user->name ?? $att->user->email }}</td>
                                        <td>
                                            <span class="badge badge-xs light badge-{{ $att->role === 'teacher' ? 'info' : 'secondary' }}">
                                                {{ strtoupper($att->role) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($att->status === 'present')
                                                <span class="text-success font-w600"><i class="fa fa-circle mr-1"></i> Present</span>
                                            @else
                                                <span class="text-danger font-w600"><i class="fa fa-circle mr-1"></i> Absent</span>
                                            @endif
                                        </td>
                                        <td class="text-right pr-4">
                                            <form action="{{ route('admin.attendance.update') }}" method="POST" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="attendance_id" value="{{ $att->id }}">
                                                <div class="d-flex justify-content-end">
                                                    <select name="status" class="form-control form-control-xs selectpicker" onchange="this.form.submit()" style="width: 6.875rem;">
                                                        <option value="present" {{ $att->status === 'present' ? 'selected' : '' }} data-content="<span class='text-success'>Present</span>">Present</option>
                                                        <option value="absent" {{ $att->status === 'absent' ? 'selected' : '' }} data-content="<span class='text-danger'>Absent</span>">Absent</option>
                                                    </select>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @if($s->attendances->isEmpty())
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="flaticon-381-search-1 display-4 d-block mb-3"></i>
                                                Nothing found for this session yet.
                                            </div>
                                        </td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @empty
                <div class="card">
                    <div class="card-body text-center py-5">
                        <p class="text-muted mb-0">No Zoom class schedules found in history.</p>
                    </div>
                </div>
                @endforelse

                @if($schedules->hasPages())
                <div class="card mt-4 mb-5">
                    <div class="card-body p-0">
                        <div class="pagination-footer m-0" style="border-radius: 0.5rem; border: none;">
                            <div class="pagination-info">
                                Showing {{ $schedules->firstItem() }} to {{ $schedules->lastItem() }} of {{ $schedules->total() }} results
                            </div>
                            <div class="pagination-per-page">
                                <span>Per page</span>
                                <select disabled>
                                    <option>10</option>
                                    <option selected>15</option>
                                    <option>25</option>
                                    <option>50</option>
                                </select>
                            </div>
                            {{ $schedules->links('vendor.pagination.custom') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright Â© {{ date('Y') }} {{ config('app.name') }}.</p>
            </div>
        </div>
    </div>

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





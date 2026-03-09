<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Weekly Timetable - {{ config('app.name') }}</title>
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
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .timetable-header {
            background: #f8fafc;
            font-weight: bold;
            text-align: center;
            padding: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .timetable-day {
            min-height: 200px;
            border-right: 1px solid #e2e8f0;
            padding: 10px;
        }
        .timetable-day:last-child {
            border-right: none;
        }
        .timetable-slot {
            background: #fff;
            border-left: 4px solid #EB8153;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            font-size: 13px;
        }
        .timetable-slot.inactive {
            border-left-color: #cbd5e0;
            opacity: 0.7;
        }
        .slot-time {
            font-weight: bold;
            color: #4a5568;
        }
        .slot-title {
            color: #2d3748;
            margin: 2px 0;
        }
        .slot-details {
            font-size: 11px;
            color: #718096;
        }
        .slot-actions {
            margin-top: 5px;
            display: flex;
            gap: 5px;
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
                        <div class="header-left"></div>
                        <ul class="navbar-nav header-right">
                             <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                                    @if(Auth::user()->avatar)
                                        <img src="{{ asset(Auth::user()->avatar) }}" width="40" height="40" alt="" style="border-radius: 50%; object-fit: cover;">
                                    @else
                                        <div class="header-profile-initials" style="width: 40px; height: 40px; border-radius: 50%; background: #EB8153; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            {{ strtoupper(substr(Auth::user()->first_name ?: Auth::user()->name, 0, 1)) }}
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
                            <h4 class="mb-0" style="font-size: 24px; font-weight: 600; color: #1f2937;">Weekly Timetable</h4>
                            <div class="d-flex gap-2">
                                <form action="{{ route('admin.dashboard') }}" method="GET" class="mr-2">
                                    <button type="button" onclick="runSync()" class="btn btn-info btn-sm">
                                        <i class="fa fa-sync"></i> Sync to Zoom
                                    </button>
                                </form>
                                <a href="{{ route('admin.timetables.create') }}" class="btn btn-primary btn-sm">
                                    <i class="flaticon-381-add-1"></i> Add Slot
                                </a>
                            </div>
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

                <div class="card">
                    <div class="card-body p-0">
                        <div class="row no-gutters">
                            @foreach($days as $day)
                            <div class="col">
                                <div class="timetable-header">{{ $day }}</div>
                                <div class="timetable-day">
                                    @foreach($groupedTimetables[$day] as $slot)
                                    <div class="timetable-slot {{ $slot->is_active ? '' : 'inactive' }}">
                                        <div class="slot-time">{{ date('h:i A', strtotime($slot->start_time)) }}</div>
                                        <div class="slot-title"><strong>{{ $slot->title }}</strong></div>
                                        <div class="slot-details">
                                            <div>Grade: {{ $slot->grade }}</div>
                                            <div>Subject: {{ $slot->subject->name ?? 'N/A' }}</div>
                                            <div>Teacher: {{ $slot->teacher->name ?? 'N/A' }}</div>
                                        </div>
                                        <div class="slot-actions">
                                            <a href="{{ route('admin.timetables.edit', $slot->id) }}" class="btn btn-xs btn-primary sharp"><i class="fa fa-pencil"></i></a>
                                            <form action="{{ route('admin.timetables.toggle', $slot->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-{{ $slot->is_active ? 'warning' : 'success' }} sharp">
                                                    <i class="fa fa-{{ $slot->is_active ? 'pause' : 'play' }}"></i>
                                                </button>
                                            </form>
                                            <form action="{{ route('admin.timetables.destroy', $slot->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this slot?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger sharp"><i class="fa fa-trash"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <div class="copyright">
                <p>Copyright © {{ date('Y') }} {{ config('app.name') }}.</p>
            </div>
        </div>
    </div>

    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
    <script>
        function runSync() {
            if(confirm('This will generate Zoom links for the next 7 days based on this timetable. Continue?')) {
                // For demonstration, we could call an API or just show an alert.
                // In a real app, you'd have a route that calls the command.
                alert('Command zoom:sync-timetable will be triggered by scheduler or manually via CLI.');
            }
        }
    </script>
</body>
</html>

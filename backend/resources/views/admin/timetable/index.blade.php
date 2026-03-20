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

        /* Dark Mode Specific Styles for Dropdowns */
        [data-theme-version="dark"] .modal-content {
            background-color: #1a152e;
            color: #fff;
        }
        [data-theme-version="dark"] .modal-header {
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        [data-theme-version="dark"] .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        [data-theme-version="dark"] .form-control {
            background-color: rgba(255,255,255,0.05) !important;
            border-color: #eb8153 !important;
            color: #fff !important;
        }
        [data-theme-version="dark"] .bootstrap-select .dropdown-toggle {
            background-color: rgba(255,255,255,0.05) !important;
            border: 1px solid #eb8153 !important;
            color: #fff !important;
        }
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu {
            background-color: #1a152e !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
        }
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li a {
            color: #fff !important;
            background-color: transparent !important;
        }
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li a:hover {
            background-color: #eb8153 !important;
            color: #fff !important;
        }
        [data-theme-version="dark"] .bootstrap-select .bs-searchbox input {
            background-color: rgba(255,255,255,0.1) !important;
            border-color: #eb8153 !important;
            color: #fff !important;
        }
        [data-theme-version="dark"] .dropdown-menu .inner {
            background-color: #1a152e !important;
        }
        [data-theme-version="dark"] .dropdown-menu {
            background-color: #1a152e !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
        }
        /* Fix for search results white background in dark mode */
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu .inner.show {
            background-color: #1a152e !important;
        }
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li.selected a {
            background-color: #eb8153 !important;
            color: #fff !important;
        }
        /* Global dropdown menu fix for dark mode - VERY AGGRESSIVE */
        [data-theme-version="dark"] .dropdown-menu,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu .inner,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu .dropdown-item,
        [data-theme-version="dark"] .dropdown-item {
            background-color: #1a152e !important;
            color: #fff !important;
        }

        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li a,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li span {
            color: #fff !important;
            background-color: #1a152e !important;
        }

        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li a:hover,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li.active a,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li.selected a {
            background-color: #eb8153 !important;
            color: #fff !important;
        }

        [data-theme-version="dark"] .bootstrap-select .dropdown-toggle:after {
            color: #fff !important;
        }

        [data-theme-version="dark"] .bootstrap-select .bs-searchbox {
            background-color: #1a152e !important;
            padding: 10px !important;
        }

        [data-theme-version="dark"] .bootstrap-select .bs-searchbox input {
            background-color: rgba(255,255,255,0.1) !important;
            border-color: #eb8153 !important;
            color: #fff !important;
        }

        /* NUCLEAR FIX FOR WHITE DROPDOWNS */
        [data-theme-version="dark"] .dropdown-menu,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu.inner,
        [data-theme-version="dark"] .dropdown-menu.show,
        [data-theme-version="dark"] .dropdown-menu .inner,
        [data-theme-version="dark"] .dropdown-menu .inner.show,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu.show,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu .inner.show {
            background-color: #1a152e !important;
            background: #1a152e !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
            color: #fff !important;
        }

        [data-theme-version="dark"] .dropdown-menu li,
        [data-theme-version="dark"] .dropdown-menu li a,
        [data-theme-version="dark"] .dropdown-menu li a span,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li a,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li a span {
            color: #fff !important;
            background-color: #1a152e !important;
        }

        [data-theme-version="dark"] .dropdown-menu li a:hover,
        [data-theme-version="dark"] .dropdown-menu li.active a,
        [data-theme-version="dark"] .dropdown-menu li.selected a,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li a:hover,
        [data-theme-version="dark"] .bootstrap-select .dropdown-menu li.active a,
        [data-theme-version="dark"] .bootstrap-select .bootstrap-select .dropdown-menu li.selected a {
            background-color: #eb8153 !important;
            color: #fff !important;
        }

        /* Fix for the white gap often seen in bootstrap-select */
        [data-theme-version="dark"] .bootstrap-select .inner {
            background-color: #1a152e !important;
        }
        
        /* Fix search box specifically */
        [data-theme-version="dark"] .bs-searchbox {
            background-color: #1a152e !important;
            border-bottom: 1px solid rgba(255,255,255,0.1) !important;
        }
        
        [data-theme-version="dark"] .bs-searchbox input {
            color: #fff !important;
            background-color: rgba(255,255,255,0.1) !important;
        }

        /* 
           NUCLEAR FORCE FIX FOR ALL DROPDOWNS 
           This targets EVERY potential dropdown element to force the dark #1a152e theme.
        */
        
        /* 1. Target native select if it's used */
        select.form-control, 
        select.form-control option {
            background-color: #1a152e !important;
            color: #ffffff !important;
        }

        /* 2. Target Bootstrap Select custom UI */
        .bootstrap-select .dropdown-menu,
        .bootstrap-select .dropdown-menu.inner,
        .bootstrap-select .dropdown-menu.inner.show,
        .dropdown-menu,
        .dropdown-menu.show,
        .dropdown-menu.inner,
        .dropdown-menu.inner.show,
        .dropdown-menu[x-placement] {
            background-color: #1a152e !important;
            background: #1a152e !important;
            border: 1px solid rgba(255,255,255,0.2) !important;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5) !important;
        }

        /* 3. Target the items inside the dropdown */
        .bootstrap-select .dropdown-menu li,
        .bootstrap-select .dropdown-menu li a,
        .bootstrap-select .dropdown-menu li a span,
        .dropdown-menu li,
        .dropdown-menu li a,
        .dropdown-menu li a span,
        .dropdown-item,
        .dropdown-item span {
            color: #ffffff !important;
            background-color: #1a152e !important;
            background: #1a152e !important;
        }

        /* 4. Target Hover and Selection states */
        .dropdown-menu li a:hover,
        .dropdown-item:hover,
        .dropdown-item:active,
        .dropdown-item.active,
        .dropdown-item.selected,
        .bootstrap-select .dropdown-menu li.selected a,
        .bootstrap-select .dropdown-menu li.active a {
            background-color: #eb8153 !important;
            color: #ffffff !important;
        }

        /* 5. Fix the search box inside dropdowns */
        .bs-searchbox,
        .bs-searchbox input {
            background-color: #1a152e !important;
            color: #ffffff !important;
            border-color: rgba(255,255,255,0.2) !important;
        }

        /* 6. Fix for specific container if bootstrap-select uses it */
        .bs-container.dropdown.bootstrap-select.open .dropdown-menu {
            background-color: #1a152e !important;
            color: #ffffff !important;
        }
        
        /* Fix the Grade/Subject/Teacher words appearing white on white */
        [data-theme-version="dark"] .form-group label {
            color: #ffffff !important;
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
                                <button type="button" onclick="runSync()" class="btn btn-info btn-sm mr-2" title="Manual sync for the next 90 days">
                                    <i class="fa fa-sync"></i> Sync to Zoom (90 Days)
                                </button>
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addSlotModal">
                                    <i class="flaticon-381-add-1"></i> Add Slot
                                </button>
                            </div>
                        </div>
                        <div class="mt-2">
                            <small class="text-info"><i class="fa fa-info-circle"></i> Timetable slots are automatically synced to Zoom for the next <strong>90 days</strong> whenever you save or update.</small>
                        </div>
                    </div>
                </div>

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
                                            <button type="button" onclick='openEditModal({!! json_encode($slot) !!})' class="btn btn-xs btn-primary sharp"><i class="fa fa-pencil"></i></button>
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

        <!-- Add Slot Modal -->
        <div class="modal fade" id="addSlotModal">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Recurring Timetable Slot</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <form action="{{ route('admin.timetables.store') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Slot Title (e.g. Maths Class)</label>
                                    <input type="text" name="title" class="form-control" placeholder="Enter title" required value="{{ old('title') }}">
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Day of Week</label>
                                    <select name="day_of_week" class="form-control" required>
                                        @foreach($days as $day)
                                            <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>{{ $day }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Start Time</label>
                                    <input type="time" name="start_time" class="form-control" required value="{{ old('start_time') }}">
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Duration (Minutes)</label>
                                    <input type="number" name="duration" class="form-control" value="{{ old('duration', 60) }}" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Grade</label>
                                    <select name="grade" class="form-control" required>
                                        <option value="">Select Grade</option>
                                        @foreach($grades as $grade)
                                            <option value="{{ $grade }}" {{ old('grade') == $grade ? 'selected' : '' }}>{{ $grade }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Subject</label>
                                    <select name="subject_id" class="form-control" required>
                                        @foreach($subjects as $s)
                                            <option value="{{ $s->id }}" {{ old('subject_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Assigned Teacher</label>
                                    <select name="teacher_id" class="form-control" required>
                                        @foreach($teachers as $t)
                                            <option value="{{ $t->id }}" {{ old('teacher_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Zoom Host Account (Optional)</label>
                                    <select name="zoom_host_email" class="form-control">
                                        <option value="">Default (me)</option>
                                        @foreach($zoomUsers as $zu)
                                            <option value="{{ $zu['email'] }}" {{ old('zoom_host_email') == $zu['email'] ? 'selected' : '' }}>{{ $zu['display_name'] ?? $zu['email'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-12">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                        <label class="custom-control-label" for="is_active">Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="submit" class="btn btn-primary">Save Slot</button>
                        </div>
                    </form>
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
            if(confirm('This will generate Zoom links for the next 90 days based on this timetable. Continue?')) {
                window.location.href = "{{ route('admin.timetables.sync') }}";
            }
        }

        function openEditModal(slot) {
            const form = $('#addSlotModal form');
            const modal = $('#addSlotModal');
            
            // Change title and action
            modal.find('.modal-title').text('Edit Recurring Timetable Slot');
            form.attr('action', `/admin/timetables/${slot.id}`);
            form.append('<input type="hidden" name="_method" value="PUT">');
            
            // Fill fields
            form.find('input[name="title"]').val(slot.title);
            form.find('select[name="day_of_week"]').val(slot.day_of_week);
            form.find('input[name="start_time"]').val(slot.start_time.substring(0, 5));
            form.find('input[name="duration"]').val(slot.duration);
            form.find('[name="grade"]').val(slot.grade);
            form.find('select[name="subject_id"]').val(slot.subject_id);
            form.find('select[name="teacher_id"]').val(slot.teacher_id);
            form.find('select[name="zoom_host_email"]').val(slot.zoom_host_email);
            form.find('input[name="is_active"]').prop('checked', slot.is_active);
            
            modal.modal('show');
        }

        // Reset modal on close
        $('#addSlotModal').on('hidden.bs.modal', function () {
            const modal = $(this);
            const form = modal.find('form');
            modal.find('.modal-title').text('Add Recurring Timetable Slot');
            form.attr('action', "{{ route('admin.timetables.store') }}");
            form.find('input[name="_method"]').remove();
            form[0].reset();
        });
    </script>
</body>
</html>

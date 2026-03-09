<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Student Entries - {{ config('app.name') }}</title>
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

        /* Custom Pagination Styles */
        .pagination-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #3b3363;/* Dark background */
            padding: 15px 25px;
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
            color: #9ca3af;
            font-size: 14px;
            margin: -25px -30px -25px -30px; /* Offset card padding */
            border-top: 1px solid #ffffff;
        }

        .pagination-info {
            flex: 1;
        }

        .pagination-per-page {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .pagination-per-page select {
            background: #1f2937;
            border: 1px solid #374151;
            color: #fff;
            padding: 4px 12px;
            border-radius: 6px;
            cursor: pointer;
            outline: none;
        }

        .custom-pagination-container {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            border: 1px solid #374151;
            border-radius: 6px;
            overflow: hidden;
            width: fit-content;
            margin-left: auto;
        }

        .pagination-item {
            padding: 8px 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            text-decoration: none;
            border-right: 1px solid #374151;
            background: #1f2937;
            transition: all 0.2s;
            min-width: 40px;
        }

        .pagination-item:last-child {
            border-right: none;
        }

        .pagination-item:hover:not(.disabled):not(.active) {
            background: #374151;
            color: #fff;
            text-decoration: none;
        }

        .pagination-item.active {
            color: #fbbf24; /* Active page color (orange/yellow) */
            font-weight: 600;
            background: #1f2937;
        }

        .pagination-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Table Visibility Improvements */
        .table-responsive {
            overflow-x: auto;
            scrollbar-width: auto; /* For Firefox */
            scrollbar-color: #EB8153 #111827; /* For Firefox */
        }

        /* Custom Scrollbar for Chrome/Safari/Edge */
        .table-responsive::-webkit-scrollbar {
            height: 10px; /* Thicker horizontal scrollbar */
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #111827;
            border-radius: 5px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: #EB8153;
            border-radius: 5px;
            border: 2px solid #111827;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: #d67044;
        }

        /* Compact Table Styles */
        .table.table-responsive-md th,
        .table.table-responsive-md td {
            padding: 12px 10px !important;
            font-size: 13px;
            vertical-align: middle;
        }

        .table.table-responsive-md th {
            white-space: nowrap;
            
        }

        /* Prevent specific columns from wrapping to save space */
        .nowrap-column {
            white-space: nowrap;
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
                            <h4 class="mb-0" style="font-size: 24px; font-weight: 600; color: #1f2937;">Student Entries</h4>
                            <a href="{{ route('admin.students.create') }}" class="btn btn-primary btn-sm">
                                <i class="flaticon-381-add-1"></i> Add Student
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

                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="card-title">All Student Entries</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-responsive-md">
                                        <thead>
                                             <tr>
                                                 <th style="font-weight: 600; width: 50px;">ID</th>
                                                 <th style="font-weight: 600;">Student</th>
                                                 <th style="font-weight: 600;">Greade</th>
                                                 <th class="nowrap-column" style="font-weight: 600;">Contact</th>
                                                 <th style="font-weight: 600; width: 80px;">Gender</th>
                                                 <th class="nowrap-column" style="font-weight: 600;">Status/Payment</th>
                                                 <th style="font-weight: 600;">Medium</th>
                                                 <th style="font-weight: 600;">Subjects</th>
                                                 <th class="nowrap-column" style="font-weight: 600;">Created</th>
                                                 <th style="font-weight: 600; width: 120px;">Actions</th>
                                             </tr>
                                         </thead>
                                         <tbody>
                                             @forelse($students as $student)
                                             @php
                                                 $latestPayment = $student->payments()->where('year_month', now()->format('Y-m'))->where('status', 'paid')->first();
                                                 $paidThisMonth = $latestPayment !== null;
                                                 
                                                 // Decode subjects - handle both JSON and comma-separated strings
                                                 $subjectsArray = [];
                                                 if ($student->selected_subjects) {
                                                     $decoded = json_decode($student->selected_subjects, true);
                                                     if (is_array($decoded)) {
                                                         $subjectsArray = $decoded;
                                                     } else {
                                                         // Fallback for plain string
                                                         $subjectsArray = array_map('trim', explode(',', $student->selected_subjects));
                                                     }
                                                 }
                                                 $subjectCount = count($subjectsArray);
                                             @endphp
                                             <tr>
                                                  <td class="nowrap-column"><strong>{{ $student->id }}</strong></td>
                                                 <td>
                                                     <div style="font-weight: 600; color: #ffab2d;">{{ $student->full_name ?? $student->name }}</div>
                                                     <small class="text-muted d-block">{{ $student->email }}</small>
                                                   
                                                 </td>

  <td>
                                                     @if($student->current_grade)
                                                         <small class="d-block mt-1" style="color: #8b5cf6; font-weight: 600;"> {{ $student->current_grade }}</small>
                                                     @endif
                                                 </td>


                                                 <td class="nowrap-column">
                                                     <div style="font-weight: 500;">{{ $student->phone_number ?? 'N/A' }}</div>
                                                 </td>
                                                 <td>
                                                     <div class="text-center">
                                                         @if($student->gender === 'female')
                                                             <span class="badge badge-pill badge-danger" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">G</span>
                                                         @else
                                                             <span class="badge badge-pill badge-primary" style="width: 30px; height: 30px; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">M</span>
                                                         @endif
                                                     </div>
                                                 </td>
                                                 <td class="nowrap-column">
                                                     <div>
                                                         @if($student->deactivated_at)
                                                             <span class="badge badge-xs badge-danger">Deactivated</span>
                                                         @elseif($student->admin_confirmed_at)
                                                             <span class="badge badge-xs badge-success">Confirmed</span>
                                                         @else
                                                             <span class="badge badge-xs badge-warning">{{ $student->registration_status ?? 'pending' }}</span>
                                                         @endif
                                                     </div>
                                                     <div class="mt-1">
                                                         @if($paidThisMonth)
                                                             <span class="badge badge-xs badge-outline-success">Paid</span>
                                                             @if($latestPayment && $latestPayment->paid_at)
                                                                 <small class="d-block mt-1" style="color: #4caf50; font-size: 11px;">{{ $latestPayment->paid_at->format('M d, Y') }}</small>
                                                             @endif
                                                         @else
                                                             <span class="badge badge-xs badge-outline-secondary">Not paid</span>
                                                         @endif
                                                     </div>
                                                 </td>
                                                 <td>
                                                     <span class="badge badge-info light text-uppercase" style="font-weight: 600;">{{ $student->medium ?? 'N/A' }}</span>
                                                 </td>
                                                 <td>
                                                     @if($subjectCount > 0)
                                                         <div style="font-weight: 600; color: #4f46e5;">
                                                             {{ $subjectCount }} {{ Str::plural('Subject', $subjectCount) }}
                                                         </div>
                                                      
                                                     @else
                                                         <small class="text-muted">None</small>
                                                     @endif
                                                 </td>
                                                 <td class="nowrap-column"><small>{{ $student->created_at->format('M d, Y') }}</small></td>
                                                 <td>
                                                     <div class="dropdown">
                                                         <button type="button" class="btn btn-primary light btn-xs sharp" data-toggle="dropdown">
                                                             <svg width="16px" height="16px" viewBox="0 0 24 24" version="1.1"><g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"><rect x="0" y="0" width="24" height="24"/><circle fill="#000000" cx="12" cy="5" r="2"/><circle fill="#000000" cx="12" cy="12" r="2"/><circle fill="#000000" cx="12" cy="19" r="2"/></g></svg>
                                                         </button>
                                                         <div class="dropdown-menu dropdown-menu-right">
                                                              @if(!$student->admin_confirmed_at)
                                                                 <form action="{{ route('admin.students.confirm', $student->id) }}" method="POST">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item text-success">Confirm Registration</button>
                                                                </form>
                                                            @endif
                                                            <a class="dropdown-item" href="{{ route('admin.students.edit', $student->id) }}">Edit Details</a>
                                                            @if($student->deactivated_at)
                                                                <form action="{{ route('admin.students.activate', $student->id) }}" method="POST">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item text-success">Activate Student</button>
                                                                </form>
                                                            @else
                                                                <form action="{{ route('admin.students.deactivate', $student->id) }}" method="POST" onsubmit="return confirm('Deactivate this student?');">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item text-warning">Deactivate Student</button>
                                                                </form>
                                                            @endif
                                                            <form action="{{ route('admin.students.destroy', $student->id) }}" method="POST" onsubmit="return confirm('à®¨à®¿à®šà¯à®šà®¯à®®à®¾à®• à®‡à®¨à¯à®¤ à®®à®¾à®£à®µà®°à¯ˆ à®¨à¯€à®•à¯à®• à®µà¯‡à®£à¯à®Ÿà¯à®®à®¾? (Are you sure you want to delete this student?)');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger">Delete Student</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="10" class="text-center" style="padding: 40px; color: #6b7280;">No student entries found</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                @if($students->hasPages())
                                <div class="pagination-footer">
                                    <div class="pagination-info">
                                        Showing {{ $students->firstItem() }} to {{ $students->lastItem() }} of {{ $students->total() }} results
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
                                    {{ $students->links('vendor.pagination.custom') }}
                                </div>
                                @endif
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
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>
</body>

</html>





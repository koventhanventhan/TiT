<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Learning Site Settings - {{ config('app.name') }}</title>
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
                            <h4 class="mb-0" style="font-size: 24px; font-weight: 600; color: #1f2937;">Learning Site Settings</h4>
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
                            <div class="card-header border-0 pb-0">
                                <h5 class="card-title mb-0">General Settings</h5>
                            </div>
                            <div class="card-body">
                                <form action="{{ route('admin.settings.store') }}" method="POST">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Notes Page Title</label>
                                                <input type="text" name="learning_notes_title" class="form-control" value="{{ App\Models\SiteSetting::get('learning_notes_title', 'Study Notes') }}">
                                            </div>
                                            <div class="form-group">
                                                <label>Notes Description</label>
                                                <textarea name="learning_notes_description" class="form-control" rows="2">{{ App\Models\SiteSetting::get('learning_notes_description', 'Access comprehensive study notes for all subjects and grades.') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Past Papers Page Title</label>
                                                <input type="text" name="learning_pastpapers_title" class="form-control" value="{{ App\Models\SiteSetting::get('learning_pastpapers_title', 'Past Papers') }}">
                                            </div>
                                            <div class="form-group">
                                                <label>Past Papers Description</label>
                                                <textarea name="learning_pastpapers_description" class="form-control" rows="2">{{ App\Models\SiteSetting::get('learning_pastpapers_description', 'Practice with previous exam papers to prepare for your exams.') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Recordings Page Title</label>
                                                <input type="text" name="learning_recordings_title" class="form-control" value="{{ App\Models\SiteSetting::get('learning_recordings_title', 'Class Recordings') }}">
                                            </div>
                                            <div class="form-group">
                                                <label>Recordings Description</label>
                                                <textarea name="learning_recordings_description" class="form-control" rows="2">{{ App\Models\SiteSetting::get('learning_recordings_description', 'Watch recorded lessons anytime, anywhere.') }}</textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Menu Label</label>
                                                <input type="text" name="learning_menu_label" class="form-control" value="{{ App\Models\SiteSetting::get('learning_menu_label', 'Learning Suite') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save General Settings</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Notes Management -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Manage Study Notes</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 border-right">
                                        <h6 class="text-primary mb-3">Add New Note</h6>
                                        <form action="{{ route('admin.settings.learning.material.store') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="type" value="note">
                                            <div class="form-group">
                                                <label>Select Grade</label>
                                                <select name="grade" class="form-control" required>
                                                    <option value="">-- Choose Grade --</option>
                                                    <option value="grade-6">Grade 6</option>
                                                    <option value="grade-7">Grade 7</option>
                                                    <option value="grade-8">Grade 8</option>
                                                    <option value="grade-9">Grade 9</option>
                                                    <option value="grade-10">Grade 10</option>
                                                    <option value="grade-11">Grade 11</option>
                                                    <option value="grade-12">Grade 12</option>
                                                    <option value="grade-13">Grade 13</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Note Title</label>
                                                <input type="text" name="title" class="form-control" required placeholder="e.g. Pure Mathematics Unit 1">
                                            </div>
                                            <div class="form-group">
                                                <label>PDF File</label>
                                                <div class="custom-file">
                                                    <input type="file" name="file" class="custom-file-input" accept=".pdf" required>
                                                    <label class="custom-file-label">Choose PDF</label>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-block">Add Note</button>
                                        </form>
                                    </div>
                                    <div class="col-md-8">
                                        <h6 class="text-primary mb-3">Existing Notes</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Grade</th>
                                                        <th>Size</th>
                                                        <th>Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($notes as $note)
                                                    <tr>
                                                        <td>{{ $note->title }}</td>
                                                        <td><span class="badge badge-warning text-uppercase">{{ str_replace('-', ' ', $note->grade) }}</span></td>
                                                        <td><span class="badge badge-info">{{ $note->file_size }}</span></td>
                                                        <td>{{ $note->created_at->format('Y-m-d') }}</td>
                                                        <td>
                                                            <div class="d-flex">
                                                                <a href="{{ asset($note->file_path) }}" target="_blank" class="btn btn-info shadow btn-xs sharp mr-1"><i class="fa fa-eye"></i></a>
                                                                <form action="{{ route('admin.settings.learning.material.delete', $note->id) }}" method="POST" onsubmit="return confirm('Delete this note?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger shadow btn-xs sharp"><i class="fa fa-trash"></i></button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Past Papers Management -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Manage Past Papers</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 border-right">
                                        <h6 class="text-primary mb-3">Add New Past Paper</h6>
                                        <form action="{{ route('admin.settings.learning.material.store') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="type" value="past_paper">
                                            <div class="form-group">
                                                <label>Select Grade</label>
                                                <select name="grade" class="form-control" required>
                                                    <option value="">-- Choose Grade --</option>
                                                    <option value="grade-6">Grade 6</option>
                                                    <option value="grade-7">Grade 7</option>
                                                    <option value="grade-8">Grade 8</option>
                                                    <option value="grade-9">Grade 9</option>
                                                    <option value="grade-10">Grade 10</option>
                                                    <option value="grade-11">Grade 11</option>
                                                    <option value="grade-12">Grade 12</option>
                                                    <option value="grade-13">Grade 13</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Paper Title</label>
                                                <input type="text" name="title" class="form-control" required placeholder="e.g. 2023 Physics Paper">
                                            </div>
                                            <div class="form-group">
                                                <label>PDF File</label>
                                                <div class="custom-file">
                                                    <input type="file" name="file" class="custom-file-input" accept=".pdf" required>
                                                    <label class="custom-file-label">Choose PDF</label>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-block">Add Past Paper</button>
                                        </form>
                                    </div>
                                    <div class="col-md-8">
                                        <h6 class="text-primary mb-3">Existing Past Papers</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Grade</th>
                                                        <th>Size</th>
                                                        <th>Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($pastPapers as $paper)
                                                    <tr>
                                                        <td>{{ $paper->title }}</td>
                                                        <td><span class="badge badge-warning text-uppercase">{{ str_replace('-', ' ', $paper->grade) }}</span></td>
                                                        <td><span class="badge badge-info">{{ $paper->file_size }}</span></td>
                                                        <td>{{ $paper->created_at->format('Y-m-d') }}</td>
                                                        <td>
                                                            <div class="d-flex">
                                                                <a href="{{ asset($paper->file_path) }}" target="_blank" class="btn btn-info shadow btn-xs sharp mr-1"><i class="fa fa-eye"></i></a>
                                                                <form action="{{ route('admin.settings.learning.material.delete', $paper->id) }}" method="POST" onsubmit="return confirm('Delete this paper?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger shadow btn-xs sharp"><i class="fa fa-trash"></i></button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recordings Management -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Manage Recordings</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 border-right">
                                        <h6 class="text-primary mb-3">Add New Recording</h6>
                                        <form action="{{ route('admin.settings.learning.material.store') }}" method="POST" enctype="multipart/form-data">
                                            @csrf
                                            <input type="hidden" name="type" value="recording">
                                            <div class="form-group">
                                                <label>Select Grade</label>
                                                <select name="grade" class="form-control" required>
                                                    <option value="">-- Choose Grade --</option>
                                                    <option value="grade-6">Grade 6</option>
                                                    <option value="grade-7">Grade 7</option>
                                                    <option value="grade-8">Grade 8</option>
                                                    <option value="grade-9">Grade 9</option>
                                                    <option value="grade-10">Grade 10</option>
                                                    <option value="grade-11">Grade 11</option>
                                                    <option value="grade-12">Grade 12</option>
                                                    <option value="grade-13">Grade 13</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label>Recording Title</label>
                                                <input type="text" name="title" class="form-control" required placeholder="e.g. Biology Lesson 10">
                                            </div>
                                            <div class="form-group">
                                                <label>Video URL (Optional)</label>
                                                <input type="text" name="url" class="form-control" placeholder="YouTube/Vimeo link">
                                            </div>
                                            <div class="form-group">
                                                <label>OR Upload File</label>
                                                <div class="custom-file">
                                                    <input type="file" name="file" class="custom-file-input">
                                                    <label class="custom-file-label">Choose File</label>
                                                </div>
                                            </div>
                                            <button type="submit" class="btn btn-success btn-block">Add Recording</button>
                                        </form>
                                    </div>
                                    <div class="col-md-8">
                                        <h6 class="text-primary mb-3">Existing Recordings</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Title</th>
                                                        <th>Grade</th>
                                                        <th>Link/Size</th>
                                                        <th>Date</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($recordings as $recording)
                                                    <tr>
                                                        <td>{{ $recording->title }}</td>
                                                        <td><span class="badge badge-warning text-uppercase">{{ str_replace('-', ' ', $recording->grade) }}</span></td>
                                                        <td>
                                                            @if($recording->url)
                                                                <span class="badge badge-outline-primary">URL</span>
                                                            @else
                                                                <span class="badge badge-info">{{ $recording->file_size }}</span>
                                                            @endif
                                                        </td>
                                                        <td>{{ $recording->created_at->format('Y-m-d') }}</td>
                                                        <td>
                                                            <div class="d-flex">
                                                                @if($recording->url)
                                                                    <a href="{{ $recording->url }}" target="_blank" class="btn btn-primary shadow btn-xs sharp mr-1"><i class="fa fa-play"></i></a>
                                                                @else
                                                                    <a href="{{ asset($recording->file_path) }}" target="_blank" class="btn btn-info shadow btn-xs sharp mr-1"><i class="fa fa-eye"></i></a>
                                                                @endif
                                                                <form action="{{ route('admin.settings.learning.material.delete', $recording->id) }}" method="POST" onsubmit="return confirm('Delete this recording?')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="btn btn-danger shadow btn-xs sharp"><i class="fa fa-trash"></i></button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
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





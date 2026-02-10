<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Zoom Classes - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('admin-theme/vendor/chartist/css/chartist.min.css') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/vendor/owl-carousel/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <style>.content-body { margin-top: 0 !important; padding-top: 20px; } .card { border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1); margin-bottom: 20px; }</style>
</head>
<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    <div id="main-wrapper">
        <div class="nav-header">
            <a href="{{ route('admin.dashboard') }}" class="brand-logo">
                <svg class="logo-abbr" width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="50" height="50" rx="20" fill="#EB8153"/><path d="M17.5 25.9L19.8 25.2L14.9 11.2C14.5 9.85 15.9 9.1 16.8 9.75L33.1 22.2C33.7 22.6 33.9 24.1 32.6 24.4L30.4 25L35.3 39.1C35.7 40.1 34.4 41.2 33.3 40.5L17 28C16.2 27.2 16.6 26.1 17.5 25.9Z" fill="white"/></svg>
            </a>
            <div class="nav-control"><div class="hamburger"><span class="line"></span><span class="line"></span><span class="line"></span></div></div>
        </div>
        <div class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        <div class="header-left"><div class="search_bar"><form><input class="form-control" type="search" placeholder="Search"><span class="search_icon"><i class="mdi mdi-magnify"></i></span></form></div></div>
                        <ul class="navbar-nav header-right">
                            <li class="nav-item"><a href="{{ route('admin.dashboard') }}" class="btn btn-primary btn-sm">Dashboard</a></li>
                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="#" data-toggle="dropdown">
                                    <div class="header-info"><span style="color:#fff;font-weight:600;">{{ Auth::user()->name }}</span><p class="fs-12 mb-0" style="color:rgba(255,255,255,0.8);">{{ Auth::user()->email }}</p></div>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button type="submit" class="dropdown-item">Logout</button></form>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>
        <div class="deznav">
            <div class="deznav-scroll">
                <ul class="metismenu" id="menu">
                    <li><a href="{{ route('admin.dashboard') }}"><i class="flaticon-381-networking"></i><span class="nav-text">Dashboard</span></a></li>
                    <li><a href="{{ route('admin.students.index') }}"><i class="flaticon-381-notepad"></i><span class="nav-text">Students</span></a></li>
                    <li><a href="{{ route('admin.teachers.index') }}"><i class="flaticon-381-user-7"></i><span class="nav-text">Teachers</span></a></li>
                    <li><a class="has-arrow" href="javascript:void()"><i class="flaticon-381-video-camera"></i><span class="nav-text">Zoom Classes</span></a>
                        <ul><li><a href="{{ route('admin.zoom.index') }}">All</a></li><li><a href="{{ route('admin.zoom.create') }}">Create</a></li></ul>
                    </li>
                    <li><a href="{{ route('admin.settings.index') }}"><i class="flaticon-381-settings-2"></i><span class="nav-text">Settings</span></a></li>
                </ul>
            </div>
        </div>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="page-title d-flex justify-content-between align-items-center">
                            <h4 class="mb-0" style="font-size:24px;font-weight:600;color:#1f2937;">Zoom Classes</h4>
                            <a href="{{ route('admin.zoom.create') }}" class="btn btn-primary btn-sm">Create Zoom Class</a>
                        </div>
                    </div>
                </div>
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="close" data-dismiss="alert">&times;</button></div>
                @endif
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header"><h4 class="card-title">All Zoom Classes</h4></div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-responsive-md">
                                        <thead>
                                            <tr>
                                                <th style="font-weight:600;">ID</th>
                                                <th style="font-weight:600;">Title</th>
                                                <th style="font-weight:600;">Scheduled At</th>
                                                <th style="font-weight:600;">Subject / Grade</th>
                                                <th style="font-weight:600;">Teachers</th>
                                                <th style="font-weight:600;">Link</th>
                                                <th style="font-weight:600;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($schedules as $s)
                                            <tr>
                                                <td><strong>{{ $s->id }}</strong></td>
                                                <td>{{ $s->title }}</td>
                                                <td>{{ $s->scheduled_at->format('M d, Y H:i') }}</td>
                                                <td>{{ $s->subject ?? '–' }} / {{ $s->grade ?? '–' }}</td>
                                                <td>@if($s->teachers->count()){{ $s->teachers->pluck('name')->join(', ') }}@else – @endif</td>
                                                <td><a href="{{ $s->zoom_link }}" target="_blank" rel="noopener">Open</a></td>
                                                <td>
                                                    <a href="{{ route('admin.zoom.edit', $s->id) }}" class="btn btn-primary btn-sm">Edit</a>
                                                    <form action="{{ route('admin.zoom.destroy', $s->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this zoom class?');">@csrf @method('DELETE')<button type="submit" class="btn btn-danger btn-sm">Delete</button></form>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr><td colspan="7" class="text-center" style="padding:40px;color:#6b7280;">No zoom classes</td></tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if($schedules->hasPages())<div class="mt-4">{{ $schedules->links() }}</div>@endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer"><div class="copyright"><p>Copyright © {{ date('Y') }} {{ config('app.name') }}.</p></div></div>
    </div>
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
</body>
</html>

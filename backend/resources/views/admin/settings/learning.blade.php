<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Learning Site Settings - {{ config('app.name') }}</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('admin-theme/vendor/chartist/css/chartist.min.css') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/vendor/owl-carousel/owl.carousel.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
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
                <svg class="logo-abbr" width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect class="svg-logo-rect" width="50" height="50" rx="20" fill="#EB8153"/>
                    <path class="svg-logo-path" d="M17.5158 25.8619L19.8088 25.2475L14.8746 11.1774C14.5189 9.84988 15.8701 9.0998 16.8205 9.75055L33.0924 22.2055C33.7045 22.5589 33.8512 24.0717 32.6444 24.3951L30.3514 25.0095L35.2856 39.0796C35.6973 40.1334 34.4431 41.2455 33.3397 40.5064L17.0678 28.0515C16.2057 27.2477 16.5504 26.1205 17.5158 25.8619ZM18.685 14.2955L22.2224 24.6007L29.4633 22.6605L18.685 14.2955ZM31.4751 35.9615L27.8171 25.6886L20.5762 27.6288L31.4751 35.9615Z" fill="white"/>
                </svg>
                <svg class="brand-title" width="74" height="22" viewBox="0 0 74 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path class="svg-logo-path" d="M0.784 17.556L10.92 5.152H1.176V1.12H16.436V4.564L6.776 16.968H16.548V21H0.784V17.556ZM25.7399 21.28C24.0785 21.28 22.6599 20.9347 21.4839 20.244C20.3079 19.5533 19.4025 18.6387 18.7679 17.5C18.1519 16.3613 17.8439 15.1293 17.8439 13.804C17.8439 12.3853 18.1519 11.088 18.7679 9.912C19.3839 8.736 20.2799 7.79333 21.4559 7.084C22.6319 6.37467 24.0599 6.02 25.7399 6.02C27.4012 6.02 28.8199 6.37467 29.9959 7.084C31.1719 7.79333 32.0585 8.72667 32.6559 9.884C33.2719 11.0413 33.5799 12.2827 33.5799 13.608C33.5799 14.1493 33.5425 14.6253 33.4679 15.036H22.6039C22.6785 16.0253 23.0332 16.7813 23.6679 17.304C24.3212 17.808 25.0585 18.06 25.8799 18.06C26.5332 18.06 27.1585 17.9013 27.7559 17.584C28.3532 17.2667 28.7639 16.8373 28.9879 16.296L32.7959 17.36C32.2172 18.5173 31.3119 19.46 30.0799 20.188C28.8665 20.916 27.4199 21.28 25.7399 21.28ZM22.4919 12.292H28.8759C28.7825 11.3587 28.4372 10.6213 27.8399 10.08C27.2612 9.52 26.5425 9.24 25.6839 9.24C24.8252 9.24 24.0972 9.52 23.4999 10.08C22.9212 10.64 22.5852 11.3773 22.4919 12.292ZM49.7783 21H45.2983V12.74C45.2983 11.7693 45.1116 11.0693 44.7383 10.64C44.3836 10.192 43.9076 9.968 43.3103 9.968C42.6943 9.968 42.069 10.2107 41.4343 10.696C40.7996 11.1813 40.3516 11.8067 40.0903 12.572V21H35.6103V6.3H39.6423V8.764C40.1836 7.90533 40.949 7.23333 41.9383 6.748C42.9276 6.26267 44.0663 6.02 45.3543 6.02C46.3063 6.02 47.0716 6.19733 47.6503 6.552C48.2476 6.888 48.6956 7.336 48.9943 7.896C49.3116 8.43733 49.517 9.03467 49.6103 9.688C49.7223 10.3413 49.7783 10.976 49.7783 11.592V21ZM52.7548 4.62V0.559999H57.2348V4.62H52.7548ZM52.7548 21V6.3H57.2348V21H52.7548ZM63.4657 6.3L66.0697 10.444L66.3497 10.976L66.6297 10.444L69.2337 6.3H73.8537L68.9257 13.608L73.9657 21H69.3457L66.6017 16.884L66.3497 16.352L66.0977 16.884L63.3537 21H58.7337L63.7737 13.692L58.8457 6.3H63.4657Z" fill="black"/>
                </svg>
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
                                    <div class="header-info">
                                        <span style="color: #fff; font-weight: 600;"><strong>{{ Auth::user()->name }}</strong></span>
                                        <p class="fs-12 mb-0" style="color: rgba(255, 255, 255, 0.8);">{{ Auth::user()->email }}</p>
                                    </div>
                                    <img src="{{ asset('admin-theme/images/profile/pic1.jpg') }}" width="20" alt="" style="border-radius: 50%;">
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <a href="#" class="dropdown-item ai-icon">
                                        <svg id="icon-user1" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                                        <span class="ml-2">Profile </span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item ai-icon">
                                            <svg id="icon-logout" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                            <span class="ml-2">Logout </span>
                                        </button>
                                    </form>
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
                    <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                            <i class="flaticon-381-networking"></i>
                            <span class="nav-text">Dashboard</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                        </ul>
                    </li>
                    <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                            <i class="flaticon-381-user-7"></i>
                            <span class="nav-text">Users</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="#">All Users</a></li>
                            <li><a href="#">Add User</a></li>
                        </ul>
                    </li>
                    <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                            <i class="flaticon-381-notepad"></i>
                            <span class="nav-text">Student Entries</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="{{ route('admin.students.index') }}">All Students</a></li>
                        </ul>
                    </li>
                    <li><a class="has-arrow ai-icon" href="javascript:void()" aria-expanded="false">
                            <i class="flaticon-381-settings-2"></i>
                            <span class="nav-text">Settings</span>
                        </a>
                        <ul aria-expanded="false">
                            <li><a href="{{ route('admin.settings.index') }}">Frontend Settings</a></li>
                            <li><a href="{{ route('admin.settings.about') }}">About Page</a></li>
                            <li><a href="{{ route('admin.settings.contact') }}">Contact Page</a></li>
                            <li><a href="{{ route('admin.settings.learning') }}">Learning Site Page</a></li>
                            <li><a href="{{ route('admin.settings.classes') }}">Classes Page</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>

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
                    <!-- General Settings -->
                    <div class="col-xl-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">General Learning Site Settings</h5>
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
                <p>Copyright © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>

    <!-- Required vendors -->
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
</body>

</html>

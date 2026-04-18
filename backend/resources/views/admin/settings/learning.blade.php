@extends('layouts.admin')

@section('title', 'Learning Site Settings')

@push('styles')
<style>
    .content-body {
        margin-top: 0 !important;
        padding-top: 1.25rem;
    }

    .card {
        border-radius: 0.75rem;
        box-shadow: 0 0.25rem 1.25rem rgba(0, 0, 0, 0.2);
        margin-bottom: 1.5625rem;
        background: rgba(43, 37, 72, 0.4) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .card-header {
        border-bottom: 1.0px solid rgba(255, 255, 255, 0.1);
        background: transparent !important;
    }

    .card-title {
        color: #fff !important;
        font-weight: 600;
    }

    .form-control, .bootstrap-select .dropdown-toggle {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        border-radius: 0.5rem !important;
    }

    .form-control:focus {
        border-color: #EB8153 !important;
        box-shadow: 0 0 0 0.2rem rgba(235, 129, 83, 0.25) !important;
    }

    label {
        color: rgba(255, 255, 255, 0.7) !important;
        font-weight: 500;
    }

    .btn-info.btn-xs {
        background-color: #EB8153;
        border-color: #EB8153;
        color: #fff;
        border-radius: 0.375rem;
        padding: 0.3125rem 0.75rem;
    }

    .btn-info.btn-xs:hover {
        background-color: #d96e42;
        border-color: #d96e42;
    }

    hr {
        border-top: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .text-muted {
        color: rgba(255, 255, 255, 0.5) !important;
    }

    .table {
        color: rgba(255, 255, 255, 0.8) !important;
    }
    .table thead th {
        border-bottom: 0.125rem solid rgba(255, 255, 255, 0.1) !important;
        color: #fff !important;
        font-weight: 600;
    }
    .table td, .table th {
        border-top: 1.0px solid rgba(255, 255, 255, 0.1) !important;
    }
    .custom-file-label {
        background: rgba(0, 0, 0, 0.2) !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1) !important;
        color: rgba(255, 255, 255, 0.5) !important;
    }
    .custom-file-label::after {
        background: #EB8153 !important;
        color: #fff !important;
        border-left: 1.0px solid rgba(255, 255, 255, 0.1) !important;
    }
    .border-right {
        border-right: 1.0px solid rgba(255, 255, 255, 0.1) !important;
    }

    .btn-primary:hover {
        transform: translateY(-0.125rem);
        box-shadow: 0 0.25rem 0.75rem rgba(102, 126, 234, 0.4) !important;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Learning Site Settings</h4>
            <div>
                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}/notes" target="_blank" class="btn btn-primary btn-sm">View Learning Site Page</a>
            </div>
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
                                <input type="text" name="learning_notes_title" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_notes_title', 'Study Notes') }}">
                            </div>
                            
                            <div class="form-group">
                                <label>Notes Description</label>
                                <textarea name="learning_notes_description" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('learning_notes_description', 'Access comprehensive study notes for all subjects and grades.') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Past Papers Page Title</label>
                                <input type="text" name="learning_pastpapers_title" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_pastpapers_title', 'Past Papers') }}">
                            </div>
                            <div class="form-group">
                                <label>Past Papers Description</label>
                                <textarea name="learning_pastpapers_description" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('learning_pastpapers_description', 'Practice with previous exam papers to prepare for your exams.') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Recordings Page Title</label>
                                <input type="text" name="learning_recordings_title" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_recordings_title', 'Class Recordings') }}">
                            </div>
                            <div class="form-group">
                                <label>Recordings Description</label>
                                <textarea name="learning_recordings_description" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('learning_recordings_description', 'Watch recorded lessons anytime, anywhere.') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Menu Label</label>
                                <input type="text" name="learning_menu_label" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_menu_label', 'Learning Suite') }}">
                            </div>
                        </div>
                    </div>
                    <hr>
                    <h5 class="mb-3 text-primary mt-3">Call to Action (CTA) Section - Bottom of All Pages</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>CTA Title</label>
                                <input type="text" name="learning_cta_title" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_title', 'Need More Resources?') }}">
                            </div>
                            <div class="form-group">
                                <label>CTA Description</label>
                                <textarea name="learning_cta_desc" class="form-control" rows="2">{{ \App\Models\SiteSetting::get('learning_cta_desc', 'Contact us to request specific materials or get access to premium content.') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>CTA Button Text</label>
                                <input type="text" name="learning_cta_btn" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_btn', 'Contact Us') }}">
                            </div>
                            <div class="form-group">
                                <label>CTA Button Link</label>
                                <input type="text" name="learning_cta_link" class="form-control" value="{{ \App\Models\SiteSetting::get('learning_cta_link', '/contact') }}">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-3">Save General Settings</button>
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
                                      <option value="grade-1">Grade 1</option>
                                        <option value="grade-2">Grade 2</option>
                                          <option value="grade-3">Grade 3</option>
                                            <option value="grade-4">Grade 4</option>
                                              <option value="grade-5">Grade 5</option>
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
                                    <label class="custom-file-label">Choose PDF (500MB max)</label>
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
                                    <option value="grade-1">Grade 1</option>
                                    <option value="grade-2">Grade 2</option>
                                    <option value="grade-3">Grade 3</option>
                                    <option value="grade-4">Grade 4</option>
                                    <option value="grade-5">Grade 5</option>
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
                                    <label class="custom-file-label">Choose PDF (500MB max)</label>
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
                                      <option value="grade-1">Grade 1</option>
                                    <option value="grade-2">Grade 2</option>
                                    <option value="grade-3">Grade 3</option>
                                    <option value="grade-4">Grade 4</option>
                                    <option value="grade-5">Grade 5</option>
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
                                     <label class="custom-file-label">Choose File (500MB max)</label>
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
@endsection

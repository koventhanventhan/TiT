<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add Timetable Slot - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <style>
        .content-body { margin-top: 0 !important; padding-top: 1.25rem; }
    </style>
</head>

<body>
    <div id="main-wrapper">
        @include('admin.partials.sidebar')
        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12">
                        <h4 style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Add Recurring Timetable Slot</h4>
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
                    <div class="card-body">
                        <form action="{{ route('admin.timetables.store') }}" method="POST">
                            @csrf
                            <div class="row">
                                <div class="form-group col-md-6">
                                    <label>Slot Title (e.g. Maths Class)</label>
                                    <input type="text" name="title" class="form-control" placeholder="Enter title" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Day of Week</label>
                                    <select name="day_of_week" class="form-control" required>
                                        @foreach($days as $day)
                                            <option value="{{ $day }}">{{ $day }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Start Time</label>
                                    <input type="time" name="start_time" class="form-control" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Duration (Minutes)</label>
                                    <input type="number" name="duration" class="form-control" value="60" required>
                                </div>
                                <div class="form-group col-md-4">
                                    <label>Grade</label>
                                    <input type="text" name="grade" class="form-control" placeholder="e.g. Grade 10" required>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Subject</label>
                                    <select name="subject_id" class="form-control" required>
                                        @foreach($subjects as $s)
                                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Assigned Teacher</label>
                                    <select name="teacher_id" class="form-control" required>
                                        @foreach($teachers as $t)
                                            <option value="{{ $t->id }}">{{ $t->name }}{{ $t->teacher_class ? ' ('.$t->teacher_class.')' : '' }} - {{ $t->email }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-6">
                                    <label>Zoom Host Account (Optional)</label>
                                    <select name="zoom_host_email" class="form-control">
                                        <option value="">Default (me)</option>
                                        @foreach($zoomUsers as $zu)
                                            <option value="{{ $zu['email'] }}">{{ $zu['display_name'] ?? $zu['email'] }} ({{ $zu['email'] }})</option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Required for concurrent meetings at the same time.</small>
                                </div>
                                <div class="form-group col-12">
                                    <div class="col-sm-6 mb-3">
                                        <label>Medium <span class="text-danger">*</span></label>
                                        <select name="medium" class="form-control default-select" required>
                                            <option value="tamil">Tamil</option>
                                            <option value="english">English</option>
                                        </select>
                                    </div>

                                    <div class="col-sm-6 mb-3">
                                        <div class="custom-control custom-checkbox mb-3">
                                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" checked>
                                            <label class="custom-control-label" for="is_active">Active (Set to inactive to pause this slot)</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Save Slot</button>
                            <a href="{{ route('admin.timetables.index') }}" class="btn btn-light">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
</body>
</html>

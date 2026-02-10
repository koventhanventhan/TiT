<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Zoom Class - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <style>.content-body { margin-top: 0 !important; padding-top: 20px; } .card { border-radius: 8px; margin-bottom: 20px; }</style>
</head>
<body>
    <div id="main-wrapper">
        <div class="nav-header"><a href="{{ route('admin.dashboard') }}" class="brand-logo">Admin</a></div>
        <div class="header"><div class="header-content"><nav class="navbar"><ul class="navbar-nav"><li><a href="{{ route('admin.zoom.index') }}">Back to Zoom Classes</a></li></ul></nav></div></div>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4"><div class="col-12"><h4 style="font-size:24px;font-weight:600;">Edit Zoom Class</h4></div></div>
                @if ($errors->any())
                    <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                @endif
                <form action="{{ route('admin.zoom.update', $schedule->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $schedule->title) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Zoom Link (URL) <span class="text-danger">*</span></label>
                                <input type="url" name="zoom_link" class="form-control" value="{{ old('zoom_link', $schedule->zoom_link) }}" required>
                            </div>
                            <div class="form-group">
                                <label>Scheduled At <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ old('scheduled_at', $schedule->scheduled_at->format('Y-m-d\TH:i')) }}" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label>Subject</label><input type="text" name="subject" class="form-control" value="{{ old('subject', $schedule->subject) }}"></div></div>
                                <div class="col-md-6"><div class="form-group"><label>Grade</label><input type="text" name="grade" class="form-control" value="{{ old('grade', $schedule->grade) }}"></div></div>
                            </div>
                            <div class="form-group">
                                <label>Assign Teachers</label>
                                @php $selectedIds = old('teacher_ids', $schedule->teachers->pluck('id')->toArray()); @endphp
                                <select name="teacher_ids[]" class="form-control" multiple>
                                    @foreach($teachers as $t)<option value="{{ $t->id }}" {{ in_array($t->id, $selectedIds) ? 'selected' : '' }}>{{ $t->name }} ({{ $t->email }})</option>@endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('admin.zoom.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
</body>
</html>

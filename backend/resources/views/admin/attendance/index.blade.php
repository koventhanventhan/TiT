<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Attendance - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <style>.content-body { margin-top: 0 !important; padding-top: 20px; } .card { border-radius: 8px; margin-bottom: 20px; } .att-table { margin-top: 12px; } .att-table th, .att-table td { padding: 8px; }</style>
</head>
<body>
    <div id="main-wrapper">
        <div class="nav-header"><a href="{{ route('admin.dashboard') }}" class="brand-logo">Admin</a></div>
        <div class="header"><div class="header-content"><nav class="navbar"><ul class="navbar-nav"><li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li><li><a href="{{ route('admin.zoom.index') }}">Zoom Classes</a></li></ul></nav></div></div>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4"><div class="col-12"><h4 style="font-size:24px;font-weight:600;">Attendance</h4></div></div>
                @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                @forelse($schedules as $s)
                <div class="card">
                    <div class="card-header">
                        <strong>{{ $s->title }}</strong> — {{ $s->scheduled_at->format('M d, Y H:i') }}
                    </div>
                    <div class="card-body">
                        <table class="table table-sm att-table">
                            <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Edit</th></tr></thead>
                            <tbody>
                                @foreach($s->attendances as $att)
                                <tr>
                                    <td>{{ $att->user->full_name ?? $att->user->name ?? $att->user->email }}</td>
                                    <td><span class="badge badge-{{ $att->role === 'teacher' ? 'info' : 'secondary' }}">{{ $att->role }}</span></td>
                                    <td><span class="badge badge-{{ $att->status === 'present' ? 'success' : 'warning' }}">{{ $att->status }}</span></td>
                                    <td>
                                        <form action="{{ route('admin.attendance.update') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="attendance_id" value="{{ $att->id }}">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="present" {{ $att->status === 'present' ? 'selected' : '' }}>Present</option>
                                                <option value="absent" {{ $att->status === 'absent' ? 'selected' : '' }}>Absent</option>
                                            </select>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                                @if($s->attendances->isEmpty())
                                <tr><td colspan="4" class="text-muted">No attendance recorded yet.</td></tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                @empty
                <p class="text-muted">No zoom classes yet.</p>
                @endforelse
                @if($schedules->hasPages())<div class="mt-4">{{ $schedules->links() }}</div>@endif
            </div>
        </div>
    </div>
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
</body>
</html>

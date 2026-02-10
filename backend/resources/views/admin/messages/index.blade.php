<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Messages - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <style>.content-body { margin-top: 0 !important; padding-top: 20px; } .card { border-radius: 8px; margin-bottom: 20px; }</style>
</head>
<body>
    <div id="main-wrapper">
        <div class="nav-header"><a href="{{ route('admin.dashboard') }}" class="brand-logo">Admin</a></div>
        <div class="header"><div class="header-content"><nav class="navbar"><ul class="navbar-nav"><li><a href="{{ route('admin.dashboard') }}">Dashboard</a></li></ul></nav></div></div>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <h4 style="font-size:24px;font-weight:600;">Messages</h4>
                        <a href="{{ route('admin.messages.create') }}" class="btn btn-primary btn-sm">New Message</a>
                    </div>
                </div>
                @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
                <div class="card">
                    <div class="card-body">
                        <table class="table">
                            <thead><tr><th>ID</th><th>Title</th><th>Type</th><th>Target</th><th>Created</th></tr></thead>
                            <tbody>
                                @forelse($messages as $m)
                                <tr>
                                    <td>{{ $m->id }}</td>
                                    <td>{{ $m->title }}</td>
                                    <td><span class="badge badge-{{ $m->target_type === 'broadcast' ? 'primary' : 'info' }}">{{ $m->target_type }}</span></td>
                                    <td>@if($m->target_user_id){{ $m->targetUser->full_name ?? $m->targetUser->name ?? $m->target_user_id }}@else All students @endif</td>
                                    <td>{{ $m->created_at->format('M d, Y H:i') }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-muted">No messages.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($messages->hasPages())<div class="mt-4">{{ $messages->links() }}</div>@endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>New Message - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/admin-responsive.css') }}" rel="stylesheet">
    <style>.content-body { margin-top: 0 !important; padding-top: 1.25rem; } .card { border-radius: 0.5rem; margin-bottom: 1.25rem; }</style>
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
    <div id="main-wrapper">
        <div class="nav-header">            <a href="{{ route('admin.dashboard') }}" class="brand-logo">
                @if(isset($site_settings['admin_logo']))
                    <img src="{{ asset($site_settings['admin_logo']) }}" alt="Logo" style="max-height: 2.8125rem; max-width: 2.8125rem; object-fit: contain;">
                @else
                    <svg class="logo-abbr" width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect class="svg-logo-rect" width="50" height="50" rx="20" fill="#EB8153"/>
                        <path class="svg-logo-path" d="M17.5158 25.8619L19.8088 25.2475L14.8746 11.1774C14.5189 9.84988 15.8701 9.0998 16.8205 9.75055L33.0924 22.2055C33.7045 22.5589 33.8512 24.0717 32.6444 24.3951L30.3514 25.0095L35.2856 39.0796C35.6973 40.1334 34.4431 41.2455 33.3397 40.5064L17.0678 28.0515C16.2057 27.2477 16.5504 26.1205 17.5158 25.8619ZM18.685 14.2955L22.2224 24.6007L29.4633 22.6605L18.685 14.2955ZM31.4751 35.9615L27.8171 25.6886L20.5762 27.6288L31.4751 35.9615Z" fill="white"/>
                    </svg>
                @endif
                <span class="brand-title" style="font-size: 1.5rem; font-weight: 700; margin-left:0.75rem; color: #fff;">
                    {{ $site_settings['admin_company_name'] ?? 'Zenix' }}
                </span>
            </a></div>
        <div class="header"><div class="header-content"><nav class="navbar"><ul class="navbar-nav"><li><a href="{{ route('admin.messages.index') }}">Back to Messages</a></li></ul></nav></div></div>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4"><div class="col-12"><h4 style="font-size:1.5rem;font-weight:600;">New Message</h4></div></div>
                @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                <form action="{{ route('admin.messages.store') }}" method="POST">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group">
                                <label>Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                            </div>
                            <div class="form-group">
                                <label>Body <span class="text-danger">*</span></label>
                                <textarea name="body" class="form-control" rows="4" required>{{ old('body') }}</textarea>
                            </div>
                            <div class="form-group">
                                <label>Send to</label>
                                <div>
                                    <label class="mr-3"><input type="radio" name="target_type" value="broadcast" {{ old('target_type', 'broadcast') === 'broadcast' ? 'checked' : '' }}> All students (broadcast)</label>
                                    <label><input type="radio" name="target_type" value="individual" {{ old('target_type') === 'individual' ? 'checked' : '' }}> Individual student(s)</label>
                                </div>
                            </div>
                            <div class="form-group" id="individual-select" style="display:{{ old('target_type') === 'individual' ? 'block' : 'none' }};">
                                <label>Select students</label>
                                <select name="target_user_ids[]" class="form-control" multiple size="8">
                                    @foreach($students as $s)<option value="{{ $s->id }}" {{ in_array($s->id, old('target_user_ids', [])) ? 'selected' : '' }}>{{ $s->full_name ?? $s->name }} ({{ $s->email }})</option>@endforeach
                                </select>
                                <small class="text-muted">Hold Ctrl to select multiple</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                            <a href="{{ route('admin.messages.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script>
      document.querySelectorAll('input[name="target_type"]').forEach(function(r) {
        r.addEventListener('change', function() {
          document.getElementById('individual-select').style.display = this.value === 'individual' ? 'block' : 'none';
        });
      });
    </script>
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>
</body>
</html>





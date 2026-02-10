<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>New Message - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <style>.content-body { margin-top: 0 !important; padding-top: 20px; } .card { border-radius: 8px; margin-bottom: 20px; }</style>
</head>
<body>
    <div id="main-wrapper">
        <div class="nav-header"><a href="{{ route('admin.dashboard') }}" class="brand-logo">Admin</a></div>
        <div class="header"><div class="header-content"><nav class="navbar"><ul class="navbar-nav"><li><a href="{{ route('admin.messages.index') }}">Back to Messages</a></li></ul></nav></div></div>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4"><div class="col-12"><h4 style="font-size:24px;font-weight:600;">New Message</h4></div></div>
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
</body>
</html>

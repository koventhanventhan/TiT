<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Add Student - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <style>.content-body { margin-top: 0 !important; padding-top: 20px; } .card { border-radius: 8px; margin-bottom: 20px; }</style>
</head>
<body>
    <div id="main-wrapper">
        <div class="nav-header"><a href="{{ route('admin.dashboard') }}" class="brand-logo">Admin</a></div>
        <div class="header"><div class="header-content"><nav class="navbar"><ul class="navbar-nav"><li><a href="{{ route('admin.students.index') }}">Back to Students</a></li></ul></nav></div></div>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row mb-4"><div class="col-12"><h4 style="font-size:24px;font-weight:600;">Add Student</h4></div></div>
                @if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
                <form action="{{ route('admin.students.store') }}" method="POST">
                    @csrf
                    <div class="card">
                        <div class="card-body">
                            <div class="form-group"><label>Username / Name <span class="text-danger">*</span></label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
                            <div class="form-group"><label>Email <span class="text-danger">*</span></label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                            <div class="form-group"><label>Password <span class="text-danger">*</span></label><input type="password" name="password" class="form-control" required></div>
                            <div class="form-group"><label>Full Name <span class="text-danger">*</span></label><input type="text" name="full_name" class="form-control" value="{{ old('full_name') }}" required></div>
                            <div class="form-group"><label>Phone</label><input type="text" name="phone_number" class="form-control" value="{{ old('phone_number') }}"></div>
                            <div class="form-group"><label>Date of Birth <span class="text-danger">*</span></label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}" required></div>
                            <div class="form-group"><label>Gender <span class="text-danger">*</span></label><select name="gender" class="form-control" required><option value="male">Male</option><option value="female">Female</option></select></div>
                            <div class="form-group"><label>School Name <span class="text-danger">*</span></label><input type="text" name="school_name" class="form-control" value="{{ old('school_name') }}" required></div>
                            <div class="form-group"><label>Medium <span class="text-danger">*</span></label><select name="medium" class="form-control" required><option value="tamil">Tamil</option><option value="english">English</option></select></div>
                            <div class="form-group"><label>Online Experience</label><select name="online_experience" class="form-control"><option value="0">No</option><option value="1">Yes</option></select></div>
                            <div class="form-group"><label>Device Used <span class="text-danger">*</span></label><input type="text" name="device_used" class="form-control" value="{{ old('device_used', 'Laptop') }}" required></div>
                            <div class="form-group"><label>Current Grade <span class="text-danger">*</span></label><input type="text" name="current_grade" class="form-control" value="{{ old('current_grade') }}" required></div>
                            <div class="form-group"><label>Stream</label><select name="stream" class="form-control"><option value="">–</option><option value="arts">Arts</option><option value="bio_maths">Bio & Maths</option></select></div>
                            <div class="form-group"><label>Selected Subjects (JSON or comma)</label><input type="text" name="selected_subjects" class="form-control" value="{{ old('selected_subjects') }}"></div>
                            <div class="form-group"><label>Registration Status</label><select name="registration_status" class="form-control"><option value="pending_payment">Pending Payment</option><option value="paid_pending_confirm">Paid Pending Confirm</option><option value="confirmed">Confirmed</option></select></div>
                            <div class="form-group"><label><input type="checkbox" name="admin_confirmed" value="1"> Admin confirmed</label></div>
                            <button type="submit" class="btn btn-primary">Add Student</button>
                            <a href="{{ route('admin.students.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
</body>
</html>

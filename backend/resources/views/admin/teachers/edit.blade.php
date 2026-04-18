@extends('layouts.admin')

@section('title', 'Edit Teacher')

@push('styles')
<style>
    .card {
        border-radius: 0.5rem;
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.1);
        margin-bottom: 1.25rem;
    }
</style>
@endpush

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="page-title d-flex justify-content-between align-items-center">
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Edit Teacher</h4>
            <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary btn-sm">
                <i class="flaticon-381-back"></i> Back to Teachers
            </a>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-xl-12">
        <form action="{{ route('admin.teachers.update', $teacher->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Teacher Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $teacher->name) }}" placeholder="Full Name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $teacher->email) }}" placeholder="example@edulearn.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Password <small class="text-muted">(leave blank to keep current)</small></label>
                                <div style="position: relative;">
                                    <input type="text" name="password" id="editTeacherPassword" class="form-control" value="{{ $teacher->plain_password }}" placeholder="{{ $teacher->plain_password ? '' : 'Enter new password' }}" minlength="8" style="padding-right: 3.125rem;">
                                    <button type="button" onclick="togglePassword('editTeacherPassword', 'editTeacherEyeIcon')" style="position: absolute; right: 0.625rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6366f1; font-size: 1.125rem; padding: 0.3125rem;">
                                        <span id="editTeacherEyeIcon">🙈</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Phone Number</label>
                                <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $teacher->phone_number) }}" placeholder="+94 ...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">subject</label>
                                <input type="text" name="teacher_class" class="form-control" value="{{ old('teacher_class', $teacher->teacher_class) }}" placeholder="e.g. Grade 10, Grade 11 Arts">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Update Teacher</button>
                        <a href="{{ route('admin.teachers.index') }}" class="btn btn-light ml-2">Cancel</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePassword(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.textContent = '🙈';
        } else {
            input.type = 'password';
            icon.textContent = '👁️';
        }
    }
</script>
@endpush

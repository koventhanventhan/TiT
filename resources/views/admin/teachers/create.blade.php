@extends('layouts.admin')

@section('title', 'Add Teacher')

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
            <h4 class="mb-0" style="font-size: 1.5rem; font-weight: 600; color: #1f2937;">Add Teacher</h4>
            <div>
                <a href="{{ route('admin.teachers.index') }}" class="btn btn-secondary btn-sm">
                    <i class="flaticon-381-back"></i> Back to Teachers
                </a>
            </div>
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
        <form action="{{ route('admin.teachers.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Teacher Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Full Name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="example@edulearn.com" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Password <span class="text-danger">*</span></label>
                                <div style="position: relative;">
                                    <input type="password" name="password" id="teacherPassword" class="form-control" placeholder="At least 8 characters" required minlength="8" style="padding-right: 3.125rem;">
                                    <button type="button" onclick="togglePassword('teacherPassword', 'teacherEyeIcon')" style="position: absolute; right: 0.625rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #6366f1; font-size: 1.125rem; padding: 0.3125rem;">
                                        <span id="teacherEyeIcon">👁️</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Phone Number</label>
                                <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number') }}" placeholder="+94 ...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Class</label>
                                <input type="text" name="teacher_class" class="form-control" value="{{ old('teacher_class') }}" placeholder="e.g. Grade 10, Grade 11 Arts">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="text-label" style="font-weight: 600;">Teacher Photo (Avatar)</label>
                                <div class="text-center" style="border: 2px dashed #d1d5db; border-radius: 0.5rem; padding: 1.25rem; background: #f9fafb; cursor: pointer; position: relative;" onclick="document.getElementById('teacherAvatarInput').click()">
                                    <img id="teacherAvatarPreview" src="" style="width: 6.25rem; height: 6.25rem; border-radius: 50%; object-fit: cover; display: none; margin: 0 auto 0.625rem;">
                                    <div id="teacherAvatarPlaceholder">
                                        <i class="la la-image" style="font-size: 2.5rem; color: #9ca3af;"></i>
                                        <p class="mt-2 mb-0" style="color: #6b7280; font-size: 0.875rem;">Click to upload photo</p>
                                        <small class="text-muted">JPG, PNG or WebP</small>
                                    </div>
                                </div>
                                <input type="file" id="teacherAvatarInput" accept="image/jpeg, image/png, image/webp" style="display: none;" onchange="handleTeacherAvatar(this)">
                                <input type="hidden" name="avatar_base64" id="teacherAvatarBase64">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">Add Teacher</button>
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

    function handleTeacherAvatar(input) {
        if (!input.files || input.files.length === 0) return;
        if (typeof openCropper === 'function') {
            openCropper(input.files[0], {
                aspectRatio: 1, // Square avatar
                wantsDataURL: true,
                callback: function(dataUrl) {
                    document.getElementById('teacherAvatarPreview').src = dataUrl;
                    document.getElementById('teacherAvatarPreview').style.display = 'block';
                    document.getElementById('teacherAvatarPlaceholder').style.display = 'none';
                    document.getElementById('teacherAvatarBase64').value = dataUrl;
                }
            });
        }
        input.value = ''; // Reset so the same file could be selected again if needed
    }
</script>
@endpush

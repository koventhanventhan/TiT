@extends('layouts.admin')

@section('title', 'Your Profile')

@push('styles')
<style>
    .content-body {
        margin-top: 0 !important;
        padding-top: 1.25rem;
        background: #2c254a !important;
    }
    
    .profile-photo-preview {
        width: 6.25rem;
        height: 6.25rem;
        border-radius: 50%;
        object-fit: cover;
        border: 0.1875rem solid #EB8153;
    }
    
    .profile-photo-placeholder {
        width: 6.25rem;
        height: 6.25rem;
        border-radius: 50%;
        background: #EB8153;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: bold;
    }
    
    .card {
        background: #3b3363 !important;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .card-title {
        color: #fff !important;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.05) !important;
        color: #fff !important;
    }
</style>
@endpush

@section('content')
<div class="page-titles">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="javascript:void(0)">Admin</a></li>
        <li class="breadcrumb-item active"><a href="javascript:void(0)">Your Profile</a></li>
    </ol>
</div>

<div class="row">
    <div class="col-xl-9 col-lg-10">
        <div class="card">
            <div class="card-header border-0 pb-0 d-flex justify-content-between">
                <h4 class="card-title">Profile Information</h4>
                <span class="badge badge-pill badge-outline-primary">Public</span>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <button type="button" class="close h-100" data-dismiss="alert"><span><i class="mdi mdi-close"></i></span></button>
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group mb-4">
                        <label class="text-white font-w600">Profile Photo</label>
                        <div class="d-flex align-items-center mt-2">
                            @php $user = Auth::user(); @endphp
                            @if($user->avatar)
                                <img src="{{ asset($user->avatar) }}" alt="Avatar" class="profile-photo-preview mr-3" id="avatarPreview">
                            @else
                                <div class="profile-photo-placeholder mr-3" id="avatarPreview">
                                    {{ strtoupper(substr($user->first_name ?: $user->name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <input type="file" name="avatar" id="avatarInput" class="d-none" accept="image/*" onchange="previewAvatar(this)">
                                <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">
                                <button type="button" class="btn btn-primary btn-sm px-4 mr-2" style="background: #EB8153; border-color: #EB8153;" onclick="document.getElementById('avatarInput').click()">Change Photo</button>
                                <button type="button" class="btn btn-danger btn-sm px-4" id="removeAvatarBtn" style="{{ !$user->avatar ? 'display: none;' : '' }}" onclick="removeAvatar()">Remove Photo</button>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-6"><label class="text-white font-w600">First Name</label><input type="text" name="first_name" class="form-control bg-transparent text-white border-dark" value="{{ $user->first_name }}"></div>
                        <div class="form-group col-md-6"><label class="text-white font-w600">Last Name</label><input type="text" name="last_name" class="form-control bg-transparent text-white border-dark" value="{{ $user->last_name }}"></div>
                    </div>

                    <div class="row">
                        <div class="form-group col-md-12"><label class="text-white font-w600">Email Address</label><input type="email" name="email" class="form-control bg-transparent text-white border-dark" value="{{ $user->email }}"></div>
                    </div>

                    <h5 class="text-white mt-4 mb-3" style="border-bottom: 1px solid rgba(255, 255, 255, 0.1); padding-bottom: 10px;">System Notifications</h5>
                    <div class="row">
                        <div class="form-group col-md-12">
                            <label class="text-white font-w600">Admin Notification Email</label>
                            <input type="email" name="admin_notification_email" class="form-control bg-transparent text-white border-dark" value="{{ \App\Models\SiteSetting::get('admin_notification_email', 'koventhanventhan153@gmail.com') }}">
                            <small class="text-white-50 mt-1 d-block">Primary email address to receive system alerts, payment notifications, and registration alerts.</small>
                        </div>
                    </div>

                    <div class="text-right mt-4 pt-4 border-top">
                        <button type="submit" class="btn btn-primary btn-sm px-4">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function previewAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('avatarPreview');
                document.getElementById('removeAvatarInput').value = "0";
                const removeBtn = document.getElementById('removeAvatarBtn');
                if (removeBtn) removeBtn.style.display = 'inline-block';
                if (preview.tagName === 'IMG') { preview.src = e.target.result; } else {
                    const img = document.createElement('img'); img.src = e.target.result; img.id = 'avatarPreview'; img.className = 'profile-photo-preview mr-3'; preview.replaceWith(img);
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeAvatar() {
        const preview = document.getElementById('avatarPreview');
        const initials = "{{ strtoupper(substr(Auth::user()->first_name ?: Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->last_name, 0, 1)) }}";
        
        const placeholder = document.createElement('div');
        placeholder.id = 'avatarPreview';
        placeholder.className = 'profile-photo-placeholder mr-3';
        placeholder.innerText = initials;
        
        preview.replaceWith(placeholder);
        document.getElementById('removeAvatarInput').value = "1";
        document.getElementById('avatarInput').value = "";
        
        const removeBtn = document.getElementById('removeAvatarBtn');
        if (removeBtn) removeBtn.style.display = 'none';
    }
</script>
@endpush

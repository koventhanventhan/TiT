<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Your Profile - {{ config('app.name') }}</title>
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/admin-responsive.css') }}" rel="stylesheet">
    <style>
        .content-body {
            margin-top: 0 !important;
            padding-top: 1.25rem;
            background: #2c254a !important;
        }
        
        body {
            background: #2c254a !important;
        }
        
        .header {
            background: #1f2937;
        }
        
        .header-profile .nav-link {
            display: flex;
            align-items: center;
            gap: 0.625rem;
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
        
        .search_bar .form-control {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border: none;
            border-radius: 0.5rem;
        }

        @media (min-width: 75rem) {
            .col-xl-9 {
                flex: 0 0 100%;
                max-width: 100%;
            }
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
    <div id="preloader">
        <div class="sk-three-bounce">
            <div class="sk-child sk-bounce1"></div>
            <div class="sk-child sk-bounce2"></div>
            <div class="sk-child sk-bounce3"></div>
        </div>
    </div>

    <div id="main-wrapper">
        <div class="nav-header">
            <a href="{{ route('admin.dashboard') }}" class="brand-logo">
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
            </a>
            <div class="nav-control">
                <div class="hamburger">
                    <span class="line"></span><span class="line"></span><span class="line"></span>
                </div>
            </div>
        </div>

        @include('admin.partials.header')

        @include('admin.partials.sidebar')

        <div class="content-body">
            <div class="container-fluid">
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

                                    <!-- <div class="form-group"><label class="text-black font-w600">Bio</label><textarea name="bio" rows="4" class="form-control" placeholder="Brief description for your profile.">{{ $user->bio }}</textarea></div>

                                    <div class="row">
                                        <div class="form-group col-md-6"><label class="text-black font-w600">Website</label><input type="url" name="website" class="form-control" value="{{ $user->website }}"></div>
                                        <div class="form-group col-md-6"><label class="text-black font-w600">Location</label><input type="text" name="location" class="form-control" value="{{ $user->location }}"></div>
                                    </div> -->

                                    <div class="text-right mt-4 pt-4 border-top">
                                        <button type="submit" class="btn btn-primary btn-sm px-4">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer"><div class="copyright"><p>Copyright Â© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p></div></div>
    </div>

    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-search.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
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
            const initials = "{{ strtoupper(substr($user->first_name ?: $user->name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}";
            
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
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
</body>
</html>





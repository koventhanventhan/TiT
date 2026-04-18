@extends('layouts.admin')

@section('title', 'Settings')

@push('styles')
<!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<style>
    :root {
        --settings-bg: #2c254a;
        --card-bg: #3b3363;
        --sidebar-active: rgba(235, 129, 83, 0.1);
        --accent-orange: #EB8153;
        --accent-blue: #4d44b5;
        --text-muted: rgba(255, 255, 255, 0.7);
    }

    .content-body {
        margin-top: 0 !important;
        padding-top: 1.25rem;
        background: #2c254a !important;
    }
    
    .settings-container {
        display: flex;
        gap: 1.25rem;
        min-height: 43.75rem;
    }

    .settings-sidebar {
        width: 17.5rem;
        background: #3b3363;
        border-radius: 0.75rem;
        padding: 1.25rem;
        height: fit-content;
    }

    @media (max-width: 991px) {
        .settings-container {
            flex-direction: column !important;
            display: flex !important;
        }
        .settings-sidebar {
            width: 100% !important;
            min-width: 100% !important;
            margin-bottom: 1rem !important;
            height: auto !important;
        }
        .calendar-layout {
            grid-template-columns: 1fr !important;
            height: auto !important;
        }
    }

    .settings-nav-item {
        display: flex;
        align-items: center;
        padding: 0.75rem 1rem;
        color: var(--text-muted);
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
        transition: all 0.2s;
        cursor: pointer;
        text-decoration: none;
        border: none;
        background: transparent;
        width: 100%;
        text-align: left;
    }

    .settings-nav-item i {
        font-size: 1.25rem;
        margin-right: 0.75rem;
    }

    .settings-nav-item:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.05);
    }

    .settings-nav-item.active {
        background: rgba(235, 129, 83, 0.1);
        color: var(--accent-orange);
    }

    .settings-content {
        flex: 1;
    }

    .settings-card {
        background: #3b3363 !important;
        border-radius: 0.75rem;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
        margin-bottom: 1.5rem;
        overflow: hidden;
    }

    .settings-card-header {
        padding: 1.5rem 1.5rem 0 1.5rem;
        border: none;
        background: transparent;
    }

    .settings-card-title {
        font-size: 1.125rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 0.25rem;
    }

    .settings-card-body {
        padding: 1.5rem;
    }

    .settings-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1.0px solid rgba(255, 255, 255, 0.05);
    }

    .settings-row:last-child {
        border-bottom: none;
    }

    .settings-info h6 {
        color: #fff;
        margin-bottom: 0.25rem;
        font-size: 0.9375rem;
    }

    .settings-info p {
        color: var(--text-muted);
        font-size: 0.8125rem;
        margin-bottom: 0;
    }

    .custom-switch-blue .custom-control-input:checked ~ .custom-control-label::before {
        background-color: var(--accent-blue);
        border-color: var(--accent-blue);
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
    
    .uppercase { text-transform: uppercase; }

    .calendar-layout {
        display: grid;
        grid-template-columns: 20rem 1fr;
        gap: 1.5rem;
        height: calc(100vh - 12.5rem);
        min-height: 50rem;
    }

    .calendar-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .calendar-main {
        background: #3b3363;
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
        display: flex;
        flex-direction: column;
    }

    #calendar {
        flex: 1;
        color: #fff;
    }

    .fc-theme-standard td, .fc-theme-standard th { border-color: rgba(255, 255, 255, 0.05); }
    .fc-col-header-cell { background: rgba(255, 255, 255, 0.02); padding: 0.75rem 0 !important; }
    .fc-daygrid-day:hover { background: rgba(255, 255, 255, 0.02); }
    .fc-day-today { background: rgba(235, 129, 83, 0.05) !important; }
    .fc-button-primary { background: #4d44b5 !important; border: none !important; }
    .fc-button-primary:hover { background: #5d54c5 !important; }
    .fc-button-active { background: var(--accent-orange) !important; }
    
    .mini-calendar {
        background: #3b3363;
        border-radius: 0.75rem;
        padding: 1.25rem;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .event-categories {
        background: #3b3363;
        border-radius: 0.75rem;
        padding: 1.25rem;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
    }

    .category-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.625rem 0;
        color: var(--text-muted);
        font-size: 0.875rem;
    }

    .category-dot {
        width: 0.625rem;
        height: 0.625rem;
        border-radius: 50%;
        margin-right: 0.75rem;
    }

    .modal-content.calendar-modal {
        background: #3b3363;
        border: 1.0px solid rgba(255, 255, 255, 0.1);
        border-radius: 1rem;
        color: #fff;
    }

    .calendar-modal .form-control {
        background: rgba(255, 255, 255, 0.05);
        border: 1.0px solid rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .calendar-modal .form-control:focus { background: rgba(255, 255, 255, 0.1); }

    .bg-event { background: #4d44b5 !important; }
    .bg-meeting { background: #10b981 !important; }
    .bg-task { background: #f59e0b !important; }
    .bg-reminder { background: #a855f7 !important; }
    .bg-deadline { background: #ef4444 !important; }

    .settings-footer {
        margin-top: 1.5rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }

    .btn-blue {
        background: var(--accent-blue);
        color: #fff;
        border: none;
        padding: 0.625rem 1.5rem;
        border-radius: 0.5rem;
        font-weight: 600;
    }

    .password-toggle-wrapper {
        position: relative;
    }
    
    .password-toggle-wrapper .toggle-password {
        position: absolute;
        right: 0.9375rem;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: var(--text-muted);
        font-size: 1.125rem;
        z-index: 10;
        transition: color 0.2s;
    }
    
    .password-toggle-wrapper .toggle-password:hover {
        color: var(--accent-orange);
    }
</style>
@endpush

@section('content')
<div class="settings-header-section mb-4">
    <h2 class="text-white font-w700 mb-1">Settings</h2>
    <p class="text-muted">Manage your account settings and preferences.</p>
</div>

<div class="settings-container">
    <!-- Internal Sidebar Navigation -->
    <div class="settings-sidebar" id="main_settings_sidebar">
        <!-- Profiles Header/Toggle at the top -->
        <div class="settings-nav-item active" id="profile_toggle" onclick="toggleProfileSubmenu(this)" style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
            <div style="display: flex; align-items: center;">
                <i class="la la-user"></i> <span>Profiles</span>
            </div>
            <i class="la la-angle-down submenu-arrow" style="margin-right: 0; transition: transform 0.3s;"></i>
        </div>
        <!-- Profile Submenu -->
        <div id="profile_submenu" style="padding-left: 1.25rem; display: block;">
            <button type="button" class="settings-nav-item active" data-target="details_tab" onclick="switchTab(this)">
                <i class="la la-user"></i> <span>Profile Details</span>
            </button>
            <button type="button" class="settings-nav-item" data-target="notifications_tab" onclick="switchTab(this)">
                <i class="la la-bell"></i> <span>Notifications</span>
            </button>
            <button type="button" class="settings-nav-item" data-target="security_tab" onclick="switchTab(this)">
                <i class="la la-shield"></i> <span>Security</span>
            </button>
            <button type="button" class="settings-nav-item" data-target="admin_tab" onclick="switchTab(this)">
                <i class="la la-user-shield"></i> <span>Admin Management</span>
            </button>
        </div>

        <!-- Calendar Hidden from sidebar list but still exists as a tab -->
        <div data-target="calendar_tab" style="display: none;"></div>
    </div>

    <!-- Main Content Sections -->
    <div class="settings-content">
        <!-- Profile Details Tab -->
        <div id="details_tab" class="tab-pane-content">
            <div class="settings-card">
                <div class="settings-card-header"><h4 class="settings-card-title">Profile Information</h4></div>
                <div class="settings-card-body">
                    <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group mb-4">
                            <label class="text-white font-w600">Profile Photo</label>
                            <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start mt-3">
                                @php $user = Auth::user(); @endphp
                                @if($user->avatar)
                                    <img src="{{ asset($user->avatar) }}" alt="Avatar" class="profile-photo-preview mb-3 mb-sm-0 mr-sm-4" id="settingsAvatarPreview">
                                @else
                                    <div class="profile-photo-placeholder mb-3 mb-sm-0 mr-sm-4" id="settingsAvatarPreview">
                                        {{ strtoupper(substr($user->first_name ?: $user->name, 0, 1)) }}{{ strtoupper(substr($user->last_name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="d-flex flex-column align-items-start mt-2">
                                    <input type="file" name="avatar" id="settingsAvatarInput" class="d-none" accept="image/*" onchange="previewSettingsAvatar(this)">
                                    <input type="hidden" name="remove_avatar" id="settingsRemoveAvatarInput" value="0">
                                    <button type="button" class="btn btn-primary btn-sm px-4 mb-2" style="background: #EB8153; border-color: #EB8153;" onclick="document.getElementById('settingsAvatarInput').click()">Change Photo</button>
                                    <button type="button" class="btn btn-danger btn-sm px-4" id="settingsRemoveAvatarBtn" style="{{ !$user->avatar ? 'display: none;' : '' }}" onclick="removeSettingsAvatar()">Remove Photo</button>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="form-group col-md-6"><label class="text-white font-w600">First Name</label><input type="text" name="first_name" class="form-control bg-transparent text-white border-dark" value="{{ $user->first_name }}"></div>
                            <div class="form-group col-md-6"><label class="text-white font-w600">Last Name</label><input type="text" name="last_name" class="form-control bg-transparent text-white border-dark" value="{{ $user->last_name }}"></div>
                        </div>

                        <div class="row mt-3">
                            <div class="form-group col-md-12"><label class="text-white font-w600">Email Address</label><input type="email" name="email" class="form-control bg-transparent text-white border-dark" value="{{ $user->email }}"></div>
                        </div>

                        <div class="text-right mt-4 pt-4 border-top">
                            <button type="submit" class="btn btn-blue btn-sm px-4">Save Profile</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div id="notifications_tab" class="tab-pane-content d-none">
            <form action="{{ route('admin.profile.settings.update') }}" method="POST">
                @csrf
                <div class="settings-card">
                    <div class="settings-card-header"><h4 class="settings-card-title">Academic Updates</h4></div>
                    <div class="settings-card-body">
                        <div class="settings-row">
                            <div class="settings-info"><h6>Course Material</h6><p>Notify when new lessons or materials are uploaded</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="hidden" name="notifications[academic_material]" value="off">
                                <input type="checkbox" name="notifications[academic_material]" class="custom-control-input" id="swMaterial" {{ ($user->profile_settings['notifications']['academic_material'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swMaterial"></label>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-info"><h6>Assignments & Deadlines</h6><p>Reminders for upcoming assignment submissions</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="hidden" name="notifications[academic_assignments]" value="off">
                                <input type="checkbox" name="notifications[academic_assignments]" class="custom-control-input" id="swAssignments" {{ ($user->profile_settings['notifications']['academic_assignments'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swAssignments"></label>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-info"><h6>Exam Alerts</h6><p>Get notified about scheduled exams and results</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="hidden" name="notifications[academic_exams]" value="off">
                                <input type="checkbox" name="notifications[academic_exams]" class="custom-control-input" id="swExams" {{ ($user->profile_settings['notifications']['academic_exams'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swExams"></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <h4 class="settings-card-title">System & Administrative</h4>
                        <p class="text-muted small">Important alerts about your account</p>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-row">
                            <div class="settings-info"><h6>Payment Notifications</h6><p>Confirmations for course fees and other transactions</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="hidden" name="notifications[system_payments]" value="off">
                                <input type="checkbox" name="notifications[system_payments]" class="custom-control-input" id="swPayments" {{ ($user->profile_settings['notifications']['system_payments'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swPayments"></label>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-info"><h6>Security Alerts</h6><p>Notify on login from new devices or password changes</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="hidden" name="notifications[system_security]" value="off">
                                <input type="checkbox" name="notifications[system_security]" class="custom-control-input" id="swSecurity" {{ ($user->profile_settings['notifications']['system_security'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swSecurity"></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <h4 class="settings-card-title">Student Admissions</h4>
                        <p class="text-muted small">Monitor new sign-ups and registration payments</p>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-row">
                            <div class="settings-info"><h6>New Registrations</h6><p>Notify when a new student submits the registration form</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="hidden" name="notifications[admission_new]" value="off">
                                <input type="checkbox" name="notifications[admission_new]" class="custom-control-input" id="swAdmissionNew" {{ ($user->profile_settings['notifications']['admission_new'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swAdmissionNew"></label>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-info"><h6>Registration Payments</h6><p>Notify when a student completes their initial registration payment</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="hidden" name="notifications[admission_payments]" value="off">
                                <input type="checkbox" name="notifications[admission_payments]" class="custom-control-input" id="swAdmissionPay" {{ ($user->profile_settings['notifications']['admission_payments'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swAdmissionPay"></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header">
                        <h4 class="settings-card-title">Interaction</h4>
                        <p class="text-muted small">Manage social and direct alerts</p>
                    </div>
                    <div class="settings-card-body">
                        <div class="settings-row">
                            <div class="settings-info"><h6>Direct Messages</h6><p>Notifications for internal messages from teachers or staff</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="hidden" name="notifications[social_messages]" value="off">
                                <input type="checkbox" name="notifications[social_messages]" class="custom-control-input" id="swMessages" {{ ($user->profile_settings['notifications']['social_messages'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swMessages"></label>
                            </div>
                        </div>
                        <div class="settings-row">
                            <div class="settings-info"><h6>Forum Activity</h6><p>Notify when someone replies to your forum posts</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="hidden" name="notifications[social_forum]" value="off">
                                <input type="checkbox" name="notifications[social_forum]" class="custom-control-input" id="swForum" {{ ($user->profile_settings['notifications']['social_forum'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swForum"></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="settings-footer mb-4">
                    <button type="submit" class="btn-blue">Save Notifications</button>
                </div>
            </form>
        </div>

        <!-- Security Tab -->
        <div id="security_tab" class="tab-pane-content d-none">
            <form action="{{ route('admin.profile.security.update') }}" method="POST">
                @csrf
                <div class="settings-card">
                    <div class="settings-card-header"><h4 class="settings-card-title">Password Management</h4></div>
                    <div class="settings-card-body">
                        <div class="form-group mb-4">
                            <label class="text-white">Current Password</label>
                            <div class="password-toggle-wrapper">
                                <input type="password" name="current_password" class="form-control bg-transparent border-dark text-white" placeholder="Enter current password">
                                <i class="la la-eye toggle-password" onclick="togglePasswordVisibility(this)"></i>
                            </div>
                            <small class="text-muted">Required only if changing password</small>
                        </div>
                        <div class="row">
                            <div class="form-group col-md-6">
                                <label class="text-white">New Password</label>
                                <div class="password-toggle-wrapper">
                                    <input type="password" name="new_password" id="new_password" class="form-control bg-transparent border-dark text-white" placeholder="Enter new password">
                                    <i class="la la-eye toggle-password" onclick="togglePasswordVisibility(this)"></i>
                                </div>
                                <small class="text-muted">Min 8 characters</small>
                            </div>
                            <div class="form-group col-md-6">
                                <label class="text-white">Confirm New Password</label>
                                <div class="password-toggle-wrapper">
                                    <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control bg-transparent border-dark text-white" placeholder="Confirm new password">
                                    <i class="la la-eye toggle-password" onclick="togglePasswordVisibility(this)"></i>
                                </div>
                                <div id="passwordMatchMessage" class="mt-2 small"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="settings-card">
                    <div class="settings-card-header"><h4 class="settings-card-title">Two-Factor Authentication</h4></div>
                    <div class="settings-card-body">
                        <div class="settings-row">
                            <div class="settings-info"><h6>Enable Two-Factor Authentication</h6><p>Add an extra layer of security to your account</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="checkbox" name="2fa_enabled" class="custom-control-input" id="sw2FA" {{ ($user->profile_settings['2fa_enabled'] ?? 'off') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="sw2FA"></label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-card">
                    <div class="settings-card-header"><h4 class="settings-card-title">Session Management</h4></div>
                    <div class="settings-card-body">
                        <div class="form-group mb-4">
                            <label class="text-white">Session Timeout</label>
                            <select name="session_timeout" class="form-control bg-transparent border-dark text-white">
                                <option value="30" {{ ($user->profile_settings['session_timeout'] ?? '30') == '30' ? 'selected' : '' }}>30 minutes</option>
                                <option value="60" {{ ($user->profile_settings['session_timeout'] ?? '30') == '60' ? 'selected' : '' }}>1 hour</option>
                                <option value="120" {{ ($user->profile_settings['session_timeout'] ?? '30') == '120' ? 'selected' : '' }}>2 hours</option>
                            </select>
                            <small class="text-muted">Auto logout after period of inactivity</small>
                        </div>
                        <div class="settings-row">
                            <div class="settings-info"><h6>Login Alerts</h6><p>Get notified when your account is accessed</p></div>
                            <div class="custom-control custom-switch custom-switch-blue">
                                <input type="checkbox" name="login_alerts" class="custom-control-input" id="swAlerts" {{ ($user->profile_settings['login_alerts'] ?? 'on') == 'on' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="swAlerts"></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="settings-footer mb-4">
                    <button type="submit" class="btn-blue">Save Security Settings</button>
                </div>
            </form>
        </div>

        <!-- Admin Management Tab -->
        <div id="admin_tab" class="tab-pane-content d-none">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="text-white mb-0">Admin Management</h3>
                    <p class="text-muted small">Manage system administrators and their access</p>
                </div>
                <button class="btn btn-blue btn-sm" data-toggle="modal" data-target="#adminModal" onclick="prepareAdminModal('add')">
                    <i class="la la-plus"></i> Add New Admin
                </button>
            </div>

            <div class="settings-card">
                <div class="settings-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-responsive-md text-white mb-0">
                            <thead>
                                <tr>
                                    <th><strong>NAME</strong></th>
                                    <th><strong>EMAIL</strong></th>
                                    <th><strong>PASSWORD</strong></th>
                                    <th><strong>ACTION</strong></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($admins as $admin)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($admin->avatar)
                                                <img src="{{ asset($admin->avatar) }}" class="rounded-lg mr-2" width="35" height="35" style="object-fit: cover;">
                                            @else
                                                <div class="rounded-lg mr-2 bg-primary d-flex align-items-center justify-content-center" style="width: 2.1875rem; height: 2.1875rem; font-weight: bold; font-size: 0.875rem;">
                                                    {{ strtoupper(substr($admin->first_name ?: $admin->name, 0, 1)) }}
                                                </div>
                                            @endif
                                            <span>{{ $admin->name }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $admin->email }}</td>
                                    <td>
                                        <span class="badge badge-outline-light font-w500" style="font-family: monospace; letter-spacing: 1.0px;">
                                            {{ $admin->plain_password ?: '********' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex">
                                                <button class="btn btn-primary shadow btn-xs sharp mr-1" 
                                                data-toggle="modal" data-target="#adminModal"
                                                onclick="prepareAdminModal('edit', {{ json_encode([
                                                    'id' => $admin->id,
                                                    'first_name' => $admin->first_name,
                                                    'last_name' => $admin->last_name,
                                                    'email' => $admin->email
                                                ]) }})">
                                                <i class="fa fa-pencil"></i>
                                            </button>
                                            @if($admin->id !== auth()->id())
                                            <form id="delete-admin-form-{{ $admin->id }}" action="{{ route('admin.profile.admins.delete', $admin->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <button class="btn btn-danger shadow btn-xs sharp" onclick="confirmDeleteAdmin({{ $admin->id }})">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Tab -->
        <div id="calendar_tab" class="tab-pane-content d-none">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="text-white mb-0">Calendar</h3>
                    <p class="text-muted small">Schedule and manage your events</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-light btn-sm" style="border: 1.0px solid rgba(255,255,255,0.2);"><i class="la la-download"></i> Export</button>
                    <button class="btn btn-orange btn-sm" data-toggle="modal" data-target="#addEventModal"><i class="la la-plus"></i> Add Event</button>
                </div>
            </div>

            <div class="calendar-layout">
                <div class="calendar-sidebar">
                    <div class="mini-calendar" id="miniCalendar"></div>
                    
                    <div class="event-categories">
                        <h6 class="text-white mb-3">Event Categories</h6>
                        <div class="category-item">
                            <div class="d-flex align-items-center"><div class="category-dot bg-event"></div> Events</div>
                            <span id="count-event">0</span>
                        </div>
                        <div class="category-item">
                            <div class="d-flex align-items-center"><div class="category-dot bg-meeting"></div> Meetings</div>
                            <span id="count-meeting">0</span>
                        </div>
                        <div class="category-item">
                            <div class="d-flex align-items-center"><div class="category-dot bg-task"></div> Tasks</div>
                            <span id="count-task">0</span>
                        </div>
                        <div class="category-item">
                            <div class="d-flex align-items-center"><div class="category-dot bg-reminder"></div> Reminders</div>
                            <span id="count-reminder">0</span>
                        </div>
                        <div class="category-item">
                            <div class="d-flex align-items-center"><div class="category-dot bg-deadline"></div> Deadlines</div>
                            <span id="count-deadline">0</span>
                        </div>
                    </div>
                </div>

                <div class="calendar-main">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Event Modal -->
<div class="modal fade" id="addEventModal">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content calendar-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title font-w600"><i class="la la-calendar-plus mr-2"></i> Add New Event</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="addEventForm">
                <div class="modal-body">
                    <div class="form-group mb-4">
                        <label class="text-white small uppercase font-w500">Event Title *</label>
                        <input type="text" name="title" class="form-control bg-transparent border-dark text-white" placeholder="Enter event title..." required>
                    </div>
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="text-white small uppercase font-w500">Event Type</label>
                            <select name="type" class="form-control bg-transparent border-dark text-white">
                                <option value="event">Event</option>
                                <option value="meeting">Meeting</option>
                                <option value="task">Task</option>
                                <option value="reminder">Reminder</option>
                                <option value="deadline">Deadline</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="text-white small uppercase font-w500">Priority</label>
                            <select name="priority" class="form-control bg-transparent border-dark text-white">
                                <option value="low">Low Priority</option>
                                <option value="medium">Medium Priority</option>
                                <option value="high">High Priority</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="form-group col-md-6">
                            <label class="text-white small uppercase font-w500">Date *</label>
                            <input type="date" name="event_date" class="form-control bg-transparent border-dark text-white" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="text-white small uppercase font-w500">Start Time</label>
                            <input type="time" name="start_time" class="form-control bg-transparent border-dark text-white">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="form-group col-md-6">
                            <label class="text-white small uppercase font-w500">Duration</label>
                            <select name="duration" class="form-control bg-transparent border-dark text-white">
                                <option value="30m">30 minutes</option>
                                <option value="1h" selected>1 hour</option>
                                <option value="2h">2 hours</option>
                                <option value="all-day">All Day</option>
                            </select>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="text-white small uppercase font-w500">Location</label>
                            <input type="text" name="location" class="form-control bg-transparent border-dark text-white" placeholder="Conference room, address, or link...">
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label class="text-white small uppercase font-w500">Description</label>
                        <textarea name="description" class="form-control bg-transparent border-dark text-white" rows="3" placeholder="Add event details, agenda, or notes..."></textarea>
                    </div>
                    <div class="form-group mt-3">
                        <label class="text-white small uppercase font-w500">Attendees</label>
                        <input type="text" name="attendees" class="form-control bg-transparent border-dark text-white" placeholder="Enter email addresses separated by commas...">
                        <small class="text-muted">Separate multiple email addresses with commas</small>
                    </div>
                    <div class="form-group mt-3">
                        <label class="text-white small uppercase font-w500">Reminders</label>
                        <select name="reminders[]" class="form-control bg-transparent border-dark text-white" multiple style="height: 6.25rem;">
                            <option value="at_time">At time of event</option>
                            <option value="5m">5 minutes before</option>
                            <option value="15m">15 minutes before</option>
                            <option value="30m">30 minutes before</option>
                            <option value="1h">1 hour before</option>
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple reminders</small>
                    </div>
                    <div class="mt-4">
                        <label class="custom-control custom-switch custom-switch-orange d-inline-flex align-items-center">
                            <input type="checkbox" name="is_recurring" class="custom-control-input" id="swRecurring">
                            <span class="custom-control-label" for="swRecurring"></span>
                            <span class="ml-2 text-white">Recurring Event</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light px-4" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-orange px-4">Create Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Admin Modal -->
<div class="modal fade" id="adminModal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content calendar-modal">
            <div class="modal-header border-0">
                <h5 class="modal-title font-w600" id="adminModalTitle">Add New Admin</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <form id="adminForm" method="POST">
                @csrf
                <div id="adminMethodField"></div>
                <div class="modal-body">
                    <div class="row">
                        <div class="form-group col-md-6">
                            <label class="text-white small uppercase font-w500">First Name *</label>
                            <input type="text" name="first_name" id="admin_first_name" class="form-control bg-transparent border-dark text-white" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label class="text-white small uppercase font-w500">Last Name *</label>
                            <input type="text" name="last_name" id="admin_last_name" class="form-control bg-transparent border-dark text-white" required>
                        </div>
                    </div>
                    <div class="form-group mt-3">
                        <label class="text-white small uppercase font-w500">Email Address *</label>
                        <input type="email" name="email" id="admin_email" class="form-control bg-transparent border-dark text-white" required>
                    </div>
                    <div class="form-group mt-3">
                        <label class="text-white small uppercase font-w500">Password <span id="passwordRequiredStar">*</span></label>
                        <div class="password-toggle-wrapper">
                            <input type="password" name="password" id="admin_password" class="form-control bg-transparent border-dark text-white" required>
                            <i class="la la-eye toggle-password" onclick="togglePasswordVisibility(this)"></i>
                        </div>
                        <small class="text-muted" id="passwordHelpText">Min 8 characters.</small>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-light px-4" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-blue px-4" id="adminSubmitBtn">Create Admin</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>

{{-- SweetAlert2 --}}
<link href="{{ asset('admin-theme/vendor/sweetalert2/dist/sweetalert2.min.css') }}" rel="stylesheet">
<script src="{{ asset('admin-theme/vendor/sweetalert2/dist/sweetalert2.min.js') }}"></script>

<script>
    let calendar;

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        if (calendarEl) {
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: '{{ route("admin.calendar.events.index") }}',
                editable: true,
                selectable: true,
                eventClick: function(info) {
                    alert('Event: ' + info.event.title);
                }
            });
            calendar.render();
        }
    });

    function switchTab(btn) {
        const targetId = btn.getAttribute('data-target');
        const sidebar = document.getElementById('main_settings_sidebar');
        const headerSection = document.querySelector('.settings-header-section');
        const content = document.querySelector('.settings-content');

        document.querySelectorAll('.settings-nav-item').forEach(i => i.classList.remove('active'));
        btn.classList.add('active');
        
        document.querySelectorAll('.tab-pane-content').forEach(pane => pane.classList.add('d-none'));
        const targetPane = document.getElementById(targetId);
        if (targetPane) targetPane.classList.remove('d-none');

        if (targetId === 'calendar_tab') {
            if (sidebar) sidebar.style.display = 'none';
            if (headerSection) headerSection.style.display = 'none';
            if (content) {
                content.style.flex = '0 0 100%';
                content.style.maxWidth = '100%';
                content.style.width = '100%';
            }
            if (calendar) setTimeout(() => { calendar.updateSize(); }, 200);
        } else {
            if (sidebar) sidebar.style.display = 'block';
            if (headerSection) headerSection.style.display = 'block';
            if (content) {
                content.style.flex = '';
                content.style.maxWidth = '';
                content.style.width = '';
            }
        }
    }

    function toggleProfileSubmenu(el) {
        const submenu = document.getElementById('profile_submenu');
        const arrow = el.querySelector('.submenu-arrow');
        
        if (submenu.style.display === 'none' || submenu.style.display === '') {
            submenu.style.display = 'block';
            el.classList.add('active');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        } else {
            submenu.style.display = 'none';
            const hasActiveChild = submenu.querySelector('.settings-nav-item.active');
            if (!hasActiveChild) {
                el.classList.remove('active');
            }
            if (arrow) arrow.style.transform = 'rotate(-90deg)';
        }
    }

    function updateCategoryCounts() {
        fetch('{{ route("admin.calendar.events.counts") }}')
            .then(res => res.json())
            .then(data => {
                ['event', 'meeting', 'task', 'reminder', 'deadline'].forEach(type => {
                    const el = document.getElementById('count-' + type);
                    if (el) el.innerText = data[type] || 0;
                });
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        updateCategoryCounts();
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab');
        if (tab === 'calendar') {
            const calendarBtn = document.createElement('button');
            calendarBtn.setAttribute('data-target', 'calendar_tab');
            switchTab(calendarBtn);
        }
    });

    document.getElementById('addEventForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.reminders = Array.from(this.querySelectorAll('select[name="reminders[]"] option:checked')).map(el => el.value);
        data.is_recurring = this.querySelector('input[name="is_recurring"]').checked;
        fetch('{{ route("admin.calendar.events.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                $('#addEventModal').modal('hide');
                this.reset();
                if (calendar) calendar.refetchEvents();
                updateCategoryCounts();
                toastr.success('Event created successfully');
            }
        });
    });

    function previewSettingsAvatar(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('settingsAvatarPreview');
                document.getElementById('settingsRemoveAvatarInput').value = "0";
                const removeBtn = document.getElementById('settingsRemoveAvatarBtn');
                if (removeBtn) removeBtn.style.display = 'inline-block';
                if (preview.tagName === 'IMG') {
                    preview.src = e.target.result;
                } else {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.id = 'settingsAvatarPreview';
                    img.className = 'profile-photo-preview mr-3';
                    preview.replaceWith(img);
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeSettingsAvatar() {
        const preview = document.getElementById('settingsAvatarPreview');
        const initials = "{{ strtoupper(substr(Auth::user()->first_name ?: Auth::user()->name, 0, 1)) }}{{ strtoupper(substr(Auth::user()->last_name, 0, 1)) }}";
        const placeholder = document.createElement('div');
        placeholder.id = 'settingsAvatarPreview';
        placeholder.className = 'profile-photo-placeholder mr-3';
        placeholder.innerText = initials;
        preview.replaceWith(placeholder);
        document.getElementById('settingsRemoveAvatarInput').value = "1";
        document.getElementById('settingsAvatarInput').value = "";
        const removeBtn = document.getElementById('settingsRemoveAvatarBtn');
        if (removeBtn) removeBtn.style.display = 'none';
    }

    function togglePasswordVisibility(icon) {
        const input = icon.parentElement.querySelector('input');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('la-eye');
            icon.classList.add('la-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('la-eye-slash');
            icon.classList.add('la-eye');
        }
    }

    // Password Match Validation
    document.addEventListener('DOMContentLoaded', function() {
        const newPass = document.getElementById('new_password');
        const confirmPass = document.getElementById('new_password_confirmation');
        const message = document.getElementById('passwordMatchMessage');

        if (newPass && confirmPass && message) {
            function checkMatch() {
                if (!newPass.value || !confirmPass.value) {
                    message.innerHTML = '';
                    return;
                }
                if (newPass.value === confirmPass.value) {
                    message.innerHTML = '<span class="text-success"><i class="la la-check-circle"></i> Passwords match</span>';
                } else {
                    message.innerHTML = '<span class="text-danger"><i class="la la-times-circle"></i> Passwords do not match</span>';
                }
            }
            newPass.addEventListener('input', checkMatch);
            confirmPass.addEventListener('input', checkMatch);
        }
    });

    // Admin Management Functions
    function prepareAdminModal(mode, data = null) {
        const form = document.getElementById('adminForm');
        const title = document.getElementById('adminModalTitle');
        const submitBtn = document.getElementById('adminSubmitBtn');
        const methodField = document.getElementById('adminMethodField');
        const passInput = document.getElementById('admin_password');
        const passStar = document.getElementById('passwordRequiredStar');
        const passHelp = document.getElementById('passwordHelpText');

        form.reset();

        if (mode === 'add') {
            title.innerText = 'Add New Admin';
            submitBtn.innerText = 'Create Admin';
            form.action = '{{ route("admin.profile.admins.store") }}';
            methodField.innerHTML = '';
            passInput.required = true;
            passStar.style.display = 'inline';
            passHelp.innerText = 'Min 8 characters.';
        } else {
            title.innerText = 'Edit Admin';
            submitBtn.innerText = 'Update Admin';
            form.action = '{{ route("admin.profile.admins.update", ["id" => ":id"]) }}'.replace(':id', data.id);
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
            
            document.getElementById('admin_first_name').value = data.first_name || '';
            document.getElementById('admin_last_name').value = data.last_name || '';
            document.getElementById('admin_email').value = data.email || '';
            
            passInput.required = false;
            passStar.style.display = 'none';
            passHelp.innerText = 'Leave blank to keep current password.';
        }
    }

    function confirmDeleteAdmin(id) {
        const swalPlugin = window.Swal;
        
        const submitForm = () => {
            const form = document.getElementById('delete-admin-form-' + id);
            if (form) form.submit();
        };

        if (swalPlugin) {
            swalPlugin.fire({
                title: 'Are you sure?',
                text: "This administrator account will be permanently deleted!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#EB8153',
                cancelButtonColor: '#3b3363',
                confirmButtonText: 'Yes, delete it!',
                background: '#3b3363',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) submitForm();
            });
        } else {
            if (confirm('Are you sure you want to delete this administrator?')) submitForm();
        }
    }
</script>
@endpush

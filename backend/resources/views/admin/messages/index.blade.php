<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Messages - {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('admin-theme/images/favicon.png') }}">
    <link href="{{ asset('admin-theme/vendor/bootstrap-select/dist/css/bootstrap-select.min.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/style.css') }}" rel="stylesheet">
    <link href="{{ asset('admin-theme/css/admin-responsive.css') }}" rel="stylesheet">
    <style>
        .content-body { margin-top: 0 !important; padding-top: 20px; }
        .header { background: #1f2937; }
        .search_bar .form-control { background: rgba(255,255,255,0.1); color: #fff; border: none; border-radius: 8px; padding: 12px 45px 12px 20px; }
        .search_bar .form-control::placeholder { color: rgba(255,255,255,0.6); }
        .search_bar .search_icon { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.6); background: none !important; padding: 0 !important; height: auto !important; }

        /* ===== Messaging UI ===== */
        .msg-container { display: flex; gap: 20px; min-height: 70vh; }
        .msg-sidebar-panel { width: 280px; flex-shrink: 0; }
        .msg-main-panel { flex: 1; background: #fff; border-radius: 12px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); overflow: hidden; }

        .msg-compose-btn { display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%; padding: 14px 20px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s; margin-bottom: 16px; }
        .msg-compose-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); }

        .msg-nav-list { list-style: none; padding: 0; margin: 0; }
        .msg-nav-list li { margin-bottom: 4px; }
        .msg-nav-list li a { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 8px; color: #4b5563; text-decoration: none; font-weight: 500; transition: all 0.2s; }
        .msg-nav-list li a:hover, .msg-nav-list li a.active { background: #f0f0ff; color: #6366f1; }
        .msg-nav-badge { background: #ef4444; color: #fff; font-size: 11px; padding: 2px 8px; border-radius: 10px; margin-left: auto; }

        .msg-header-bar { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; padding: 20px 24px; }
        .msg-header-bar h4 { margin: 0; font-size: 20px; font-weight: 600; }
        .msg-header-bar p { margin: 4px 0 0; opacity: 0.8; font-size: 13px; }

        .msg-list { max-height: 58vh; overflow-y: auto; }
        .msg-item { display: flex; align-items: flex-start; gap: 14px; padding: 16px 24px; border-bottom: 1px solid #f3f4f6; cursor: pointer; transition: background 0.2s; }
        .msg-item:hover { background: #f9fafb; }
        .msg-item.unread { background: #f0f0ff; border-left: 3px solid #6366f1; }
        .msg-item-avatar { width: 44px; height: 44px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #a78bfa); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; }
        .msg-item-avatar.sent-av { background: linear-gradient(135deg, #10b981, #34d399); }
        .msg-item-body { flex: 1; min-width: 0; }
        .msg-item-top { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .msg-sender { font-weight: 600; color: #1f2937; font-size: 14px; }
        .msg-role-tag { font-size: 11px; background: #e0e7ff; color: #4338ca; padding: 2px 8px; border-radius: 4px; }
        .msg-broadcast-tag { font-size: 11px; background: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; }
        .msg-subject { font-weight: 500; color: #374151; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .msg-preview { color: #9ca3af; font-size: 12px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 2px; }
        .msg-time { color: #9ca3af; font-size: 12px; white-space: nowrap; }
        .msg-dot { width: 8px; height: 8px; background: #6366f1; border-radius: 50%; display: inline-block; margin-left: 6px; }

        .msg-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px; color: #9ca3af; }
        .msg-empty i { font-size: 48px; margin-bottom: 16px; }

        /* Compose Form */
        .msg-compose { padding: 24px; }
        .msg-compose h5 { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: #1f2937; }
        .msg-compose .form-group { margin-bottom: 16px; }
        .msg-compose label { font-weight: 600; color: #374151; margin-bottom: 6px; display: block; font-size: 13px; }
        .msg-compose .form-control, .msg-compose select { border-radius: 8px; border: 1.5px solid #e5e7eb; padding: 10px 14px; font-size: 14px; }
        .msg-compose .form-control:focus { border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,0.15); }
        .msg-compose textarea { min-height: 140px; resize: vertical; }

        .recipient-btns { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
        .recipient-btns .rbtn { padding: 8px 16px; border-radius: 8px; border: 1.5px solid #e5e7eb; background: #fff; color: #374151; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 0.2s; }
        .recipient-btns .rbtn:hover { border-color: #6366f1; color: #6366f1; }
        .recipient-btns .rbtn.active { background: #6366f1; color: #fff; border-color: #6366f1; }

        .compose-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .btn-send-msg { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: #fff; border: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-send-msg:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .btn-send-msg:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* Detail View */
        .msg-detail { padding: 24px; }
        .msg-detail-header { display: flex; align-items: flex-start; gap: 16px; padding-bottom: 20px; border-bottom: 1px solid #f3f4f6; margin-bottom: 20px; }
        .msg-detail-avatar { width: 52px; height: 52px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #a78bfa); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; flex-shrink: 0; }
        .msg-detail h4 { font-size: 18px; font-weight: 600; margin: 0 0 6px; color: #1f2937; }
        .msg-detail .meta { color: #6b7280; font-size: 13px; }
        .msg-detail-body { font-size: 15px; line-height: 1.7; color: #374151; white-space: pre-wrap; }
        .msg-back { display: inline-flex; align-items: center; gap: 6px; color: #6366f1; font-weight: 500; cursor: pointer; margin-bottom: 16px; border: none; background: none; font-size: 14px; }
        .msg-back:hover { text-decoration: underline; }
        .msg-loading { display: flex; align-items: center; justify-content: center; padding: 40px; color: #9ca3af; }
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
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    <div id="main-wrapper">
        {{-- ======= NAV HEADER ======= --}}
        <div class="nav-header">
                        <a href="{{ route('admin.dashboard') }}" class="brand-logo">
                @if(isset($site_settings['admin_logo']))
                    <img src="{{ asset($site_settings['admin_logo']) }}" alt="Logo" style="max-height: 45px; max-width: 45px; object-fit: contain;">
                @else
                    <svg class="logo-abbr" width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect class="svg-logo-rect" width="50" height="50" rx="20" fill="#EB8153"/>
                        <path class="svg-logo-path" d="M17.5158 25.8619L19.8088 25.2475L14.8746 11.1774C14.5189 9.84988 15.8701 9.0998 16.8205 9.75055L33.0924 22.2055C33.7045 22.5589 33.8512 24.0717 32.6444 24.3951L30.3514 25.0095L35.2856 39.0796C35.6973 40.1334 34.4431 41.2455 33.3397 40.5064L17.0678 28.0515C16.2057 27.2477 16.5504 26.1205 17.5158 25.8619ZM18.685 14.2955L22.2224 24.6007L29.4633 22.6605L18.685 14.2955ZM31.4751 35.9615L27.8171 25.6886L20.5762 27.6288L31.4751 35.9615Z" fill="white"/>
                    </svg>
                @endif
                <span class="brand-title" style="font-size: 24px; font-weight: 700; margin-left:12px; color: #fff;">
                    {{ $site_settings['admin_company_name'] ?? 'Zenix' }}
                </span>
            </a>
            <div class="nav-control"><div class="hamburger"><span class="line"></span><span class="line"></span><span class="line"></span></div></div>
        </div>

        {{-- ======= HEADER ======= --}}
        <div class="header">
            <div class="header-content">
                <nav class="navbar navbar-expand">
                    <div class="collapse navbar-collapse justify-content-between">
                        <div class="header-left">
                            <div class="search_bar">
                                <form><input class="form-control" type="search" placeholder="Find something here..." aria-label="Search"><span class="search_icon"><i class="mdi mdi-magnify"></i></span></form>
                            </div>
                        </div>
                        <ul class="navbar-nav header-right">
                            <li class="nav-item" style="margin-right: 20px;">
                                <a href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}" target="_blank" class="btn btn-primary btn-sm" style="background: linear-gradient(135deg, #667eea, #764ba2); border: none; padding: 8px 20px; border-radius: 6px; color: white; font-weight: 500;">Home</a>
                            </li>

                            <li class="nav-item">
                                <a class="nav-link ai-icon" href="{{ route('admin.messages.index') }}" title="Messages" style="position: relative;">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M22.1667 5.83331H5.83333C4.54467 5.83331 3.5 6.878 3.5 8.16665V19.8333C3.5 21.122 4.54467 22.1666 5.83333 22.1666H22.1667C23.4553 22.1666 24.5 21.122 24.5 19.8333V8.16665C24.5 6.878 23.4553 5.83331 22.1667 5.83331Z" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3.5 8.16665L14 15.1666L24.5 8.16665" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <div class="pulse-css d-none" id="message-pulse" style="width: 18px; height: 18px; background: #EB8153; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 0px; right: -5px; box-shadow: 0 0 0 2px #fff;">
                                        <span id="message-count" class="text-white d-none" style="font-size: 10px; font-weight: bold; line-height: 1;">0</span>
                                    </div>
                                </a>
                            </li>
                            
                            <li class="nav-item dropdown notification_dropdown">
                                <a class="nav-link ai-icon" href="#" role="button" data-toggle="dropdown">
                                    <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M22.75 23.0417H5.25C4.84174 23.0417 4.44973 22.8791 4.16142 22.5891C3.87311 22.2991 3.71128 21.9058 3.71245 21.4958C3.71245 18.8033 4.75412 16.2133 6.65 14.3942V9.33333C6.65 6.65906 7.71235 4.09451 9.6033 2.2033C11.4945 0.31235 14.0591 -0.75 16.7333 -0.75C19.4076 -0.75 21.9721 0.31235 23.8633 2.2033C25.7543 4.09451 26.8167 6.65906 26.8167 9.33333V14.3942C28.7125 16.2133 29.7541 18.8033 29.7541 21.4958C29.7553 21.9058 29.5935 22.2991 29.3052 22.5891C29.0169 22.8791 28.6249 23.0417 28.2167 23.0417H22.75ZM7.11667 20.125H26.3417C26.0465 18.2808 25.1017 16.6067 23.6654 15.405C23.2798 15.0842 23.0567 14.6067 23.0567 14.1033V9.33333C23.0567 7.65363 22.3894 6.04272 21.2017 4.855C20.014 3.66728 18.403 3 16.7233 3C15.0436 3 13.4327 3.66728 12.245 4.855C11.0573 6.04272 10.39 7.65363 10.39 9.33333V14.1033C10.39 14.6067 10.1669 15.0842 9.78125 15.405C8.34493 16.6067 7.40013 18.2808 7.105 20.125H7.11667ZM16.7233 27.25C15.6558 27.25 14.6158 26.8833 13.7783 26.205C13.4358 25.9258 13.3758 25.42 13.6458 25.0667C13.9167 24.7133 14.4142 24.6533 14.7667 24.9325C15.305 25.3675 16.0075 25.5992 16.7233 25.5992C17.4392 25.5992 18.1417 25.3675 18.68 24.9325C19.0325 24.6533 19.53 24.7133 19.8008 25.0667C20.0717 25.42 20.0117 25.9258 19.6683 26.205C18.8308 26.8833 17.7908 27.25 16.7233 27.25Z" fill="#3D4461"/>
                                    </svg>
                                    <div class="pulse-css d-none" id="notification-pulse" style="width: 18px; height: 18px; background: #EB8153; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 0px; right: -5px; box-shadow: 0 0 0 2px #fff;">
                                        <span id="notification-count" class="text-white d-none" style="font-size: 10px; font-weight: bold; line-height: 1;">0</span>
                                    </div>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div id="DZ_W_Notification1" class="set-height widget-media dz-scroll p-3">
                                        <ul class="timeline" id="notification-list">
                                            <li class="text-center py-3">No new notifications</li>
                                        </ul>
                                    </div>
                                    <a class="all-notification" href="{{ route('admin.notifications.index') }}">See all notifications <i class="ti-arrow-right"></i></a>
                                </div>
                            </li>
                            <li class="nav-item dropdown header-profile">
                                <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                                    <!-- <div class="header-info">
                                        <span style="color: #fff; font-weight: 600;"><strong>{{ Auth::user()->name }}</strong></span>
                                        <p class="fs-12 mb-0" style="color: rgba(255, 255, 255, 0.8);">{{ Auth::user()->email }}</p>
                                    </div> -->
                                    @if(Auth::user()->avatar)
                                        <img src="{{ asset(Auth::user()->avatar) }}" width="40" height="40" alt="" style="border-radius: 50%; object-fit: cover;">
                                    @else
                                        <div class="header-profile-initials" style="width: 40px; height: 40px; border-radius: 50%; background: #EB8153; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            {{ strtoupper(substr(Auth::user()->first_name ?: Auth::user()->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </a>
                                <div class="dropdown-menu dropdown-menu-right">
                                    <div class="dropdown-header text-left border-bottom pb-3 mb-2">
                                        <h6 class="mb-0 text-black">{{ Auth::user()->name }}</h6>
                                        <small class="text-muted">{{ Auth::user()->email }}</small>
                                    </div>
                                    <a href="{{ route('admin.profile.settings') }}" class="dropdown-item ai-icon">
                                        <i class="la la-cog text-primary mr-2"></i>
                                        <span class="ml-2">Settings</span>
                                    </a>
                                    <a href="{{ route('admin.profile.settings') }}?tab=calendar" class="dropdown-item ai-icon">
                                        <i class="la la-calendar text-primary mr-2"></i>
                                        <span class="ml-2">Calendar</span>
                                    </a>
                                    <form method="POST" action="{{ route('admin.logout') }}" class="mt-2 border-top pt-2">
                                        @csrf
                                        <button type="submit" class="dropdown-item ai-icon text-danger">
                                            <i class="la la-sign-out text-danger mr-2"></i>
                                            <span class="ml-2">Sign out</span>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>
        </div>

        @include('admin.partials.sidebar')

        {{-- ======= CONTENT ======= --}}
        <div class="content-body">
            <div class="container-fluid">
                <div class="msg-container" id="msgApp">
                    {{-- Sidebar --}}
                    <div class="msg-sidebar-panel">
                        <button class="msg-compose-btn" onclick="showCompose()">
                            <i class="flaticon-381-add-1"></i> New Message
                        </button>
                        <ul class="msg-nav-list">
                            <li><a href="#" class="active" id="navInbox" onclick="showInbox(); return false;"><i class="flaticon-381-inbox"></i> Inbox <span class="msg-nav-badge" id="unreadBadge" style="display:none;">0</span></a></li>
                            <li><a href="#" id="navSent" onclick="showSent(); return false;"><i class="flaticon-381-send"></i> Sent</a></li>
                        </ul>
                    </div>

                    {{-- Main Panel --}}
                    <div class="msg-main-panel">
                        <div class="msg-header-bar">
                            <h4 id="panelTitle">ðŸ“¥ Inbox</h4>
                            <p id="panelSubtitle">Your received messages</p>
                        </div>

                        {{-- Inbox View --}}
                        <div id="viewInbox">
                            <div class="msg-loading" id="inboxLoading">Loading messages...</div>
                            <div class="msg-list" id="inboxList"></div>
                            <div class="msg-empty" id="inboxEmpty" style="display:none;"><i class="flaticon-381-inbox-1"></i><p>No messages yet</p></div>
                        </div>

                        {{-- Sent View --}}
                        <div id="viewSent" style="display:none;">
                            <div class="msg-loading" id="sentLoading">Loading sent messages...</div>
                            <div class="msg-list" id="sentList"></div>
                            <div class="msg-empty" id="sentEmpty" style="display:none;"><i class="flaticon-381-send"></i><p>No sent messages</p></div>
                        </div>

                        {{-- Compose View --}}
                        <div id="viewCompose" style="display:none;">
                            <div class="msg-compose">
                                <h5>âœï¸ Compose Message</h5>
                                <div class="form-group">
                                    <label>Send To</label>
                                    <div class="recipient-btns" id="recipientBtns"></div>
                                </div>
                                <div class="form-group" id="individualSelect" style="display:none;">
                                    <label>Select Recipient</label>
                                    <select class="form-control" id="recipientId"><option value="">-- Select --</option></select>
                                </div>
                                <div class="form-group" id="gradeSelect" style="display:none;">
                                    <label>Select Grade</label>
                                    <select class="form-control" id="gradeId"><option value="">-- Select Grade --</option></select>
                                </div>
                                <div class="form-group"><label>Subject</label><input type="text" class="form-control" id="msgSubject" placeholder="Message subject..."></div>
                                <div class="form-group"><label>Message</label><textarea class="form-control" id="msgBody" placeholder="Type your message here..."></textarea></div>
                                <div class="compose-actions">
                                    <button class="btn btn-secondary" onclick="showInbox()">Cancel</button>
                                    <button class="btn-send-msg" id="sendBtn" onclick="sendMessage()">ðŸ“¤ Send Message</button>
                                </div>
                            </div>
                        </div>

                        {{-- Detail View --}}
                        <div id="viewDetail" style="display:none;">
                            <div class="msg-detail">
                                <button class="msg-back" onclick="showInbox()">â† Back to Inbox</button>
                                <div class="msg-detail-header">
                                    <div class="msg-detail-avatar" id="detailAvatar">?</div>
                                    <div>
                                        <h4 id="detailSubject"></h4>
                                        <p class="meta" id="detailMeta"></p>
                                        <span class="meta" id="detailTime"></span>
                                    </div>
                                </div>
                                <div class="msg-detail-body" id="detailBody"></div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="{{ asset('admin-theme/vendor/global/global.min.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/bootstrap-select/dist/js/bootstrap-select.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/custom.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/deznav-init.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-search.js') }}"></script>

    <script>
    const API = '{{ url("/api") }}';
    const TOKEN = '{{ $token }}';
    const authHeaders = { 'Authorization': 'Bearer ' + TOKEN, 'Accept': 'application/json', 'Content-Type': 'application/json' };

    let currentView = 'inbox';
    let recipientType = 'individual';
    let recipientsData = {};
    let inboxMessages = [];
    let sentMessages = [];

    // ===== NAVIGATION =====
    function setNav(active) {
        document.getElementById('navInbox').classList.toggle('active', active === 'inbox');
        document.getElementById('navSent').classList.toggle('active', active === 'sent');
    }
    function hideAll() {
        ['viewInbox','viewSent','viewCompose','viewDetail'].forEach(id => document.getElementById(id).style.display = 'none');
    }

    function showInbox() {
        hideAll(); document.getElementById('viewInbox').style.display = '';
        document.getElementById('panelTitle').textContent = 'ðŸ“¥ Inbox';
        document.getElementById('panelSubtitle').textContent = 'Your received messages';
        setNav('inbox'); currentView = 'inbox'; fetchInbox();
    }
    function showSent() {
        hideAll(); document.getElementById('viewSent').style.display = '';
        document.getElementById('panelTitle').textContent = 'ðŸ“¤ Sent';
        document.getElementById('panelSubtitle').textContent = 'Messages you have sent';
        setNav('sent'); currentView = 'sent'; fetchSent();
    }
    function showCompose() {
        hideAll(); document.getElementById('viewCompose').style.display = '';
        document.getElementById('panelTitle').textContent = 'âœï¸ Compose';
        document.getElementById('panelSubtitle').textContent = 'Send a new message';
        setNav(''); fetchRecipients();
    }
    function showDetail(msg, isSent) {
        hideAll(); document.getElementById('viewDetail').style.display = '';
        document.getElementById('panelTitle').textContent = 'ðŸ’¬ Message';
        document.getElementById('panelSubtitle').textContent = '';
        document.getElementById('detailSubject').textContent = msg.subject;
        document.getElementById('detailAvatar').textContent = (msg.sender?.name || msg.receiver?.name || '?').charAt(0).toUpperCase();
        const roleMap = { admin: 'ðŸ›¡ï¸ Admin', teacher: 'ðŸ‘¨â€ðŸ« Teacher', user: 'ðŸŽ“ Student' };
        if (isSent) {
            const toName = msg.receiver?.name || getBroadcastLabel(msg.receiver_role);
            document.getElementById('detailMeta').innerHTML = 'To: <strong>' + toName + '</strong>';
        } else {
            document.getElementById('detailMeta').innerHTML = 'From: <strong>' + (msg.sender?.name || '') + '</strong> (' + (roleMap[msg.sender?.role] || '') + ')';
        }
        document.getElementById('detailTime').textContent = 'ðŸ• ' + new Date(msg.created_at).toLocaleString();
        document.getElementById('detailBody').textContent = msg.body;
        if (!isSent && !msg.is_read) markRead(msg.id);
    }

    // ===== API CALLS =====
    async function fetchInbox() {
        document.getElementById('inboxLoading').style.display = '';
        document.getElementById('inboxEmpty').style.display = 'none';
        try {
            const res = await fetch(API + '/messages', { headers: authHeaders });
            const data = await res.json();
            inboxMessages = data.messages || [];
            renderInbox();
        } catch(e) { console.error(e); }
        document.getElementById('inboxLoading').style.display = 'none';
    }

    async function fetchSent() {
        document.getElementById('sentLoading').style.display = '';
        document.getElementById('sentEmpty').style.display = 'none';
        try {
            const res = await fetch(API + '/messages/sent', { headers: authHeaders });
            const data = await res.json();
            sentMessages = data.messages || [];
            renderSent();
        } catch(e) { console.error(e); }
        document.getElementById('sentLoading').style.display = 'none';
    }

    async function fetchUnread() {
        try {
            const res = await fetch(API + '/messages/unread-count', { headers: authHeaders });
            const data = await res.json();
            const badge = document.getElementById('unreadBadge');
            if (data.unread_count > 0) { badge.textContent = data.unread_count; badge.style.display = ''; }
            else { badge.style.display = 'none'; }
        } catch(e) {}
    }

    async function fetchRecipients() {
        try {
            const res = await fetch(API + '/messages/recipients', { headers: authHeaders });
            recipientsData = await res.json();
            renderRecipientButtons();
            setRecipientType('individual');
        } catch(e) { console.error(e); }
    }

    async function sendMessage() {
        const subject = document.getElementById('msgSubject').value.trim();
        const body = document.getElementById('msgBody').value.trim();
        if (!subject || !body) { alert('Please fill in subject and message.'); return; }

        const btn = document.getElementById('sendBtn');
        btn.disabled = true; btn.textContent = 'Sending...';

        const payload = { subject, body, recipient_type: recipientType };
        if (recipientType === 'individual') payload.recipient_id = document.getElementById('recipientId').value;
        if (recipientType === 'grade') payload.grade = document.getElementById('gradeId').value;

        try {
            const res = await fetch(API + '/messages', { method: 'POST', headers: authHeaders, body: JSON.stringify(payload) });
            if (res.ok) {
                document.getElementById('msgSubject').value = '';
                document.getElementById('msgBody').value = '';
                showSent();
            } else {
                const err = await res.json();
                alert(err.message || 'Failed to send.');
            }
        } catch(e) { alert('Network error.'); }
        btn.disabled = false; btn.textContent = 'ðŸ“¤ Send Message';
    }

    async function markRead(id) {
        try { await fetch(API + '/messages/' + id + '/read', { method: 'POST', headers: authHeaders }); fetchUnread(); } catch(e) {}
    }

    // ===== RENDERING =====
    function formatDate(iso) {
        const d = new Date(iso), now = new Date(), diff = now - d;
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff/60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff/3600000) + 'h ago';
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short' });
    }
    function getBroadcastLabel(role) {
        if (role === 'all_students') return 'ðŸ“¢ All Students';
        if (role === 'all_teachers') return 'ðŸ“¢ All Teachers';
        if (role === 'admin') return 'ðŸ“¢ Admin';
        if (role && role.startsWith('grade_')) return 'ðŸ“¢ Grade ' + role.replace('grade_', '');
        return role || '';
    }

    function renderInbox() {
        const list = document.getElementById('inboxList');
        if (inboxMessages.length === 0) { list.innerHTML = ''; document.getElementById('inboxEmpty').style.display = ''; return; }
        document.getElementById('inboxEmpty').style.display = 'none';
        const roleMap = { admin: 'ðŸ›¡ï¸ Admin', teacher: 'ðŸ‘¨â€ðŸ« Teacher', user: 'ðŸŽ“ Student' };
        list.innerHTML = inboxMessages.map(m => `
            <div class="msg-item ${!m.is_read ? 'unread' : ''}" onclick="showDetail(inboxMessages[${inboxMessages.indexOf(m)}], false)">
                <div class="msg-item-avatar">${(m.sender?.name || '?').charAt(0).toUpperCase()}</div>
                <div class="msg-item-body">
                    <div class="msg-item-top">
                        <span class="msg-sender">${m.sender?.name || 'Unknown'}</span>
                        <span class="msg-role-tag">${roleMap[m.sender?.role] || ''}</span>
                        ${m.is_broadcast ? '<span class="msg-broadcast-tag">' + getBroadcastLabel(m.receiver_role) + '</span>' : ''}
                    </div>
                    <div class="msg-subject">${m.subject || ''}</div>
                    <div class="msg-preview">${(m.body || '').substring(0, 80)}...</div>
                </div>
                <div style="text-align:right;">
                    <span class="msg-time">${formatDate(m.created_at)}</span>
                    ${!m.is_read ? '<span class="msg-dot"></span>' : ''}
                </div>
            </div>
        `).join('');
    }

    function renderSent() {
        const list = document.getElementById('sentList');
        if (sentMessages.length === 0) { list.innerHTML = ''; document.getElementById('sentEmpty').style.display = ''; return; }
        document.getElementById('sentEmpty').style.display = 'none';
        list.innerHTML = sentMessages.map(m => `
            <div class="msg-item" onclick="showDetail(sentMessages[${sentMessages.indexOf(m)}], true)">
                <div class="msg-item-avatar sent-av">${(m.receiver?.name || 'ðŸ“¢').charAt(0).toUpperCase()}</div>
                <div class="msg-item-body">
                    <div class="msg-item-top"><span class="msg-sender">To: ${m.receiver?.name || getBroadcastLabel(m.receiver_role)}</span></div>
                    <div class="msg-subject">${m.subject || ''}</div>
                    <div class="msg-preview">${(m.body || '').substring(0, 80)}...</div>
                </div>
                <div style="text-align:right;"><span class="msg-time">${formatDate(m.created_at)}</span></div>
            </div>
        `).join('');
    }

    function renderRecipientButtons() {
        const btns = document.getElementById('recipientBtns');
        let html = '<button class="rbtn active" data-type="individual" onclick="setRecipientType(\'individual\')">ðŸ‘¤ Individual</button>';
        (recipientsData.broadcast || []).forEach(opt => {
            if (opt.value.startsWith('grade_')) return;
            html += `<button class="rbtn" data-type="${opt.value}" onclick="setRecipientType('${opt.value}')">${opt.label}</button>`;
        });
        const hasGrades = (recipientsData.broadcast || []).some(o => o.value.startsWith('grade_'));
        if (hasGrades) html += '<button class="rbtn" data-type="grade" onclick="setRecipientType(\'grade\')">ðŸ“š By Grade</button>';
        btns.innerHTML = html;

        // Populate individual select
        const sel = document.getElementById('recipientId');
        sel.innerHTML = '<option value="">-- Select --</option>';
        (recipientsData.teachers || []).forEach(t => sel.innerHTML += `<option value="${t.id}">${t.name} (Teacher) â€” ${t.email}</option>`);
        (recipientsData.students || []).forEach(s => sel.innerHTML += `<option value="${s.id}">${s.name} (Student) â€” ${s.email}</option>`);
        (recipientsData.admins || []).forEach(a => sel.innerHTML += `<option value="${a.id}">${a.name} (Admin) â€” ${a.email}</option>`);

        // Populate grade select
        const gSel = document.getElementById('gradeId');
        gSel.innerHTML = '<option value="">-- Select Grade --</option>';
        (recipientsData.broadcast || []).filter(o => o.value.startsWith('grade_')).forEach(g => {
            gSel.innerHTML += `<option value="${g.value.replace('grade_', '')}">${g.label}</option>`;
        });
    }

    function setRecipientType(type) {
        recipientType = type;
        document.querySelectorAll('.recipient-btns .rbtn').forEach(b => b.classList.toggle('active', b.dataset.type === type));
        document.getElementById('individualSelect').style.display = type === 'individual' ? '' : 'none';
        document.getElementById('gradeSelect').style.display = type === 'grade' ? '' : 'none';
    }

    // ===== INIT =====
    document.addEventListener('DOMContentLoaded', function() {
        fetchInbox();
        fetchUnread();
    });
    </script>
    <script src="{{ asset('admin-theme/js/admin-branding.js') }}"></script>
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    
    <script src="{{ asset('admin-theme/vendor/toastr/js/toastr.min.js') }}"></script>
    <script src="{{ asset('admin-theme/js/admin-notifications.js?v=' . time()) }}"></script>
</body>
</html>





<div class="header">
    <style>
        [data-header-position="fixed"] .deznav {
            margin-top: -1.20rem;
        }

        /* ══════════════════════════════════════
           MOBILE ONLY (max 767px)
           ══════════════════════════════════════ */
        @media (max-width: 767px) {

            .nav-header {
                z-index: 10001 !important;
                width: 4.5rem !important;
                height: 65px !important;
                background: #1f2937 !important;
                position: fixed !important;
                top: 0 !important; left: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 0 !important;
                overflow: visible !important;
            }
            .nav-header .brand-logo {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 2px !important;
                text-decoration: none !important;
                padding: 0 !important;
            }
            .nav-header .brand-logo img,
            .nav-header .brand-logo .logo-abbr {
                width: 26px !important; height: 26px !important;
                max-width: 26px !important; display: block !important;
            }
            .nav-header .brand-title {
                display: block !important;
                font-size: 0.55rem !important;
                font-weight: 700 !important;
                color: #fff !important;
                margin: 0 !important; padding: 0 !important;
                white-space: nowrap !important;
                letter-spacing: 0.8px !important;
                line-height: 1 !important;
                text-transform: uppercase !important;
            }
            .nav-header .nav-control { display: none !important; }

            .header {
                height: 65px !important;
                background: #1f2937 !important;
                border-bottom: 2px solid #4f46e5 !important;
                position: fixed !important;
                top: 0 !important; left: 0 !important;
                width: 100% !important;
                z-index: 10000 !important;
                padding: 0 0 0 4.5rem !important;
                box-sizing: border-box !important;
            }
            .header-content {
                padding: 0 !important;
                height: 65px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .header .navbar,
            .header .navbar-expand {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
                height: 65px !important;
                padding: 0 8px 0 0 !important;
                width: 100% !important;
            }
            .header .collapse.navbar-collapse,
            .header .navbar-collapse,
            .header .navbar-collapse.justify-content-between,
            .header .navbar-expand .navbar-collapse {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
                height: 65px !important;
                justify-content: space-between !important;
                width: 100% !important;
            }

            /* Hamburger */
            .custom-hamburger {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                width: 20px !important; height: 15px !important;
                cursor: pointer !important;
                flex-shrink: 0 !important;
                margin: 0 8px 0 10px !important;
                background: none !important;
                border: none !important;
                padding: 0 !important; outline: none !important;
            }
            .custom-hamburger span {
                display: block !important;
                width: 20px !important; height: 2px !important;
                background: #ffffff !important;
                border-radius: 2px !important;
                transition: all 0.3s ease !important;
                transform-origin: center !important;
            }
            .custom-hamburger.active span:nth-child(1) {
                transform: translateY(6.5px) rotate(45deg) !important;
            }
            .custom-hamburger.active span:nth-child(2) {
                opacity: 0 !important; transform: scaleX(0) !important;
            }
            .custom-hamburger.active span:nth-child(3) {
                transform: translateY(-6.5px) rotate(-45deg) !important;
            }

            /* Search */
            .header-left {
                flex: 1 1 auto !important;
                min-width: 0 !important;
                max-width: 145px !important;
                margin-right: 35px !important;
                padding: 0 !important;
            }
            .header-left .search_bar { width: 100% !important; }
            .header-left .search_bar form {
                position: relative !important;
                display: flex !important;
                align-items: center !important;
                width: 100% !important;
            }
            .header-left .search_bar .form-control {
                height: 32px !important;
                font-size: 10px !important;
                padding: 0 8px 0 26px !important;
                background: rgba(255,255,255,0.1) !important;
                border: 1px solid rgba(255,255,255,0.18) !important;
                border-radius: 50px !important;
                color: #fff !important;
                box-shadow: none !important;
                width: 100% !important;
                outline: none !important;
            }
            .header-left .search_bar .form-control::placeholder {
                color: rgba(255,255,255,0.38) !important;
                font-size: 10px !important;
            }
            .header-left .search_bar .search_icon {
                position: absolute !important;
                left: 8px !important; top: 50% !important;
                transform: translateY(-50%) !important;
                color: rgba(255,255,255,0.55) !important;
                font-size: 13px !important;
                z-index: 10 !important;
                pointer-events: none !important;
            }

            /* Right icons */
            .header-right {
                flex-shrink: 0 !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                flex-wrap: nowrap !important;
                gap: 0 !important;
                margin: 0 !important; padding: 0 !important;
                list-style: none !important;
            }
            .header-right .nav-item {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 !important; padding: 0 !important;
            }
            .header-right .nav-link {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 4px 5px !important;
                position: relative !important;
            }
            .header-right .nav-item:not(.header-profile) .nav-link svg {
                width: 18px !important; height: 18px !important;
                display: block !important; flex-shrink: 0 !important;
            }

            /* Profile avatar - MOBILE */
            .header-profile > .nav-link { padding: 4px !important; }
            .header-profile img,
            .header-profile .header-profile-initials {
                width: 36px !important; height: 36px !important;
                flex-shrink: 0 !important;
                border: 2px solid rgba(79,70,229,0.55) !important;
                border-radius: 50% !important;
            }

            /* Pulse dot - MOBILE */
            #message-pulse,
            #notification-pulse {
                width: 0.55rem !important;
                height: 0.55rem !important;
                top: 3px !important;
                right: 3px !important;
                box-shadow: 0 0 0 2px #1f2937 !important;
            }
            #message-count,
            #notification-count {
                display: none !important;
            }

            [data-header-position="fixed"] .content-body {
                padding-top: 65px !important;
            }
        }

        /* ══════════════════════════════════════
           TABLET (768px – 1199px)
           Covers ALL Android phones + tablets
           ══════════════════════════════════════ */
        @media (min-width: 768px) and (max-width: 1199px) {

            .nav-header {
                z-index: 10001 !important;
                width: 5rem !important;
                height: 65px !important;
                background: #1f2937 !important;
                position: fixed !important;
                top: 0 !important; left: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 0 !important;
                overflow: visible !important;
            }
            .nav-header .brand-logo {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 2px !important;
                text-decoration: none !important;
                padding: 0 !important;
            }
            .nav-header .brand-logo img,
            .nav-header .brand-logo .logo-abbr {
                width: 28px !important; height: 28px !important;
                max-width: 28px !important; display: block !important;
            }
            .nav-header .brand-title {
                display: block !important;
                font-size: 0.55rem !important;
                font-weight: 700 !important;
                color: #fff !important;
                margin: 0 !important; padding: 0 !important;
                white-space: nowrap !important;
                letter-spacing: 0.8px !important;
                line-height: 1 !important;
                text-transform: uppercase !important;
            }
            .nav-header .nav-control { display: none !important; }

            .header {
                height: 65px !important;
                background: #1f2937 !important;
                border-bottom: 2px solid #4f46e5 !important;
                position: fixed !important;
                top: 0 !important; left: 0 !important;
                width: 100% !important;
                z-index: 10000 !important;
                padding: 0 0 0 5rem !important;
                box-sizing: border-box !important;
            }
            .header-content {
                padding: 0 !important;
                height: 65px !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }
            .header .navbar,
            .header .navbar-expand {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
                height: 65px !important;
                padding: 0 6px 0 0 !important;
                width: 100% !important;
                overflow: hidden !important;
            }
            .header .collapse.navbar-collapse,
            .header .navbar-collapse,
            .header .navbar-collapse.justify-content-between,
            .header .navbar-expand .navbar-collapse {
                display: flex !important;
                flex-direction: row !important;
                flex-wrap: nowrap !important;
                align-items: center !important;
                height: 65px !important;
                justify-content: space-between !important;
                width: 100% !important;
                overflow: hidden !important;
            }

            /* Hamburger - TABLET */
            .custom-hamburger {
                display: flex !important;
                flex-direction: column !important;
                justify-content: space-between !important;
                width: 20px !important; height: 15px !important;
                cursor: pointer !important;
                flex-shrink: 0 !important;
                margin: 0 6px 0 8px !important;
                background: none !important;
                border: none !important;
                padding: 0 !important; outline: none !important;
            }
            .custom-hamburger span {
                display: block !important;
                width: 20px !important; height: 2px !important;
                background: #ffffff !important;
                border-radius: 2px !important;
                transition: all 0.3s ease !important;
                transform-origin: center !important;
            }
            .custom-hamburger.active span:nth-child(1) {
                transform: translateY(6.5px) rotate(45deg) !important;
            }
            .custom-hamburger.active span:nth-child(2) {
                opacity: 0 !important; transform: scaleX(0) !important;
            }
            .custom-hamburger.active span:nth-child(3) {
                transform: translateY(-6.5px) rotate(-45deg) !important;
            }

            /* Search — fixed width, never grows into icons */
            .header-left {
                flex: 0 0 120px !important;
                width: 120px !important;
                min-width: 0 !important;
                max-width: 120px !important;
                margin-right: 4px !important;
                padding: 0 !important;
                overflow: hidden !important;
            }
            .header-left .search_bar { width: 100% !important; }
            .header-left .search_bar form {
                position: relative !important;
                display: flex !important;
                align-items: center !important;
                width: 100% !important;
            }
            .header-left .search_bar .form-control {
                height: 30px !important;
                font-size: 10px !important;
                padding: 0 6px 0 24px !important;
                background: rgba(255,255,255,0.1) !important;
                border: 1px solid rgba(255,255,255,0.18) !important;
                border-radius: 50px !important;
                color: #fff !important;
                box-shadow: none !important;
                width: 100% !important;
                outline: none !important;
            }
            .header-left .search_bar .form-control::placeholder {
                color: rgba(255,255,255,0.38) !important;
                font-size: 10px !important;
            }
            .header-left .search_bar .search_icon {
                position: absolute !important;
                left: 7px !important; top: 50% !important;
                transform: translateY(-50%) !important;
                color: rgba(255,255,255,0.55) !important;
                font-size: 12px !important;
                z-index: 10 !important;
                pointer-events: none !important;
            }

            /* Right icons — never shrink, always stay right */
            .header-right {
                flex: 0 0 auto !important;
                flex-shrink: 0 !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                flex-wrap: nowrap !important;
                gap: 0 !important;
                margin: 0 !important; padding: 0 4px 0 0 !important;
                list-style: none !important;
            }
            .header-right .nav-item {
                flex-shrink: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 0 !important; padding: 0 !important;
            }
            .header-right .nav-link {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 4px 4px !important;
                position: relative !important;
            }
            .header-right .nav-item:not(.header-profile) .nav-link svg {
                width: 17px !important; height: 17px !important;
                display: block !important; flex-shrink: 0 !important;
            }

            /* Profile avatar - TABLET */
            .header-profile > .nav-link { padding: 4px !important; }
            .header-profile img,
            .header-profile .header-profile-initials {
                width: 34px !important; height: 34px !important;
                flex-shrink: 0 !important;
                border: 2px solid rgba(79,70,229,0.55) !important;
                border-radius: 50% !important;
            }

            /* Pulse dot - TABLET */
            #message-pulse,
            #notification-pulse {
                width: 0.55rem !important;
                height: 0.55rem !important;
                top: 3px !important;
                right: 3px !important;
                box-shadow: 0 0 0 2px #1f2937 !important;
            }
            #message-count,
            #notification-count {
                display: none !important;
            }

            [data-header-position="fixed"] .content-body {
                padding-top: 65px !important;
            }
        }

        /* ══════════════════════════════════════
           DESKTOP (min 1200px)
           ══════════════════════════════════════ */
        @media (min-width: 1200px) {

            .custom-hamburger { display: none !important; }

            .header-left {
                flex: 1 !important;
                max-width: 400px !important;
            }

            /* Icons - desktop */
            .header-right .nav-item:not(.header-profile) .nav-link svg {
                width: 28px !important;
                height: 28px !important;
                display: block !important;
            }
            .header-right .nav-link {
                padding: 5px 10px !important;
                position: relative !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            /* Profile avatar - DESKTOP */
            .header-profile img,
            .header-profile .header-profile-initials {
                width: 52px !important;
                height: 52px !important;
                border: 2px solid rgba(79,70,229,0.55) !important;
                border-radius: 50% !important;
            }
            .header-profile > .nav-link { padding: 5px !important; }

            /* Pulse dot - DESKTOP */
            #message-pulse,
            #notification-pulse {
                width: 0.80rem !important;
                height: 0.80rem !important;
                top: 4px !important;
                right: 4px !important;
                box-shadow: 0 0 0 2.5px #1f2937 !important;
            }
            #message-count,
            #notification-count {
                font-size: 0.45rem !important;
                font-weight: bold !important;
                line-height: 1 !important;
            }

            /* Dropdown */
            .header-profile .dropdown-menu {
                min-width: 210px !important;
                border-radius: 12px !important;
                box-shadow: 0 8px 24px rgba(0,0,0,0.15) !important;
                border: 1px solid rgba(0,0,0,0.08) !important;
                padding: 8px 0 !important;
                right: 0 !important; left: auto !important;
                top: calc(100% + 8px) !important;
            }
            .header-profile .dropdown-item {
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
                padding: 9px 16px !important;
                font-size: 0.82rem !important;
                color: #333 !important;
                transition: background 0.15s !important;
            }
            .header-profile .dropdown-item:hover { background: #f0f4ff !important; }
            .header-profile .dropdown-item i {
                font-size: 1.1rem !important;
                width: 18px !important;
                text-align: center !important;
            }
        }
    </style>

    <div class="header-content">
        <nav class="navbar navbar-expand">
            <div class="collapse navbar-collapse justify-content-between"
                 style="display:flex!important;flex-direction:row!important;flex-wrap:nowrap!important;align-items:center!important;height:65px!important;width:100%!important;">

                {{-- CUSTOM HAMBURGER: mobile & tablet only --}}
                <button class="custom-hamburger"
                        id="customHamburger"
                        aria-label="Toggle menu"
                        style="display:none;">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                {{-- SEARCH --}}
                <div class="header-left">
                    <div class="search_bar">
                        <form>
                            <input class="form-control" type="search" placeholder="Search..." aria-label="Search">
                            <span class="search_icon"><i class="mdi mdi-magnify"></i></span>
                        </form>
                    </div>
                </div>

                {{-- RIGHT ICONS --}}
                <ul class="navbar-nav header-right"
                    style="display:flex!important;flex-direction:row!important;align-items:center!important;flex-wrap:nowrap!important;gap:0!important;margin:0!important;padding:0!important;list-style:none!important;">

                    {{-- Home --}}
                    <li class="nav-item">
                        <a class="nav-link" href="{{ config('services.frontend_url') }}"
                           target="_blank" rel="noopener noreferrer" title="Home">
                            <svg viewBox="0 0 24 24" fill="none"
                                 stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                                <polyline points="9 22 9 12 15 12 15 22"/>
                            </svg>
                        </a>
                    </li>

                    {{-- Messages --}}
                    <li class="nav-item">
                        <a class="nav-link ai-icon" href="{{ route('admin.messages.index') }}" title="Messages"
                           style="position:relative!important;">
                            <svg viewBox="0 0 28 28" fill="none">
                                <path d="M22.1667 5.83331H5.83333C4.54467 5.83331 3.5 6.878 3.5 8.16665V19.8333C3.5 21.122 4.54467 22.1666 5.83333 22.1666H22.1667C23.4553 22.1666 24.5 21.122 24.5 19.8333V8.16665C24.5 6.878 23.4553 5.83331 22.1667 5.83331Z"
                                      stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M3.5 8.16665L14 15.1666L24.5 8.16665"
                                      stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="pulse-css d-none" id="message-pulse"
                                 style="background:#EB8153;border-radius:50%;display:flex;align-items:center;justify-content:center;position:absolute;">
                                <span id="message-count" class="text-white d-none">0</span>
                            </div>
                        </a>
                    </li>

                    {{-- Notifications --}}
                    <li class="nav-item dropdown notification_dropdown">
                        <a class="nav-link ai-icon" href="javascript:void(0)" role="button" data-toggle="dropdown"
                           style="position:relative!important;">
                            <svg viewBox="0 0 28 28" fill="none">
                                <path d="M22.75 23.0417H5.25C4.84174 23.0417 4.44973 22.8791 4.16142 22.5891C3.87311 22.2991 3.71128 21.9058 3.71245 21.4958C3.71245 18.8033 4.75412 16.2133 6.65 14.3942V9.33333C6.65 6.65906 7.71235 4.09451 9.6033 2.2033C11.4945 0.31235 14.0591 -0.75 16.7333 -0.75C19.4076 -0.75 21.9721 0.31235 23.8633 2.2033C25.7543 4.09451 26.8167 6.65906 26.8167 9.33333V14.3942C28.7125 16.2133 29.7541 18.8033 29.7541 21.4958C29.7553 21.9058 29.5935 22.2991 29.3052 22.5891C29.0169 22.8791 28.6249 23.0417 28.2167 23.0417H22.75Z"
                                      fill="#FFFFFF"/>
                                <path d="M16.7233 27.25C15.6558 27.25 14.6158 26.8833 13.7783 26.205C13.4358 25.9258 13.3758 25.42 13.6458 25.0667C13.9167 24.7133 14.4142 24.6533 14.7667 24.9325C15.305 25.3675 16.0075 25.5992 16.7233 25.5992C17.4392 25.5992 18.1417 25.3675 18.68 24.9325C19.0325 24.6533 19.53 24.7133 19.8008 25.0667C20.0717 25.42 20.0117 25.9258 19.6683 26.205C18.8308 26.8833 17.7908 27.25 16.7233 27.25Z"
                                      fill="#FFFFFF"/>
                            </svg>
                            <div class="pulse-css d-none" id="notification-pulse"
                                 style="background:#EB8153;border-radius:50%;display:flex;align-items:center;justify-content:center;position:absolute;">
                                <span id="notification-count" class="text-white d-none">0</span>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div id="DZ_W_Notification1" class="set-height widget-media dz-scroll p-3">
                                <ul class="timeline" id="notification-list">
                                    <li class="text-center py-3">No new notifications</li>
                                </ul>
                            </div>
                            <a class="all-notification" href="{{ route('admin.notifications.index') }}">
                                See all notifications <i class="ti-arrow-right"></i>
                            </a>
                        </div>
                    </li>

                    {{-- Profile --}}
                    <li class="nav-item dropdown header-profile">
                        <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                            @if(Auth::user()->avatar)
                                <img src="{{ asset(Auth::user()->avatar) }}" alt=""
                                     style="border-radius:50%;object-fit:cover;">
                            @else
                                <div class="header-profile-initials"
                                     style="background:linear-gradient(135deg,#EB8153,#e05a1e);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;flex-shrink:0;">
                                    {{ strtoupper(substr(Auth::user()->first_name ?: Auth::user()->name, 0, 1)) }}
                                </div>
                            @endif
                        </a>
                        <div class="dropdown-menu dropdown-menu-right"
                             style="min-width:210px;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.15);border:1px solid rgba(0,0,0,0.08);padding:8px 0;right:0;left:auto;">
                            <div style="padding:10px 16px 12px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;gap:10px;">
                                @if(Auth::user()->avatar)
                                    <img src="{{ asset(Auth::user()->avatar) }}" alt=""
                                         style="width:36px;height:36px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                                @else
                                    <div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#EB8153,#e05a1e);color:white;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px;flex-shrink:0;">
                                        {{ strtoupper(substr(Auth::user()->first_name ?: Auth::user()->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div style="min-width:0;">
                                    <div style="font-size:0.85rem;font-weight:600;color:#1a1a2e;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        {{ Auth::user()->name }}
                                    </div>
                                    <div style="font-size:0.7rem;color:#888;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                                        {{ Auth::user()->email }}
                                    </div>
                                </div>
                            </div>
                            <a href="{{ route('admin.profile.settings') }}" class="dropdown-item"
                               style="display:flex;align-items:center;gap:10px;padding:9px 16px;font-size:0.82rem;color:#333;">
                                <i class="la la-cog" style="font-size:1.1rem;color:#4f46e5;width:18px;text-align:center;"></i>
                                Settings
                            </a>
                            <a href="{{ route('admin.profile.settings') }}?tab=calendar" class="dropdown-item"
                               style="display:flex;align-items:center;gap:10px;padding:9px 16px;font-size:0.82rem;color:#333;">
                                <i class="la la-calendar" style="font-size:1.1rem;color:#4f46e5;width:18px;text-align:center;"></i>
                                Calendar
                            </a>
                            <div style="border-top:1px solid #f0f0f0;margin:4px 0;"></div>
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item"
                                        style="display:flex;align-items:center;gap:10px;padding:9px 16px;font-size:0.82rem;color:#e53e3e;background:none;border:none;width:100%;text-align:left;cursor:pointer;">
                                    <i class="la la-sign-out" style="font-size:1.1rem;color:#e53e3e;width:18px;text-align:center;"></i>
                                    Sign out
                                </button>
                            </form>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>
    </div>

    <script>
        (function () {
            var btn = document.getElementById('customHamburger');
            if (!btn) return;
            function checkScreen() {
                // Show hamburger for both mobile (<=767) and tablet (768-1199)
                btn.style.display = window.innerWidth <= 1199 ? 'flex' : 'none';
                if (window.innerWidth > 1199) btn.classList.remove('active');
            }
            checkScreen();
            window.addEventListener('resize', checkScreen);
            btn.addEventListener('click', function () {
                btn.classList.toggle('active');
                var original = document.querySelector('.nav-control');
                if (original) original.click();
                else document.body.classList.toggle('menu-toggle');
            });
        })();
    </script>

    @stack('scripts')
</div>
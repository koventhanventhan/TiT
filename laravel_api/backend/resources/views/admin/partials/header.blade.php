<div class="header">
    <style>
        /* ══════════════════════════════════════════════════════════════
           C1 TOP HEADER: FIXED CSS GRID / FLEX CONTRACT (3 ZONES)
           ══════════════════════════════════════════════════════════════ */
        
        .header {
            height: 65px !important;
            background: var(--adm-sidebar) !important;
            border-bottom: 1px solid var(--adm-border) !important;
            position: fixed !important;
            top: 0 !important;
            z-index: 10000 !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .adm-header-container {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: space-between !important;
            height: 65px !important;
            width: 100% !important;
            box-sizing: border-box !important;
            padding: 0 14px !important;
            position: relative !important;
            gap: 8px !important;
        }

        /* ─── ZONE 1: LEFT (Toggle + Brand) ─── */
        .adm-header-zone-left {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            min-width: 0 !important;
            flex: 0 1 auto !important;
        }

        .adm-header-brand {
            display: flex !important;
            align-items: center !important;
            gap: 8px !important;
            text-decoration: none !important;
            min-width: 0 !important;
        }

        .adm-header-brand img {
            width: 28px !important;
            height: 28px !important;
            max-width: 28px !important;
            object-fit: contain !important;
            flex-shrink: 0 !important;
        }

        .adm-header-brand svg {
            width: 28px !important;
            height: 28px !important;
            flex-shrink: 0 !important;
        }

        .adm-brand-text {
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            color: var(--adm-text) !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 160px !important;
            min-width: 0 !important;
            letter-spacing: 0.3px !important;
        }

        /* Hamburger button */
        .custom-hamburger {
            display: inline-flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            gap: 4px !important;
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
            cursor: pointer !important;
            flex-shrink: 0 !important;
            background: transparent !important;
            border: 1px solid var(--adm-border) !important;
            border-radius: 8px !important;
            padding: 0 !important;
            outline: none !important;
            touch-action: manipulation !important;
            color: var(--adm-text) !important;
        }

        .custom-hamburger span {
            display: block !important;
            width: 18px !important;
            height: 2px !important;
            background: var(--adm-text) !important;
            border-radius: 2px !important;
            transition: all 0.25s ease !important;
            transform-origin: center !important;
        }

        .custom-hamburger.active span:nth-child(1) {
            transform: translateY(6px) rotate(45deg) !important;
        }
        .custom-hamburger.active span:nth-child(2) {
            opacity: 0 !important;
            transform: scaleX(0) !important;
        }
        .custom-hamburger.active span:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg) !important;
        }

        /* ─── ZONE 2: CENTRE (Search) ─── */
        .adm-header-zone-centre {
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            flex: 1 1 auto !important;
            min-width: 0 !important;
        }

        .adm-search-bar {
            width: 100% !important;
            max-width: 380px !important;
            display: none !important;
        }

        .adm-search-bar form {
            position: relative !important;
            width: 100% !important;
            display: flex !important;
            align-items: center !important;
        }

        .adm-search-input {
            width: 100% !important;
            height: 36px !important;
            background: var(--adm-input-bg) !important;
            border: 1px solid var(--adm-border) !important;
            border-radius: 20px !important;
            padding: 0 12px 0 34px !important;
            font-size: 0.85rem !important;
            color: var(--adm-text) !important;
            outline: none !important;
        }

        .adm-search-input:focus {
            border-color: var(--adm-accent) !important;
            box-shadow: 0 0 0 2px rgba(235, 129, 83, 0.2) !important;
        }

        .adm-search-icon {
            position: absolute !important;
            left: 10px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            color: var(--adm-text-3) !important;
            pointer-events: none !important;
            display: flex !important;
            align-items: center !important;
        }

        .adm-search-close-btn {
            display: none !important;
        }

        /* ─── ZONE 3: RIGHT (40x40 Icons + Avatar) ─── */
        .adm-header-zone-right {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
            gap: 6px !important;
            flex-shrink: 0 !important;
            list-style: none !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .adm-header-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
            min-height: 40px !important;
            border-radius: 8px !important;
            background: transparent !important;
            border: 1px solid var(--adm-border) !important;
            color: var(--adm-text) !important;
            padding: 0 !important;
            cursor: pointer !important;
            position: relative !important;
            text-decoration: none !important;
            flex-shrink: 0 !important;
            box-sizing: border-box !important;
            transition: background 0.15s, border-color 0.15s !important;
        }

        .adm-header-btn:hover,
        .adm-header-btn:focus {
            background: rgba(255, 255, 255, 0.06) !important;
            color: var(--adm-accent) !important;
            border-color: var(--adm-accent) !important;
        }

        .adm-header-btn svg {
            width: 20px !important;
            height: 20px !important;
            stroke: currentColor !important;
            display: block !important;
            flex-shrink: 0 !important;
        }

        /* Absolute Badge inside 40x40 container */
        .adm-badge {
            position: absolute !important;
            top: -3px !important;
            right: -3px !important;
            background: var(--adm-accent, #eb8153) !important;
            color: var(--adm-on-accent, #0f172a) !important;
            font-size: 10px !important;
            font-weight: 800 !important;
            line-height: 1 !important;
            min-width: 17px !important;
            height: 17px !important;
            padding: 1px 3px !important;
            border-radius: 999px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 0 0 2px var(--adm-sidebar) !important;
            pointer-events: none !important;
            box-sizing: border-box !important;
        }

        /* Avatar Box & Initials fallback */
        .adm-header-profile-btn {
            display: inline-flex !important;
            align-items: center !important;
            height: 40px !important;
            border-radius: 20px !important;
            background: transparent !important;
            border: 1px solid var(--adm-border) !important;
            padding: 2px !important;
            cursor: pointer !important;
            gap: 8px !important;
            color: var(--adm-text) !important;
            flex-shrink: 0 !important;
        }

        .adm-avatar-box {
            width: 34px !important;
            height: 34px !important;
            min-width: 34px !important;
            min-height: 34px !important;
            border-radius: 50% !important;
            overflow: hidden !important;
            flex-shrink: 0 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .adm-avatar-img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 50% !important;
            display: block !important;
        }

        .adm-avatar-initials {
            width: 100% !important;
            height: 100% !important;
            border-radius: 50% !important;
            background: var(--adm-accent, #eb8153) !important;
            color: var(--adm-on-accent, #0f172a) !important;
            font-weight: 800 !important;
            font-size: 13px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-sizing: border-box !important;
        }

        .adm-header-user-name {
            display: none;
            font-size: 0.85rem !important;
            font-weight: 600 !important;
            color: var(--adm-text) !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            max-width: 130px !important;
            padding-right: 8px !important;
        }

        /* ─── DROPDOWN MENUS ─── */
        .adm-header-dropdown .dropdown-menu {
            min-width: 220px !important;
            border-radius: 12px !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
            border: 1px solid var(--adm-border) !important;
            background: var(--adm-surface) !important;
            padding: 8px 0 !important;
            margin-top: 8px !important;
            right: 0 !important;
            left: auto !important;
        }

        .adm-header-dropdown .dropdown-item {
            display: flex !important;
            align-items: center !important;
            gap: 10px !important;
            padding: 9px 16px !important;
            font-size: 0.84rem !important;
            color: var(--adm-text) !important;
            transition: background 0.15s !important;
        }

        .adm-header-dropdown .dropdown-item:hover,
        .adm-header-dropdown .dropdown-item:focus {
            background: rgba(255, 255, 255, 0.08) !important;
            color: var(--adm-accent) !important;
        }

        /* ══════════════════════════════════════
           BREAKPOINTS
           ══════════════════════════════════════ */

        /* DESKTOP (>= 1200px) */
        @media (min-width: 1200px) {
            .nav-header {
                display: flex !important;
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                width: 17.5rem !important;
                height: 65px !important;
                background: var(--adm-sidebar) !important;
                border-bottom: 1px solid var(--adm-border) !important;
                border-right: 1px solid var(--adm-border) !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 0 16px !important;
                z-index: 10001 !important;
                box-sizing: border-box !important;
            }

            .header {
                left: 17.5rem !important;
                width: calc(100% - 17.5rem) !important;
            }

            .custom-hamburger {
                display: none !important;
            }

            .adm-header-brand {
                display: none !important;
            }

            .mobile-search-btn {
                display: none !important;
            }

            .adm-search-bar {
                display: block !important;
            }

            .adm-header-user-name {
                display: inline-block !important;
            }

            /* Main wrapper menu-toggle state on desktop */
            #main-wrapper.menu-toggle .nav-header {
                width: 5rem !important;
                justify-content: center !important;
                padding: 0 !important;
            }

            #main-wrapper.menu-toggle .nav-header .brand-title {
                display: none !important;
            }

            #main-wrapper.menu-toggle .header {
                left: 5rem !important;
                width: calc(100% - 5rem) !important;
            }
        }

        /* TABLET & LAPTOP (1024px – 1199px) */
        @media (min-width: 1024px) and (max-width: 1199px) {
            .nav-header {
                display: none !important;
            }

            .header {
                left: 0 !important;
                width: 100% !important;
            }

            .custom-hamburger {
                display: inline-flex !important;
            }

            .adm-header-brand {
                display: flex !important;
            }

            .adm-brand-text {
                max-width: 220px !important;
            }

            .mobile-search-btn {
                display: none !important;
            }

            .adm-search-bar {
                display: block !important;
                max-width: 280px !important;
            }
        }

        /* TABLET (768px – 1023px) */
        @media (min-width: 768px) and (max-width: 1023px) {
            .nav-header {
                display: none !important;
            }

            .header {
                left: 0 !important;
                width: 100% !important;
            }

            .custom-hamburger {
                display: inline-flex !important;
            }

            .adm-header-brand {
                display: flex !important;
            }

            .adm-brand-text {
                max-width: 180px !important;
            }

            .mobile-search-btn {
                display: inline-flex !important;
            }

            .adm-search-bar {
                display: none !important;
            }
        }

        /* MOBILE (< 768px) */
        @media (max-width: 767px) {
            .nav-header {
                display: none !important;
            }

            .header {
                left: 0 !important;
                width: 100% !important;
            }

            .adm-header-container {
                padding: 0 12px !important;
                gap: 6px !important;
            }

            .custom-hamburger {
                display: inline-flex !important;
            }

            .adm-header-brand {
                display: flex !important;
            }

            .adm-brand-text {
                max-width: 110px !important;
                font-size: 0.85rem !important;
            }

            .mobile-search-btn {
                display: inline-flex !important;
            }

            .adm-search-bar {
                display: none !important;
            }

            /* Collapsing ~70px empty band */
            [data-header-position="fixed"] .content-body {
                padding-top: 65px !important;
            }
            .content-body .container-fluid {
                padding-top: 14px !important;
                padding-left: 12px !important;
                padding-right: 12px !important;
            }
        }

        /* ─── FULL-WIDTH SEARCH OVERLAY (When expanded below 1024px) ─── */
        .adm-header-zone-centre.search-expanded {
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            background: var(--adm-surface) !important;
            z-index: 1050 !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 14px !important;
            box-sizing: border-box !important;
        }

        .adm-header-zone-centre.search-expanded .mobile-search-btn {
            display: none !important;
        }

        .adm-header-zone-centre.search-expanded .adm-search-bar {
            display: flex !important;
            width: 100% !important;
            max-width: 100% !important;
        }

        .adm-header-zone-centre.search-expanded .adm-search-input {
            flex: 1 1 auto !important;
            border-radius: 8px !important;
            padding-right: 44px !important;
        }

        .adm-header-zone-centre.search-expanded .adm-search-close-btn {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 36px !important;
            height: 36px !important;
            position: absolute !important;
            right: 4px !important;
            background: transparent !important;
            border: none !important;
            color: var(--adm-text) !important;
            cursor: pointer !important;
            outline: none !important;
        }
    </style>

    <div class="adm-header-container">
        {{-- ZONE 1: LEFT (Toggle + Brand) --}}
        <div class="adm-header-zone-left">
            <button class="custom-hamburger" id="customHamburger" aria-label="Toggle menu" type="button">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <a href="{{ route('admin.dashboard') }}" class="adm-header-brand" title="{{ $brandName ?? 'TiT Institute' }}">
                @if(isset($site_settings['admin_logo']))
                    <img src="{{ asset($site_settings['admin_logo']) }}" alt="Logo">
                @else
                    <svg viewBox="0 0 50 50" fill="none">
                        <rect width="50" height="50" rx="14" fill="var(--adm-accent, #EB8153)"/>
                        <path d="M17.5158 25.8619L19.8088 25.2475L14.8746 11.1774C14.5189 9.84988 15.8701 9.0998 16.8205 9.75055L33.0924 22.2055C33.7045 22.5589 33.8512 24.0717 32.6444 24.3951L30.3514 25.0095L35.2856 39.0796C35.6973 40.1334 34.4431 41.2455 33.3397 40.5064L17.0678 28.0515C16.2057 27.2477 16.5504 26.1205 17.5158 25.8619ZM18.685 14.2955L22.2224 24.6007L29.4633 22.6605L18.685 14.2955ZM31.4751 35.9615L27.8171 25.6886L20.5762 27.6288L31.4751 35.9615Z" fill="white"/>
                    </svg>
                @endif
                <span class="adm-brand-text">{{ $brandName ?? 'TiT Institute' }}</span>
            </a>
        </div>

        {{-- ZONE 2: CENTRE (Search) --}}
        <div class="adm-header-zone-centre" id="headerSearchContainer">
            <button type="button" class="adm-header-btn mobile-search-btn" id="mobileSearchBtn" aria-label="Open search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>

            <div class="adm-search-bar">
                <form action="{{ route('admin.search') }}" method="GET">
                    <input class="form-control adm-search-input" type="search" name="q" placeholder="Search..." aria-label="Search">
                    <span class="adm-search-icon"><i class="mdi mdi-magnify"></i></span>
                    <button type="button" class="adm-search-close-btn" id="mobileSearchClose" aria-label="Close search">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </form>
            </div>
        </div>

        {{-- ZONE 3: RIGHT (Icons + Avatar) --}}
        <div class="adm-header-zone-right">
            {{-- Home --}}
            <a class="adm-header-btn" href="{{ config('services.frontend_url') }}" target="_blank" rel="noopener noreferrer" title="Home" aria-label="Home">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </a>

            {{-- Messages --}}
            <a class="adm-header-btn" href="{{ route('admin.messages.index') }}" title="Messages" aria-label="Messages">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <div class="adm-badge pulse-css d-none" id="message-pulse">
                    <span id="message-count" class="d-none">0</span>
                </div>
            </a>

            {{-- Notifications --}}
            <div class="dropdown adm-header-dropdown">
                <button class="adm-header-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Notifications" aria-label="Notifications">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                    <div class="adm-badge pulse-css d-none" id="notification-pulse">
                        <span id="notification-count" class="d-none">0</span>
                    </div>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <div id="DZ_W_Notification1" class="set-height widget-media dz-scroll p-3">
                        <ul class="timeline" id="notification-list">
                            <li class="text-center py-3" style="color:var(--adm-text-3);">No new notifications</li>
                        </ul>
                    </div>
                    <a class="all-notification" href="{{ route('admin.notifications.index') }}" style="color:var(--adm-text); font-weight:600; display:block; padding:10px 16px; text-decoration:none; text-align:center; border-top:1px solid var(--adm-border);">
                        See all notifications <i class="ti-arrow-right"></i>
                    </a>
                </div>
            </div>

            {{-- Avatar / Profile --}}
            <div class="dropdown adm-header-dropdown">
                <button class="adm-header-profile-btn" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="User profile">
                    @php
                        $userInitials = strtoupper(substr(Auth::user()->first_name ?: Auth::user()->name, 0, 1));
                        $avatarUrl = Auth::user()->avatar ? asset(Auth::user()->avatar) : null;
                    @endphp
                    <div class="adm-avatar-box">
                        @if($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="" class="adm-avatar-img"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="adm-avatar-initials" style="display: none;">
                                {{ $userInitials }}
                            </div>
                        @else
                            <div class="adm-avatar-initials">
                                {{ $userInitials }}
                            </div>
                        @endif
                    </div>
                    <span class="adm-header-user-name">{{ Auth::user()->name }}</span>
                </button>
                <div class="dropdown-menu dropdown-menu-right">
                    <div style="padding:10px 16px 12px;border-bottom:1px solid var(--adm-border);display:flex;align-items:center;gap:10px;">
                        <div class="adm-avatar-box" style="width:36px;height:36px;">
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="" class="adm-avatar-img"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="adm-avatar-initials" style="display: none;">
                                    {{ $userInitials }}
                                </div>
                            @else
                                <div class="adm-avatar-initials">
                                    {{ $userInitials }}
                                </div>
                            @endif
                        </div>
                        <div style="min-width:0;">
                            <div style="font-size:0.85rem;font-weight:600;color:var(--adm-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">
                                {{ Auth::user()->name }}
                            </div>
                            <div style="font-size:0.7rem;color:var(--adm-text-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:140px;">
                                {{ Auth::user()->email }}
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('admin.profile.settings') }}" class="dropdown-item">
                        <i class="la la-cog" style="font-size:1.1rem;color:var(--adm-accent);width:18px;text-align:center;"></i>
                        Settings
                    </a>
                    <a href="{{ route('admin.profile.settings') }}?tab=calendar" class="dropdown-item">
                        <i class="la la-calendar" style="font-size:1.1rem;color:var(--adm-accent);width:18px;text-align:center;"></i>
                        Calendar
                    </a>
                    <div style="border-top:1px solid var(--adm-border);margin:4px 0;"></div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item" style="color:#f87171;background:none;border:none;width:100%;text-align:left;cursor:pointer;">
                            <i class="la la-sign-out" style="font-size:1.1rem;color:#f87171;width:18px;text-align:center;"></i>
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var btn = document.getElementById('customHamburger');
            if (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    btn.classList.toggle('active');
                    var wrapper = document.getElementById('main-wrapper') || document.body;
                    wrapper.classList.toggle('menu-toggle');
                });
            }

            // Close off-canvas drawer on outside click for mobile
            document.addEventListener('click', function (e) {
                if (window.innerWidth < 1200) {
                    var wrapper = document.getElementById('main-wrapper') || document.body;
                    if (wrapper && wrapper.classList.contains('menu-toggle')) {
                        var deznav = document.querySelector('.deznav');
                        if (deznav && !deznav.contains(e.target) && btn && !btn.contains(e.target)) {
                            wrapper.classList.remove('menu-toggle');
                            if (btn) btn.classList.remove('active');
                        }
                    }
                }
            });

            // Mobile search overlay collapse & expand
            var searchBtn = document.getElementById('mobileSearchBtn');
            var searchClose = document.getElementById('mobileSearchClose');
            var searchContainer = document.getElementById('headerSearchContainer');
            var searchInput = searchContainer ? searchContainer.querySelector('input[type="search"]') : null;

            if (searchBtn && searchContainer) {
                searchBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    searchContainer.classList.add('search-expanded');
                    if (searchInput) {
                        setTimeout(function () { searchInput.focus(); }, 80);
                    }
                });
            }

            if (searchClose && searchContainer) {
                searchClose.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    searchContainer.classList.remove('search-expanded');
                });
            }

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && searchContainer && searchContainer.classList.contains('search-expanded')) {
                    searchContainer.classList.remove('search-expanded');
                }
            });
        })();
    </script>
</div>

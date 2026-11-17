<div class="header">
    <style>
        /* ══════════════════════════════════════════════════════════════
           PROMPT 31c: ADMIN HEADER CSS GRID CONTRACT
           ≥1024px: 3 columns (auto minmax(0, 1fr) auto)
           <1024px: 2 columns (minmax(0, 1fr) auto) - single row 65px
           ══════════════════════════════════════════════════════════════ */
        
        [data-sidebar-style] .header,
        #main-wrapper .header,
        .header {
            height: 65px;
            background: var(--adm-sidebar);
            border-bottom: 1px solid var(--adm-border);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 10000;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .adm-header-container {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            grid-template-rows: 65px;
            grid-auto-rows: 0;
            align-items: center;
            height: 65px;
            width: 100%;
            box-sizing: border-box;
            padding: 0 14px;
            position: relative;
            gap: 8px;
            overflow: visible;
        }

        @media (min-width: 1024px) {
            .adm-header-container {
                grid-template-columns: auto minmax(0, 1fr) auto;
            }
        }

        /* ─── ZONE 1: LEFT (Toggle + Brand) ─── */
        .adm-header-zone-left {
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 0;
            width: 100%;
            height: 100%;
            grid-column: 1;
            grid-row: 1;
            box-sizing: border-box;
        }

        @media (min-width: 1024px) {
            .adm-header-zone-left {
                width: auto;
                gap: 10px;
                flex-shrink: 0;
            }
        }

        .adm-header-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            min-width: 0;
            flex: 1 1 auto;
        }

        .adm-header-brand img {
            width: 28px;
            height: 28px;
            max-width: 28px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .adm-header-brand svg {
            width: 28px;
            height: 28px;
            flex-shrink: 0;
        }

        .adm-brand-text {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--adm-text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
            letter-spacing: 0.3px;
        }

        .adm-brand-text-short {
            display: none;
        }

        /* Hamburger button (40x40 fixed) */
        .custom-hamburger {
            display: inline-flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 4px;
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            max-width: 40px;
            max-height: 40px;
            cursor: pointer;
            flex-shrink: 0;
            background: transparent;
            border: 1px solid var(--adm-border);
            border-radius: 8px;
            padding: 0;
            margin: 0;
            outline: none;
            touch-action: manipulation;
            color: var(--adm-text);
            box-sizing: border-box;
        }

        .custom-hamburger span {
            display: block;
            width: 18px;
            height: 2px;
            background: var(--adm-text);
            border-radius: 2px;
            transition: all 0.25s ease;
            transform-origin: center;
        }

        .custom-hamburger.active span:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }
        .custom-hamburger.active span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }
        .custom-hamburger.active span:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }

        /* ─── ZONE 2: CENTRE (Search Bar on Desktop / Overlay on Mobile) ─── */
        .adm-header-zone-centre {
            display: none;
        }

        @media (min-width: 1024px) {
            .adm-header-zone-centre {
                display: flex;
                grid-column: 2;
                grid-row: 1;
                align-items: center;
                justify-content: center;
                min-width: 0;
                height: 100%;
                box-sizing: border-box;
            }
        }

        .adm-search-bar {
            width: 100%;
            max-width: 380px;
            display: none;
        }

        .adm-search-bar form {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
        }

        .adm-search-input {
            width: 100%;
            height: 36px;
            background: var(--adm-input-bg);
            border: 1px solid var(--adm-border);
            border-radius: 20px;
            padding: 0 12px 0 34px;
            font-size: 0.85rem;
            color: var(--adm-text);
            outline: none;
        }

        .adm-search-input:focus {
            border-color: var(--adm-accent);
            box-shadow: 0 0 0 2px rgba(235, 129, 83, 0.2);
        }

        .adm-search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--adm-text-3);
            pointer-events: none;
            display: flex;
            align-items: center;
        }

        .adm-search-close-btn {
            display: none;
        }

        /* ─── ZONE 3: RIGHT (Fixed-size 40px Buttons + Avatar) ─── */
        .adm-header-zone-right {
            display: flex;
            flex-direction: row;
            align-items: center;
            flex-wrap: nowrap;
            gap: 8px;
            flex-shrink: 0;
            list-style: none;
            margin: 0;
            padding: 0;
            height: 100%;
            grid-column: 2;
            grid-row: 1;
            box-sizing: border-box;
        }

        @media (min-width: 1024px) {
            .adm-header-zone-right {
                grid-column: 3;
            }
        }

        .adm-header-dropdown {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            min-width: 40px;
            max-width: 40px;
            position: relative;
            flex-shrink: 0;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .adm-header-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            max-width: 40px;
            max-height: 40px;
            border-radius: 8px;
            background: transparent;
            border: 1px solid var(--adm-border);
            color: var(--adm-text);
            padding: 0;
            margin: 0;
            cursor: pointer;
            position: relative;
            text-decoration: none;
            flex-shrink: 0;
            box-sizing: border-box;
            transition: background 0.15s, border-color 0.15s;
        }

        .adm-header-btn:hover,
        .adm-header-btn:focus {
            background: rgba(255, 255, 255, 0.06);
            color: var(--adm-accent);
            border-color: var(--adm-accent);
        }

        .adm-header-btn svg {
            width: 20px;
            height: 20px;
            stroke: currentColor;
            display: block;
            flex-shrink: 0;
        }

        /* Badges strictly contained inside 40x40 button box */
        .adm-badge {
            position: absolute;
            top: 3px;
            right: 3px;
            background: var(--adm-accent, #eb8153);
            color: var(--adm-on-accent, #0f172a);
            width: 8px;
            height: 8px;
            min-width: 8px;
            min-height: 8px;
            border-radius: 50%;
            box-shadow: 0 0 0 2px var(--adm-sidebar);
            pointer-events: none;
            box-sizing: border-box;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            z-index: 2;
        }

        .adm-badge.has-count {
            top: 2px;
            right: 2px;
            min-width: 16px;
            height: 16px;
            padding: 0 3px;
            border-radius: 999px;
            font-size: 9px;
            font-weight: 800;
            line-height: 1;
        }

        /* Disable pulse pseudo-elements that create grey expanding circles */
        .pulse-css:before,
        .pulse-css:after,
        #message-pulse:before,
        #message-pulse:after,
        #notification-pulse:before,
        #notification-pulse:after {
            display: none;
            content: none;
        }

        /* Avatar Box: 40px fixed, no negative margins */
        .adm-header-profile-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            min-width: 40px;
            min-height: 40px;
            max-width: 40px;
            max-height: 40px;
            border-radius: 50%;
            background: transparent;
            border: 1px solid var(--adm-border);
            padding: 0;
            margin: 0;
            cursor: pointer;
            color: var(--adm-text);
            flex-shrink: 0;
            box-sizing: border-box;
            position: relative;
        }

        .adm-avatar-box {
            width: 34px;
            height: 34px;
            min-width: 34px;
            min-height: 34px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .adm-avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
        }

        .adm-avatar-initials {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: var(--adm-accent, #eb8153);
            color: var(--adm-on-accent, #0f172a);
            font-weight: 800;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
        }

        .adm-header-user-name {
            display: none;
        }

        /* Dropdown menus */
        .adm-header-dropdown .dropdown-menu {
            min-width: 220px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4);
            border: 1px solid var(--adm-border);
            background: var(--adm-surface);
            padding: 8px 0;
            margin-top: 8px;
            right: 0;
            left: auto;
        }

        .adm-header-dropdown .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 16px;
            font-size: 0.84rem;
            color: var(--adm-text);
            transition: background 0.15s;
        }

        .adm-header-dropdown .dropdown-item:hover,
        .adm-header-dropdown .dropdown-item:focus {
            background: rgba(255, 255, 255, 0.08);
            color: var(--adm-accent);
        }

        /* ══════════════════════════════════════
           RESPONSIVE VIEWPORT BREAKPOINTS
           ══════════════════════════════════════ */

        /* DESKTOP (>= 1200px) */
        @media (min-width: 1200px) {
            .nav-header {
                display: flex;
                position: fixed;
                top: 0;
                left: 0;
                width: 17.5rem;
                height: 65px;
                background: var(--adm-sidebar);
                border-bottom: 1px solid var(--adm-border);
                border-right: 1px solid var(--adm-border);
                align-items: center;
                justify-content: space-between;
                padding: 0 16px;
                z-index: 10001;
                box-sizing: border-box;
            }

            [data-sidebar-style] .header,
            #main-wrapper .header,
            .header {
                left: 17.5rem;
                width: calc(100% - 17.5rem);
                padding-left: 0;
            }

            .custom-hamburger {
                display: none;
            }

            .adm-header-brand {
                display: none;
            }

            .mobile-search-btn {
                display: none;
            }

            .adm-search-bar {
                display: block;
            }

            .adm-header-dropdown:last-child {
                width: auto;
                max-width: 220px;
            }

            .adm-header-profile-btn {
                width: auto;
                max-width: 220px;
                border-radius: 20px;
                padding: 2px 10px 2px 2px;
                gap: 8px;
            }

            .adm-header-user-name {
                display: inline-block;
                font-size: 0.85rem;
                font-weight: 600;
                color: var(--adm-text);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                max-width: 130px;
                padding-right: 4px;
            }

            #main-wrapper.menu-toggle .nav-header {
                width: 5rem;
                justify-content: center;
                padding: 0;
            }

            #main-wrapper.menu-toggle .nav-header .brand-title {
                display: none;
            }

            [data-sidebar-style] #main-wrapper.menu-toggle .header,
            #main-wrapper.menu-toggle .header {
                left: 5rem;
                width: calc(100% - 5rem);
                padding-left: 0;
            }
        }

        /* TABLET & LAPTOP (1024px – 1199px) */
        @media (min-width: 1024px) and (max-width: 1199px) {
            .nav-header {
                display: none;
            }

            .header {
                left: 0;
                width: 100%;
            }

            .custom-hamburger {
                display: inline-flex;
            }

            .adm-header-brand {
                display: flex;
            }

            .mobile-search-btn {
                display: none;
            }

            .adm-search-bar {
                display: block;
                max-width: 280px;
            }
        }

        /* TABLET (768px – 1023px) */
        @media (min-width: 768px) and (max-width: 1023px) {
            .nav-header {
                display: none;
            }

            .header {
                left: 0;
                width: 100%;
            }

            .custom-hamburger {
                display: inline-flex;
            }

            .adm-header-brand {
                display: flex;
                flex: 1 1 auto;
            }

            .adm-brand-text {
                max-width: none;
            }

            .mobile-search-btn {
                display: inline-flex;
            }

            .adm-search-bar {
                display: none;
            }
        }

        /* MOBILE (< 768px) */
        @media (max-width: 767px) {
            .nav-header {
                display: none;
            }

            .header {
                left: 0;
                width: 100%;
            }

            .adm-header-container {
                padding: 0 12px;
                gap: 8px;
            }

            .custom-hamburger {
                display: inline-flex;
            }

            .adm-header-brand {
                display: flex;
                flex: 1 1 auto;
                min-width: 0;
            }

            .adm-brand-text {
                max-width: none;
                font-size: 0.85rem;
                min-width: 0;
            }

            .mobile-search-btn {
                display: inline-flex;
            }

            .adm-search-bar {
                display: none;
            }
        }

        /* SMARTPHONE COMPACT (<= 480px) */
        @media (max-width: 480px) {
            .adm-brand-text-full {
                display: none;
            }
            .adm-brand-text-short {
                display: inline-block;
            }
            .adm-home-btn {
                display: none;
            }
        }

        /* ─── FULL-WIDTH SEARCH OVERLAY (When expanded below 1024px) ─── */
        .adm-header-zone-centre.search-expanded {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 65px;
            background: var(--adm-surface);
            z-index: 1050;
            display: flex;
            align-items: center;
            padding: 0 14px;
            box-sizing: border-box;
        }

        .adm-header-zone-centre.search-expanded .mobile-search-btn {
            display: none;
        }

        .adm-header-zone-centre.search-expanded .adm-search-bar {
            display: flex;
            width: 100%;
            max-width: 100%;
        }

        .adm-header-zone-centre.search-expanded .adm-search-input {
            flex: 1 1 auto;
            border-radius: 8px;
            padding-right: 44px;
        }

        .adm-header-zone-centre.search-expanded .adm-search-close-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            position: absolute;
            right: 4px;
            background: transparent;
            border: none;
            color: var(--adm-text);
            cursor: pointer;
            outline: none;
        }
    </style>

    @php
        $currentInstId = \App\Models\Institute::resolveContextInstituteId()
            ?? (auth()->check() ? auth()->user()?->institute_id : null)
            ?? 1;
        $brandFullName = $brandName ?? ($site_settings['admin_company_name'] ?? \App\Models\Institute::displayBrandName($currentInstId, 'TiT Institute'));
        $words = explode(' ', trim($brandFullName));
        $brandShortName = count($words) > 2 ? ($words[0] . ' ' . $words[1]) : $brandFullName;
    @endphp

    <div class="adm-header-container">
        {{-- ZONE 1: LEFT (Toggle + Brand - takes remaining space on mobile) --}}
        <div class="adm-header-zone-left">
            <button class="custom-hamburger" id="customHamburger" aria-label="Toggle menu" type="button">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <a href="{{ route('admin.dashboard') }}" class="adm-header-brand" title="{{ $brandFullName }}">
                @if(isset($site_settings['admin_logo']) && !empty($site_settings['admin_logo']))
                    <img src="{{ asset($site_settings['admin_logo']) }}" alt="Logo">
                @else
                    <svg viewBox="0 0 50 50" fill="none">
                        <rect width="50" height="50" rx="14" fill="var(--adm-accent, #EB8153)"/>
                        <path d="M17.5158 25.8619L19.8088 25.2475L14.8746 11.1774C14.5189 9.84988 15.8701 9.0998 16.8205 9.75055L33.0924 22.2055C33.7045 22.5589 33.8512 24.0717 32.6444 24.3951L30.3514 25.0095L35.2856 39.0796C35.6973 40.1334 34.4431 41.2455 33.3397 40.5064L17.0678 28.0515C16.2057 27.2477 16.5504 26.1205 17.5158 25.8619ZM18.685 14.2955L22.2224 24.6007L29.4633 22.6605L18.685 14.2955ZM31.4751 35.9615L27.8171 25.6886L20.5762 27.6288L31.4751 35.9615Z" fill="white"/>
                    </svg>
                @endif
                <span class="adm-brand-text adm-brand-text-full">{{ $brandFullName }}</span>
                <span class="adm-brand-text adm-brand-text-short">{{ $brandShortName }}</span>
            </a>
        </div>

        {{-- ZONE 2: CENTRE (Search bar on desktop / Overlay container) --}}
        <div class="adm-header-zone-centre" id="headerSearchContainer">
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

        {{-- ZONE 3: RIGHT (Fixed-size 40px Buttons + Avatar with margin) --}}
        <div class="adm-header-zone-right">
            {{-- Mobile Search Toggle Button --}}
            <button type="button" class="adm-header-btn mobile-search-btn" id="mobileSearchBtn" aria-label="Open search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>

            {{-- Home (hidden on <=480px to protect brand space) --}}
            <a class="adm-header-btn adm-home-btn" href="{{ config('services.frontend_url') }}" target="_blank" rel="noopener noreferrer" title="Home" aria-label="Home">
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
                <div class="adm-badge d-none" id="message-pulse">
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
                    <div class="adm-badge d-none" id="notification-pulse">
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

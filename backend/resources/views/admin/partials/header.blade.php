<div class="header">
    <style>
        @media (max-width: 991px) {
            /* Keep only search icon */
            .header-left .search_bar .form-control {
                display: none !important;
            }
            .header-left .search_bar .search_icon {
                position: static !important;
                transform: none !important;
                display: flex !important;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, 0.1) !important;
                width: 2.25rem;
                height: 2.25rem;
                border-radius: 0.375rem;
                cursor: pointer;
            }
            .header-left .search_bar form {
                margin: 0;
            }

            /* Keep only home icon */
            .home-text {
                display: none !important;
            }
            .header-right .nav-item .btn {
                padding: 0.4rem !important; /* Square button */
            }

            /* Ensure profile and other icons fit well */
            .header-right .nav-item {
                margin-right: 0.5rem !important;
            }
            .header-right .nav-link svg {
                width: 20px;
                height: 20px;
            }
            /* Profile icon resizing */
            .header-profile img, .header-profile .header-profile-initials {
                width: 32px !important;
                height: 32px !important;
            }

            /* Fix dropdown positioning on mobile */
            .navbar-nav .dropdown-menu {
                position: absolute !important;
                right: 0 !important;
                left: auto !important;
                z-index: 1050 !important;
                margin-top: 0.5rem;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
            }
            .nav-item.dropdown.show .dropdown-menu,
            .navbar-nav .dropdown-menu.show {
                display: block !important;
                max-width: 95vw !important;
            }
            .header-profile .dropdown-menu {
                min-width: 200px;
                right: 0 !important;
            }
            .notification_dropdown .dropdown-menu {
                min-width: 260px;
                right: 0 !important; 
            }
            
            /* Give header a high z-index to overlay body */
            .header, .header-content, .navbar {
                z-index: 1040 !important;
                overflow: visible !important;
            }
        }
    </style>
    <div class="header-content">
        <nav class="navbar navbar-expand">
            <div class="collapse navbar-collapse justify-content-between">
                <div class="header-left">
                    <div class="search_bar">
                        <form>
                            <input class="form-control" type="search" placeholder="Find something here..." aria-label="Search">
                            <span class="search_icon"><i class="mdi mdi-magnify"></i></span>
                        </form>
                    </div>
                </div>

                <ul class="navbar-nav header-right">
                    {{-- Home Button --}}
                    <li class="nav-item">
                        <a class="nav-link ai-icon" href="{{ env('FRONTEND_URL', 'http://localhost:4000') }}" target="_blank" rel="noopener noreferrer" title="Home Website">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </a>
                    </li>

                    {{-- Messages --}}
                    <li class="nav-item">
                        <a class="nav-link ai-icon" href="{{ route('admin.messages.index') }}" title="Messages" style="position: relative;">
                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.1667 5.83331H5.83333C4.54467 5.83331 3.5 6.878 3.5 8.16665V19.8333C3.5 21.122 4.54467 22.1666 5.83333 22.1666H22.1667C23.4553 22.1666 24.5 21.122 24.5 19.8333V8.16665C24.5 6.878 23.4553 5.83331 22.1667 5.83331Z" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M3.5 8.16665L14 15.1666L24.5 8.16665" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <div class="pulse-css d-none" id="message-pulse" style="width: 1.125rem; height: 1.125rem; background: #EB8153; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 0.0px; right: -0.3125rem; box-shadow: 0 0 0 0.125rem #fff;">
                                <span id="message-count" class="text-white d-none" style="font-size: 0.625rem; font-weight: bold; line-height: 1;">0</span>
                            </div>
                        </a>
                    </li>

                    {{-- Notifications --}}
                    <li class="nav-item dropdown notification_dropdown">
                        <a class="nav-link ai-icon" href="javascript:void(0)" role="button" data-toggle="dropdown">
                            <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M22.75 23.0417H5.25C4.84174 23.0417 4.44973 22.8791 4.16142 22.5891C3.87311 22.2991 3.71128 21.9058 3.71245 21.4958C3.71245 18.8033 4.75412 16.2133 6.65 14.3942V9.33333C6.65 6.65906 7.71235 4.09451 9.6033 2.2033C11.4945 0.31235 14.0591 -0.75 16.7333 -0.75C19.4076 -0.75 21.9721 0.31235 23.8633 2.2033C25.7543 4.09451 26.8167 6.65906 26.8167 9.33333V14.3942C28.7125 16.2133 29.7541 18.8033 29.7541 21.4958C29.7553 21.9058 29.5935 22.2991 29.3052 22.5891C29.0169 22.8791 28.6249 23.0417 28.2167 23.0417H22.75ZM7.11667 20.125H26.3417C26.0465 18.2808 25.1017 16.6067 23.6654 15.405C23.2798 15.0842 23.0567 14.6067 23.0567 14.1033V9.33333C23.0567 7.65363 22.3894 6.04272 21.2017 4.855C20.014 3.66728 18.403 3 16.7233 3C15.0436 3 13.4327 3.66728 12.245 4.855C11.0573 6.04272 10.39 7.65363 10.39 9.33333V14.1033C10.39 14.6067 10.1669 15.0842 9.78125 15.405C8.34493 16.6067 7.40013 18.2808 7.105 20.125H7.11667ZM16.7233 27.25C15.6558 27.25 14.6158 26.8833 13.7783 26.205C13.4358 25.9258 13.3758 25.42 13.6458 25.0667C13.9167 24.7133 14.4142 24.6533 14.7667 24.9325C15.305 25.3675 16.0075 25.5992 16.7233 25.5992C17.4392 25.5992 18.1417 25.3675 18.68 24.9325C19.0325 24.6533 19.53 24.7133 19.8008 25.0667C20.0717 25.42 20.0117 25.9258 19.6683 26.205C18.8308 26.8833 17.7908 27.25 16.7233 27.25Z" fill="#3D4461"/>
                            </svg>
                            <div class="pulse-css d-none" id="notification-pulse" style="width: 1.125rem; height: 1.125rem; background: #EB8153; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 0.0px; right: -0.3125rem; box-shadow: 0 0 0 0.125rem #fff;">
                                <span id="notification-count" class="text-white d-none" style="font-size: 0.625rem; font-weight: bold; line-height: 1;">0</span>
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

                    {{-- Profile --}}
                    <li class="nav-item dropdown header-profile">
                        <a class="nav-link" href="#" role="button" data-toggle="dropdown">
                            @if(Auth::user()->avatar)
                                <img src="{{ asset(Auth::user()->avatar) }}" width="40" height="40" alt="" style="border-radius: 50%; object-fit: cover;">
                            @else
                                <div class="header-profile-initials" style="width: 2.5rem; height: 2.5rem; border-radius: 50%; background: #EB8153; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
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

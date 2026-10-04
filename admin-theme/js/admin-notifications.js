const NOTIFICATION_ICON_HTML = `
<li class="nav-item dropdown notification_dropdown">
    <a class="nav-link ai-icon" href="javascript:void(0)" role="button" data-toggle="dropdown">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.75 23.0417H5.25C4.84174 23.0417 4.44973 22.8791 4.16142 22.5891C3.87311 22.2991 3.71128 21.9058 3.71245 21.4958C3.71245 18.8033 4.75412 16.2133 6.65 14.3942V9.33333C6.65 6.65906 7.71235 4.09451 9.6033 2.2033C11.4945 0.31235 14.0591 -0.75 16.7333 -0.75C19.4076 -0.75 21.9721 0.31235 23.8633 2.2033C25.7543 4.09451 26.8167 6.65906 26.8167 9.33333V14.3942C28.7125 16.2133 29.7541 18.8033 29.7541 21.4958C29.7553 21.9058 29.5935 22.2991 29.3052 22.5891C29.0169 22.8791 28.6249 23.0417 28.2167 23.0417H22.75ZM7.11667 20.125H26.3417C26.0465 18.2808 25.1017 16.6067 23.6654 15.405C23.2798 15.0842 23.0567 14.6067 23.0567 14.1033V9.33333C23.0567 7.65363 22.3894 6.04272 21.2017 4.855C20.014 3.66728 18.403 3 16.7233 3C15.0436 3 13.4327 3.66728 12.245 4.855C11.0573 6.04272 10.39 7.65363 10.39 9.33333V14.1033C10.39 14.6067 10.1669 15.0842 9.78125 15.405C8.34493 16.6067 7.40013 18.2808 7.105 20.125H7.11667ZM16.7233 27.25C15.6558 27.25 14.6158 26.8833 13.7783 26.205C13.4358 25.9258 13.3758 25.42 13.6458 25.0667C13.9167 24.7133 14.4142 24.6533 14.7667 24.9325C15.305 25.3675 16.0075 25.5992 16.7233 25.5992C17.4392 25.5992 18.1417 25.3675 18.68 24.9325C19.0325 24.6533 19.53 24.7133 19.8008 25.0667C20.0717 25.42 20.0117 25.9258 19.6683 26.205C18.8308 26.8833 17.7908 27.25 16.7233 27.25Z" fill="#3D4461"/>
        </svg>
        <div class="pulse-css d-none" id="notification-pulse"></div>
    </a>
    <div class="dropdown-menu dropdown-menu-right">
        <div id="DZ_W_Notification1" class="set-height widget-media dz-scroll p-3">
            <ul class="timeline" id="notification-list">
                <li class="text-center py-3">No new notifications</li>
            </ul>
        </div>
        <a class="all-notification" href="/admin/notifications">See all notifications <i class="ti-arrow-right"></i></a>
    </div>
</li>
`;

const MESSAGE_ICON_HTML = `
<li class="nav-item">
    <a class="nav-link ai-icon" href="/admin/messages" title="Messages" style="position: relative;">
        <svg width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.1667 5.83331H5.83333C4.54467 5.83331 3.5 6.878 3.5 8.16665V19.8333C3.5 21.122 4.54467 22.1666 5.83333 22.1666H22.1667C23.4553 22.1666 24.5 21.122 24.5 19.8333V8.16665C24.5 6.878 23.4553 5.83331 22.1667 5.83331Z" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M3.5 8.16665L14 15.1666L24.5 8.16665" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="pulse-css d-none" id="message-pulse" style="width: 18px; height: 18px; background: #EB8153; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: absolute; top: 0px; right: -5px; box-shadow: 0 0 0 2px #fff;">
            <span id="message-count" class="text-white d-none" style="font-size: 10px; font-weight: bold; line-height: 1;">0</span>
        </div>
    </a>
</li>
`;

function initNotifications() {
    // Icons are now manually added to the Blade templates for better reliability.
    // We only need to initialize the data fetching and Pusher here.

    // 1. Fetch Initial Data
    fetchNotifications();
    fetchMessageCount();

    // 2. Initialize Pusher if available
    if (typeof Pusher !== 'undefined' && window.USER_ID) {
        try {
            const pusher = new Pusher(window.PUSHER_KEY, {
                cluster: window.PUSHER_CLUSTER,
                encrypted: true,
                authEndpoint: '/broadcasting/auth',
                auth: {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                }
            });

            const channel = pusher.subscribe(`private-App.Models.User.${window.USER_ID}`);
            channel.bind('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', function (data) {
                handleNewNotification(data);
            });
        } catch (e) {
            console.error("Pusher initialization failed", e);
        }
    }
}

function fetchNotifications() {
    fetch('/admin/notifications', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(res => res.json())
        .then(data => {
            if (data.notifications) {
                renderNotifications(data.notifications, data.systemAlerts);
                updateNotificationBadge(data.unreadCount || 0, data.systemAlerts);
            }
        })
        .catch(err => console.error("Error fetching notifications:", err));
}

function renderNotifications(notifications, systemAlerts) {
    const list = document.getElementById('notification-list');
    if (!list) return;

    let html = '';
    let count = 1;

    // 1. Prioritized System Alerts
    if (systemAlerts) {
        if (systemAlerts.pendingApprovals > 0) {
            html += `
                <li class="p-2 mb-2" style="background: rgba(235, 129, 83, 0.1); border-radius: 6px; border-left: 4px solid #EB8153;">
                    <a href="/admin/students" class="d-flex align-items-center" style="text-decoration: none; color: inherit;">
                        <div class="mr-3 text-warning"><i class="fa fa-user-clock"></i></div>
                        <div>
                            <h6 class="mb-0" style="font-size: 13px; font-weight: 700;">${count++}. Pending Approvals: ${systemAlerts.pendingApprovals}</h6>
                            <small class="text-muted">Students waiting for confirmation</small>
                        </div>
                    </a>
                </li>
            `;
        }
        if (systemAlerts.pendingPayments > 0) {
            html += `
                <li class="p-2 mb-2" style="background: rgba(235, 129, 83, 0.1); border-radius: 6px; border-left: 4px solid #EB8153;">
                    <a href="/admin/payments" class="d-flex align-items-center" style="text-decoration: none; color: inherit;">
                        <div class="mr-3 text-danger"><i class="fa fa-money-bill-wave"></i></div>
                        <div>
                            <h6 class="mb-0" style="font-size: 13px; font-weight: 700;">${count++}. Pending Payments: ${systemAlerts.pendingPayments}</h6>
                            <small class="text-muted">New payments to verify</small>
                        </div>
                    </a>
                </li>
            `;
        }
    }

    // 2. Regular Notifications
    if (!notifications || notifications.length === 0) {
        if (html === '') {
            html = '<li class="text-center py-3">No new notifications</li>';
        }
    } else {
        html += notifications.map(n => {
            let nData = n.data;
            if (typeof nData === 'string') {
                try {
                    nData = JSON.parse(nData);
                } catch (e) {
                    console.error("Failed to parse notification data", e);
                }
            }

            const message = nData.message || 'New notification';
            const link = nData.link || 'javascript:void(0)';
            const time = n.created_at ? new Date(n.created_at).toLocaleString() : '';

            return `
                <li>
                    <a href="${link}" class="timeline-panel" style="text-decoration: none; color: inherit; display: block;">
                        <div class="media-body">
                            <h6 class="mb-1" style="font-size: 13px;">${count++}. ${message}</h6>
                            <small class="d-block" style="color: rgba(0,0,0,0.5);">${time}</small>
                        </div>
                    </a>
                </li>
            `;
        }).join('');
    }

    list.innerHTML = html;
}

function updateNotificationBadge(unreadCount, systemAlerts) {
    const pulse = document.getElementById('notification-pulse');
    const badge = document.getElementById('notification-count');
    
    // Total count including system alerts for the badge
    let totalCount = parseInt(unreadCount);
    if (systemAlerts) {
        if (systemAlerts.pendingApprovals > 0) totalCount++;
        if (systemAlerts.pendingPayments > 0) totalCount++;
    }

    if (pulse) {
        if (totalCount > 0) {
            pulse.classList.remove('d-none');
            if (badge) {
                badge.innerText = totalCount > 9 ? '9+' : totalCount;
                badge.classList.remove('d-none');
            }
        } else {
            pulse.classList.add('d-none');
            if (badge) badge.classList.add('d-none');
        }
    }
}

function handleNewNotification(data) {
    // Refresh data
    fetchNotifications();

    // Show toast if library is available
    if (typeof toastr !== 'undefined') {
        const message = data.message || (data.data ? data.data.message : 'New notification');
        toastr.info(message);
    }
}

function fetchMessageCount() {
    fetch('/admin/messages/unread-count', {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(res => res.json())
        .then(data => {
            const pulse = document.getElementById('message-pulse');
            const badge = document.getElementById('message-count');
            const msgCount = parseInt(data.unread_count) || 0;

            if (pulse) {
                if (msgCount > 0) {
                    pulse.classList.remove('d-none');
                    if (badge) {
                        badge.innerText = msgCount > 9 ? '9+' : msgCount;
                        badge.classList.remove('d-none');
                    }
                } else {
                    pulse.classList.add('d-none');
                    if (badge) badge.classList.add('d-none');
                }
            }
        })
        .catch(err => console.error("Error fetching message count:", err));
}

function markAllNotificationsAsRead() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    if (!csrfToken) return;

    fetch('/admin/notifications/mark-all-as-read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken.content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    })
        .then(() => fetchNotifications())
        .catch(err => console.error("Error marking as read:", err));
}

document.addEventListener('DOMContentLoaded', initNotifications);

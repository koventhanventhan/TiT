@extends('admin.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row page-titles mx-0">
        <div class="col-sm-6 p-md-0">
            <div class="welcome-text">
                <h4>All Notifications</h4>
                <p class="mb-0">Stay updated with the latest alerts</p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Notification History</h4>
                    <button class="btn btn-primary btn-sm" onclick="markAllNotificationsAsRead()">Mark all as read</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-responsive-md">
                            <thead>
                                <tr>
                                    <th><strong>STATUS</strong></th>
                                    <th><strong>MESSAGE</strong></th>
                                    <th><strong>DATE</strong></th>
                                    <th><strong>ACTION</strong></th>
                                </tr>
                            </thead>
                            <tbody id="full-notification-list">
                                @forelse($notifications as $notification)
                                <tr class="{{ $notification->read_at ? '' : 'table-light font-weight-bold' }}">
                                    <td>
                                        @if($notification->read_at)
                                            <span class="badge light badge-success">Read</span>
                                        @else
                                            <span class="badge light badge-warning">Unread</span>
                                        @endif
                                    </td>
                                    <td>{{ $notification->data['message'] ?? 'No message' }}</td>
                                    <td>{{ $notification->created_at->diffForHumans() }}</td>
                                    <td>
                                        @if(!$notification->read_at)
                                            <button class="btn btn-outline-primary btn-xxs" onclick="markAsRead('{{ $notification->id }}')">Mark read</button>
                                        @endif
                                        @if(isset($notification->data['link']) && $notification->data['link'] !== '#')
                                            <a href="{{ $notification->data['link'] }}" class="btn btn-outline-info btn-xxs">View</a>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No notifications found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $notifications->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function markAsRead(id) {
    fetch('/admin/notifications/mark-as-read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    }).then(() => location.reload());
}

function markAllNotificationsAsRead() {
    fetch('/admin/notifications/mark-all-as-read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json'
        }
    }).then(() => location.reload());
}
</script>
@endsection

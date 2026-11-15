@php
    $isSuperAdminArea = request()->is('super-admin*')
        || (request()->is('livewire/*') && str_contains((string) request()->header('referer'), '/super-admin'));
    $user = auth()->user() ?: (auth('web')->check() ? auth('web')->user() : null);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $isSuperAdminArea ? 'This area is for the platform owner' : 'Access Restricted (403)' }}</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --accent-color: #6366f1;
            --accent-hover: #4f46e5;
            --warning-color: #f59e0b;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .container {
            width: 100%;
            max-width: 32rem;
            background: var(--card-bg);
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            padding: 2.5rem 2rem;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3), 0 10px 10px -5px rgba(0, 0, 0, 0.2);
        }
        .icon-wrap {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(245, 158, 11, 0.15);
            color: var(--warning-color);
        }
        .title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-main);
            line-height: 1.3;
        }
        .desc {
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            background-color: var(--accent-color);
            color: #ffffff !important;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            text-decoration: none;
            transition: background-color 0.15s ease;
            min-height: 44px;
        }
        .btn-action:hover, .btn-action:focus {
            background-color: var(--accent-hover);
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container" role="main">
        <div class="icon-wrap" aria-hidden="true">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        @if($isSuperAdminArea)
            <h1 class="title">This area is for the platform owner</h1>
            <p class="desc">
                The Super Administration panel is restricted to the platform owner. As an institute administrator or staff member, please use your institute administration dashboard to manage students, classes, and settings.
            </p>
            <a href="{{ route('admin.dashboard') }}" class="btn-action" data-testid="back-to-admin-link">
                &larr; Return to Institute Dashboard
            </a>
        @else
            <h1 class="title">Access Restricted</h1>
            <p class="desc">
                {{ $exception->getMessage() ?: 'You do not have permission to view or manage this resource.' }}
            </p>
            @if($user && in_array($user->role, ['admin', 'teacher']))
                <a href="{{ route('admin.dashboard') }}" class="btn-action">
                    &larr; Return to Dashboard
                </a>
            @else
                <a href="/" class="btn-action">
                    &larr; Return to Home
                </a>
            @endif
        @endif
    </div>
</body>
</html>

@php
    $reg = config('plans.features', [
        'zoom' => 'Zoom Classes',
        'ai_assistant' => 'AI Learning Assistant',
        'whatsapp' => 'WhatsApp Messaging',
        'custom_domain' => 'Custom Domain',
        'online_payments' => 'Online Payments (PayHere)',
        'advanced_reports' => 'Advanced Reports',
    ]);
    $featureName = $reg[$feature] ?? ucwords(str_replace('_', ' ', $feature));
    $instName = $institute?->name ?? 'Institute';
    $email = config('tenant.platform_contact_email');
    $phone = config('tenant.platform_contact_phone');
    $whatsapp = config('tenant.platform_contact_whatsapp');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Feature Not In Plan - {{ $instName }}</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #f59e0b;
            --border-color: #334155;
            --btn-bg: #3b82f6;
            --btn-text: #ffffff;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }
        .container {
            max-width: 540px;
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2.25rem 2rem;
            text-align: center;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
        }
        .icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(245, 158, 11, 0.15);
            color: var(--accent);
            margin-bottom: 1.25rem;
        }
        .icon svg { width: 34px; height: 34px; }
        h1 {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            line-height: 1.3;
        }
        .desc {
            color: var(--text-muted);
            font-size: 0.95rem;
            line-height: 1.55;
            margin-bottom: 1.5rem;
        }
        .feature-badge {
            display: inline-block;
            background: rgba(245, 158, 11, 0.2);
            color: #fde68a;
            border: 1px solid rgba(245, 158, 11, 0.4);
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .contacts {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            margin: 1.25rem 0;
            text-align: left;
            font-size: 0.9rem;
        }
        .contacts-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-main);
            font-size: 0.825rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .contact-item {
            color: var(--text-muted);
            margin-top: 0.35rem;
            word-break: break-all;
        }
        .contact-item a {
            color: var(--accent);
            text-decoration: none;
        }
        .contact-item a:hover { text-decoration: underline; }
        .actions {
            margin-top: 1.5rem;
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.6rem 1.25rem;
            border-radius: 6px;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: opacity 0.15s;
        }
        .btn:hover { opacity: 0.9; }
        .btn-primary {
            background: var(--btn-bg);
            color: var(--btn-text);
        }
        .btn-outline {
            background: transparent;
            color: var(--text-muted);
            border-color: var(--border-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
            </svg>
        </div>

        <div class="feature-badge">{{ $featureName }}</div>
        <h1>This feature is not included in your plan</h1>

        <p class="desc">
            Your subscription for <strong>{{ $instName }}</strong> does not include access to <strong>{{ $featureName }}</strong>.
            Please contact the platform owner to upgrade your plan.
        </p>

        @if($email || $phone || $whatsapp)
            <div class="contacts">
                <div class="contacts-title">Platform Contact</div>
                @if($email)
                    <div class="contact-item">Email: <a href="mailto:{{ $email }}">{{ $email }}</a></div>
                @endif
                @if($phone)
                    <div class="contact-item">Phone: <a href="tel:{{ $phone }}">{{ $phone }}</a></div>
                @endif
                @if($whatsapp)
                    <div class="contact-item">WhatsApp: <a href="https://wa.me/{{ preg_replace('/\D/', '', $phone ?? $whatsapp) }}" target="_blank" rel="noopener">{{ $whatsapp }}</a></div>
                @endif
            </div>
        @endif

        <div class="actions">
            @if(auth()->check() && auth()->user()->role === 'admin')
                <a href="{{ route('admin.subscription') }}" class="btn btn-primary">View Your Plan</a>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline">Dashboard</a>
            @else
                <a href="/" class="btn btn-outline">Back to Home</a>
            @endif
        </div>
    </div>
</body>
</html>


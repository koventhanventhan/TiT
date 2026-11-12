@php
    $instituteName = \App\Models\Institute::displayBrandName($institute->id ?? null, $institute->name ?? 'Institute');
    $logoUrl = !empty($institute->logo_path) ? asset($institute->logo_path) : null;
    $email = $contactEmail ?? config('tenant.platform_contact_email');
    $phone = $contactPhone ?? config('tenant.platform_contact_phone');
    $whatsapp = $contactWhatsapp ?? config('tenant.platform_contact_whatsapp');
    $hasContact = !empty($email) || !empty($phone) || !empty($whatsapp);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title }} - {{ $instituteName }}</title>
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --accent-color: #3b82f6;
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
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
        }
        .logo {
            max-height: 3.5rem;
            max-width: 12rem;
            object-fit: contain;
            margin-bottom: 1.5rem;
        }
        .institute-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 1rem;
        }
        .status-icon {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            margin: 0 auto 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }
        .icon-expired {
            background-color: #fef2f2;
            color: var(--danger-color);
        }
        .icon-suspended {
            background-color: #fffbeb;
            color: var(--warning-color);
        }
        h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: var(--text-main);
            line-height: 1.3;
        }
        p.description {
            font-size: 0.95rem;
            line-height: 1.6;
            color: var(--text-muted);
            margin-bottom: 1.75rem;
        }
        .contact-box {
            background-color: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 1.25rem;
            text-align: left;
            margin-top: 1rem;
        }
        .contact-title {
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 0.75rem;
        }
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }
        .contact-item:last-child {
            margin-bottom: 0;
        }
        .contact-item a {
            color: var(--accent-color);
            text-decoration: none;
            word-break: break-all;
        }
        .contact-item a:hover {
            text-decoration: underline;
        }
        .footer-note {
            margin-top: 1.75rem;
            font-size: 0.8rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $instituteName }}" class="logo">
        @endif
        <div class="institute-title">{{ $instituteName }}</div>

        <div class="status-icon {{ $isExpired ? 'icon-expired' : 'icon-suspended' }}">
            @if($isExpired)
                &#9203;
            @else
                &#128274;
            @endif
        </div>

        <h1>{{ $title }}</h1>

        <p class="description">
            @if($isExpired)
                The subscription for this institute has ended. Public access and student portals are currently unavailable. If you are an administrator, please sign in or contact the platform owner to renew.
            @else
                This institute is temporarily unavailable. Public access is currently paused. Please check back later or contact the administrator for more information.
            @endif
        </p>

        @if($hasContact)
            <div class="contact-box">
                <div class="contact-title">Platform Support</div>
                @if(!empty($email))
                    <div class="contact-item">
                        <span>Email:</span>
                        <a href="mailto:{{ $email }}">{{ $email }}</a>
                    </div>
                @endif
                @if(!empty($phone))
                    <div class="contact-item">
                        <span>Phone:</span>
                        <a href="tel:{{ $phone }}">{{ $phone }}</a>
                    </div>
                @endif
                @if(!empty($whatsapp))
                    <div class="contact-item">
                        <span>WhatsApp:</span>
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}" target="_blank" rel="noopener noreferrer">{{ $whatsapp }}</a>
                    </div>
                @endif
            </div>
        @endif

        <div class="footer-note">
            If you are an administrator of this institute, you may still access the administration portal to view your subscription details.
        </div>
    </div>
</body>
</html>

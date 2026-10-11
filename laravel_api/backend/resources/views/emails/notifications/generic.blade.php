<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
@php
    $color = $data['color'] ?? '#4f46e5';
    $icon = $data['icon'] ?? '🔔';
    $lines = $data['lines'] ?? [];
    $details = $data['details'] ?? [];
@endphp
<body style="margin:0;padding:0;background:#f4f1fa;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1fa;padding:32px 0;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(79,70,229,0.08);">
    <!-- Header -->
    <tr>
        <td style="background:{{ $color }};padding:32px 40px;text-align:center;">
            <h1 style="color:#ffffff;font-size:22px;margin:0 0 4px;">{{ $icon }} {{ $data['heading'] ?? 'Notification' }}</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">TiT Education</p>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:36px 40px;">
            @if(!empty($data['greeting']))
            <h2 style="color:#1e1b4b;font-size:18px;margin:0 0 16px;">{{ $data['greeting'] }}</h2>
            @endif

            @php $bodyText = $data['message'] ?? $data['update_details'] ?? null; @endphp
            @if(!empty($bodyText))
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">{!! nl2br(e($bodyText)) !!}</p>
            @endif

            @if(!empty($details))
            <table cellpadding="0" cellspacing="0" style="background:#f8fafc;border-radius:10px;width:100%;margin:20px 0;border-left:4px solid {{ $color }};">
                <tr><td style="padding:20px;">
                    @foreach($details as $label => $value)
                        @if($value !== null && $value !== '')
                        <p style="color:#334155;font-size:14px;margin:0 0 6px;">
                            <span style="color:#64748b;">{{ $label }}:</span> <strong>{{ $value }}</strong>
                        </p>
                        @endif
                    @endforeach
                </td></tr>
            </table>
            @endif

            @foreach($lines as $line)
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 12px;">{{ $line }}</p>
            @endforeach

            @if(!empty($data['button_url']))
            <p style="text-align:center;margin:28px 0 8px;">
                <a href="{{ $data['button_url'] }}" style="display:inline-block;background:{{ $color }};color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:13px 32px;border-radius:10px;">
                    {{ $data['button_text'] ?? 'Open' }}
                </a>
            </p>
            @endif

            @if(!empty($data['footer_note']))
            <p style="color:#94a3b8;font-size:12px;line-height:1.6;margin:20px 0 0;">{{ $data['footer_note'] }}</p>
            @endif
        </td>
    </tr>
    <!-- Footer -->
    <tr>
        <td style="background:#1e1b4b;padding:20px 40px;text-align:center;">
            <p style="color:rgba(255,255,255,0.7);font-size:12px;margin:0;">© {{ date('Y') }} TiT Education. All rights reserved.</p>
        </td>
    </tr>
</table>
</td></tr>
</table>
</body>
</html>

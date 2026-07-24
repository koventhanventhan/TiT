<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin:0;padding:0;background:#f4f1fa;font-family:'Segoe UI',Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f1fa;padding:32px 0;">
<tr><td align="center">
<table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(79,70,229,0.08);">
    <!-- Header -->
    <tr>
        <td style="background:linear-gradient(135deg,#1e1b4b 0%,#4f46e5 100%);padding:32px 40px;text-align:center;">
            <h1 style="color:#ffffff;font-size:22px;margin:0 0 4px;">🔔 Admin Alert</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">TiT Education System</p>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:36px 40px;">
            <h2 style="color:#1e1b4b;font-size:20px;margin:0 0 16px;">{{ $data['alert_title'] ?? 'System Notification' }}</h2>
            <table cellpadding="0" cellspacing="0" style="background:#f5f3ff;border-radius:10px;width:100%;margin:20px 0;border-left:4px solid #4f46e5;">
                <tr><td style="padding:20px;">
                    <p style="color:#4338ca;font-size:15px;margin:0;line-height:1.7;">
                        {{ $data['alert_message'] ?? 'No details provided.' }}
                    </p>
                </td></tr>
            </table>
            @if(!empty($data['alert_details']))
            <table cellpadding="0" cellspacing="0" style="background:#f9fafb;border-radius:10px;width:100%;margin:16px 0;">
                <tr><td style="padding:16px 20px;">
                    <p style="color:#6b7280;font-size:13px;margin:0;line-height:1.6;">
                        {!! nl2br(e($data['alert_details'])) !!}
                    </p>
                </td></tr>
            </table>
            @endif
            <p style="color:#9ca3af;font-size:13px;margin:24px 0 0;border-top:1px solid #e5e7eb;padding-top:16px;">
                This is an automated alert from TiT Education system. Sent at {{ now()->format('Y-m-d H:i:s') }}.
            </p>
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

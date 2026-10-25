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
        <td style="background:linear-gradient(135deg,#3b82f6 0%,#1d4ed8 100%);padding:32px 40px;text-align:center;">
            <h1 style="color:#ffffff;font-size:22px;margin:0 0 4px;">📋 Offline Payment Request Received</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">TiT Education</p>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:36px 40px;">
            <h2 style="color:#1e1b4b;font-size:20px;margin:0 0 16px;">Dear {{ $data['student_name'] ?? 'Student' }},</h2>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                We received your offline payment request for <strong>{{ $data['month'] ?? 'this month' }}</strong> – Rs {{ isset($data['amount']) ? number_format($data['amount'], 2) : '0.00' }}. Please pay at the institute / bank and send the slip; your access opens as soon as the admin confirms. You will get your receipt by e-mail and WhatsApp.
            </p>
            <table cellpadding="0" cellspacing="0" style="background:#eff6ff;border-radius:10px;width:100%;margin:20px 0;border-left:4px solid #3b82f6;">
                <tr><td style="padding:15px 20px;">
                    <p style="color:#1e40af;font-size:16px;margin:0;font-weight:600;">
                        Requested Amount: Rs. {{ isset($data['amount']) ? number_format($data['amount'], 2) : '0.00' }}
                    </p>
                    <p style="color:#6b7280;font-size:13px;margin:4px 0 0;">
                        Billing Month: {{ $data['month'] ?? 'Current Month' }}
                    </p>
                </td></tr>
            </table>
            <p style="color:#4b5563;font-size:14px;line-height:1.6;margin:0 0 16px;">
                உங்கள் ஆஃப்லைன் கட்டணக் கோரிக்கை பெறப்பட்டது. தயவுசெய்து கல்வி நிறுவனத்தில் அல்லது வங்கியில் கட்டணத்தைச் செலுத்தி பற்றுச்சீட்டை அனுப்பவும். நிர்வாகி உறுதிப்படுத்தியவுடன் உங்கள் அனுமதி திறக்கப்படும்.
            </p>
            <p style="color:#9ca3af;font-size:13px;margin:24px 0 0;border-top:1px solid #e5e7eb;padding-top:16px;">
                If you have any questions or have already handed in your slip, please contact the institute office.
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

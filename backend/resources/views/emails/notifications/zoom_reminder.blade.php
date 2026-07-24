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
        <td style="background:linear-gradient(135deg,#3b82f6 0%,#6366f1 100%);padding:32px 40px;text-align:center;">
            <h1 style="color:#ffffff;font-size:22px;margin:0 0 4px;">📹 Zoom Class Reminder</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">TiT Education</p>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:36px 40px;">
            <h2 style="color:#1e1b4b;font-size:20px;margin:0 0 16px;">Your class is starting soon! 🕐</h2>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                You have an upcoming Zoom class. Please be ready to join on time.
            </p>
            <table cellpadding="0" cellspacing="0" style="background:#eff6ff;border-radius:10px;width:100%;margin:20px 0;border-left:4px solid #3b82f6;">
                <tr><td style="padding:20px;">
                    <p style="color:#1e40af;font-size:16px;margin:0 0 8px;font-weight:700;">
                        {{ $data['class_title'] ?? 'Zoom Class' }}
                    </p>
                    <p style="color:#1e40af;font-size:14px;margin:0;">
                        🕐 Time: <strong>{{ $data['class_time'] ?? 'Check your schedule' }}</strong>
                    </p>
                </td></tr>
            </table>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                உங்கள் Zoom வகுப்பு விரைவில் தொடங்கும். தயவுசெய்து சரியான நேரத்தில் இணையுங்கள்.
            </p>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 20px;">
                Login to your student dashboard to find the Zoom link.
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

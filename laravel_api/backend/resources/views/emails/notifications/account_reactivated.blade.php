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
        <td style="background:linear-gradient(135deg,#059669 0%,#10b981 100%);padding:32px 40px;text-align:center;">
            <h1 style="color:#ffffff;font-size:22px;margin:0 0 4px;">✅ Account Reactivated</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">TiT Education</p>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:36px 40px;">
            <h2 style="color:#1e1b4b;font-size:20px;margin:0 0 16px;">Dear {{ $data['student_name'] ?? 'Student' }},</h2>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                Good news! Your account has been <strong>reactivated</strong>. All your access to live classes, recordings, study materials, and timetable has been fully restored.
            </p>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                நற்செய்தி! உங்கள் கணக்கு வெற்றிகரமாக <strong>மீண்டும் செயல்படுத்தப்பட்டது</strong>. நேரடி வகுப்புகள், பாடக்குறிப்புகள் மற்றும் கால அட்டவணைக்கான அனைத்து அணுகல்களும் மீண்டும் வழங்கப்பட்டுள்ளன.
            </p>
            <table cellpadding="0" cellspacing="0" style="background:#ecfdf5;border-radius:10px;width:100%;margin:20px 0;border-left:4px solid #059669;">
                <tr><td style="padding:20px;">
                    <p style="color:#065f46;font-size:14px;margin:0;font-weight:600;line-height:1.6;">
                        ✓ Account Status: Active<br>
                        ✓ All features and classes are now accessible.
                    </p>
                </td></tr>
            </table>
            <div style="text-align:center;margin:30px 0 10px;">
                <a href="{{ $data['button_url'] ?? config('app.frontend_url', 'http://localhost:4000') }}" style="display:inline-block;background:linear-gradient(135deg,#059669 0%,#10b981 100%);color:#ffffff;padding:14px 32px;font-size:15px;font-weight:600;text-decoration:none;border-radius:8px;box-shadow:0 4px 12px rgba(5,150,105,0.25);">
                    Login to Dashboard / லாகின் செய்யவும்
                </a>
            </div>
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

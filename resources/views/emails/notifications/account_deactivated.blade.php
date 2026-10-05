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
        <td style="background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%);padding:32px 40px;text-align:center;">
            <h1 style="color:#ffffff;font-size:22px;margin:0 0 4px;">⚠️ Account Deactivated</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">TiT Education</p>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:36px 40px;">
            <h2 style="color:#1e1b4b;font-size:20px;margin:0 0 16px;">Dear {{ $data['student_name'] ?? 'Student' }},</h2>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                Your account has been <strong>deactivated</strong> by the admin. If you believe this is an error, please contact the admin.
            </p>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                உங்கள் கணக்கு admin-ஆல் <strong>செயலிழக்கச் செய்யப்பட்டது</strong>. இது தவறு என்று நீங்கள் நம்பினால், admin-ஐ தொடர்புகொள்ளவும்.
            </p>
            <table cellpadding="0" cellspacing="0" style="background:#fef2f2;border-radius:10px;width:100%;margin:20px 0;border-left:4px solid #ef4444;">
                <tr><td style="padding:20px;">
                    <p style="color:#991b1b;font-size:14px;margin:0;font-weight:600;">
                        Your access to classes and materials has been temporarily suspended.
                    </p>
                </td></tr>
            </table>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 20px;">
                To reactivate your account, please reach out to the admin team.
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

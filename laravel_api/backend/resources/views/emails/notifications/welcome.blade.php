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
        <td style="background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);padding:32px 40px;text-align:center;">
            <h1 style="color:#ffffff;font-size:22px;margin:0 0 4px;">🎓 TiT Education</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">Welcome Aboard!</p>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:36px 40px;">
            <h2 style="color:#1e1b4b;font-size:20px;margin:0 0 16px;">Welcome, {{ $data['student_name'] ?? 'Student' }}! 🎉</h2>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                Thank you for registering with <strong>TiT Education</strong>. Your account has been created successfully.
            </p>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                TiT Education-ல பதிவு செய்ததற்கு நன்றி. உங்கள் கணக்கு வெற்றிகரமாக உருவாக்கப்பட்டது.
            </p>
            <table cellpadding="0" cellspacing="0" style="background:#f5f3ff;border-radius:10px;width:100%;margin:20px 0;">
                <tr><td style="padding:20px;">
                    <p style="color:#6d28d9;font-size:14px;margin:0 0 8px;font-weight:600;">Your Details:</p>
                    <p style="color:#4b5563;font-size:14px;margin:0;">
                        <strong>Username:</strong> {{ $data['username'] ?? 'N/A' }}
                    </p>
                </td></tr>
            </table>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 20px;">
                Please complete your payment to activate your account. Admin will review and confirm your registration.
            </p>
            <p style="color:#9ca3af;font-size:13px;margin:24px 0 0;border-top:1px solid #e5e7eb;padding-top:16px;">
                If you did not register, please ignore this email.
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

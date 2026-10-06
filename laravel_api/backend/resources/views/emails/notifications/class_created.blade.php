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
        <td style="background:linear-gradient(135deg,#10b981 0%,#059669 100%);padding:32px 40px;text-align:center;">
            <h1 style="color:#ffffff;font-size:22px;margin:0 0 4px;">📅 New Class Scheduled</h1>
            <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">TiT Education</p>
        </td>
    </tr>
    <!-- Body -->
    <tr>
        <td style="padding:36px 40px;">
            <h2 style="color:#1e1b4b;font-size:20px;margin:0 0 16px;">A new class has been scheduled! 🎉</h2>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                A new class matching your grade and subjects has just been scheduled. Please check the details below.
            </p>
            <table cellpadding="0" cellspacing="0" style="background:#ecfdf5;border-radius:10px;width:100%;margin:20px 0;border-left:4px solid #10b981;">
                <tr><td style="padding:20px;">
                    <p style="color:#065f46;font-size:16px;margin:0 0 8px;font-weight:700;">
                        {{ $data['class_title'] ?? 'New Class' }}
                    </p>
                    <p style="color:#065f46;font-size:14px;margin:0 0 4px;">
                        🕐 Time: <strong>{{ $data['class_time'] ?? 'Check your schedule' }}</strong>
                    </p>
                    @if(!empty($data['subject']))
                    <p style="color:#065f46;font-size:14px;margin:0 0 4px;">
                        📚 Subject: <strong>{{ $data['subject'] }}</strong>
                    </p>
                    @endif
                    @if(!empty($data['grade']))
                    <p style="color:#065f46;font-size:14px;margin:0;">
                        🎓 Grade: <strong>{{ $data['grade'] }}</strong>
                    </p>
                    @endif
                </td></tr>
            </table>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 16px;">
                புதிய வகுப்பு திட்டமிடப்பட்டுள்ளது. தயவுசெய்து உங்கள் கணக்கில் நுழைந்து பார்க்கவும்.
            </p>
            <p style="color:#4b5563;font-size:15px;line-height:1.7;margin:0 0 20px;">
                Login to your student dashboard for more details.
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

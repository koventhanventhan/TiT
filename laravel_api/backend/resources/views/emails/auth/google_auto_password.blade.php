<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $isReset ? 'Password Reset' : 'Welcome to TiT Education' }}</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f7f6; color: #333; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05);">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="color: #6366f1; margin: 0;">TiT Education</h1>
        </div>

        <h2 style="color: #1e293b; font-size: 20px; font-weight: 700;">
            {{ $isReset ? 'Your Password Has Been Reset' : 'Welcome to TiT Education!' }}
        </h2>

        <p style="font-size: 16px; line-height: 1.5; color: #475569;">
            Hello {{ $user->full_name ?? $user->name }},
        </p>

        @if($isReset)
            <p style="font-size: 15px; line-height: 1.5; color: #475569;">
                An administrator has reset your password. You can now log in using your registered email and the new auto-generated password below.
            </p>
        @else
            <p style="font-size: 15px; line-height: 1.5; color: #475569;">
                Thank you for joining us using your Google account! An account has been created for you automatically. You can always log in seamlessly using the <strong>Sign in with Google</strong> button.
            </p>
            <p style="font-size: 15px; line-height: 1.5; color: #475569;">
                If you ever prefer to log in with an email and password, we have generated a secure temporary password for you:
            </p>
        @endif

        <div style="background-color: #f1f5f9; padding: 15px; border-radius: 6px; margin: 20px 0;">
            <p style="margin: 0 0 10px 0; font-size: 15px;"><strong>Email:</strong> {{ $user->email }}</p>
            <p style="margin: 0; font-size: 15px;"><strong>Password:</strong> <span style="font-family: monospace; font-size: 16px; font-weight: bold; padding: 3px 6px; background-color: #e2e8f0; border-radius: 4px;">{{ $plainPassword }}</span></p>
        </div>

        <p style="font-size: 15px; line-height: 1.5; color: #ef4444; font-weight: bold;">
            * Important: We strongly recommend you change this password immediately after your next login via your Account Settings.
        </p>

        <div style="text-align: center; margin-top: 30px;">
            <a href="{{ config('app.frontend_url', url('/')) }}" style="background-color: #6366f1; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;">
                Login to Your Account
            </a>
        </div>

        <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0 20px 0;">

        <p style="font-size: 12px; color: #94a3b8; text-align: center;">
            If you did not request this account or password reset, please contact support immediately.<br>
            © {{ date('Y') }} TiT Education. All rights reserved.
        </p>
    </div>
</body>
</html>

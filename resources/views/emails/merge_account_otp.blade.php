<!DOCTYPE html>
<html>
<head>
    <title>Verification Code</title>
</head>
<body>
    <h2>Hello {{ $user->full_name ?? $user->name }},</h2>
    <p>We received a request to add a new student to your account.</p>
    <p>Your verification code is: <strong>{{ $otp }}</strong></p>
    <p>This code will expire in 10 minutes.</p>
    <p>If you did not request this, please ignore this email.</p>
</body>
</html>

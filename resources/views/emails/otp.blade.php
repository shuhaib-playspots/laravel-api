<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Verification Code</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .wrapper { max-width: 480px; margin: 40px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.08); }
        .header { background: #1a1a2e; padding: 32px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 22px; letter-spacing: 1px; }
        .body { padding: 40px 32px; text-align: center; }
        .body p { color: #555; font-size: 15px; line-height: 1.6; margin: 0 0 24px; }
        .otp { display: inline-block; font-size: 42px; font-weight: 700; letter-spacing: 12px; color: #1a1a2e; background: #f0f0ff; padding: 16px 32px; border-radius: 8px; margin: 8px 0 24px; }
        .note { color: #999; font-size: 13px; }
        .footer { padding: 20px 32px; text-align: center; background: #fafafa; border-top: 1px solid #eee; }
        .footer p { color: #bbb; font-size: 12px; margin: 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ config('app.name') }}</h1>
        </div>
        <div class="body">
            <p>Use the code below to verify your email address.</p>
            <div class="otp">{{ $otp }}</div>
            <p class="note">This code expires in <strong>{{ $expiresInMinutes }} minutes</strong>.<br>If you did not request this, you can safely ignore this email.</p>
        </div>
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

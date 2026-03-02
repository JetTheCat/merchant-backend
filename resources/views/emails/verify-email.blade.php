<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email Address</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .email-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2d3748;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .content p {
            margin: 0 0 15px 0;
            color: #4a5568;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .verify-button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #4f46e5;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
        }
        .verify-button:hover {
            background-color: #4338ca;
        }
        .alternative-link {
            margin-top: 20px;
            padding: 15px;
            background-color: #f7fafc;
            border-radius: 6px;
            font-size: 12px;
            color: #718096;
            word-break: break-all;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 12px;
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Verify Your Email Address</h1>
        </div>

        <div class="content">
            <p>Hello {{ $user->first_name }},</p>
            
            <p>Thank you for registering! Please verify your email address by clicking the button below:</p>
        </div>

        <div class="button-container">
            <a href="{{ $verificationUrl }}" class="verify-button">
                Verify Email Address
            </a>
        </div>

        <div class="content">
            <p>This verification link will expire in 60 minutes.</p>

            <p>
                <strong>If you did not create an account:</strong> 
                Someone may have used your email address by mistake. 
                For your security, we recommend changing your password for any accounts associated with this email address. 
                No further action is required regarding this verification email.
            </p>
        </div>

        <div class="alternative-link">
            <p style="margin: 0 0 10px 0; color: #4a5568;">If the button doesn't work, copy and paste this URL into your browser:</p>
            <p style="margin: 0;">{{ $verificationUrl }}</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

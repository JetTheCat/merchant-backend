<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .header {
            background-color: #4f46e5;
            color: #ffffff;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px;
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
        .reset-button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #4f46e5;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
        }
        .reset-button:hover {
            background-color: #4338ca;
        }
        .warning-box {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-box p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
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
            <h1>Reset Your Password</h1>
        </div>

        <div class="content">
            <p>Hello {{ $user->first_name }},</p>
            
            <p>
                We received a request to reset your password. 
                Click the button below to create a new password:
            </p>
        </div>

        <div class="button-container">
            <a href="{{ $resetUrl }}" class="reset-button">
                Reset Password
            </a>
        </div>

        <div class="content">
            <p>This password reset link will expire in 1 hour.</p>
            
            <div class="warning-box">
                <p>
                    <strong>Security Notice:</strong> 
                    If you did not request a password reset, please ignore this email. 
                    Your password will remain unchanged. 
                    If you're concerned about your account security, please contact our support team immediately.
                </p>
            </div>
        </div>

        <div class="alternative-link">
            <p style="margin: 0 0 10px 0; color: #4a5568;">
                If the button doesn't work, copy and paste this URL into your browser:
            </p>
            <p style="margin: 0;">{{ $resetUrl }}</p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>

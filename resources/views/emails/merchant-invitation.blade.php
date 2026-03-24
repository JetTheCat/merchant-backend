<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You've Been Invited</title>
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
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .top-bar {
            background-color: #059669;
            height: 5px;
        }
        .body-padding {
            padding: 40px;
        }
        .portal-label {
            text-align: center;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #059669;
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
        .merchant-badge {
            display: inline-block;
            margin-top: 12px;
            padding: 4px 14px;
            background-color: #ecfdf5;
            color: #065f46;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        .content {
            margin-bottom: 30px;
        }
        .content p {
            margin: 0 0 15px 0;
            color: #4a5568;
        }
        .capabilities {
            margin: 24px 0;
            padding: 20px 24px;
            background-color: #f0fdf4;
            border-radius: 8px;
        }
        .capabilities p {
            margin: 0 0 10px 0;
            font-size: 13px;
            font-weight: 700;
            color: #065f46;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .capabilities ul {
            margin: 0;
            padding-left: 18px;
        }
        .capabilities ul li {
            font-size: 14px;
            color: #374151;
            margin-bottom: 4px;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .setup-button {
            display: inline-block;
            padding: 14px 32px;
            background-color: #059669;
            color: #ffffff;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 16px;
        }
        .setup-button:hover {
            background-color: #047857;
        }
        .info-box {
            margin: 20px 0;
            padding: 16px 20px;
            background-color: #f7fafc;
            border-left: 4px solid #059669;
            border-radius: 0 6px 6px 0;
        }
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #4a5568;
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
        <div class="top-bar"></div>

        <div class="body-padding">
            <div class="portal-label">Merchant Portal</div>

            <div class="header">
                <h1>You've Been Invited</h1>
                <span class="merchant-badge">{{ $merchantUser->merchant->name }}</span>
            </div>

            <div class="content">
                <p>Hello {{ $merchantUser->name }},</p>

                <p>
                    You've been invited to join <strong>{{ $merchantUser->merchant->name }}</strong> as a
                    <strong>{{ $merchantUser->role->label() }}</strong> on the {{ config('app.name') }} Merchant Portal.
                </p>

                <p>Once your account is set up, you'll be able to:</p>
            </div>

            <div class="capabilities">
                <p>As a merchant, you can</p>
                <ul>
                    <li>Manage your credit packages and pricing</li>
                    <li>Scan customer QR codes and process redemptions</li>
                    <li>View transaction history and daily summaries</li>
                    <li>Track settlements and payouts</li>
                </ul>
            </div>

            <div class="content">
                <p>Click the button below to set up your account and create a password:</p>
            </div>

            <div class="button-container">
                <a href="{{ $setupUrl }}" class="setup-button">
                    Set Up Your Account
                </a>
            </div>

            <div class="info-box">
                <p>This invitation link will expire in <strong>72 hours</strong>. If it expires, contact your administrator to resend it.</p>
            </div>

            <div class="content">
                <p>
                    <strong>Didn't expect this email?</strong>
                    If you were not expecting an invitation, you can safely ignore it.
                    No account will be created until you click the link above.
                </p>
            </div>

            <div class="alternative-link">
                <p style="margin: 0 0 10px 0; color: #4a5568;">If the button doesn't work, copy and paste this URL into your browser:</p>
                <p style="margin: 0;">{{ $setupUrl }}</p>
            </div>

            <div class="footer">
                <p>© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>

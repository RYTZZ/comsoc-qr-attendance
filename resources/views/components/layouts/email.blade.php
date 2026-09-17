<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 15px; color: #1a1a2e; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; background-color: #f4f4f5; padding: 40px 16px; }
        .container { max-width: 560px; margin: 0 auto; }
        .header { text-align: center; padding-bottom: 24px; }
        .header-inner { display: inline-flex; align-items: center; gap: 10px; text-decoration: none; }
        .logo-mark { width: 40px; height: 40px; background-color: #7A1618; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; }
        .logo-mark svg { display: block; }
        .brand-name { font-size: 16px; font-weight: 700; color: #303644; letter-spacing: -0.3px; }
        .card { background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04); }
        .card-accent { height: 4px; background-color: #7A1618; }
        .card-body { padding: 36px 40px; }
        .email-title { font-size: 20px; font-weight: 700; color: #111827; margin-bottom: 8px; line-height: 1.3; }
        .email-subtitle { font-size: 14px; color: #6b7280; margin-bottom: 28px; line-height: 1.5; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        .content { color: #374151; font-size: 15px; line-height: 1.7; }
        .content p { margin-bottom: 16px; }
        .content p:last-child { margin-bottom: 0; }
        .btn-wrapper { text-align: center; margin: 32px 0; }
        .btn { display: inline-block; padding: 12px 28px; background-color: #7A1618; color: #ffffff !important; text-decoration: none; font-size: 14px; font-weight: 600; border-radius: 8px; letter-spacing: 0.3px; }
        .btn:hover { background-color: #621214; }
        .otp-block { background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 20px; text-align: center; margin: 24px 0; }
        .otp-code { font-size: 32px; font-weight: 800; color: #7A1618; letter-spacing: 8px; font-family: 'Courier New', Courier, monospace; }
        .otp-label { font-size: 12px; color: #9ca3af; margin-top: 4px; text-transform: uppercase; letter-spacing: 1px; }
        .notice { background-color: #f9fafb; border-left: 3px solid #7A1618; border-radius: 0 6px 6px 0; padding: 12px 16px; margin: 20px 0; font-size: 13px; color: #6b7280; line-height: 1.6; }
        .footer { padding: 24px 0 0; text-align: center; }
        .footer-text { font-size: 12px; color: #9ca3af; line-height: 1.7; }
        .footer-org { font-weight: 600; color: #6b7280; }
        .footer-divider { border: none; border-top: 1px solid #e5e7eb; margin: 16px 0; }
        @media only screen and (max-width: 600px) {
            .card-body { padding: 28px 24px; }
            .otp-code { font-size: 26px; letter-spacing: 6px; }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="container">

        <div class="header">
            <div class="header-inner">
                <div class="logo-mark">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                        <circle cx="9" cy="7" r="4"/>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                </div>
                <span class="brand-name">Computing Society</span>
            </div>
        </div>

        <div class="card">
            <div class="card-accent"></div>
            <div class="card-body">

                <div class="email-title">{{ $title ?? 'Notification' }}</div>
                @if(isset($subtitle))
                <div class="email-subtitle">{{ $subtitle }}</div>
                @endif

                <hr class="divider">

                <div class="content">
                    {{ $slot }}
                </div>

                @if(isset($actionUrl) && isset($actionText))
                <div class="btn-wrapper">
                    <a href="{{ $actionUrl }}" class="btn">{{ $actionText }}</a>
                </div>
                @endif

                @if(isset($otp))
                <div class="otp-block">
                    <div class="otp-code">{{ $otp }}</div>
                    <div class="otp-label">One-Time Password</div>
                </div>
                @endif

                @if(isset($notice))
                <div class="notice">{{ $notice }}</div>
                @endif

            </div>
        </div>

        <div class="footer">
            <hr class="footer-divider">
            <p class="footer-text">
                <span class="footer-org">Computing Society — Sorsogon State University BC</span><br>
                This is an automated message. Please do not reply to this email.<br>
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </p>
        </div>

    </div>
</div>
</body>
</html>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="light" />
    <meta name="supported-color-schemes" content="light" />
    <title>{{ $subject ?? config('app.name') }}</title>
    <style type="text/css">
        :root {
            color-scheme: light;
            supported-color-schemes: light;
        }
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .card-padding { padding: 28px 20px !important; }
            .otp-code { font-size: 26px !important; letter-spacing: 6px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; color: #1e293b;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="560">
                <tr>
                <td align="center" valign="top" width="560">
                <![endif]-->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 560px; margin: 0 auto;">
                    
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <span style="font-size: 18px; font-weight: 700; color: #303644; letter-spacing: -0.2px; text-decoration: none; display: inline-block;">
                                Computing Society
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.04);">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td height="4" style="background-color: #7A1618; line-height: 4px; font-size: 4px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class="card-padding" style="padding: 36px 36px 32px 36px;">
                                        
                                        <div style="font-size: 20px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0; line-height: 1.3;">
                                            {{ $title ?? 'Notification' }}
                                        </div>

                                        @if(isset($subtitle))
                                        <div style="font-size: 14px; color: #64748b; margin: 0 0 20px 0; line-height: 1.5;">
                                            {{ $subtitle }}
                                        </div>
                                        @endif

                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 16px 0 24px 0;">
                                            <tr>
                                                <td height="1" style="background-color: #f1f5f9; line-height: 1px; font-size: 1px;"></td>
                                            </tr>
                                        </table>

                                        <div style="color: #334155; font-size: 15px; line-height: 1.65;">
                                            {{ $slot }}
                                        </div>

                                        @if(isset($actionUrl) && isset($actionText))
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 30px 0 10px 0;">
                                            <tr>
                                                <td align="center">
                                                    <table border="0" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td align="center" bgcolor="#7A1618" style="border-radius: 8px;">
                                                                <a href="{{ $actionUrl }}" target="_blank" style="display: inline-block; padding: 13px 28px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; font-weight: 600; color: #ffffff !important; text-decoration: none; border-radius: 8px; background-color: #7A1618; letter-spacing: 0.2px;">
                                                                    {{ $actionText }}
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
                                        @endif

                                        @if(isset($otp))
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0;">
                                            <tr>
                                                <td align="center" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px 24px;">
                                                    <div style="font-size: 30px; font-weight: 800; color: #7A1618; letter-spacing: 8px; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;">
                                                        {{ $otp }}
                                                    </div>
                                                    <div style="font-size: 11px; color: #64748b; margin-top: 4px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;">
                                                        One-Time Password
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                        @endif

                                        @if(isset($notice))
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 24px 0 0 0;">
                                            <tr>
                                                <td style="background-color: #f8fafc; border-left: 3px solid #7A1618; border-radius: 0 6px 6px 0; padding: 12px 16px; font-size: 13px; color: #64748b; line-height: 1.55;">
                                                    {{ $notice }}
                                                </td>
                                            </tr>
                                        </table>
                                        @endif

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding-top: 24px;">
                            <p style="font-size: 12px; color: #94a3b8; line-height: 1.6; margin: 0;">
                                <span style="font-weight: 600; color: #64748b;">Computing Society — Sorsogon State University BC</span><br />
                                This is an automated message. Please do not reply to this email.<br />
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
                <!--[if (gte mso 9)|(IE)]>
                </td>
                </tr>
                </table>
                <![endif]-->
            </td>
        </tr>
    </table>
</body>
</html>

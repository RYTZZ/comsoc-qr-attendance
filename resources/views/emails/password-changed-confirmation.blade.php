<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="dark light" />
    <meta name="supported-color-schemes" content="dark light" />
    <title>Password Reset Confirmation — COMSOC Hub</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, p, a, span { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        body {
            margin: 0 !important;
            padding: 0 !important;
            background-color: #0B1120 !important;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }
        table {
            border-collapse: collapse !important;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }
        td {
            mso-line-height-rule: exactly;
        }
        img {
            border: 0;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .mobile-content {
                padding: 20px 16px !important;
            }
            .mobile-header {
                padding: 16px 16px !important;
            }
            .mobile-footer {
                padding: 18px 16px 20px 16px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0B1120; color: #F8FAFC;">
    <div style="display: none; font-size: 1px; color: #0B1120; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Security Notice: Your COMSOC Hub password was successfully updated.
    </div>

    @php
        $configuredUrl = config('app.url');
        if (empty($configuredUrl) || str_contains($configuredUrl, 'localhost') || str_contains($configuredUrl, '127.0.0.1')) {
            $baseLogoUrl = 'https://comsochub.tech';
        } else {
            $baseLogoUrl = rtrim($configuredUrl, '/');
            if (!str_starts_with($baseLogoUrl, 'http://') && !str_starts_with($baseLogoUrl, 'https://')) {
                $baseLogoUrl = 'https://' . $baseLogoUrl;
            }
            if (str_starts_with($baseLogoUrl, 'http://')) {
                $baseLogoUrl = 'https://' . substr($baseLogoUrl, 7);
            }
        }
        $logoUrl = $baseLogoUrl . '/images/COMSOC.png';
    @endphp

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0B1120; width: 100%; margin: 0; padding: 20px 12px;">
        <tr>
            <td align="center" valign="top">
                <table role="presentation" class="email-container" width="540" cellpadding="0" cellspacing="0" border="0" style="max-width: 540px; width: 100%; background-color: #172033; border: 1px solid #334155; border-radius: 16px; overflow: hidden; box-shadow: 0 16px 32px rgba(0,0,0,0.5);">
                    
                    <tr>
                        <td height="5" style="background-color: #059669; font-size: 1px; line-height: 5px;">&nbsp;</td>
                    </tr>

                    <tr>
                        <td align="center" class="mobile-header" style="padding: 24px 24px 18px 24px; border-bottom: 1px solid #334155;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="padding-bottom: 10px;">
                                        <img src="{{ $logoUrl }}" alt="COMSOC Logo" width="60" height="60" style="width: 60px; height: 60px; display: block; border: 0; margin: 0 auto;" />
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <h1 style="margin: 0; font-size: 20px; font-weight: 800; color: #FFFFFF; letter-spacing: 0.5px;">COMSOC HUB</h1>
                                        <p style="margin: 2px 0 0 0; font-size: 11px; font-weight: 600; color: #10B981; letter-spacing: 1.5px; text-transform: uppercase;">Security Alert</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td class="mobile-content" style="padding: 28px 28px 24px 28px;">
                            <p style="margin: 0 0 14px 0; font-size: 15px; line-height: 1.5; color: #F8FAFC; font-weight: 500;">
                                Hello <strong style="color: #FFFFFF;">{{ $userName }}</strong>,
                            </p>

                            <p style="margin: 0 0 18px 0; font-size: 13.5px; line-height: 1.6; color: #94A3B8;">
                                This is an official confirmation that the password for your COMSOC Hub account (<strong style="color: #F8FAFC;">{{ $email }}</strong>) was successfully reset.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 20px 0; background-color: #0B1120; border-radius: 10px; border: 1px solid #334155;">
                                <tr>
                                    <td style="padding: 14px 16px;">
                                        <div style="font-size: 12px; color: #94A3B8; margin-bottom: 4px;">
                                            <strong style="color: #F8FAFC;">Timestamp:</strong> {{ $resetTime }}
                                        </div>
                                        @if($ipAddress)
                                        <div style="font-size: 12px; color: #94A3B8;">
                                            <strong style="color: #F8FAFC;">IP Address:</strong> {{ $ipAddress }}
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0 0 20px 0; font-size: 13px; line-height: 1.6; color: #94A3B8;">
                                All previous active login sessions across devices have been revoked for your security. You may now sign in using your new password.
                            </p>

                            <div style="background-color: #451a1a; border-left: 3px solid #ef4444; padding: 12px 14px; border-radius: 0 8px 8px 0; margin-bottom: 20px;">
                                <p style="margin: 0 0 4px 0; font-size: 12px; font-weight: 700; color: #fca5a5;">
                                    Did not make this change?
                                </p>
                                <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #fecaca;">
                                    If you did not authorize this password reset, please contact the COMSOC administration immediately through our <a href="{{ $assistanceUrl }}" target="_blank" style="color: #ffffff; text-decoration: underline; font-weight: 700;">Account Assistance</a> channel to secure your account.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" class="mobile-footer" style="padding: 20px 24px; background-color: #0B1120; border-top: 1px solid #334155;">
                            <p style="margin: 0 0 4px 0; font-size: 11px; color: #94A3B8; font-weight: 500;">
                                Computing Society Organization — Sorsogon State University Bulan Campus
                            </p>
                            <p style="margin: 0; font-size: 10px; color: #64748B;">
                                This is an automated security notice from COMSOC Hub.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>


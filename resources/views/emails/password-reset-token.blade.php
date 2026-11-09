<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="dark light" />
    <meta name="supported-color-schemes" content="dark light" />
    <title>Password Reset Token — COMSOC Hub</title>
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
        .token-code-text {
            font-family: 'Courier New', Courier, Consolas, Monaco, monospace !important;
            font-size: 24px !important;
            font-weight: 800 !important;
            letter-spacing: 2px !important;
            color: #FFFFFF !important;
            line-height: 1.3 !important;
            word-break: break-all !important;
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
            .token-code-text {
                font-size: 20px !important;
                letter-spacing: 1px !important;
            }
            .token-box {
                padding: 16px 10px !important;
            }
            .mobile-footer {
                padding: 18px 16px 20px 16px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0B1120; color: #F8FAFC;">
    <!-- Preheader text (preview in email client inbox) -->
    <div style="display: none; font-size: 1px; color: #0B1120; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Your COMSOC Hub password reset token is ready. Expires in {{ $expirationMinutes }} minutes.
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

    <!-- Main Wrapper Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0B1120; width: 100%; margin: 0; padding: 20px 12px;">
        <tr>
            <td align="center" valign="top">
                <!-- Inner Container -->
                <table role="presentation" class="email-container" width="540" cellpadding="0" cellspacing="0" border="0" style="max-width: 540px; width: 100%; background-color: #172033; border: 1px solid #334155; border-radius: 16px; overflow: hidden; box-shadow: 0 16px 32px rgba(0,0,0,0.5);">
                    
                    <!-- Top Maroon Accent Strip -->
                    <tr>
                        <td height="5" style="background-color: #560E0F; font-size: 1px; line-height: 5px;">&nbsp;</td>
                    </tr>

                    <!-- Header with Official Logo & Brand -->
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
                                        <p style="margin: 2px 0 0 0; font-size: 11px; font-weight: 600; color: #df747a; letter-spacing: 1.5px; text-transform: uppercase;">Password Recovery</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td class="mobile-content" style="padding: 28px 28px 24px 28px;">
                            <p style="margin: 0 0 14px 0; font-size: 15px; line-height: 1.5; color: #F8FAFC; font-weight: 500;">
                                Hello <strong style="color: #FFFFFF;">{{ $userName }}</strong>,
                            </p>

                            <p style="margin: 0 0 20px 0; font-size: 13.5px; line-height: 1.6; color: #94A3B8;">
                                We received a request to reset the password for your COMSOC Hub account. Use the secure password reset token below to verify your request and set a new password:
                            </p>

                            <!-- Token Display Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 22px 0;">
                                <tr>
                                    <td align="center" class="token-box" style="background-color: #0B1120; border: 2px dashed #8F3033; border-radius: 12px; padding: 20px 16px;">
                                        <div style="font-size: 11px; font-weight: 700; color: #df747a; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 8px;">
                                            Your Password Reset Token
                                        </div>
                                        <div class="token-code-text" style="font-family: monospace; font-size: 22px; font-weight: 800; color: #FFFFFF; user-select: all; -webkit-user-select: all;">
                                            {{ $token }}
                                        </div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-top: 10px;">
                                            Expires in <strong style="color: #F8FAFC;">{{ $expirationMinutes }} minutes</strong>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Action Button -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $verifyUrl }}" target="_blank" style="display: inline-block; background-color: #560E0F; color: #FFFFFF; font-size: 13.5px; font-weight: 700; text-decoration: none; padding: 12px 28px; border-radius: 8px; border: 1px solid #8F3033; box-shadow: 0 4px 12px rgba(86,14,15,0.4);">
                                            Verify Token &amp; Reset Password
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Instructions -->
                            <div style="background-color: #0B1120; border-left: 3px solid #df747a; padding: 12px 14px; border-radius: 0 8px 8px 0; margin-bottom: 20px;">
                                <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 700; color: #FFFFFF;">
                                    How to complete your password reset:
                                </p>
                                <ol style="margin: 0; padding-left: 18px; font-size: 12px; line-height: 1.6; color: #94A3B8;">
                                    <li>Click the button above or navigate to the token verification page.</li>
                                    <li>Enter your email (<strong style="color: #F8FAFC;">{{ $email }}</strong>) and the reset token.</li>
                                    <li>Upon verification, create and confirm your new secure password.</li>
                                </ol>
                            </div>

                            <!-- Security Notice -->
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748B;">
                                <strong style="color: #94A3B8;">Security Notice:</strong> If you did not request a password reset, please disregard this email. Your password will remain unchanged and your account remains secure.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" class="mobile-footer" style="padding: 20px 24px; background-color: #0B1120; border-top: 1px solid #334155;">
                            <p style="margin: 0 0 4px 0; font-size: 11px; color: #94A3B8; font-weight: 500;">
                                Computing Society Organization — Sorsogon State University Bulan Campus
                            </p>
                            <p style="margin: 0; font-size: 10px; color: #64748B;">
                                This is an automated message from COMSOC Hub. Please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>


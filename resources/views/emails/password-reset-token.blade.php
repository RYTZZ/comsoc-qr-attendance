<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="dark light" />
    <meta name="supported-color-schemes" content="dark light" />
    <title>Your COMSOC Hub Password Reset Code</title>
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
        .reset-code-text {
            font-family: 'Courier New', Courier, Consolas, Monaco, monospace !important;
            font-size: 34px !important;
            font-weight: 800 !important;
            letter-spacing: 10px !important;
            color: #FFFFFF !important;
            line-height: 1.2 !important;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .mobile-content {
                padding: 24px 18px !important;
            }
            .mobile-header {
                padding: 20px 18px 16px 18px !important;
            }
            .reset-code-text {
                font-size: 28px !important;
                letter-spacing: 8px !important;
            }
            .code-box {
                padding: 18px 12px !important;
            }
            .mobile-footer {
                padding: 20px 18px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0B1120; color: #F8FAFC;">
    <!-- Preheader text (preview in email client inbox) -->
    <div style="display: none; font-size: 1px; color: #0B1120; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Your COMSOC Hub password reset code is {{ $token }}. Expires in {{ $expirationMinutes }} minutes.
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
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0B1120; width: 100%; margin: 0; padding: 24px 12px;">
        <tr>
            <td align="center" valign="top">
                <!-- Inner Container -->
                <table role="presentation" class="email-container" width="540" cellpadding="0" cellspacing="0" border="0" style="max-width: 540px; width: 100%; background-color: #172033; border: 1px solid #334155; border-radius: 16px; overflow: hidden; box-shadow: 0 16px 32px rgba(0,0,0,0.5);">
                    
                    <!-- Header Section: Upper-Left Logo & Wordmark, followed by PASSWORD RECOVERY, separated by maroon accent line -->
                    <tr>
                        <td align="left" class="mobile-header" style="padding: 26px 28px 18px 28px;">
                            <!-- First Row: Logo & Wordmark horizontally aligned on the upper left -->
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="left">
                                <tr>
                                    <td valign="middle" style="padding-right: 12px;">
                                        <img src="{{ $logoUrl }}" alt="COMSOC Logo" width="42" height="42" style="width: 42px; height: 42px; display: block; border: 0;" />
                                    </td>
                                    <td valign="middle">
                                        <span style="font-size: 20px; font-weight: 800; color: #FFFFFF; letter-spacing: 0.5px; display: inline-block; line-height: 1;">COMSOC HUB</span>
                                    </td>
                                </tr>
                            </table>

                            <div style="clear: both;"></div>

                            <!-- Second Row: PASSWORD RECOVERY heading directly underneath the logo and wordmark, left-aligned -->
                            <div style="margin-top: 14px; text-align: left;">
                                <span style="font-size: 13px; font-weight: 800; color: #df747a; letter-spacing: 2px; text-transform: uppercase; display: block;">PASSWORD RECOVERY</span>
                            </div>
                        </td>
                    </tr>

                    <!-- Third Row: Thin maroon accent line separating the branding header from main content -->
                    <tr>
                        <td height="2" style="background-color: #560E0F; font-size: 1px; line-height: 2px; padding: 0;">&nbsp;</td>
                    </tr>

                    <!-- Main Email Content -->
                    <tr>
                        <td class="mobile-content" style="padding: 30px 28px 24px 28px;">
                            
                            <!-- Heading: Reset your password -->
                            <h2 style="margin: 0 0 16px 0; font-size: 20px; font-weight: 700; color: #FFFFFF; letter-spacing: -0.2px;">
                                Reset your password
                            </h2>

                            <!-- Greeting & Message -->
                            <p style="margin: 0 0 12px 0; font-size: 14.5px; line-height: 1.6; color: #F8FAFC;">
                                Hello, <strong style="color: #FFFFFF;">{{ $userName }}</strong>.
                            </p>

                            <p style="margin: 0 0 12px 0; font-size: 14px; line-height: 1.6; color: #94A3B8;">
                                We received a request to reset the password associated with your COMSOC Hub account.
                            </p>

                            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.6; color: #94A3B8;">
                                Enter the verification code below on the password recovery page to continue.
                            </p>

                            <!-- Verification Code Container (Centered presentation, prominent, large & bold) -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px 0;">
                                <tr>
                                    <td align="center" class="code-box" style="background-color: #0B1120; border: 2px dashed #8F3033; border-radius: 12px; padding: 22px 16px;">
                                        <div style="font-size: 11px; font-weight: 700; color: #df747a; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px;">
                                            YOUR RESET CODE
                                        </div>
                                        <div class="reset-code-text" style="font-family: 'Courier New', Courier, Consolas, Monaco, monospace; font-size: 32px; font-weight: 800; letter-spacing: 10px; color: #FFFFFF; user-select: all; -webkit-user-select: all; text-align: center;">
                                            {{ $token }}
                                        </div>
                                        <div style="font-size: 12px; color: #94A3B8; margin-top: 10px; font-weight: 500;">
                                            Expires in <strong style="color: #F8FAFC;">{{ $expirationMinutes }} minutes</strong>.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Notice (Visually separated) -->
                            <div style="background-color: #1E293B; border-left: 3px solid #df747a; padding: 14px 16px; border-radius: 0 8px 8px 0; margin-top: 8px; margin-bottom: 4px;">
                                <p style="margin: 0 0 4px 0; font-size: 12.5px; font-weight: 700; color: #FFFFFF;">
                                    Didn't request this password reset?
                                </p>
                                <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #94A3B8;">
                                    You can safely ignore this email. Your existing password will remain unchanged unless the reset process is completed successfully.
                                </p>
                            </div>
                        </td>
                    </tr>

                    <!-- Email Footer -->
                    <tr>
                        <td align="center" class="mobile-footer" style="padding: 24px 28px; background-color: #0B1120; border-top: 1px solid #334155;">
                            <p style="margin: 0 0 6px 0; font-size: 12px; font-weight: 700; color: #FFFFFF; letter-spacing: 0.3px;">
                                Innovating Today for a Better Community.
                            </p>
                            <p style="margin: 0 0 4px 0; font-size: 11.5px; color: #94A3B8; font-weight: 500;">
                                Computing Society Organization &bull; SorSU Bulan Campus
                            </p>
                            <p style="margin: 8px 0 0 0; font-size: 10px; color: #64748B;">
                                Automated security notification. Please do not reply to this email.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

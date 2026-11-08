<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="dark light" />
    <meta name="supported-color-schemes" content="dark light" />
    <title>Verify Your Email Address — COMSOC Hub</title>
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
        .otp-code-text {
            font-family: 'Courier New', Courier, Consolas, Monaco, monospace !important;
            font-size: 34px !important;
            font-weight: 800 !important;
            letter-spacing: 8px !important;
            color: #FFFFFF !important;
            line-height: 1.2 !important;
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
            .otp-code-text {
                font-size: 28px !important;
                letter-spacing: 6px !important;
            }
            .otp-box {
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
        Your COMSOC Hub email verification code is {{ $otpCode }}. Expires in {{ $expirationMinutes }} minutes.
    </div>

    @php
        // Resolve absolute HTTPS logo URL
        $configuredUrl = config('app.url');
        if (empty($configuredUrl) || str_contains($configuredUrl, 'localhost') || str_contains($configuredUrl, '127.0.0.1')) {
            $baseLogoUrl = 'https://comsochub.tech';
        } else {
            $baseLogoUrl = rtrim($configuredUrl, '/');
            if (!str_starts_with($baseLogoUrl, 'http://') && !str_starts_with($baseLogoUrl, 'https://')) {
                $baseLogoUrl = 'https://' . $baseLogoUrl;
            }
            // Upgrade HTTP to HTTPS for secure email image loading
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
                <table role="presentation" class="email-container" width="520" cellpadding="0" cellspacing="0" border="0" style="max-width: 520px; width: 100%; background-color: #172033; border: 1px solid #334155; border-radius: 16px; overflow: hidden; box-shadow: 0 16px 32px rgba(0,0,0,0.5);">
                    
                    <!-- Top Maroon Accent Strip -->
                    <tr>
                        <td height="3" style="background: linear-gradient(90deg, #560E0F 0%, #8F3033 50%, #560E0F 100%); line-height: 3px; font-size: 3px;">&nbsp;</td>
                    </tr>

                    <!-- Header -->
                    <tr>
                        <td class="mobile-header" style="background-color: #111A2E; padding: 18px 24px; border-bottom: 1px solid #23314D;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="42" valign="middle" style="padding-right: 12px;">
                                        <img src="{{ $logoUrl }}" width="40" height="40" alt="COMSOC Hub" style="display: block; width: 40px; height: 40px; max-width: 40px; border-radius: 8px; background-color: #0B1120; border: 1px solid #334155; object-fit: contain; color: #FFFFFF; font-size: 10px;" />
                                    </td>
                                    <td valign="middle">
                                        <div style="font-size: 17px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.2px; line-height: 1.2;">
                                            COMSOC Hub
                                        </div>
                                        <div style="font-size: 11px; font-weight: 600; color: #df747a; margin-top: 1px; line-height: 1.2;">
                                            Sorsogon State University &ndash; Computing Society
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Email Body -->
                    <tr>
                        <td class="mobile-content" style="padding: 24px 24px 20px 24px;">
                            <!-- Heading -->
                            <h1 style="margin: 0 0 12px 0; font-size: 19px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.3px; line-height: 1.3;">
                                Verify Your Email Address
                            </h1>

                            <!-- Greeting -->
                            <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: 700; color: #F8FAFC; line-height: 1.4;">
                                Hello, {{ $studentName }}!
                            </p>

                            <!-- Instruction -->
                            <p style="margin: 0 0 18px 0; font-size: 13px; line-height: 1.5; color: #CBD5E1;">
                                Use the verification code below to verify your COMSOC Hub account.
                            </p>

                            <!-- Single Clean Maroon Outline OTP Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 18px 0;">
                                <tr>
                                    <td class="otp-box" align="center" style="background-color: #0B1120; border: 1.5px solid #8F3033; border-radius: 12px; padding: 18px 14px; text-align: center;">
                                        <div class="otp-code-text" style="font-family: 'Courier New', Courier, Consolas, Monaco, monospace; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #FFFFFF; text-align: center; text-indent: 8px; line-height: 1.2;">
                                            {{ $otpCode }}
                                        </div>
                                        <div style="margin-top: 8px; font-size: 11px; font-weight: 600; color: #df747a;">
                                            Expires in {{ $expirationMinutes }} minutes.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Short Security Notice -->
                            <p style="margin: 0 0 8px 0; font-size: 12px; line-height: 1.5; color: #94A3B8;">
                                <strong style="color: #CBD5E1;">Security Notice:</strong> Never share this code with anyone. COMSOC Hub will never ask for it.
                            </p>

                            <!-- Ignore Disclaimer -->
                            <p style="margin: 0; font-size: 12px; line-height: 1.5; color: #64748B;">
                                If you didn't request this code, simply ignore this email.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 24px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-top: 1px solid #23314D; height: 1px; line-height: 1px; font-size: 1px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Complete Footer -->
                    <tr>
                        <td class="mobile-footer" style="padding: 18px 24px 22px 24px; background-color: #111A2E; text-align: center;">
                            <div style="font-size: 13px; font-weight: 800; color: #F8FAFC; margin-bottom: 2px;">
                                COMSOC Hub
                            </div>
                            <div style="font-size: 11px; font-weight: 600; color: #df747a; margin-bottom: 4px;">
                                One Community. One Platform. More Possibilities.
                            </div>
                            <div style="font-size: 11px; line-height: 1.5; color: #64748B;">
                                Computing Society Organization – SorSU Bulan Campus<br />
                                &copy; {{ date('Y') }}
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

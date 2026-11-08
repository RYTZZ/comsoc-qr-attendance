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
            font-size: 38px !important;
            font-weight: 800 !important;
            letter-spacing: 12px !important;
            color: #FFFFFF !important;
            line-height: 1.2 !important;
        }
        @media only screen and (max-width: 600px) {
            .email-container {
                width: 100% !important;
                max-width: 100% !important;
            }
            .mobile-content {
                padding: 24px 20px !important;
            }
            .mobile-header {
                padding: 20px !important;
            }
            .otp-code-text {
                font-size: 30px !important;
                letter-spacing: 8px !important;
            }
            .otp-box {
                padding: 20px 12px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0B1120; color: #F8FAFC;">
    <!-- Preheader text (preview in email client inbox) -->
    <div style="display: none; font-size: 1px; color: #0B1120; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Your six-digit COMSOC Hub email verification code is {{ $otpCode }}. Expires in {{ $expirationMinutes }} minutes.
    </div>

    <!-- Main Wrapper Table -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #0B1120; width: 100%; margin: 0; padding: 36px 16px;">
        <tr>
            <td align="center" valign="top">
                <!-- Inner Container -->
                <table role="presentation" class="email-container" width="560" cellpadding="0" cellspacing="0" border="0" style="max-width: 560px; width: 100%; background-color: #172033; border: 1px solid #334155; border-radius: 18px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.6);">
                    
                    <!-- Top Maroon Accent Strip -->
                    <tr>
                        <td height="4" style="background: linear-gradient(90deg, #560E0F 0%, #8F3033 50%, #560E0F 100%); line-height: 4px; font-size: 4px;">&nbsp;</td>
                    </tr>

                    <!-- Compact & Elegant Header -->
                    <tr>
                        <td class="mobile-header" style="background-color: #111A2E; padding: 24px 32px; border-bottom: 1px solid #23314D;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="48" valign="middle" style="padding-right: 14px;">
                                        <img src="{{ asset('images/COMSOC.png') }}" width="44" height="44" alt="COMSOC Hub Logo" style="display: block; width: 44px; height: 44px; max-width: 44px; border-radius: 10px; background-color: #0B1120; border: 1px solid #334155; object-fit: contain;" />
                                    </td>
                                    <td valign="middle">
                                        <div style="font-size: 10px; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: #df747a; margin-bottom: 2px;">
                                            Sorsogon State University &bull; Computing Society
                                        </div>
                                        <div style="font-size: 20px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.3px; line-height: 1.2;">
                                            COMSOC Hub
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Email Body -->
                    <tr>
                        <td class="mobile-content" style="padding: 36px 32px 32px 32px;">
                            <!-- Heading -->
                            <h1 style="margin: 0 0 16px 0; font-size: 22px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.4px; line-height: 1.3;">
                                Verify Your Email Address
                            </h1>

                            <!-- Greeting -->
                            <p style="margin: 0 0 14px 0; font-size: 15px; font-weight: 700; color: #F8FAFC;">
                                Hello, {{ $studentName }}!
                            </p>

                            <!-- Explanatory Message -->
                            <p style="margin: 0 0 24px 0; font-size: 14px; line-height: 1.65; color: #CBD5E1;">
                                Thank you for using COMSOC Hub. Please use the following six-digit verification code to confirm your email address and complete your account verification.
                            </p>

                            <!-- Verification Code Centerpiece Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px 0;">
                                <tr>
                                    <td class="otp-box" align="center" style="background-color: #0B1120; border: 1px solid #8F3033; border-radius: 14px; padding: 26px 16px; text-align: center;">
                                        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; color: #94A3B8; margin-bottom: 10px;">
                                            Your One-Time Verification Code
                                        </div>
                                        <div class="otp-code-text" style="font-family: 'Courier New', Courier, Consolas, Monaco, monospace; font-size: 38px; font-weight: 800; letter-spacing: 12px; color: #FFFFFF; text-align: center; text-indent: 12px;">
                                            {{ $otpCode }}
                                        </div>
                                        <table role="presentation" align="center" cellpadding="0" cellspacing="0" border="0" style="margin-top: 14px;">
                                            <tr>
                                                <td style="background-color: rgba(86, 14, 15, 0.4); border: 1px solid rgba(223, 116, 122, 0.35); border-radius: 20px; padding: 4px 12px;">
                                                    <span style="font-size: 11px; font-weight: 700; color: #df747a;">
                                                        &#9201; This code will expire in {{ $expirationMinutes }} minutes.
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Security Reminder Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 24px 0;">
                                <tr>
                                    <td style="background-color: #1E293B; border-left: 3px solid #8F3033; border-radius: 4px 10px 10px 4px; padding: 12px 16px;">
                                        <p style="margin: 0; font-size: 12px; line-height: 1.55; color: #94A3B8;">
                                            <strong style="color: #CBD5E1;">Security Notice:</strong> Never share this verification code with anyone. COMSOC Hub administrators and officers will never ask you to disclose it.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #64748B;">
                                If you did not initiate this request or register for COMSOC Hub, you can safely ignore this email. No changes will be applied to your account.
                            </p>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding: 0 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td style="border-top: 1px solid #23314D; height: 1px; line-height: 1px; font-size: 1px;">&nbsp;</td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Email Footer -->
                    <tr>
                        <td style="padding: 24px 32px 30px 32px; background-color: #111A2E; text-align: center;">
                            <div style="font-size: 13px; font-weight: 800; color: #F8FAFC; margin-bottom: 4px; letter-spacing: -0.2px;">
                                COMSOC Hub
                            </div>
                            <div style="font-size: 11px; font-weight: 600; color: #df747a; margin-bottom: 6px;">
                                One Community. One Platform. More Possibilities.
                            </div>
                            <div style="font-size: 11px; line-height: 1.5; color: #64748B;">
                                Computing Society Organization &bull; Sorsogon State University &ndash; Bulan Campus<br />
                                &copy; {{ date('Y') }} COMSOC Hub. All rights reserved.
                            </div>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>

@php
    $memberName = !empty($user->name) ? $user->name : 'Member';
    $expireMinutes = $count ?? 60;
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="x-apple-disable-message-reformatting" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="color-scheme" content="dark" />
    <meta name="supported-color-schemes" content="dark" />
    <title>Reset Your Password — COMSOC HUB</title>
    <!--[if mso]>
    <style type="text/css">
        body, table, td, h1, h2, p, a, span { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
    <![endif]-->
    <style type="text/css">
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #0B1120; font-family: Arial, Helvetica, sans-serif; }
        a { color: #94A3B8; text-decoration: none; }
        a:hover { color: #F8FAFC; }
        .reset-btn:hover { background-color: #6E1213 !important; }
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; max-width: 100% !important; }
            .card-padding { padding: 28px 20px !important; }
            .button-cell { padding: 14px 20px !important; width: 100% !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #0B1120; font-family: Arial, Helvetica, sans-serif; -webkit-font-smoothing: antialiased; color: #F8FAFC;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0B1120; width: 100%; margin: 0; padding: 0;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <!--[if (gte mso 9)|(IE)]>
                <table align="center" border="0" cellspacing="0" cellpadding="0" width="560">
                <tr>
                <td align="center" valign="top" width="560">
                <![endif]-->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 560px; margin: 0 auto; text-align: left;">
                    
                    <!-- Preheader spacing -->
                    <tr>
                        <td height="10" style="font-size: 10px; line-height: 10px;">&nbsp;</td>
                    </tr>

                    <!-- Main Branded Card -->
                    <tr>
                        <td style="background-color: #172033; border-radius: 12px; overflow: hidden; border: 1px solid #1E293B; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <!-- Maroon Brand Accent Top Bar -->
                                <tr>
                                    <td height="4" style="background-color: #560E0F; line-height: 4px; font-size: 4px;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td class="card-padding" style="padding: 36px 36px 32px 36px;">
                                        
                                        <!-- Header: Brand Name + Security Badge -->
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 24px;">
                                            <tr>
                                                <td valign="middle">
                                                    <!-- Platform Brand Name -->
                                                    <div style="font-size: 22px; font-weight: 800; color: #F8FAFC; letter-spacing: 0.5px; line-height: 1.2;">
                                                        COMSOC HUB
                                                    </div>
                                                    <!-- Subtitle -->
                                                    <div style="font-size: 11px; font-weight: 700; color: #94A3B8; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 4px; line-height: 1.4;">
                                                        COMPUTING SOCIETY ORGANIZATION
                                                    </div>
                                                </td>
                                                <td align="right" valign="middle">
                                                    <!-- Security Icon Badge -->
                                                    <table border="0" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td style="background-color: #1E293B; border: 1px solid #334155; border-radius: 20px; padding: 6px 12px;">
                                                                <span style="font-size: 11px; font-weight: 700; color: #94A3B8; letter-spacing: 0.8px; text-transform: uppercase;">
                                                                    &#128274; SECURITY
                                                                </span>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Subtle Divider -->
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 28px 0;">
                                            <tr>
                                                <td height="1" style="background-color: #1E293B; line-height: 1px; font-size: 1px;"></td>
                                            </tr>
                                        </table>

                                        <!-- Main Heading -->
                                        <div style="font-size: 22px; font-weight: 700; color: #F8FAFC; margin: 0 0 8px 0; line-height: 1.3;">
                                            Reset Your Password
                                        </div>

                                        <!-- Personalized Greeting -->
                                        <div style="font-size: 15px; font-weight: 600; color: #F8FAFC; margin: 0 0 14px 0; line-height: 1.5;">
                                            Hi, {{ $memberName }}!
                                        </div>

                                        <!-- Supporting Text -->
                                        <div style="font-size: 14px; color: #94A3B8; line-height: 1.65; margin: 0 0 28px 0;">
                                            We received a request to reset the password for your COMSOC HUB account. Click the button below to choose a new password.
                                        </div>

                                        <!-- Primary Call-to-Action Button -->
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 28px 0;">
                                            <tr>
                                                <td align="center">
                                                    <!--[if mso]>
                                                    <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $url }}" style="height:48px;v-text-anchor:middle;width:260px;" arcsize="15%" stroke="f" fillcolor="#560E0F">
                                                    <w:anchorlock/>
                                                    <center style="color:#ffffff;font-family:Arial,sans-serif;font-size:14px;font-weight:bold;letter-spacing:0.5px;">RESET MY PASSWORD &rarr;</center>
                                                    </v:roundrect>
                                                    <![endif]-->
                                                    <!--[if !mso]><!-->
                                                    <table border="0" cellpadding="0" cellspacing="0">
                                                        <tr>
                                                            <td align="center" bgcolor="#560E0F" style="border-radius: 8px; background-color: #560E0F;">
                                                                <a href="{{ $url }}" target="_blank" class="reset-btn" style="display: inline-block; padding: 15px 32px; font-family: Arial, Helvetica, sans-serif; font-size: 14px; font-weight: 700; color: #FFFFFF !important; text-decoration: none; border-radius: 8px; background-color: #560E0F; letter-spacing: 0.5px; text-transform: uppercase;">
                                                                    RESET MY PASSWORD &rarr;
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                    <!--<![endif]-->
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Expiration Notice -->
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 24px 0;">
                                            <tr>
                                                <td style="background-color: #1E293B; border-left: 4px solid #560E0F; border-radius: 4px; padding: 12px 16px;">
                                                    <span style="font-size: 13px; font-weight: 600; color: #F8FAFC; line-height: 1.5;">
                                                        &#9201; This password reset link expires in {{ $expireMinutes }} minutes.
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>

                                        <!-- Fallback URL -->
                                        <div style="font-size: 13px; color: #94A3B8; line-height: 1.6; margin: 0 0 8px 0;">
                                            If the button doesn't work, copy and paste the following link into your browser:
                                        </div>
                                        <div style="margin: 0 0 28px 0; word-break: break-all; word-wrap: break-word;">
                                            <a href="{{ $url }}" target="_blank" style="font-size: 12px; color: #F8FAFC; text-decoration: underline; line-height: 1.5; font-family: 'Courier New', Courier, monospace;">
                                                {{ $url }}
                                            </a>
                                        </div>

                                        <!-- Subtle Divider -->
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 20px 0;">
                                            <tr>
                                                <td height="1" style="background-color: #1E293B; line-height: 1px; font-size: 1px;"></td>
                                            </tr>
                                        </table>

                                        <!-- Security Notice -->
                                        <div style="background-color: #0B1120; border: 1px solid #1E293B; border-radius: 6px; padding: 14px 16px;">
                                            <p style="margin: 0; font-size: 12px; color: #94A3B8; line-height: 1.6;">
                                                <strong style="color: #F8FAFC;">Didn't request a password reset?</strong> You can safely ignore this email. Your password will remain unchanged unless you complete the reset process.
                                            </p>
                                        </div>

                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="padding: 28px 16px 12px 16px;">
                            <div style="font-size: 12px; font-weight: 700; color: #94A3B8; line-height: 1.5; letter-spacing: 0.3px;">
                                COMSOC HUB
                            </div>
                            <div style="font-size: 12px; color: #64748B; line-height: 1.5; margin-top: 2px;">
                                Computing Society Organization &bull; Sorsogon State University &ndash; Bulan Campus
                            </div>
                            <div style="font-size: 11px; color: #475569; line-height: 1.6; margin-top: 10px;">
                                Automated security notification. Please do not reply to this email.
                            </div>
                            <div style="font-size: 11px; color: #334155; line-height: 1.6; margin-top: 6px;">
                                &copy; {{ date('Y') }} COMSOC HUB. All rights reserved.
                            </div>
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

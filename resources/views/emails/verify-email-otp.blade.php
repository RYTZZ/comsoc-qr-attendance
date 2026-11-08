<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Your COMSOC Hub Email Address</title>
</head>
<body style="margin:0; padding:0; background-color:#0B1120; font-family:-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#F8FAFC; -webkit-font-smoothing:antialiased;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#0B1120; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:540px; background-color:#172033; border:1px solid #334155; border-radius:20px; overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.5);">
                    <!-- Brand Header -->
                    <tr>
                        <td style="background:linear-gradient(135deg, #560E0F 0%, #8F3033 100%); padding:24px 32px; border-bottom:1px solid #8F3033;">
                            <div style="font-size:11px; font-weight:700; letter-spacing:1.8px; text-transform:uppercase; color:#F8FAFC; opacity:0.9;">
                                Sorsogon State University &bull; Computing Society
                            </div>
                            <div style="font-size:22px; font-weight:800; color:#FFFFFF; margin-top:4px; letter-spacing:-0.3px;">
                                COMSOC Hub
                            </div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px 0; font-size:16px; font-weight:700; color:#F8FAFC;">
                                Hello, {{ $studentName }}!
                            </p>

                            <p style="margin:0 0 16px 0; font-size:14px; line-height:1.6; color:#CBD5E1;">
                                Thank you for using COMSOC Hub.
                            </p>

                            <p style="margin:0 0 24px 0; font-size:14px; line-height:1.6; color:#CBD5E1;">
                                Please use the following six-digit verification code to verify your email address:
                            </p>

                            <!-- Six-Digit OTP Display Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:0 0 24px 0;">
                                <tr>
                                    <td align="center" style="background-color:#0B1120; border:1px solid #8F3033; border-radius:16px; padding:24px 16px;">
                                        <div style="font-family:'Courier New', Courier, monospace; font-size:36px; font-weight:800; letter-spacing:10px; color:#FFFFFF; text-align:center;">
                                            {{ $otpCode }}
                                        </div>
                                        <div style="margin-top:8px; font-size:12px; font-weight:600; color:#df747a;">
                                            This code will expire in {{ $expirationMinutes }} minutes.
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 24px 0; font-size:13px; line-height:1.6; color:#94A3B8;">
                                If you did not request this verification, you can safely ignore this email.
                            </p>

                            <hr style="border:none; border-top:1px solid #334155; margin:24px 0;">

                            <p style="margin:0; font-size:13px; font-weight:700; color:#F8FAFC;">
                                COMSOC Hub
                            </p>
                            <p style="margin:4px 0 0 0; font-size:12px; color:#94A3B8;">
                                Your computing community, connected.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>


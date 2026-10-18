<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>EXP Expiration Warning</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #0B1120; color: #F8FAFC; margin: 0; padding: 24px; }
        .card { background-color: #172033; border: 1px solid #1E293B; border-radius: 16px; padding: 24px; max-width: 520px; margin: 0 auto; }
        .warning-box { background-color: rgba(245, 158, 11, 0.1); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 12px; padding: 18px; text-align: center; margin: 20px 0; }
        .exp { font-size: 26px; font-weight: 800; color: #FBBF24; }
        .btn { display: inline-block; background-color: #2563EB; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; margin-top: 16px; }
        .footer { font-size: 11px; color: #94A3B8; text-align: center; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="margin-top: 0; color: #FBBF24;">EXP Expiration Notice</h2>
        <p>Hello {{ $user->name }},</p>
        <p>You have unspent Spendable EXP that is scheduled to expire soon.</p>

        <div class="warning-box">
            <div class="exp">{{ number_format($expAmount) }} EXP</div>
            <div style="font-size: 12px; color: #F59E0B; margin-top: 4px;">
                Expires in {{ $daysRemaining }} day{{ $daysRemaining > 1 ? 's' : '' }} ({{ $expirationDate }})
            </div>
        </div>

        <p style="font-size: 13px; color: #94A3B8;">Spend your EXP on club merchandise, stickers, caps, tote bags, or discount vouchers in the COMSOC Marketplace before it expires.</p>

        <div style="text-align: center;">
            <a href="{{ route('shop.index') }}" class="btn">Browse Reward Shop</a>
        </div>

        <div class="footer">
            Computing Society Organization • Sorsogon State University – Bulan Campus
        </div>
    </div>
</body>
</html>

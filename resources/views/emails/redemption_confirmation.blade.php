<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reward Claim Code</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #0B1120; color: #F8FAFC; margin: 0; padding: 24px; }
        .card { background-color: #172033; border: 1px solid #1E293B; border-radius: 16px; padding: 24px; max-width: 520px; margin: 0 auto; }
        .code-box { background-color: #0B1120; border: 2px dashed #60A5FA; border-radius: 12px; padding: 18px; text-align: center; margin: 20px 0; }
        .code { font-family: monospace; font-size: 26px; font-weight: 800; color: #FBBF24; letter-spacing: 3px; }
        .btn { display: inline-block; background-color: #2563EB; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; margin-top: 16px; }
        .footer { font-size: 11px; color: #94A3B8; text-align: center; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="margin-top: 0; color: #60A5FA;">Reward Redemption Confirmed!</h2>
        <p>Hello {{ $user->name }},</p>
        <p>Your redemption for <strong>{{ $redemption->item_title_snapshot }}</strong> has been verified. {{ number_format($redemption->points_spent) }} Spendable EXP was deducted using FEFO.</p>

        <div class="code-box">
            <div style="font-size: 12px; text-transform: uppercase; color: #94A3B8; margin-bottom: 6px;">Your Single-Use Claim Code</div>
            <div class="code">{{ $redemption->claim_code }}</div>
        </div>

        <p style="font-size: 13px; color: #94A3B8;">Present this claim code to authorized COMSOC collection staff at the student organization desk to pick up your merchandise.</p>

        <div style="text-align: center;">
            <a href="{{ route('shop.index') }}" class="btn">View Marketplace</a>
        </div>

        <div class="footer">
            Computing Society Organization • Sorsogon State University – Bulan Campus
        </div>
    </div>
</body>
</html>

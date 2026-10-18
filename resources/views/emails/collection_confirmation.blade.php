<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reward Collected</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background-color: #0B1120; color: #F8FAFC; margin: 0; padding: 24px; }
        .card { background-color: #172033; border: 1px solid #1E293B; border-radius: 16px; padding: 24px; max-width: 520px; margin: 0 auto; }
        .success-box { background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; padding: 16px; text-align: center; margin: 20px 0; color: #34D399; font-weight: 700; }
        .footer { font-size: 11px; color: #94A3B8; text-align: center; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="margin-top: 0; color: #34D399;">Reward Handed Over!</h2>
        <p>Hello {{ $user->name }},</p>
        <p>This is a confirmation that your reward <strong>{{ $redemption->item_title_snapshot }}</strong> was physically verified and collected.</p>

        <div class="success-box">
            Verified by: {{ $staff->name }}<br>
            Timestamp: {{ now()->format('M d, Y g:i A') }}
        </div>

        <p style="font-size: 13px; color: #94A3B8;">Thank you for your active participation in Computing Society events and programs!</p>

        <div class="footer">
            Computing Society Organization • Sorsogon State University – Bulan Campus
        </div>
    </div>
</body>
</html>

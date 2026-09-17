<x-layouts.email
    subject="Reset Your Password"
    title="Reset Your Password"
    subtitle="You requested a password reset for your account."
    :action-url="$url"
    action-text="Reset Password"
    notice="This link expires in {{ $count }} minutes. If you did not request a password reset, no action is required."
>
    <p style="margin: 0 0 16px 0; color: #1e293b; font-size: 15px; line-height: 1.6;">Hi <strong>{{ $user->name }}</strong>,</p>
    <p style="margin: 0; color: #334155; font-size: 15px; line-height: 1.65;">We received a request to reset the password for your account. Click the button below to set a new password.</p>
</x-layouts.email>

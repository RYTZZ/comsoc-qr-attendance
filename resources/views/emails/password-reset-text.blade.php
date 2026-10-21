@php
    $memberName = !empty($user->name) ? $user->name : 'Member';
    $expireMinutes = $count ?? 60;
@endphp
COMSOC HUB
COMPUTING SOCIETY ORGANIZATION
Sorsogon State University – Bulan Campus

=======================================================
RESET YOUR PASSWORD
=======================================================

Hi, {{ $memberName }}!

We received a request to reset the password for your COMSOC HUB account. Click the button below to choose a new password.

RESET MY PASSWORD:
{{ $url }}

This password reset link expires in {{ $expireMinutes }} minutes.

If the button doesn't work, copy and paste the following link into your browser:
{{ $url }}

Didn't request a password reset? You can safely ignore this email. Your password will remain unchanged unless you complete the reset process.

=======================================================
COMSOC HUB
Computing Society Organization
Sorsogon State University – Bulan Campus
Automated security notification. Please do not reply to this email.
(c) {{ date('Y') }} COMSOC HUB. All rights reserved.

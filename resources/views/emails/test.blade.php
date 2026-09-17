<x-layouts.email
    subject="ComSoc Email Test"
    title="Email Delivery Test"
    subtitle="This is a test message from the ComSoc QR Attendance System."
    action-url="{{ config('app.url') }}"
    action-text="Open Application"
    notice="This is an automated test email. No action is required."
>
    <p>Hi <strong>{{ $recipientName }}</strong>,</p>
    <p>This test confirms that the ComSoc QR Attendance System email service is configured correctly and working as expected.</p>
    <p>If you received this message, transactional emails such as password resets, OTPs, registration confirmations, and event notifications are ready to be sent.</p>
</x-layouts.email>

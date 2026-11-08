<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $studentName,
        public readonly string $otpCode,
        public readonly int $expirationMinutes = 15,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', env('MAIL_FROM_ADDRESS', 'computingsocietysorsubc@gmail.com'));

        return new Envelope(
            from: new Address($fromAddress, 'COMSOC Hub'),
            subject: 'Verify Your COMSOC Hub Email Address',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.verify-email-otp',
            with: [
                'studentName' => $this->studentName,
                'otpCode' => $this->otpCode,
                'expirationMinutes' => $this->expirationMinutes,
            ],
        );
    }

    /**
     * Prevent plaintext OTP from appearing in serialized debug dumps.
     */
    public function __debugInfo(): array
    {
        return [
            'studentName' => $this->studentName,
            'otpCode' => '******',
            'expirationMinutes' => $this->expirationMinutes,
        ];
    }
}


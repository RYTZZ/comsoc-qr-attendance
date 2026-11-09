<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetTokenMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $token,
        public readonly string $email,
        public readonly int $expirationMinutes = 60,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', env('MAIL_FROM_ADDRESS', 'computingsocietysorsubc@gmail.com'));

        return new Envelope(
            from: new Address($fromAddress, 'COMSOC Hub'),
            subject: 'Password Reset Token — COMSOC Hub',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-reset-token',
            with: [
                'userName' => $this->userName,
                'token' => $this->token,
                'email' => $this->email,
                'expirationMinutes' => $this->expirationMinutes,
                'verifyUrl' => route('password.verify.show', ['email' => $this->email]),
            ],
        );
    }

    /**
     * Prevent raw reset token from appearing in serialized debug dumps.
     */
    public function __debugInfo(): array
    {
        return [
            'userName' => $this->userName,
            'email' => $this->email,
            'token' => '******',
            'expirationMinutes' => $this->expirationMinutes,
        ];
    }
}


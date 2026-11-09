<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangedConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $userName,
        public readonly string $email,
        public readonly ?string $ipAddress = null,
    ) {}

    public function envelope(): Envelope
    {
        $fromAddress = config('mail.from.address', env('MAIL_FROM_ADDRESS', 'computingsocietysorsubc@gmail.com'));

        return new Envelope(
            from: new Address($fromAddress, 'COMSOC Hub'),
            subject: 'Security Alert: Your Password Was Successfully Reset — COMSOC Hub',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.password-changed-confirmation',
            with: [
                'userName' => $this->userName,
                'email' => $this->email,
                'ipAddress' => $this->ipAddress,
                'resetTime' => now()->toFormattedDateString() . ' at ' . now()->format('g:i A T'),
                'assistanceUrl' => route('account.assistance'),
            ],
        );
    }
}


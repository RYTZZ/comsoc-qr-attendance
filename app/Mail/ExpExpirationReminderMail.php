<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpExpirationReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public int $expAmount,
        public int $daysRemaining,
        public string $expirationDate
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Urgent: {$this->expAmount} EXP Expiring in {$this->daysRemaining} Day" . ($this->daysRemaining > 1 ? 's' : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.exp_expiration_reminder',
        );
    }
}

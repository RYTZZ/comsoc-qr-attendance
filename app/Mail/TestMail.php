<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientName;

    public function __construct(string $recipientName = 'Team')
    {
        $this->recipientName = $recipientName;
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'ComSoc Email Test');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.test', with: ['recipientName' => $this->recipientName]);
    }
}

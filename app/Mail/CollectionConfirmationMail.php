<?php

namespace App\Mail;

use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CollectionConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public RewardRedemption $redemption,
        public User $staff
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reward Collected: ' . $this->redemption->item_title_snapshot,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.collection_confirmation',
        );
    }
}

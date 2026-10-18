<?php

namespace App\Mail;

use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RedemptionConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public RewardRedemption $redemption
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reward Claim Code: ' . $this->redemption->claim_code . ' — ' . $this->redemption->item_title_snapshot,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.redemption_confirmation',
        );
    }
}

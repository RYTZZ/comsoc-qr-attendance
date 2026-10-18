<?php

namespace App\Services;

use App\Mail\CollectionConfirmationMail;
use App\Mail\ExpExpirationReminderMail;
use App\Mail\RedemptionConfirmationMail;
use App\Models\EmailDeliveryLog;
use App\Models\RewardRedemption;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrganizationMailService
{
    /**
     * Resolves deduplicated recipient email addresses (primary university email & preferred email).
     */
    public static function getRecipientEmails(User $user): array
    {
        $emails = [];
        if (!empty($user->email)) {
            $emails[] = strtolower(trim($user->email));
        }
        if (!empty($user->preferred_email)) {
            $emails[] = strtolower(trim($user->preferred_email));
        }

        return array_values(array_unique($emails));
    }

    /**
     * Send redemption confirmation email with claim code.
     * Transaction safety rule: Mail failure must NOT roll back or replay a completed redemption transaction.
     */
    public function sendRedemptionConfirmation(User $user, RewardRedemption $redemption): void
    {
        $emails = self::getRecipientEmails($user);
        if (empty($emails)) return;

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new RedemptionConfirmationMail($user, $redemption));

                EmailDeliveryLog::create([
                    'user_id' => $user->id,
                    'notification_type' => 'redemption_claim',
                    'recipient_email' => $email,
                    'subject' => 'Reward Claim Code: ' . $redemption->claim_code,
                    'status' => 'sent',
                    'reference_id' => $redemption->id,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send redemption email to {$email}: " . $e->getMessage());

                EmailDeliveryLog::create([
                    'user_id' => $user->id,
                    'notification_type' => 'redemption_claim',
                    'recipient_email' => $email,
                    'subject' => 'Reward Claim Code: ' . $redemption->claim_code,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'reference_id' => $redemption->id,
                ]);
            }
        }
    }

    /**
     * Send physical collection confirmation email.
     */
    public function sendCollectionConfirmation(User $user, RewardRedemption $redemption, User $staff): void
    {
        $emails = self::getRecipientEmails($user);
        if (empty($emails)) return;

        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new CollectionConfirmationMail($user, $redemption, $staff));

                EmailDeliveryLog::create([
                    'user_id' => $user->id,
                    'notification_type' => 'collection_confirmed',
                    'recipient_email' => $email,
                    'subject' => 'Reward Collected: ' . $redemption->item_title_snapshot,
                    'status' => 'sent',
                    'reference_id' => $redemption->id,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send collection confirmation email to {$email}: " . $e->getMessage());

                EmailDeliveryLog::create([
                    'user_id' => $user->id,
                    'notification_type' => 'collection_confirmed',
                    'recipient_email' => $email,
                    'subject' => 'Reward Collected: ' . $redemption->item_title_snapshot,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'reference_id' => $redemption->id,
                ]);
            }
        }
    }

    /**
     * Send EXP expiration reminders (7 days or 1 day).
     * Prevents duplicate reminders by checking email delivery logs.
     */
    public function sendExpirationReminder(User $user, int $expAmount, int $daysRemaining, string $expirationDate, string $creditId): void
    {
        $type = $daysRemaining === 1 ? 'expiry_1d' : 'expiry_7d';

        // Check user preferences
        $prefs = $user->notificationPreferences;
        if ($prefs) {
            if ($daysRemaining === 7 && !$prefs->notify_expiration_7d) return;
            if ($daysRemaining === 1 && !$prefs->notify_expiration_1d) return;
        }

        // Check if reminder was already sent for this specific credit and interval
        $alreadySent = EmailDeliveryLog::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->where('reference_id', $creditId)
            ->exists();

        if ($alreadySent) return;

        $emails = self::getRecipientEmails($user);
        foreach ($emails as $email) {
            try {
                Mail::to($email)->send(new ExpExpirationReminderMail($user, $expAmount, $daysRemaining, $expirationDate));

                EmailDeliveryLog::create([
                    'user_id' => $user->id,
                    'notification_type' => $type,
                    'recipient_email' => $email,
                    'subject' => "Urgent: {$expAmount} EXP Expiring in {$daysRemaining} Day" . ($daysRemaining > 1 ? 's' : ''),
                    'status' => 'sent',
                    'reference_id' => $creditId,
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to send {$type} reminder to {$email}: " . $e->getMessage());

                EmailDeliveryLog::create([
                    'user_id' => $user->id,
                    'notification_type' => $type,
                    'recipient_email' => $email,
                    'subject' => "Urgent: {$expAmount} EXP Expiring in {$daysRemaining} Day" . ($daysRemaining > 1 ? 's' : ''),
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'reference_id' => $creditId,
                ]);
            }
        }
    }
}

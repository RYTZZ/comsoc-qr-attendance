<?php

namespace App\Console\Commands;

use App\Models\ExpCredit;
use App\Services\OrganizationMailService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendExpExpirationRemindersCommand extends Command
{
    protected $signature = 'comsoc:send-expiration-reminders';
    protected $description = 'Send 7-day and 1-day EXP expiration email reminders to members with remaining credits';

    public function handle(OrganizationMailService $mailService): int
    {
        $this->info('Checking for credits expiring in 7 days and 1 day...');

        // 1. Process 7-day expiration reminders (credits expiring between 6 and 7.5 days from now)
        $sevenDayCredits = ExpCredit::with('user')
            ->where('status', 'active')
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now()->addDays(6)->startOfDay(), now()->addDays(7)->endOfDay()])
            ->get();

        $count7d = 0;
        foreach ($sevenDayCredits as $credit) {
            if ($credit->user && $credit->isAvailable()) {
                $daysRemaining = max(1, (int) round(now()->diffInDays($credit->expires_at)));
                $mailService->sendExpirationReminder(
                    user: $credit->user,
                    expAmount: $credit->remaining_amount,
                    daysRemaining: $daysRemaining,
                    expirationDate: $credit->expires_at->format('M d, Y g:i A'),
                    creditId: $credit->id
                );
                $count7d++;
            }
        }

        // 2. Process 1-day expiration reminders (credits expiring in <= 24 hours)
        $oneDayCredits = ExpCredit::with('user')
            ->where('status', 'active')
            ->where('remaining_amount', '>', 0)
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [now(), now()->addHours(26)])
            ->get();

        $count1d = 0;
        foreach ($oneDayCredits as $credit) {
            if ($credit->user && $credit->isAvailable()) {
                $mailService->sendExpirationReminder(
                    user: $credit->user,
                    expAmount: $credit->remaining_amount,
                    daysRemaining: 1,
                    expirationDate: $credit->expires_at->format('M d, Y g:i A'),
                    creditId: $credit->id
                );
                $count1d++;
            }
        }

        $this->info("Completed: Sent {$count7d} 7-day reminders and {$count1d} 1-day reminders.");

        return Command::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PasswordResetCodeService;
use Illuminate\Console\Command;

class SendTestPasswordResetCode extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:send-test-reset-code {email=rytzz.grieta@sorsu.edu.ph}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a real test password reset recovery code email to the specified address';

    /**
     * Execute the console command.
     */
    public function handle(PasswordResetCodeService $codeService): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        $this->info("Looking up user for email: {$email}...");

        $user = User::where('email', $email)
            ->orWhere('preferred_email', $email)
            ->first();

        if (! $user) {
            $this->warn("No existing user found for {$email}. Creating temporary/fallback user record for test dispatch...");
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => 'Rytzz Grieta',
                    'password' => bcrypt('TemporaryTestPass123!'),
                    'role' => 'student',
                    'is_active' => true,
                ]
            );
        }

        $this->info("User identified: {$user->name} ({$user->email}, ID: {$user->id})");
        $this->info("Generating 6-character code and dispatching email via configured mailer...");

        $result = $codeService->issueAndSend($user, '127.0.0.1', enforceCooldown: false);

        if ($result['sent']) {
            $this->info("SUCCESS: " . $result['message']);
            return Command::SUCCESS;
        } else {
            $this->error("FAILED: " . ($result['message'] ?? 'Unknown error'));
            return Command::FAILURE;
        }
    }
}

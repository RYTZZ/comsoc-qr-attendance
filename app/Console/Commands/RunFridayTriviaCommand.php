<?php

namespace App\Console\Commands;

use App\Services\FridayTriviaAutomationService;
use Illuminate\Console\Command;

class RunFridayTriviaCommand extends Command
{
    protected $signature = 'trivia:run-weekly
        {--manual : Mark the execution as a manual administrator trigger}';

    protected $description = 'Automatically select, shuffle, and activate the weekly Friday Trivia session from the database question bank (Asia/Manila).';

    public function handle(FridayTriviaAutomationService $automation): int
    {
        $result = $automation->activateWeeklySession(
            nowManila: null,
            manual: (bool) $this->option('manual')
        );

        $this->info("[{$result['status']}] {$result['message']}");

        return self::SUCCESS;
    }
}

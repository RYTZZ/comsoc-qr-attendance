<?php

namespace App\Console\Commands;

use App\Models\Incident;
use Illuminate\Console\Command;

class ArchiveOldIncidents extends Command
{
    protected $signature = 'incidents:archive';
    protected $description = 'Archive open incidents older than 7 days';

    public function handle(): void
    {
        $count = Incident::archivable()->update([
            'status' => 'archived',
            'archived_at' => now(),
        ]);

        $this->info("Archived {$count} incidents.");
    }
}

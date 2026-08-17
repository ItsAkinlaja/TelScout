<?php

namespace App\Console\Commands;

use App\Jobs\ProcessFollowUpJob;
use Illuminate\Console\Command;

class ProcessFollowUps extends Command
{
    protected $signature   = 'outreach:process-followups';
    protected $description = 'Process due follow-ups and update statuses';

    public function handle(): int
    {
        ProcessFollowUpJob::dispatch();
        $this->info('Follow-up processing job queued.');
        return self::SUCCESS;
    }
}

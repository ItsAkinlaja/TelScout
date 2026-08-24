<?php

namespace App\Console\Commands;

use App\Jobs\FetchCompanyJobs;
use App\Models\JobSource;
use Illuminate\Console\Command;

class FetchSourcedJobs extends Command
{
    protected $signature = 'jobs:fetch-sources
                            {--ats= : Filter by ATS type (greenhouse/lever/ashby)}';

    protected $description = 'Dispatch fetch jobs for all active registered job sources';

    public function handle(): int
    {
        $query = JobSource::active()
            ->with('company')
            ->where(function ($q) {
                $q->whereNull('next_fetch_at')
                  ->orWhere('next_fetch_at', '<=', now());
            });

        if ($atsType = $this->option('ats')) {
            $query->where('ats_type', $atsType);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->info('No active job sources due for fetching.');
            return self::SUCCESS;
        }

        foreach ($sources as $source) {
            FetchCompanyJobs::dispatch($source->id);
        }

        $this->info("Dispatched fetch jobs for {$sources->count()} source(s).");

        return self::SUCCESS;
    }
}

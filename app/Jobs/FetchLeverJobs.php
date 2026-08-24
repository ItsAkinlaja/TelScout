<?php

namespace App\Jobs;

use App\Services\JobIngestionService;
use App\Services\JobSources\LeverSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchLeverJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        private string $companySlug
    ) {}

    public function handle(JobIngestionService $ingestion, LeverSource $source): void
    {
        $results = $source->search(['company_slugs' => [$this->companySlug]]);

        $ingested  = 0;
        $skipped   = 0;
        $seenIds   = [];

        foreach ($results as $jobData) {
            if (!empty($jobData['external_id'])) {
                $seenIds[] = $jobData['external_id'];
            }

            $listing = $ingestion->ingest($jobData);

            if ($listing !== null) {
                $ingested++;
            } else {
                $skipped++;
            }
        }

        $staleCount = $ingestion->markStaleJobs('lever', $seenIds);

        Log::info('FetchLeverJobs: completed', [
            'company_slug' => $this->companySlug,
            'fetched'      => $results->count(),
            'ingested'     => $ingested,
            'skipped'      => $skipped,
            'stale'        => $staleCount,
        ]);
    }

    public function backoff(): array
    {
        return [60, 300, 600];
    }
}

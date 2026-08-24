<?php

namespace App\Jobs;

use App\Services\JobIngestionService;
use App\Services\JobSources\GreenhouseSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchGreenhouseJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public function __construct(
        private string $boardToken,
        private string $companyName
    ) {}

    public function handle(JobIngestionService $ingestion, GreenhouseSource $source): void
    {
        $results = $source->search(['board_tokens' => [$this->boardToken]]);

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

        $staleCount = $ingestion->markStaleJobs('greenhouse', $seenIds);

        Log::info('FetchGreenhouseJobs: completed', [
            'board_token' => $this->boardToken,
            'company'     => $this->companyName,
            'fetched'     => $results->count(),
            'ingested'    => $ingested,
            'skipped'     => $skipped,
            'stale'       => $staleCount,
        ]);
    }

    public function backoff(): array
    {
        return [60, 300, 600];
    }
}

<?php

namespace App\Jobs;

use App\Models\JobSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchJobSourceJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 60;

    public function handle(): void
    {
        $sources = JobSource::active()->due()->with('company')->get();

        Log::info('FetchJobSourceJobs: dispatching fetch jobs', [
            'source_count' => $sources->count(),
        ]);

        foreach ($sources as $source) {
            try {
                $this->dispatchForSource($source);

                $source->update([
                    'last_fetched_at' => now(),
                    'next_fetch_at'   => now()->addHours(6),
                    'failure_count'   => 0,
                ]);
            } catch (\Exception $e) {
                $source->increment('failure_count');

                Log::warning('FetchJobSourceJobs: failed to dispatch for source', [
                    'job_source_id' => $source->id,
                    'ats_type'      => $source->ats_type,
                    'company'       => $source->company->name ?? 'unknown',
                    'error'         => $e->getMessage(),
                ]);
            }
        }
    }

    private function dispatchForSource(JobSource $source): void
    {
        $companyName = $source->company->name ?? 'unknown';

        match ($source->ats_type) {
            'greenhouse' => FetchGreenhouseJobs::dispatch(
                $source->meta['board_token'] ?? $source->source_url,
                $companyName
            ),
            'lever' => FetchLeverJobs::dispatch(
                $source->meta['company_slug'] ?? $source->source_url
            ),
            'ashby' => FetchAshbyJobs::dispatch(
                $source->meta['organization_id'] ?? $source->source_url
            ),
            default => Log::warning('FetchJobSourceJobs: unknown ats_type', [
                'job_source_id' => $source->id,
                'ats_type'      => $source->ats_type,
            ]),
        };
    }
}

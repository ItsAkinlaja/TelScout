<?php

namespace App\Jobs;

use App\Models\JobSource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Router job — dispatches the appropriate per-ATS fetch job
 * based on the job_source record's ats_type.
 */
class FetchCompanyJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 1;
    public int $timeout = 30;

    public function __construct(
        private int $jobSourceId
    ) {}

    public function handle(): void
    {
        $jobSource = JobSource::with('company')->find($this->jobSourceId);

        if (!$jobSource) {
            Log::warning('FetchCompanyJobs: JobSource not found', [
                'job_source_id' => $this->jobSourceId,
            ]);
            return;
        }

        if (!$jobSource->active) {
            Log::info('FetchCompanyJobs: source is inactive, skipping', [
                'job_source_id' => $this->jobSourceId,
            ]);
            return;
        }

        try {
            match ($jobSource->ats_type) {
                'greenhouse' => FetchGreenhouseJobs::dispatch(
                    $jobSource->meta['board_token'] ?? '',
                    $jobSource->company->name ?? ucwords(str_replace(['-', '_'], ' ', $jobSource->meta['board_token'] ?? ''))
                ),
                'lever' => FetchLeverJobs::dispatch(
                    $jobSource->meta['company_slug'] ?? ''
                ),
                'ashby' => FetchAshbyJobs::dispatch(
                    $jobSource->meta['company_slug'] ?? $jobSource->meta['organization_id'] ?? ''
                ),
                default => Log::info('FetchCompanyJobs: generic crawler not yet implemented', [
                    'source_url'    => $jobSource->source_url,
                    'ats_type'      => $jobSource->ats_type,
                    'job_source_id' => $this->jobSourceId,
                ]),
            };
        } catch (\Exception $e) {
            Log::error('FetchCompanyJobs: dispatch failed', [
                'job_source_id' => $this->jobSourceId,
                'ats_type'      => $jobSource->ats_type,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

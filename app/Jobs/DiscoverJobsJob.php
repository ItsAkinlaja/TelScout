<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Opportunity;
use App\Models\SearchRun;
use App\Models\User;
use App\Services\JobIngestionService;
use App\Services\JobSources\JobSourceManager;
use App\Services\MatchScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DiscoverJobsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Queue timeout: 2 minutes — gives all sources time to respond.
     * The job itself enforces a 90-second soft deadline internally.
     */
    public int $tries   = 1;    // No retries — stale runs confuse the UI
    public int $timeout = 120;

    public function __construct(
        private int $searchRunId,
        private int $userId
    ) {}

    public function handle(
        MatchScoringService $scorer,
        \App\Services\ContactDiscoveryService $discovery,
        JobIngestionService $ingestion,
        JobSourceManager $manager
    ): void {
        $run = SearchRun::findOrFail($this->searchRunId);
        $run->update(['status' => 'running', 'started_at' => now()]);

        // Hard deadline: abort after 90 seconds so the run is never stuck as "running"
        $deadline = time() + 90;

        try {
            $user     = User::findOrFail($this->userId);
            $profile  = $user->candidateProfile()->with(['skills', 'experiences'])->first();
            $criteria = $run->criteria;
            $criteria['days_old'] = $criteria['days_old'] ?? 30;

            // ── Fetch jobs from all sources ────────────────────────────────
            $jobs = $manager->search($criteria);

            Log::info("DiscoverJobsJob: fetched {$jobs->count()} raw jobs", [
                'sources' => $manager->getSourceNames(),
                'user_id' => $this->userId,
            ]);

            $newJobs = 0;
            $newOpps = 0;
            $processed = 0;

            foreach ($jobs as $jobData) {
                // Deadline check — stop processing but complete the run gracefully
                if (time() > $deadline) {
                    Log::info('DiscoverJobsJob: deadline reached, stopping early', [
                        'processed' => $processed,
                        'remaining' => $jobs->count() - $processed,
                    ]);
                    break;
                }

                $processed++;

                // ── Ingest (resolve company, deduplicate, create/update) ──
                $job = $ingestion->ingest($jobData);
                if (!$job) continue;

                if ($job->wasRecentlyCreated) {
                    $newJobs++;
                }

                // ── Score opportunity ─────────────────────────────────────
                if ($profile) {
                    $job->loadMissing(['skills', 'company']);
                    $scoreResult = $scorer->score($profile, $job);

                    $minScore = $criteria['min_score'] ?? 0;
                    if ($scoreResult['score'] < $minScore) continue;

                    // ── Contact discovery ─────────────────────────────────
                    $company   = $job->company ?? $job->load('company')->company;
                    $foundEmail = null;

                    // Only run contact discovery if we still have time
                    if (time() < ($deadline - 10)) {
                        $foundEmail = $discovery->discover($job);
                    }

                    $contactId = null;
                    if ($foundEmail && $company) {
                        $contact = $company->contacts()->firstOrCreate(
                            ['email' => $foundEmail],
                            ['name' => 'Hiring Team', 'contact_type' => 'hiring_manager']
                        );
                        $contactId = $contact->id;
                        if (!$company->contact_email) {
                            $company->update(['contact_email' => $foundEmail]);
                        }
                    }

                    Opportunity::firstOrCreate(
                        [
                            'user_id'        => $this->userId,
                            'job_listing_id' => $job->id,
                        ],
                        [
                            'company_id'           => $company?->id,
                            'contact_id'           => $contactId,
                            'match_score'          => $scoreResult['score'],
                            'match_classification' => $scoreResult['classification'],
                            'matched_skills'       => $scoreResult['matched_skills'],
                            'missing_skills'       => $scoreResult['missing_skills'],
                            'match_reasoning'      => $scoreResult['reasoning'],
                            'score_breakdown'      => $scoreResult['score_breakdown'],
                            'application_url'      => $job->application_url,
                        ]
                    );
                    $newOpps++;
                }
            }

            $run->update([
                'status'        => 'completed',
                'results_count' => $jobs->count(),
                'new_jobs'      => $newJobs,
                'completed_at'  => now(),
            ]);

            Log::info('Job discovery completed', [
                'search_run_id' => $this->searchRunId,
                'new_jobs'      => $newJobs,
                'new_opps'      => $newOpps,
                'processed'     => $processed,
            ]);

        } catch (\Exception $e) {
            $run->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
            Log::error('Job discovery failed', [
                'search_run_id' => $this->searchRunId,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * If the job fails on the queue, mark the run as failed so the UI doesn't hang.
     */
    public function failed(\Throwable $e): void
    {
        SearchRun::where('id', $this->searchRunId)
            ->whereIn('status', ['pending', 'running'])
            ->update([
                'status'        => 'failed',
                'error_message' => 'Search timed out or failed: ' . $e->getMessage(),
                'completed_at'  => now(),
            ]);
    }
}

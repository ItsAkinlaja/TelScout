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

    public int $tries   = 2;
    public int $timeout = 180;

    public function __construct(
        private int $searchRunId,
        private int $userId
    ) {}

    public function handle(MatchScoringService $scorer, \App\Services\ContactDiscoveryService $discovery, JobIngestionService $ingestion, JobSourceManager $manager): void
    {
        $run  = SearchRun::findOrFail($this->searchRunId);
        $run->update(['status' => 'running', 'started_at' => now()]);

        try {
            $user     = User::findOrFail($this->userId);
            $profile  = $user->candidateProfile()->with(['skills', 'experiences'])->first();
            $criteria = $run->criteria;

            // Ensure days_old is set (default 30 days)
            $criteria['days_old'] = $criteria['days_old'] ?? 30;

            // Run all sources via the injected manager (configured in AppServiceProvider)
            $jobs = $manager->search($criteria);

            Log::info("DiscoverJobsJob: fetched {$jobs->count()} raw jobs from all sources", [
                'sources'  => $manager->getSourceNames(),
                'user_id'  => $this->userId,
            ]);

            $newCompanies = 0;
            $newJobs      = 0;
            $newOpps      = 0;

            foreach ($jobs as $jobData) {
                // ── Ingest (resolve company, deduplicate, create/update) ───
                $job = $ingestion->ingest($jobData);
                if (!$job) continue;

                // Track newly created listings
                if ($job->wasRecentlyCreated) {
                    $newJobs++;
                } else {
                    // Job already existed — still check opportunity scoring below
                }

                // Resolve the company for opportunity creation
                $company = $job->company ?? $job->load('company')->company;

                // ── Auto-score opportunity ─────────────────────────────────
                if ($profile) {
                    $job->loadMissing(['skills', 'company']);
                    $scoreResult = $scorer->score($profile, $job);

                    // Skip very low matches
                    $minScore = $criteria['min_score'] ?? 0;
                    if ($scoreResult['score'] < $minScore) continue;

                    // ── Discover Contact ────────────────────────────────────
                    $foundEmail = $discovery->discover($job);
                    $contactId  = null;

                    if ($foundEmail) {
                        $contact = $company->contacts()->firstOrCreate(
                            ['email' => $foundEmail],
                            ['name' => 'Hiring Team', 'contact_type' => 'hiring_manager']
                        );
                        $contactId = $contact->id;

                        // Also update company email if not set
                        if (!$company->contact_email) {
                            $company->update(['contact_email' => $foundEmail]);
                        }
                    }

                    Opportunity::create([
                        'user_id'              => $this->userId,
                        'job_listing_id'       => $job->id,
                        'company_id'           => $company->id,
                        'contact_id'           => $contactId,
                        'match_score'          => $scoreResult['score'],
                        'match_classification' => $scoreResult['classification'],
                        'matched_skills'       => $scoreResult['matched_skills'],
                        'missing_skills'       => $scoreResult['missing_skills'],
                        'match_reasoning'      => $scoreResult['reasoning'],
                        'score_breakdown'      => $scoreResult['score_breakdown'],
                        'application_url'      => $job->application_url,
                    ]);
                    $newOpps++;
                }
            }

            $run->update([
                'status'        => 'completed',
                'results_count' => $jobs->count(),
                'new_companies' => $newCompanies,
                'new_jobs'      => $newJobs,
                'completed_at'  => now(),
            ]);

            Log::info('Job discovery completed', [
                'search_run_id' => $this->searchRunId,
                'new_jobs'      => $newJobs,
                'new_companies' => $newCompanies,
                'new_opps'      => $newOpps,
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
}

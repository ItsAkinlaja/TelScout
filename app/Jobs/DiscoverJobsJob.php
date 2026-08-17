<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\JobListing;
use App\Models\Opportunity;
use App\Models\SearchRun;
use App\Models\User;
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

    public function handle(MatchScoringService $scorer): void
    {
        $run  = SearchRun::findOrFail($this->searchRunId);
        $run->update(['status' => 'running', 'started_at' => now()]);

        try {
            $user     = User::findOrFail($this->userId);
            $profile  = $user->candidateProfile()->with(['skills', 'experiences'])->first();
            $criteria = $run->criteria;

            // Ensure days_old is set (default 30 days)
            $criteria['days_old'] = $criteria['days_old'] ?? 30;

            // Run all sources via the manager
            $manager  = new JobSourceManager();
            $jobs     = $manager->search($criteria);

            Log::info("DiscoverJobsJob: fetched {$jobs->count()} raw jobs from all sources", [
                'sources'  => $manager->getSourceNames(),
                'user_id'  => $this->userId,
            ]);

            $newCompanies = 0;
            $newJobs      = 0;
            $newOpps      = 0;

            foreach ($jobs as $jobData) {
                // ── Resolve/deduplicate company ────────────────────────────
                $company = Company::findOrCreateByDomain([
                    'name'    => $jobData['company'] ?? 'Unknown',
                    'website' => $jobData['company_url'] ?? null,
                ]);
                if ($company->wasRecentlyCreated) $newCompanies++;

                // Skip excluded companies
                if ($company->is_excluded) continue;

                // ── Deduplicate job by source_url or external_id ───────────
                $existing = null;
                if (!empty($jobData['source_url'])) {
                    $existing = JobListing::where('source_url', $jobData['source_url'])->first();
                }
                if (!$existing && !empty($jobData['external_id'])) {
                    $existing = JobListing::where('external_id', $jobData['external_id'])
                        ->where('source', $jobData['source'])
                        ->first();
                }
                if ($existing) continue;

                // ── Create job listing ─────────────────────────────────────
                $job = JobListing::create([
                    'company_id'      => $company->id,
                    'title'           => $jobData['title'],
                    'description'     => $jobData['description'] ?? null,
                    'location'        => $jobData['location'] ?? null,
                    'is_remote'       => $jobData['is_remote'] ?? false,
                    'salary_min'      => $jobData['salary_min'] ?? null,
                    'salary_max'      => $jobData['salary_max'] ?? null,
                    'salary_currency' => $jobData['salary_currency'] ?? 'USD',
                    'application_url' => $jobData['application_url'] ?? null,
                    'source_url'      => $jobData['source_url'] ?? null,
                    'external_id'     => $jobData['external_id'] ?? null,
                    'source'          => $jobData['source'] ?? 'unknown',
                    'status'          => 'active',
                    'posted_at'       => $jobData['posted_at'] ?? now(),
                ]);
                $newJobs++;

                // Attach skills/tags
                foreach (array_unique($jobData['tags'] ?? []) as $tag) {
                    if (!empty($tag)) {
                        $job->skills()->firstOrCreate(['skill' => strtolower(trim($tag))]);
                    }
                }

                // ── Auto-score opportunity ─────────────────────────────────
                if ($profile) {
                    $job->loadMissing(['skills', 'company']);
                    $scoreResult = $scorer->score($profile, $job);

                    // Skip very low matches
                    $minScore = $criteria['min_score'] ?? 0;
                    if ($scoreResult['score'] < $minScore) continue;

                    Opportunity::create([
                        'user_id'              => $this->userId,
                        'job_listing_id'       => $job->id,
                        'company_id'           => $company->id,
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

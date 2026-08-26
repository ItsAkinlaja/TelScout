<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Models\SearchRun;
use App\Models\User;
use App\Services\JobIngestionService;
use App\Services\JobSources\JobSourceInterface;
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

    public int $tries   = 1;
    public int $timeout = 300; // 5 min max on queue; sync mode ignores this

    public function __construct(
        private int $searchRunId,
        private int $userId
    ) {}

    public function handle(
        MatchScoringService  $scorer,
        \App\Services\ContactDiscoveryService $discovery,
        JobIngestionService  $ingestion,
        JobSourceManager     $manager
    ): void {
        // Extend PHP time limit for XAMPP/sync mode (ignored when running as a queue worker)
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        $run = SearchRun::findOrFail($this->searchRunId);
        $run->update(['status' => 'running', 'started_at' => now()]);

        try {
            $user     = User::findOrFail($this->userId);
            $profile  = $user->candidateProfile()->with(['skills', 'experiences'])->first();
            $criteria = $run->criteria ?? [];
            $criteria['days_old'] = $criteria['days_old'] ?? 30;

            $totalNew  = 0;
            $totalFetched = 0;
            $sourcesDone  = 0;
            $sourceNames  = $manager->getSourceNames();
            $sourceCount  = count($sourceNames);

            // ── Process each source individually so we can track progress ──
            foreach ($manager->getSources() as $source) {
                $sourceName = $source->getName();

                // Update run with current source being fetched
                $run->update([
                    'error_message' => null, // clear previous partial messages
                    'meta'          => array_merge($run->meta ?? [], [
                        'current_source' => $sourceName,
                        'sources_done'   => $sourcesDone,
                        'sources_total'  => $sourceCount,
                    ]),
                ]);

                try {
                    $sourceJobsRaw = $source->search($criteria);

                    // Apply central filtering (relevance + location) before ingestion
                    $sourceJobs = $manager->filterResults($sourceJobsRaw, $criteria);

                    $totalFetched += $sourceJobsRaw->count();

                    foreach ($sourceJobs as $jobData) {
                        $job = $ingestion->ingest($jobData);
                        if (!$job) continue;

                        if ($job->wasRecentlyCreated) {
                            $totalNew++;
                        }

                        if ($profile) {
                            $job->loadMissing(['skills', 'company']);
                            $scoreResult = $scorer->score($profile, $job);

                            $minScore = $criteria['min_score'] ?? 0;
                            if ($scoreResult['score'] < $minScore) continue;

                            $company    = $job->company;
                            $contactId  = null;

                            // Contact discovery only when time is not critical
                            try {
                                $foundEmail = $discovery->discover($job);
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
                            } catch (\Throwable) {
                                // Contact discovery failure is non-fatal
                            }

                            Opportunity::firstOrCreate(
                                ['user_id' => $this->userId, 'job_listing_id' => $job->id],
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
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Source [{$sourceName}] failed during discovery", [
                        'error' => $e->getMessage(),
                    ]);
                    // Non-fatal — continue with remaining sources
                }

                $sourcesDone++;

                // Persist incremental progress so frontend polls can see it
                $run->update([
                    'new_jobs'      => $totalNew,
                    'results_count' => $totalFetched,
                    'meta'          => array_merge($run->fresh()->meta ?? [], [
                        'current_source' => null,
                        'sources_done'   => $sourcesDone,
                        'sources_total'  => $sourceCount,
                        'source_names'   => $sourceNames,
                    ]),
                ]);
            }

            $run->update([
                'status'        => 'completed',
                'results_count' => $totalFetched,
                'new_jobs'      => $totalNew,
                'completed_at'  => now(),
                'meta'          => array_merge($run->fresh()->meta ?? [], [
                    'current_source' => null,
                    'sources_done'   => $sourceCount,
                    'sources_total'  => $sourceCount,
                    'source_names'   => $sourceNames,
                ]),
            ]);

            Log::info('Job discovery completed', [
                'run_id'   => $this->searchRunId,
                'new_jobs' => $totalNew,
                'fetched'  => $totalFetched,
                'sources'  => $sourcesDone,
            ]);

        } catch (\Throwable $e) {
            $run->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at'  => now(),
            ]);
            Log::error('Job discovery failed', [
                'run_id' => $this->searchRunId,
                'error'  => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        SearchRun::where('id', $this->searchRunId)
            ->whereIn('status', ['pending', 'running'])
            ->update([
                'status'        => 'failed',
                'error_message' => 'Search failed: ' . $e->getMessage(),
                'completed_at'  => now(),
            ]);
    }
}

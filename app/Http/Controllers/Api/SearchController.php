<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DiscoverJobsJob;
use App\Models\SearchRun;
use App\Services\JobSources\JobSourceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public function __construct(private JobSourceManager $sourceManager) {}

    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keywords'   => 'sometimes|array',
            'keywords.*' => 'string',
            'locations'  => 'sometimes|array',
            'locations.*'=> 'string',
            'remote_only'=> 'sometimes|boolean',
            'min_score'  => 'sometimes|integer|min:0|max:100',
            'days_old'   => 'sometimes|integer|min:1|max:90',
        ]);

        $data['days_old'] = $data['days_old'] ?? 30;

        // Create the run record immediately so the frontend can poll it
        $run = SearchRun::create([
            'user_id'  => $request->user()->id,
            'provider' => 'all',
            'criteria' => $data,
            'status'   => 'pending',
        ]);

        $userId = $request->user()->id;

        // Detect whether a real queue worker is active.
        // If queue is 'database', check if there's a worker that has consumed
        // a job recently — otherwise run inline so the user isn't left waiting.
        $runInline = $this->shouldRunInline();

        if ($runInline) {
            // Run directly in this request with a hard time limit.
            // The job itself caps at 90 s, which is within typical PHP max_execution_time.
            DiscoverJobsJob::dispatchSync($run->id, $userId);
        } else {
            DiscoverJobsJob::dispatch($run->id, $userId);
        }

        // Refresh the run so the response reflects any inline completion
        $run->refresh();

        return response()->json([
            'message'    => $runInline
                ? 'Search complete — scroll down to see results.'
                : 'Search queued. Results will appear automatically in 30–60 seconds.',
            'search_run' => $run,
            'sources'    => $this->sourceManager->getSourceNames(),
            'inline'     => $runInline,
        ], 202);
    }

    public function history(Request $request): JsonResponse
    {
        $runs = SearchRun::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($runs);
    }

    public function show(Request $request, SearchRun $run): JsonResponse
    {
        if ($run->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        return response()->json($run);
    }

    /**
     * Returns true when the job should run synchronously in the HTTP request.
     *
     * We run inline when:
     *  - The queue driver is 'sync', OR
     *  - The queue driver is 'database' and no worker has consumed a job in the
     *    last 10 minutes (queue_jobs table has pending jobs older than 5 minutes).
     */
    private function shouldRunInline(): bool
    {
        $driver = config('queue.default');

        if ($driver === 'sync') return true;

        if ($driver === 'database') {
            // Check if there's a worker by looking at recent failed_jobs activity
            // or simply whether we have stale pending jobs (no worker = jobs pile up).
            try {
                $stalePending = DB::table('jobs')
                    ->where('available_at', '<', now()->subMinutes(5)->timestamp)
                    ->exists();

                if ($stalePending) return true;

                // Also check: if queue is empty entirely, we can't tell — run inline
                // to guarantee the user gets results without needing a worker
                $anyPending = DB::table('jobs')->exists();
                if (!$anyPending) {
                    // No queued jobs at all — could mean worker cleared them (good)
                    // or no worker (we just haven't queued anything yet).
                    // Default to inline to guarantee UX.
                    return true;
                }
            } catch (\Throwable) {
                return true; // If jobs table doesn't exist, run inline
            }
        }

        return false;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\DiscoverJobsJob;
use App\Models\SearchRun;
use App\Services\JobSources\JobSourceManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(private JobSourceManager $sourceManager) {}

    public function run(Request $request): JsonResponse
    {
        $data = $request->validate([
            'keywords'    => 'sometimes|array',
            'keywords.*'  => 'string|max:100',
            'locations'   => 'sometimes|array',
            'locations.*' => 'string|max:100',
            'remote_only' => 'sometimes|boolean',
            'min_score'   => 'sometimes|integer|min:0|max:100',
            'days_old'    => 'sometimes|integer|min:1|max:90',
        ]);

        $data['days_old'] = $data['days_old'] ?? 30;

        // Prevent duplicate concurrent searches for the same user
        $running = SearchRun::where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'running'])
            ->where('created_at', '>', now()->subMinutes(10))
            ->first();

        if ($running) {
            return response()->json([
                'message'    => 'A search is already in progress.',
                'search_run' => $running,
                'sources'    => $this->sourceManager->getSourceNames(),
                'inline'     => false,
            ], 202);
        }

        $run = SearchRun::create([
            'user_id'  => $request->user()->id,
            'provider' => 'all',
            'criteria' => $data,
            'status'   => 'pending',
            'meta'     => [
                'sources_total' => count($this->sourceManager->getSourceNames()),
                'sources_done'  => 0,
                'source_names'  => $this->sourceManager->getSourceNames(),
                'current_source'=> null,
            ],
        ]);

        $userId = $request->user()->id;
        $isSync = config('queue.default') === 'sync';

        if ($isSync) {
            // Local/XAMPP: run the job synchronously.
            // The job writes per-source progress to DB as it goes.
            // PHP time limit extended inside the job.
            DiscoverJobsJob::dispatchSync($run->id, $userId);
            $run->refresh();
        } else {
            DiscoverJobsJob::dispatch($run->id, $userId);
        }

        return response()->json([
            'message'    => $isSync ? 'Search complete.' : 'Search running.',
            'search_run' => $run,
            'sources'    => $this->sourceManager->getSourceNames(),
            'inline'     => $isSync,
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
}

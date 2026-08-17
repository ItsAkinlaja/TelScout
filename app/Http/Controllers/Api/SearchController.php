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
            'provider'   => 'sometimes|string',
        ]);

        $data['days_old'] = $data['days_old'] ?? 30;

        // Create the run record immediately so the frontend can poll it
        $run = SearchRun::create([
            'user_id'  => $request->user()->id,
            'provider' => 'all',
            'criteria' => $data,
            'status'   => 'pending',
        ]);

        // On shared hosting (no persistent queue worker), run synchronously
        // but dispatch to queue first — if queue_connection=database and
        // no worker is running, we fall back to running it inline after response.
        // The cron will also pick it up if it hasn't run yet.
        if (config('queue.default') === 'sync') {
            // sync driver: runs immediately inline (local dev without worker)
            DiscoverJobsJob::dispatch($run->id, $request->user()->id);
        } else {
            // async driver: dispatch to queue, also schedule a fallback
            // The cron (*/5 * * * *) will process this within 5 minutes max.
            // We dispatch with a slight delay so the response returns first.
            DiscoverJobsJob::dispatch($run->id, $request->user()->id);
        }

        return response()->json([
            'message'    => 'Search queued. Results will appear automatically — this usually takes 30–60 seconds.',
            'search_run' => $run,
            'sources'    => (new JobSourceManager())->getSourceNames(),
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

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

        // Always dispatch asynchronously — the cron job (php artisan schedule:run)
        // will pick it up within 5 minutes. This is safe for shared hosting.
        DiscoverJobsJob::dispatch($run->id, $userId);

        return response()->json([
            'message'    => 'Search queued. Results will appear automatically when the scheduler runs.',
            'search_run' => $run,
            'sources'    => $this->sourceManager->getSourceNames(),
            'inline'     => false,
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

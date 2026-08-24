<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\FetchAshbyJobs;
use App\Jobs\FetchGreenhouseJobs;
use App\Jobs\FetchLeverJobs;
use App\Models\JobSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class JobSourceController extends Controller
{
    /**
     * GET /api/job-sources
     * List all job sources with their associated company, newest first.
     */
    public function index(Request $request): JsonResponse
    {
        $sources = JobSource::with('company')
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json($sources, 200);
    }

    /**
     * POST /api/job-sources
     * Register a new ATS feed source for a company.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id'  => 'required|exists:companies,id',
            'source_type' => 'required|string|in:greenhouse,lever,ashby,workable,generic',
            'source_url'  => 'required|url',
            'ats_type'    => 'nullable|string|in:greenhouse,lever,ashby,workable,generic',
            'meta'        => 'nullable|array',
        ]);

        $source = JobSource::create($data);

        return response()->json($source->load('company'), 201);
    }

    /**
     * DELETE /api/job-sources/{jobSource}
     * Remove a registered source.
     */
    public function destroy(JobSource $jobSource): JsonResponse
    {
        $jobSource->delete();

        return response()->json(['message' => 'Job source removed.']);
    }

    /**
     * POST /api/job-sources/{jobSource}/trigger
     * Manually queue a fetch for this specific source.
     *
     * Dispatches the appropriate ATS job based on ats_type.
     * Generic / workable sources use source_url directly.
     */
    public function trigger(JobSource $jobSource): JsonResponse
    {
        $companyName = $jobSource->company->name ?? 'unknown';

        match ($jobSource->ats_type) {
            'greenhouse' => FetchGreenhouseJobs::dispatch(
                $jobSource->meta['board_token'] ?? $jobSource->source_url,
                $companyName
            ),
            'lever' => FetchLeverJobs::dispatch(
                $jobSource->meta['company_slug'] ?? $jobSource->source_url
            ),
            'ashby' => FetchAshbyJobs::dispatch(
                $jobSource->meta['organization_id'] ?? $jobSource->source_url
            ),
            default => Log::info('JobSourceController@trigger: no specific fetcher for ats_type', [
                'job_source_id' => $jobSource->id,
                'ats_type'      => $jobSource->ats_type,
            ]),
        };

        return response()->json(['message' => 'Fetch queued.'], 202);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\Opportunity;
use App\Services\MatchScoringService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function __construct(private MatchScoringService $scorer) {}

    public function index(Request $request): JsonResponse
    {
        $query = JobListing::with(['company', 'skills'])
            ->withCount('opportunities');

        if ($request->filled('search')) {
            $q = $request->input('search');
            $query->where(function ($q2) use ($q) {
                $q2->where('title', 'like', "%{$q}%")
                   ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($request->boolean('remote')) {
            $query->where('is_remote', true);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('country')) {
            $query->where('country', $request->input('country'));
        }

        if ($request->filled('workplace_type')) {
            $query->where('workplace_type', $request->input('workplace_type'));
        }

        if ($request->filled('experience_level')) {
            $query->where('experience_level', $request->input('experience_level'));
        }

        if ($request->filled('employment_type')) {
            $query->where('employment_type', $request->input('employment_type'));
        }

        if ($request->filled('source')) {
            $query->where('source', $request->input('source'));
        }

        if ($request->filled('salary_min')) {
            $query->where('salary_min', '>=', $request->input('salary_min'));
        }

        if ($request->filled('date_posted')) {
            $query->where('posted_at', '>=', now()->subDays($request->input('date_posted')));
        }

        $jobs = $query->orderByDesc('posted_at')
            ->paginate($request->input('per_page', 20));

        return response()->json($jobs);
    }

    public function show(JobListing $job): JsonResponse
    {
        return response()->json(
            $job->load(['company', 'skills', 'opportunities'])
        );
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'company_id'      => 'nullable|exists:companies,id',
            'company_name'    => 'required_without:company_id|string|max:255',
            'company_website' => 'sometimes|nullable|url',
            'title'           => 'required|string|max:255',
            'description'     => 'sometimes|nullable|string',
            'location'        => 'sometimes|nullable|string|max:255',
            'is_remote'       => 'sometimes|boolean',
            'employment_type' => 'sometimes|nullable|string|max:50',
            'salary_min'      => 'sometimes|nullable|numeric|min:0',
            'salary_max'      => 'sometimes|nullable|numeric|min:0',
            'salary_currency' => 'sometimes|nullable|string|max:10',
            'application_url' => 'sometimes|nullable|url|max:500',
            'source_url'      => 'sometimes|nullable|url|max:500',
            'source'          => 'sometimes|nullable|string|max:50',
            'posted_at'       => 'sometimes|nullable|date',
            'skills'          => 'sometimes|array',
            'skills.*'        => 'string|max:100',
        ]);

        // Resolve or create company
        $companyId = $data['company_id'] ?? null;
        if (!$companyId && !empty($data['company_name'])) {
            $company = Company::findOrCreateByDomain([
                'name'    => $data['company_name'],
                'website' => $data['company_website'] ?? null,
            ]);
            $companyId = $company->id;
        }

        // Deduplicate by source_url / external_id
        if (!empty($data['source_url'])) {
            $existing = JobListing::where('source_url', $data['source_url'])->first();
            if ($existing) {
                return response()->json($existing->load(['company', 'skills']), 200);
            }
        }

        $job = JobListing::create(array_merge(
            array_diff_key($data, array_flip(['company_name', 'company_website', 'skills'])),
            ['company_id' => $companyId]
        ));

        // Attach skills
        if (!empty($data['skills'])) {
            foreach (array_unique($data['skills']) as $skill) {
                $job->skills()->firstOrCreate(['skill' => strtolower(trim($skill))]);
            }
        }

        // Auto-create opportunity for the current user
        $user    = $request->user();
        $profile = $user->candidateProfile()->with(['skills', 'experiences'])->first();

        if ($profile) {
            $scoreResult = $this->scorer->score($profile, $job->load(['skills', 'company']));
            Opportunity::create([
                'user_id'             => $user->id,
                'job_listing_id'      => $job->id,
                'company_id'          => $job->company_id,
                'match_score'         => $scoreResult['score'],
                'match_classification'=> $scoreResult['classification'],
                'matched_skills'      => $scoreResult['matched_skills'],
                'missing_skills'      => $scoreResult['missing_skills'],
                'match_reasoning'     => $scoreResult['reasoning'],
                'score_breakdown'     => $scoreResult['score_breakdown'],
                'application_url'     => $job->application_url,
            ]);
        }

        return response()->json($job->load(['company', 'skills']), 201);
    }

    public function filters(): JsonResponse
    {
        return response()->json([
            'workplace_types'   => ['remote', 'hybrid', 'onsite', 'unknown'],
            'experience_levels' => ['internship', 'entry', 'mid', 'senior', 'lead', 'executive', 'unknown'],
            'employment_types'  => JobListing::whereNotNull('employment_type')->distinct()->pluck('employment_type'),
            'sources'           => JobListing::whereNotNull('source')->distinct()->pluck('source'),
            'countries'         => JobListing::whereNotNull('country')->distinct()->orderBy('country')->pluck('country'),
        ]);
    }

    public function destroy(JobListing $job): JsonResponse
    {
        $job->delete();
        return response()->json(['message' => 'Job deleted.']);
    }
}

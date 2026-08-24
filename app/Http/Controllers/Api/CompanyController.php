<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Company::query()
            ->with(['contacts'])
            ->withCount(['jobs', 'opportunities']);

        if ($request->filled('search')) {
            $q = $request->input('search');
            $query->where(function ($q2) use ($q) {
                $q2->where('name', 'like', "%{$q}%")
                   ->orWhere('domain', 'like', "%{$q}%")
                   ->orWhere('industry', 'like', "%{$q}%");
            });
        }

        if ($request->filled('industry')) {
            $query->where('industry', $request->input('industry'));
        }

        if ($request->boolean('excluded') === false) {
            $query->where('is_excluded', false);
        }

        $companies = $query->orderBy('name')
            ->paginate($request->input('per_page', 20));

        return response()->json($companies);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'required|string|max:255',
            'website'      => 'sometimes|nullable|url|max:500',
            'careers_url'  => 'sometimes|nullable|url|max:500',
            'linkedin_url' => 'sometimes|nullable|url|max:500',
            'description'  => 'sometimes|nullable|string',
            'industry'     => 'sometimes|nullable|string|max:100',
            'location'     => 'sometimes|nullable|string|max:255',
            'size'         => 'sometimes|nullable|string|max:100',
            'tech_stack'   => 'sometimes|nullable|array',
            'contact_email'=> 'sometimes|nullable|email',
        ]);

        $company = Company::findOrCreateByDomain($data);

        return response()->json($company->load(['contacts', 'sources']), 201);
    }

    public function show(Company $company): JsonResponse
    {
        return response()->json(
            $company->load(['contacts', 'sources', 'jobs' => fn($q) => $q->limit(10)])
                    ->loadCount(['jobs', 'opportunities'])
        );
    }

    public function update(Request $request, Company $company): JsonResponse
    {
        $data = $request->validate([
            'name'         => 'sometimes|string|max:255',
            'website'      => 'sometimes|nullable|url|max:500',
            'careers_url'  => 'sometimes|nullable|url|max:500',
            'linkedin_url' => 'sometimes|nullable|url|max:500',
            'description'  => 'sometimes|nullable|string',
            'industry'     => 'sometimes|nullable|string|max:100',
            'location'     => 'sometimes|nullable|string|max:255',
            'size'         => 'sometimes|nullable|string|max:100',
            'tech_stack'   => 'sometimes|nullable|array',
            'contact_email'=> 'sometimes|nullable|email',
            'contact_status'=> 'sometimes|in:available,unavailable,partial',
        ]);

        $company->update($data);

        return response()->json($company->load(['contacts', 'sources']));
    }

    public function destroy(Company $company): JsonResponse
    {
        $company->delete();
        return response()->json(['message' => 'Company deleted.']);
    }

    public function bulkDestroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids'   => 'sometimes|array',
            'ids.*' => 'integer',
            'all'   => 'sometimes|boolean',
        ]);

        $userId = $request->user()->id;

        // Only delete companies that belong exclusively to this user's opportunities.
        // Never delete a company that another user has opportunities or jobs linked to.
        $deletableIds = Company::whereDoesntHave('opportunities', function ($q) use ($userId) {
            $q->where('user_id', '!=', $userId);
        })->pluck('id');

        if (!empty($data['all'])) {
            $count = Company::whereIn('id', $deletableIds)->count();
            Company::whereIn('id', $deletableIds)->delete();
        } else {
            $ids   = collect($data['ids'] ?? [])->intersect($deletableIds)->values()->all();
            $count = Company::whereIn('id', $ids)->count();
            Company::whereIn('id', $ids)->delete();
        }

        return response()->json(['message' => "Deleted {$count} companies.", 'count' => $count]);
    }

    public function exclude(Company $company): JsonResponse
    {
        $company->update(['is_excluded' => true]);
        return response()->json(['message' => 'Company excluded from outreach.']);
    }

    public function include(Company $company): JsonResponse
    {
        $company->update(['is_excluded' => false]);
        return response()->json(['message' => 'Company included in outreach.']);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Services\AI\AIService;
use App\Services\MatchScoringService;
use App\Models\EmailMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function __construct(
        private MatchScoringService $scorer,
    ) {}

    // AI service is instantiated with userId so it picks up user's DB key
    private function ai(int $userId): AIService
    {
        return new AIService($userId);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Opportunity::with(['job', 'company', 'contact', 'emails'])
            ->where('user_id', $request->user()->id);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('min_score')) {
            $query->where('match_score', '>=', $request->input('min_score'));
        }

        if ($request->filled('classification')) {
            $query->where('match_classification', $request->input('classification'));
        }

        if ($request->filled('search')) {
            $q = $request->input('search');
            $query->whereHas('job', fn($q2) => $q2->where('title', 'like', "%{$q}%"))
                  ->orWhereHas('company', fn($q2) => $q2->where('name', 'like', "%{$q}%"));
        }

        $opportunities = $query->orderByDesc('match_score')
            ->paginate($request->input('per_page', 20));

        return response()->json($opportunities);
    }

    public function show(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('view', $opportunity);

        return response()->json(
            $opportunity->load([
                'job.skills',
                'company.contacts',
                'contact',
                'emails.events',
                'followUps',
                'application',
            ])
        );
    }

    public function update(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $data = $request->validate([
            'status'          => 'sometimes|in:discovered,shortlisted,contacted,follow_up,replied,interview,offer,rejected,closed',
            'notes'           => 'sometimes|nullable|string',
            'application_url' => 'sometimes|nullable|url',
            'interview_dates' => 'sometimes|nullable|array',
        ]);

        $opportunity->update($data);

        return response()->json($opportunity);
    }

    public function approve(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $opportunity->update(['status' => 'shortlisted']);

        return response()->json(['message' => 'Opportunity approved.', 'opportunity' => $opportunity]);
    }

    public function reject(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $opportunity->update(['status' => 'rejected']);

        // Cancel pending follow-ups
        $opportunity->followUps()->where('status', 'pending')->update([
            'status'             => 'cancelled',
            'cancellation_reason'=> 'opportunity_rejected',
        ]);

        return response()->json(['message' => 'Opportunity rejected.', 'opportunity' => $opportunity]);
    }

    public function score(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $profile = $request->user()->candidateProfile()->with(['skills', 'experiences'])->first();
        if (!$profile) {
            return response()->json(['message' => 'Candidate profile not found.'], 422);
        }

        $job         = $opportunity->job()->with(['skills', 'company'])->first();
        $scoreResult = $this->scorer->score($profile, $job);

        $opportunity->update([
            'match_score'         => $scoreResult['score'],
            'match_classification'=> $scoreResult['classification'],
            'matched_skills'      => $scoreResult['matched_skills'],
            'missing_skills'      => $scoreResult['missing_skills'],
            'match_reasoning'     => $scoreResult['reasoning'],
            'score_breakdown'     => $scoreResult['score_breakdown'],
        ]);

        return response()->json($scoreResult);
    }

    public function generateEmail(Request $request, Opportunity $opportunity): JsonResponse
    {
        $this->authorize('update', $opportunity);

        $profile = $request->user()->candidateProfile()->with(['skills', 'experiences'])->first();
        if (!$profile) {
            return response()->json(['message' => 'Candidate profile not found.'], 422);
        }

        $job     = $opportunity->job()->with(['skills', 'company'])->first();
        $company = $opportunity->company;
        $contact = $opportunity->contact;

        $jobData = [
            'title'           => $job->title,
            'description'     => $job->description,
            'location'        => $job->location,
            'is_remote'       => $job->is_remote,
            'company_name'    => $company?->name,
            'required_skills' => $job->skill_names,
            'contact_name'    => $contact?->name,
        ];

        $companyData = [
            'name'        => $company?->name,
            'description' => $company?->description,
            'tech_stack'  => $company?->tech_stack ?? [],
            'industry'    => $company?->industry,
        ];

        $profileData = [
            'full_name'         => $profile->full_name,
            'primary_title'     => $profile->primary_title,
            'portfolio_url'     => $profile->portfolio_url,
            'skills'            => $profile->skill_names,
            'experiences'       => $profile->experiences->toArray(),
            'years_of_experience'=> $profile->years_of_experience,
        ];

        $result = $this->ai($request->user()->id)->generateEmail($jobData, $companyData, $profileData);

        // Create/update draft email
        $email = EmailMessage::updateOrCreate(
            ['opportunity_id' => $opportunity->id, 'status' => 'draft'],
            [
                'user_id'          => $request->user()->id,
                'recipient_email'  => $contact?->email ?? $company?->contact_email ?? '',
                'recipient_name'   => $contact?->name,
                'subject'          => $result['subject'],
                'body_text'        => $result['body'],
                'body_html'        => nl2br(htmlspecialchars($result['body'])),
                'status'           => 'draft',
            ]
        );

        return response()->json([
            'email'   => $email,
            'subject' => $result['subject'],
            'body'    => $result['body'],
        ]);
    }
}

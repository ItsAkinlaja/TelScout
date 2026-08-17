<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use App\Models\Opportunity;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\FollowUp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $today  = now()->startOfDay();

        // Today's stats
        $todayOpportunities = Opportunity::where('user_id', $userId)
            ->whereDate('discovered_at', today())
            ->get();

        $todayEmails = EmailMessage::where('user_id', $userId)
            ->whereDate('created_at', today())
            ->get();

        // All-time stats
        $allOpportunities = Opportunity::where('user_id', $userId);

        $stats = [
            'today' => [
                'companies_discovered'  => Company::whereDate('created_at', today())->count(),
                'opportunities_found'   => $todayOpportunities->count(),
                'strong_matches'        => $todayOpportunities->whereIn('match_classification', ['excellent', 'strong'])->count(),
                'ready_for_outreach'    => $todayOpportunities->where('status', 'shortlisted')->count(),
                'awaiting_approval'     => EmailMessage::where('user_id', $userId)->where('status', 'draft')->count(),
                'emails_sent'           => EmailMessage::where('user_id', $userId)->whereDate('sent_at', today())->where('status', 'sent')->count(),
                'replies'               => EmailMessage::where('user_id', $userId)->where('status', 'replied')->whereDate('updated_at', today())->count(),
                'interviews'            => Opportunity::where('user_id', $userId)->where('status', 'interview')->whereDate('updated_at', today())->count(),
            ],
            'totals' => [
                'total_opportunities'   => (clone $allOpportunities)->count(),
                'shortlisted'           => (clone $allOpportunities)->where('status', 'shortlisted')->count(),
                'contacted'             => (clone $allOpportunities)->where('status', 'contacted')->count(),
                'replied'               => (clone $allOpportunities)->where('status', 'replied')->count(),
                'interviews'            => (clone $allOpportunities)->where('status', 'interview')->count(),
                'offers'                => (clone $allOpportunities)->where('status', 'offer')->count(),
                'rejected'              => (clone $allOpportunities)->where('status', 'rejected')->count(),
                'follow_ups_due'        => FollowUp::where('user_id', $userId)->where('status', 'pending')->where('scheduled_at', '<=', now())->count(),
                'emails_sent_total'     => EmailMessage::where('user_id', $userId)->where('status', 'sent')->count(),
                'total_companies'       => Company::count(),
                'total_jobs'            => JobListing::count(),
            ],
            'match_distribution' => [
                'excellent' => (clone $allOpportunities)->where('match_classification', 'excellent')->count(),
                'strong'    => (clone $allOpportunities)->where('match_classification', 'strong')->count(),
                'good'      => (clone $allOpportunities)->where('match_classification', 'good')->count(),
                'possible'  => (clone $allOpportunities)->where('match_classification', 'possible')->count(),
                'low'       => (clone $allOpportunities)->where('match_classification', 'low')->count(),
            ],
        ];

        // Recent opportunities
        $recentOpportunities = Opportunity::with(['job', 'company'])
            ->where('user_id', $userId)
            ->orderByDesc('match_score')
            ->limit(5)
            ->get();

        // Outreach over time (last 14 days)
        $outreachChart = EmailMessage::where('user_id', $userId)
            ->where('status', 'sent')
            ->where('sent_at', '>=', now()->subDays(14))
            ->selectRaw('DATE(sent_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'stats'                => $stats,
            'recent_opportunities' => $recentOpportunities,
            'outreach_chart'       => $outreachChart,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailMessage;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $days   = (int) $request->input('days', 30);
        $from   = now()->subDays($days)->startOfDay();

        $emails      = EmailMessage::where('user_id', $userId);
        $sent        = (clone $emails)->where('status', 'sent');
        $replied     = (clone $emails)->where('status', 'replied');
        $opps        = Opportunity::where('user_id', $userId);

        $totalSent    = (clone $sent)->count();
        $totalReplied = (clone $replied)->count();
        $replyRate    = $totalSent > 0 ? round($totalReplied / $totalSent * 100, 1) : 0;

        $interviews   = (clone $opps)->where('status', 'interview')->count();
        $interviewRate= $totalSent > 0 ? round($interviews / $totalSent * 100, 1) : 0;

        $avgScore = (clone $opps)->avg('match_score');

        // Outreach over time
        $outreachOverTime = EmailMessage::where('user_id', $userId)
            ->where('status', 'sent')
            ->where('sent_at', '>=', $from)
            ->selectRaw('DATE(sent_at) as date, COUNT(*) as emails_sent')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Replies over time
        $repliesOverTime = EmailMessage::where('user_id', $userId)
            ->where('status', 'replied')
            ->where('updated_at', '>=', $from)
            ->selectRaw('DATE(updated_at) as date, COUNT(*) as replies')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Applications by status
        $byStatus = Opportunity::where('user_id', $userId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Match score distribution
        $scoreDistribution = Opportunity::where('user_id', $userId)
            ->selectRaw('match_classification, COUNT(*) as count')
            ->groupBy('match_classification')
            ->get()
            ->pluck('count', 'match_classification');

        return response()->json([
            'summary' => [
                'emails_sent'     => $totalSent,
                'replies'         => $totalReplied,
                'reply_rate'      => $replyRate,
                'interviews'      => $interviews,
                'interview_rate'  => $interviewRate,
                'avg_match_score' => round($avgScore ?? 0, 1),
                'period_days'     => $days,
            ],
            'charts' => [
                'outreach_over_time'    => $outreachOverTime,
                'replies_over_time'     => $repliesOverTime,
                'applications_by_status'=> $byStatus,
                'score_distribution'    => $scoreDistribution,
            ],
        ]);
    }
}

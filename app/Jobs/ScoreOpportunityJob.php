<?php

namespace App\Jobs;

use App\Models\Opportunity;
use App\Models\User;
use App\Services\MatchScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScoreOpportunityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 30;

    public function __construct(
        private int $opportunityId,
        private int $userId
    ) {}

    public function handle(MatchScoringService $scorer): void
    {
        $opportunity = Opportunity::findOrFail($this->opportunityId);
        $user        = User::findOrFail($this->userId);
        $profile     = $user->candidateProfile()->with(['skills', 'experiences'])->first();

        if (!$profile) return;

        $job    = $opportunity->job()->with(['skills', 'company'])->first();
        $result = $scorer->score($profile, $job);

        $opportunity->update([
            'match_score'          => $result['score'],
            'match_classification' => $result['classification'],
            'matched_skills'       => $result['matched_skills'],
            'missing_skills'       => $result['missing_skills'],
            'match_reasoning'      => $result['reasoning'],
            'score_breakdown'      => $result['score_breakdown'],
        ]);

        Log::info('Opportunity scored', ['id' => $this->opportunityId, 'score' => $result['score']]);
    }
}

<?php

namespace App\Jobs;

use App\Models\EmailMessage;
use App\Models\Opportunity;
use App\Models\User;
use App\Services\AI\AIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function __construct(
        private int $opportunityId,
        private int $userId
    ) {}

    public function handle(AIService $ai): void
    {
        $opportunity = Opportunity::with(['job.skills', 'company', 'contact'])->findOrFail($this->opportunityId);
        $user        = User::findOrFail($this->userId);
        $profile     = $user->candidateProfile()->with(['skills', 'experiences'])->first();

        if (!$profile) {
            Log::warning('GenerateEmailJob: no profile found', ['user_id' => $this->userId]);
            return;
        }

        $job     = $opportunity->job;
        $company = $opportunity->company;
        $contact = $opportunity->contact;

        $result = $ai->generateEmail(
            [
                'title'           => $job->title,
                'description'     => $job->description,
                'location'        => $job->location,
                'is_remote'       => $job->is_remote,
                'company_name'    => $company?->name,
                'required_skills' => $job->skill_names,
                'contact_name'    => $contact?->name,
            ],
            [
                'name'        => $company?->name,
                'description' => $company?->description,
                'tech_stack'  => $company?->tech_stack ?? [],
                'industry'    => $company?->industry,
            ],
            [
                'full_name'          => $profile->full_name,
                'primary_title'      => $profile->primary_title,
                'portfolio_url'      => $profile->portfolio_url,
                'skills'             => $profile->skill_names,
                'experiences'        => $profile->experiences->toArray(),
                'years_of_experience'=> $profile->years_of_experience,
            ]
        );

        EmailMessage::updateOrCreate(
            ['opportunity_id' => $opportunity->id, 'status' => 'draft'],
            [
                'user_id'         => $this->userId,
                'recipient_email' => $contact?->email ?? $company?->contact_email ?? '',
                'recipient_name'  => $contact?->name,
                'subject'         => $result['subject'],
                'body_text'       => $result['body'],
                'body_html'       => nl2br(htmlspecialchars($result['body'])),
                'status'          => 'draft',
            ]
        );

        Log::info('Email generated', ['opportunity_id' => $this->opportunityId]);
    }

    public function backoff(): array
    {
        return [60, 300];
    }
}

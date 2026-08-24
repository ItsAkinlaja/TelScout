<?php

namespace Tests\Feature;

use App\Models\AutomationSettings;
use App\Models\EmailMessage;
use App\Models\Opportunity;
use App\Models\Company;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailSafetyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        AutomationSettings::create([
            'user_id'         => $this->user->id,
            'daily_send_limit'=> 3,
            'require_approval'=> true,
            'auto_send'       => false,
        ]);
    }

    public function test_email_requires_approval_before_sending(): void
    {
        $email = EmailMessage::create([
            'user_id'         => $this->user->id,
            'recipient_email' => 'recruiter@company.com',
            'subject'         => 'Test',
            'body_text'       => 'Test body',
            'status'          => 'draft', // Not approved
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/emails/{$email->id}/send")
            ->assertStatus(422);
    }

    public function test_daily_limit_blocks_sending(): void
    {
        // Already sent 3 emails today (the limit)
        for ($i = 0; $i < 3; $i++) {
            EmailMessage::create([
                'user_id'         => $this->user->id,
                'recipient_email' => "recruiter{$i}@company.com",
                'subject'         => 'Test',
                'body_text'       => 'Body',
                'status'          => 'sent',
                'sent_at'         => now(),
            ]);
        }

        $email = EmailMessage::create([
            'user_id'         => $this->user->id,
            'recipient_email' => 'new@company.com',
            'subject'         => 'Test',
            'body_text'       => 'Body',
            'status'          => 'approved',
            'approved_at'     => now(),
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/emails/{$email->id}/send")
            ->assertStatus(429);
    }

    public function test_duplicate_recipient_is_blocked(): void
    {
        // Already sent to this email
        EmailMessage::create([
            'user_id'         => $this->user->id,
            'recipient_email' => 'recruiter@company.com',
            'subject'         => 'First email',
            'body_text'       => 'Body',
            'status'          => 'sent',
            'sent_at'         => now(),
        ]);

        $email = EmailMessage::create([
            'user_id'         => $this->user->id,
            'recipient_email' => 'recruiter@company.com', // duplicate
            'subject'         => 'Follow up',
            'body_text'       => 'Body',
            'status'          => 'approved',
            'approved_at'     => now(),
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/emails/{$email->id}/send")
            ->assertStatus(422);
    }

    public function test_can_approve_draft_email(): void
    {
        $email = EmailMessage::create([
            'user_id'         => $this->user->id,
            'recipient_email' => 'test@co.com',
            'subject'         => 'Test',
            'body_text'       => 'Body',
            'status'          => 'draft',
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/emails/{$email->id}/approve")
            ->assertOk();

        $this->assertEquals('approved', $email->fresh()->status);
    }

    public function test_hourly_limit_blocks_sending(): void
    {
        // Patch: set hourly_send_limit to 2
        AutomationSettings::where('user_id', $this->user->id)
            ->update(['hourly_send_limit' => 2]);

        // Already sent 2 emails within the last hour
        for ($i = 0; $i < 2; $i++) {
            EmailMessage::create([
                'user_id'         => $this->user->id,
                'recipient_email' => "hourly{$i}@company.com",
                'subject'         => 'Test',
                'body_text'       => 'Body',
                'status'          => 'sent',
                'sent_at'         => now()->subMinutes(10),
            ]);
        }

        $email = EmailMessage::create([
            'user_id'         => $this->user->id,
            'recipient_email' => 'nexthour@company.com',
            'subject'         => 'Test',
            'body_text'       => 'Body',
            'status'          => 'approved',
            'approved_at'     => now(),
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/emails/{$email->id}/send")
            ->assertStatus(429)
            ->assertJsonFragment(['message' => 'Hourly send limit of 2 reached. Try again shortly.']);
    }

    public function test_blacklisted_domain_is_rejected(): void
    {
        $email = EmailMessage::create([
            'user_id'         => $this->user->id,
            'recipient_email' => 'someone@gmail.com',
            'subject'         => 'Test',
            'body_text'       => 'Body',
            'status'          => 'approved',
            'approved_at'     => now(),
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/emails/{$email->id}/send")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Sending to this domain is not allowed.']);
    }

    public function test_follow_up_is_cancelled_when_opportunity_rejected(): void
    {
        $company = Company::create(['name' => 'Test Co']);
        $job     = JobListing::create(['company_id' => $company->id, 'title' => 'Dev']);
        $opp     = Opportunity::create([
            'user_id'        => $this->user->id,
            'job_listing_id' => $job->id,
            'company_id'     => $company->id,
            'status'         => 'contacted',
        ]);

        \App\Models\FollowUp::create([
            'user_id'          => $this->user->id,
            'opportunity_id'   => $opp->id,
            'follow_up_number' => 1,
            'scheduled_at'     => now()->addDays(4),
            'status'           => 'pending',
        ]);

        // Rejecting should cancel follow-ups
        $this->actingAs($this->user)
            ->postJson("/api/opportunities/{$opp->id}/reject")
            ->assertOk();

        $this->assertEquals('cancelled', $opp->followUps()->first()->fresh()->status);
    }
}

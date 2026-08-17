<?php

namespace App\Jobs;

use App\Models\AutomationSettings;
use App\Models\EmailMessage;
use App\Models\FollowUp;
use App\Models\Opportunity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessFollowUpJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 60;

    public function handle(): void
    {
        // Find all pending follow-ups that are due
        $dueFollowUps = FollowUp::with(['opportunity.company', 'emailMessage'])
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($dueFollowUps as $followUp) {
            $opportunity = $followUp->opportunity;

            // Auto-cancel if opportunity is replied/rejected/closed
            if (in_array($opportunity->status, ['replied', 'rejected', 'closed', 'offer'])) {
                $followUp->update([
                    'status'              => 'cancelled',
                    'cancellation_reason' => 'opportunity_' . $opportunity->status,
                ]);
                continue;
            }

            // Check if contact opted out
            $contact = $opportunity->contact;
            if ($contact?->opted_out) {
                $followUp->update([
                    'status'              => 'cancelled',
                    'cancellation_reason' => 'contact_opted_out',
                ]);
                continue;
            }

            // Check settings for max follow-ups
            $settings   = AutomationSettings::where('user_id', $opportunity->user_id)->first();
            $maxFollowUp = $settings?->max_follow_ups ?? 2;

            if ($followUp->follow_up_number > $maxFollowUp) {
                $followUp->update([
                    'status'              => 'cancelled',
                    'cancellation_reason' => 'max_follow_ups_reached',
                ]);
                continue;
            }

            // Update opportunity status to follow_up
            $opportunity->update(['status' => 'follow_up']);
            $opportunity->emails()->where('status', 'sent')->latest()->update(['status' => 'follow_up_due']);

            Log::info('Follow-up due', [
                'follow_up_id'   => $followUp->id,
                'opportunity_id' => $opportunity->id,
            ]);
        }
    }
}

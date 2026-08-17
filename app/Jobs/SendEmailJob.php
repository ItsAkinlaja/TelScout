<?php

namespace App\Jobs;

use App\Models\EmailMessage;
use App\Models\EmailEvent;
use App\Models\User;
use App\Services\Mail\MailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private int $emailId,
        private int $userId
    ) {}

    public function handle(): void
    {
        $email = EmailMessage::findOrFail($this->emailId);

        if ($email->status !== 'queued') {
            Log::warning('SendEmailJob: email not in queued state', [
                'email_id' => $this->emailId,
                'status'   => $email->status,
            ]);
            return;
        }

        $user    = User::findOrFail($this->userId);
        $account = $user->defaultMailAccount();

        if (!$account) {
            $this->fail('No mail account connected. Connect Gmail, Outlook, or SMTP in Settings → Mail.');
            return;
        }

        $email->update(['status' => 'sending']);

        try {
            $provider       = MailService::for($account);
            $attachmentPath = null;

            if ($email->attach_cv && $email->cv_path) {
                $attachmentPath = storage_path('app/' . $email->cv_path);
                if (!file_exists($attachmentPath)) {
                    $attachmentPath = null;
                    Log::warning('SendEmailJob: Attachment file missing', ['path' => $email->cv_path]);
                }
            }

            $messageId = $provider->send($email, $attachmentPath);

            $email->update([
                'status'           => 'sent',
                'sent_at'          => now(),
                'google_account_id'=> $account->provider === 'gmail' ? $account->id : null,
                'meta'             => array_merge($email->meta ?? [], [
                    'provider'   => $account->provider,
                    'account_id' => $account->id,
                    'message_id' => $messageId,
                ]),
            ]);

            EmailEvent::create([
                'email_message_id' => $email->id,
                'event_type'       => 'sent',
                'description'      => "Email sent via {$provider->getName()}.",
            ]);

            if ($email->opportunity_id) {
                $email->opportunity()->update(['status' => 'contacted']);
            }

            Log::info('Email sent', [
                'email_id'  => $email->id,
                'provider'  => $account->provider,
                'recipient' => $email->recipient_email,
            ]);

        } catch (\Exception $e) {
            $isPermanent = $this->isPermanentFailure($e->getMessage());

            $email->update([
                'status'         => 'failed',
                'failure_reason' => $e->getMessage(),
                'retry_count'    => $email->retry_count + 1,
            ]);

            EmailEvent::create([
                'email_message_id' => $email->id,
                'event_type'       => 'failed',
                'description'      => $e->getMessage(),
            ]);

            Log::error('Email send failed', ['email_id' => $email->id, 'error' => $e->getMessage()]);

            if ($isPermanent) {
                $this->fail($e);
            } else {
                throw $e;
            }
        }
    }

    private function isPermanentFailure(string $message): bool
    {
        $permanent = ['invalid recipient', '5.1.1', 'does not exist', 'user unknown', 'no such user'];
        foreach ($permanent as $p) {
            if (str_contains(strtolower($message), $p)) return true;
        }
        return false;
    }

    public function backoff(): array
    {
        return [30, 120, 300];
    }
}

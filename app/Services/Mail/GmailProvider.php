<?php

namespace App\Services\Mail;

use App\Models\EmailMessage;
use App\Models\MailAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GmailProvider implements MailProviderInterface
{
    public function __construct(private MailAccount $account) {}

    public function getName(): string { return 'Gmail'; }

    public function send(EmailMessage $email): string
    {
        $this->ensureFreshToken();

        $raw     = $this->buildRaw($email);
        $encoded = rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');

        $response = Http::withToken($this->account->getAccessToken())
            ->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
                'raw' => $encoded,
            ]);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown Gmail API error');
            throw new RuntimeException("Gmail: {$error} (HTTP {$response->status()})");
        }

        $result = $response->json();

        // Store Gmail message/thread IDs back on the email
        $email->update([
            'gmail_message_id' => $result['id']       ?? null,
            'gmail_thread_id'  => $result['threadId'] ?? null,
        ]);

        return $result['id'] ?? 'gmail-sent';
    }

    public function verify(): bool
    {
        $this->ensureFreshToken();
        $res = Http::withToken($this->account->getAccessToken())
            ->get('https://gmail.googleapis.com/gmail/v1/users/me/profile');
        return $res->ok();
    }

    private function buildRaw(EmailMessage $email): string
    {
        $to = $email->recipient_name
            ? "\"{$email->recipient_name}\" <{$email->recipient_email}>"
            : $email->recipient_email;

        return implode("\r\n", [
            "To: {$to}",
            "Subject: =?UTF-8?B?" . base64_encode($email->subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: text/plain; charset=UTF-8",
            "Content-Transfer-Encoding: base64",
            "",
            base64_encode($email->body_text ?? strip_tags($email->body_html ?? '')),
        ]);
    }

    private function ensureFreshToken(): void
    {
        if (!$this->account->isTokenExpired()) return;

        $refreshToken = $this->account->getRefreshToken();
        if (!$refreshToken) {
            throw new RuntimeException('Gmail token expired. Please reconnect your Gmail account.');
        }

        $res = Http::post('https://oauth2.googleapis.com/token', [
            'client_id'     => $this->account->getOAuthClientId(),
            'client_secret' => $this->account->getOAuthClientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
        ]);

        if ($res->failed()) {
            throw new RuntimeException('Failed to refresh Gmail token. Reconnect your account.');
        }

        $tokens = $res->json();
        $this->account->setAccessToken($tokens['access_token']);
        $this->account->token_expires_at = now()->addSeconds($tokens['expires_in'] ?? 3600);
        $this->account->save();

        Log::info('Gmail token refreshed', ['account_id' => $this->account->id]);
    }
}

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

    public function send(EmailMessage $email, ?string $attachmentPath = null): string
    {
        $this->ensureFreshToken();

        $raw     = $this->buildRaw($email, $attachmentPath);
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

    private function buildRaw(EmailMessage $email, ?string $attachmentPath): string
    {
        $to = $email->recipient_name
            ? "\"{$email->recipient_name}\" <{$email->recipient_email}>"
            : $email->recipient_email;
        $subject = "=?UTF-8?B?" . base64_encode($email->subject) . "?=";
        $body = $email->body_text ?? strip_tags($email->body_html ?? '');

        if (!$attachmentPath || !file_exists($attachmentPath)) {
            return implode("\r\n", [
                "To: {$to}",
                "Subject: {$subject}",
                "MIME-Version: 1.0",
                "Content-Type: text/plain; charset=UTF-8",
                "Content-Transfer-Encoding: base64",
                "",
                base64_encode($body),
            ]);
        }

        $boundary = uniqid('np', true);
        $fileContent = chunk_split(base64_encode(file_get_contents($attachmentPath)));
        $fileName = basename($attachmentPath);

        $raw = "To: {$to}\r\n";
        $raw .= "Subject: {$subject}\r\n";
        $raw .= "MIME-Version: 1.0\r\n";
        $raw .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n\r\n";

        $raw .= "--{$boundary}\r\n";
        $raw .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $raw .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $raw .= base64_encode($body) . "\r\n\r\n";

        $raw .= "--{$boundary}\r\n";
        $raw .= "Content-Type: application/pdf; name=\"{$fileName}\"\r\n";
        $raw .= "Content-Description: {$fileName}\r\n";
        $raw .= "Content-Disposition: attachment; filename=\"{$fileName}\"; size=" . filesize($attachmentPath) . ";\r\n";
        $raw .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $raw .= $fileContent . "\r\n";
        $raw .= "--{$boundary}--";

        return $raw;
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

<?php

namespace App\Services\Mail;

use App\Models\EmailMessage;
use App\Models\MailAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Microsoft Graph API (Outlook / Microsoft 365)
 * OAuth scopes required: Mail.Send, User.Read, offline_access
 * OAuth app: https://portal.azure.com → App registrations
 */
class OutlookProvider implements MailProviderInterface
{
    private string $graphBase = 'https://graph.microsoft.com/v1.0';
    private string $tokenUrl  = 'https://login.microsoftonline.com/common/oauth2/v2.0/token';

    public function __construct(private MailAccount $account) {}

    public function getName(): string { return 'Outlook'; }

    public function send(EmailMessage $email): string
    {
        $this->ensureFreshToken();

        $payload = [
            'message' => [
                'subject' => $email->subject,
                'body'    => [
                    'contentType' => 'Text',
                    'content'     => $email->body_text ?? strip_tags($email->body_html ?? ''),
                ],
                'toRecipients' => [[
                    'emailAddress' => [
                        'address' => $email->recipient_email,
                        'name'    => $email->recipient_name ?? $email->recipient_email,
                    ],
                ]],
            ],
            'saveToSentItems' => true,
        ];

        $response = Http::withToken($this->account->getAccessToken())
            ->post("{$this->graphBase}/me/sendMail", $payload);

        if ($response->failed()) {
            $error = $response->json('error.message', 'Unknown Microsoft Graph error');
            throw new RuntimeException("Outlook: {$error} (HTTP {$response->status()})");
        }

        // Graph sendMail returns 202 with no body — generate a reference ID
        return 'outlook-' . uniqid();
    }

    public function verify(): bool
    {
        $this->ensureFreshToken();
        $res = Http::withToken($this->account->getAccessToken())
            ->get("{$this->graphBase}/me");
        return $res->ok();
    }

    private function ensureFreshToken(): void
    {
        if (!$this->account->isTokenExpired()) return;

        $refreshToken = $this->account->getRefreshToken();
        if (!$refreshToken) {
            throw new RuntimeException('Outlook token expired. Please reconnect your Outlook account.');
        }

        $res = Http::asForm()->post($this->tokenUrl, [
            'client_id'     => $this->account->getOAuthClientId(),
            'client_secret' => $this->account->getOAuthClientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type'    => 'refresh_token',
            'scope'         => 'https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/User.Read offline_access',
        ]);

        if ($res->failed()) {
            throw new RuntimeException('Failed to refresh Outlook token. Reconnect your account.');
        }

        $tokens = $res->json();
        $this->account->setAccessToken($tokens['access_token']);
        if (!empty($tokens['refresh_token'])) {
            $this->account->setRefreshToken($tokens['refresh_token']);
        }
        $this->account->token_expires_at = now()->addSeconds($tokens['expires_in'] ?? 3600);
        $this->account->save();

        Log::info('Outlook token refreshed', ['account_id' => $this->account->id]);
    }
}

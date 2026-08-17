<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailAccount;
use App\Services\Mail\MailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MailAccountController extends Controller
{
    private array $oauthProviders = ['gmail', 'outlook'];
    private array $smtpProviders  = ['zoho', 'yahoo', 'smtp'];

    // ── List all connected accounts ──────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $accounts = MailAccount::where('user_id', $request->user()->id)
            ->get()
            ->map(fn($a) => $a->toSafeArray());

        return response()->json($accounts);
    }

    // ── Connect a new account (OAuth → redirect, SMTP → save directly) ───────

    public function connect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'provider'         => 'required|in:gmail,outlook,zoho,yahoo,smtp',
            'label'            => 'nullable|string|max:100',

            // OAuth credentials
            'oauth_client_id'     => 'required_if:provider,gmail,outlook|nullable|string',
            'oauth_client_secret' => 'required_if:provider,gmail,outlook|nullable|string',
            'oauth_redirect_uri'  => 'nullable|url',

            // SMTP credentials
            'email'            => 'required_if:provider,zoho,yahoo,smtp|nullable|email',
            'smtp_host'        => 'required_if:provider,zoho,yahoo,smtp|nullable|string',
            'smtp_port'        => 'required_if:provider,zoho,yahoo,smtp|nullable|integer',
            'smtp_encryption'  => 'nullable|in:tls,ssl,none',
            'smtp_username'    => 'required_if:provider,zoho,yahoo,smtp|nullable|string',
            'smtp_password'    => 'required_if:provider,zoho,yahoo,smtp|nullable|string',
        ]);

        $provider = $data['provider'];

        if (in_array($provider, $this->oauthProviders)) {
            return $this->initiateOAuth($request, $data);
        }

        return $this->saveSmtpAccount($request, $data);
    }

    // ── OAuth callback (handles Gmail and Outlook) ────────────────────────────

    public function callback(Request $request): mixed
    {
        $code     = $request->input('code');
        $state    = $request->input('state');   // contains account ID + provider
        $frontend = config('app.frontend_url', 'http://localhost:5173');

        if (!$code || !$state) {
            return redirect("{$frontend}/settings/mail?error=missing_params");
        }

        $stateData = json_decode(base64_decode($state), true);
        $accountId = $stateData['account_id'] ?? null;
        $provider  = $stateData['provider']   ?? null;

        $account = MailAccount::find($accountId);
        if (!$account) {
            return redirect("{$frontend}/settings/mail?error=account_not_found");
        }

        try {
            match ($provider) {
                'gmail'   => $this->handleGmailCallback($account, $code),
                'outlook' => $this->handleOutlookCallback($account, $code),
                default   => throw new \RuntimeException("Unknown provider: {$provider}"),
            };
        } catch (\Throwable $e) {
            Log::error('Mail OAuth callback failed', ['error' => $e->getMessage()]);
            return redirect("{$frontend}/settings/mail?error=oauth_failed");
        }

        return redirect("{$frontend}/settings/mail?connected=1&provider={$provider}");
    }

    // ── Disconnect / delete ───────────────────────────────────────────────────

    public function disconnect(Request $request, MailAccount $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $account->delete();

        return response()->json(['message' => "{$account->getProviderLabel()} account disconnected."]);
    }

    // ── Set as default ────────────────────────────────────────────────────────

    public function setDefault(Request $request, MailAccount $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        // Clear existing default
        MailAccount::where('user_id', $request->user()->id)->update(['is_default' => false]);
        $account->update(['is_default' => true]);

        return response()->json(['message' => "{$account->getProviderLabel()} set as default."]);
    }

    // ── Test connection ───────────────────────────────────────────────────────

    public function test(Request $request, MailAccount $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        try {
            $ok = MailService::for($account)->verify();
            return response()->json(['ok' => $ok, 'message' => $ok ? 'Connection successful.' : 'Connection failed.']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function initiateOAuth(Request $request, array $data): JsonResponse
    {
        // Create a pending account record to store credentials before OAuth
        $account = MailAccount::create([
            'user_id'  => $request->user()->id,
            'provider' => $data['provider'],
            'label'    => $data['label'] ?? null,
            'is_active'=> false, // activate after OAuth completes
        ]);

        if (!empty($data['oauth_client_id'])) {
            $account->setOAuthClientId($data['oauth_client_id']);
        }
        if (!empty($data['oauth_client_secret'])) {
            $account->setOAuthClientSecret($data['oauth_client_secret']);
        }
        $account->oauth_redirect_uri = $data['oauth_redirect_uri'] ?? url('/api/mail/callback');
        $account->save();

        $state = base64_encode(json_encode([
            'account_id' => $account->id,
            'provider'   => $data['provider'],
        ]));

        $authUrl = match ($data['provider']) {
            'gmail'   => $this->gmailAuthUrl($account, $state),
            'outlook' => $this->outlookAuthUrl($account, $state),
        };

        return response()->json([
            'redirect_url' => $authUrl,
            'account_id'   => $account->id,
        ]);
    }

    private function saveSmtpAccount(Request $request, array $data): JsonResponse
    {
        $smtpDefaults = match ($data['provider']) {
            'zoho'  => ['smtp_host' => 'smtp.zoho.com',          'smtp_port' => 587, 'smtp_encryption' => 'tls'],
            'yahoo' => ['smtp_host' => 'smtp.mail.yahoo.com',    'smtp_port' => 587, 'smtp_encryption' => 'tls'],
            default => ['smtp_host' => $data['smtp_host'] ?? '', 'smtp_port' => $data['smtp_port'] ?? 587, 'smtp_encryption' => $data['smtp_encryption'] ?? 'tls'],
        };

        $account = MailAccount::create([
            'user_id'         => $request->user()->id,
            'provider'        => $data['provider'],
            'label'           => $data['label'] ?? null,
            'email'           => $data['email'],
            'smtp_host'       => $data['smtp_host']       ?? $smtpDefaults['smtp_host'],
            'smtp_port'       => $data['smtp_port']       ?? $smtpDefaults['smtp_port'],
            'smtp_encryption' => $data['smtp_encryption'] ?? $smtpDefaults['smtp_encryption'],
            'connected_at'    => now(),
            'is_active'       => true,
        ]);

        if (!empty($data['smtp_username'])) {
            $account->setSmtpUsername($data['smtp_username']);
        }
        if (!empty($data['smtp_password'])) {
            $account->setSmtpPassword($data['smtp_password']);
        }
        $account->save();

        // Auto-set as default if it's the first account
        if (!MailAccount::where('user_id', $request->user()->id)->where('id', '!=', $account->id)->exists()) {
            $account->update(['is_default' => true]);
        }

        return response()->json([
            'message' => "{$account->getProviderLabel()} account connected.",
            'account' => $account->toSafeArray(),
        ], 201);
    }

    private function handleGmailCallback(MailAccount $account, string $code): void
    {
        $res = Http::post('https://oauth2.googleapis.com/token', [
            'client_id'     => $account->getOAuthClientId(),
            'client_secret' => $account->getOAuthClientSecret(),
            'redirect_uri'  => $account->getEffectiveRedirectUri(),
            'grant_type'    => 'authorization_code',
            'code'          => $code,
        ]);

        if ($res->failed()) throw new \RuntimeException('Gmail token exchange failed.');

        $tokens   = $res->json();
        $userInfo = Http::withToken($tokens['access_token'])->get('https://www.googleapis.com/oauth2/v3/userinfo')->json();

        $account->setAccessToken($tokens['access_token']);
        if (!empty($tokens['refresh_token'])) {
            $account->setRefreshToken($tokens['refresh_token']);
        }
        $account->email            = $userInfo['email'] ?? null;
        $account->token_expires_at = now()->addSeconds($tokens['expires_in'] ?? 3600);
        $account->scopes           = ['gmail.send', 'userinfo.email'];
        $account->connected_at     = now();
        $account->is_active        = true;
        $account->is_default       = !MailAccount::where('user_id', $account->user_id)
            ->where('id', '!=', $account->id)->where('is_default', true)->exists();
        $account->save();
    }

    private function handleOutlookCallback(MailAccount $account, string $code): void
    {
        $res = Http::asForm()->post('https://login.microsoftonline.com/common/oauth2/v2.0/token', [
            'client_id'     => $account->getOAuthClientId(),
            'client_secret' => $account->getOAuthClientSecret(),
            'redirect_uri'  => $account->getEffectiveRedirectUri(),
            'grant_type'    => 'authorization_code',
            'scope'         => 'https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/User.Read offline_access',
            'code'          => $code,
        ]);

        if ($res->failed()) throw new \RuntimeException('Outlook token exchange failed.');

        $tokens   = $res->json();
        $userInfo = Http::withToken($tokens['access_token'])
            ->get('https://graph.microsoft.com/v1.0/me')->json();

        $account->setAccessToken($tokens['access_token']);
        if (!empty($tokens['refresh_token'])) {
            $account->setRefreshToken($tokens['refresh_token']);
        }
        $account->email            = $userInfo['mail'] ?? $userInfo['userPrincipalName'] ?? null;
        $account->token_expires_at = now()->addSeconds($tokens['expires_in'] ?? 3600);
        $account->scopes           = ['Mail.Send', 'User.Read'];
        $account->connected_at     = now();
        $account->is_active        = true;
        $account->is_default       = !MailAccount::where('user_id', $account->user_id)
            ->where('id', '!=', $account->id)->where('is_default', true)->exists();
        $account->save();
    }

    private function gmailAuthUrl(MailAccount $account, string $state): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id'     => $account->getOAuthClientId(),
            'redirect_uri'  => $account->getEffectiveRedirectUri(),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/userinfo.email openid',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);
    }

    private function outlookAuthUrl(MailAccount $account, string $state): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize?' . http_build_query([
            'client_id'     => $account->getOAuthClientId(),
            'redirect_uri'  => $account->getEffectiveRedirectUri(),
            'response_type' => 'code',
            'scope'         => 'https://graph.microsoft.com/Mail.Send https://graph.microsoft.com/User.Read offline_access',
            'state'         => $state,
        ]);
    }
}

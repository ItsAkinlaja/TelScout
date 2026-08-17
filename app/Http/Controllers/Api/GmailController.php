<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomationSettings;
use App\Models\GoogleAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GmailController extends Controller
{
    private string $authUrl    = 'https://accounts.google.com/o/oauth2/v2/auth';
    private string $tokenUrl   = 'https://oauth2.googleapis.com/token';
    private string $userInfoUrl= 'https://www.googleapis.com/oauth2/v3/userinfo';

    private function getSettings(Request $request): AutomationSettings
    {
        return AutomationSettings::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['user_id' => $request->user()->id]
        );
    }

    public function connect(Request $request): JsonResponse
    {
        $settings     = $this->getSettings($request);
        $clientId     = $settings->getGoogleClientId();
        $redirectUri  = $settings->getEffectiveRedirectUri();

        if (empty($clientId)) {
            return response()->json([
                'message'  => 'Google OAuth is not configured.',
                'hint'     => 'Go to Settings → Gmail and enter your Google Client ID and Secret first.',
                'setup_url'=> config('app.frontend_url') . '/settings/gmail',
            ], 503);
        }

        session(['gmail_oauth_user_id' => $request->user()->id]);

        $params = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/gmail.send https://www.googleapis.com/auth/userinfo.email openid',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => csrf_token(),
        ]);

        return response()->json(['redirect_url' => "{$this->authUrl}?{$params}"]);
    }

    public function callback(Request $request): mixed
    {
        $code   = $request->input('code');
        $userId = session('gmail_oauth_user_id');
        $frontend = config('app.frontend_url', 'http://localhost:5173');

        if (!$code || !$userId) {
            return redirect("{$frontend}/settings/gmail?error=missing_params");
        }

        $settings    = AutomationSettings::where('user_id', $userId)->first();
        $clientId    = $settings?->getGoogleClientId();
        $clientSecret= $settings?->getGoogleClientSecret();
        $redirectUri = $settings?->getEffectiveRedirectUri();

        $tokenResponse = Http::post($this->tokenUrl, [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $redirectUri,
            'grant_type'    => 'authorization_code',
            'code'          => $code,
        ]);

        if ($tokenResponse->failed()) {
            Log::error('Gmail OAuth token exchange failed', ['response' => $tokenResponse->json()]);
            return redirect("{$frontend}/settings/gmail?error=token_exchange_failed");
        }

        $tokens = $tokenResponse->json();

        $userInfoResponse = Http::withToken($tokens['access_token'])->get($this->userInfoUrl);
        $email = $userInfoResponse->json('email', '');

        $account = GoogleAccount::firstOrNew(['user_id' => $userId, 'email' => $email]);
        $account->user_id          = $userId;
        $account->email            = $email;
        $account->provider         = 'google';
        $account->scopes           = ['gmail.send', 'userinfo.email'];
        $account->connected_at     = now();
        $account->token_expires_at = now()->addSeconds($tokens['expires_in'] ?? 3600);
        $account->setAccessToken($tokens['access_token']);

        if (!empty($tokens['refresh_token'])) {
            $account->setRefreshToken($tokens['refresh_token']);
        }

        $account->save();

        Log::info('Gmail connected', ['user_id' => $userId, 'email' => $email]);

        return redirect("{$frontend}/settings/gmail?connected=1");
    }

    public function disconnect(Request $request): JsonResponse
    {
        $request->user()->googleAccounts()->delete();
        return response()->json(['message' => 'Gmail disconnected.']);
    }

    public function status(Request $request): JsonResponse
    {
        $account  = $request->user()->getGoogleAccount();
        $settings = $this->getSettings($request);

        return response()->json([
            'connected'        => (bool) $account,
            'email'            => $account?->email,
            'connected_at'     => $account?->connected_at,
            'scopes'           => $account?->scopes,
            'token_expired'    => $account?->isTokenExpired(),
            'oauth_configured' => $settings->hasGoogleOAuth(),
        ]);
    }
}

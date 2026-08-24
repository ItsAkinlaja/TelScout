<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\MagicLinkMail;
use App\Models\AutomationSettings;
use App\Models\CandidateProfile;
use App\Models\MagicLinkToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // ── Magic Link ────────────────────────────────────────────────────────────

    /**
     * Issue a magic-link token and queue the email.
     * Works even before SMTP is fully configured (mail driver can be 'log').
     */
    public function magicLinkRequest(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower(trim($data['email']));

        // Throttle: max 3 tokens per email in 5 minutes
        $recentCount = MagicLinkToken::where('email', $email)
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        if ($recentCount >= 3) {
            return response()->json([
                'message' => 'Too many requests. Please wait a few minutes and try again.',
            ], 429);
        }

        // Purge expired tokens for this email
        MagicLinkToken::where('email', $email)
            ->where('expires_at', '<', now())
            ->delete();

        $token = Str::random(48);

        MagicLinkToken::create([
            'email'      => $email,
            'token'      => $token,
            'expires_at' => now()->addMinutes(15),
        ]);

        $loginUrl = url("/api/auth/magic-link/verify?token={$token}");

        try {
            Mail::to($email)->send(new MagicLinkMail($loginUrl, $email));
        } catch (\Throwable $e) {
            Log::warning('MagicLink mail failed', ['email' => $email, 'error' => $e->getMessage()]);
            // Don't surface mail errors to the client — avoids user enumeration
        }

        return response()->json([
            'message' => 'If an account exists for that email, a sign-in link has been sent.',
        ]);
    }

    /**
     * Verify the magic-link token, create/login the user, redirect to SPA with token.
     */
    public function magicLinkVerify(Request $request): RedirectResponse
    {
        $tokenValue = $request->query('token');

        if (! $tokenValue) {
            return redirect(config('app.frontend_url', '') . '/login?error=invalid_token');
        }

        $record = MagicLinkToken::where('token', $tokenValue)->first();

        if (! $record || $record->isExpired()) {
            $record?->delete();
            return redirect(config('app.frontend_url', '') . '/login?error=expired_token');
        }

        // Consume the token immediately (one-time use)
        $email = $record->email;
        $record->delete();

        // Upsert user — create if first time
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => explode('@', $email)[0],
                'password' => null,
            ]
        );

        // Bootstrap profile + settings for brand-new users
        if ($user->wasRecentlyCreated) {
            CandidateProfile::firstOrCreate(['user_id' => $user->id], [
                'full_name' => $user->name,
            ]);
            AutomationSettings::firstOrCreate(['user_id' => $user->id]);
        }

        $token = $user->createToken('api-token')->plainTextToken;

        $frontendUrl = config('app.frontend_url', '');

        return redirect("{$frontendUrl}/auth/callback?token={$token}&user=" . urlencode(json_encode([
            'id'    => $user->id,
            'name'  => $user->name,
            'email' => $user->email,
        ])));
    }

    // ── Google OAuth ──────────────────────────────────────────────────────────

    /**
     * Redirect to Google's consent screen.
     */
    public function googleRedirect(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')
            ->scopes(['openid', 'profile', 'email'])
            ->redirect();
    }

    /**
     * Handle Google callback, upsert user, redirect to SPA with token.
     */
    public function googleCallback(): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            Log::warning('Google OAuth callback failed', ['error' => $e->getMessage()]);
            return redirect(config('app.frontend_url', '') . '/login?error=google_failed');
        }

        /** @var User|null $user */
        $user = User::where('google_id', $socialUser->getId())->first()
            ?? User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Sync Google ID and avatar only if not already set — never overwrite existing data
            $updates = [];
            if (!$user->google_id) $updates['google_id'] = $socialUser->getId();
            if (!$user->avatar)    $updates['avatar']    = $socialUser->getAvatar();
            if (!empty($updates))  $user->update($updates);
        } else {
            $user = User::create([
                'name'      => $socialUser->getName() ?? explode('@', $socialUser->getEmail())[0],
                'email'     => $socialUser->getEmail(),
                'google_id' => $socialUser->getId(),
                'avatar'    => $socialUser->getAvatar(),
                'password'  => null,
            ]);

            CandidateProfile::create([
                'user_id'   => $user->id,
                'full_name' => $user->name,
            ]);
            AutomationSettings::create(['user_id' => $user->id]);
        }

        $token       = $user->createToken('api-token')->plainTextToken;
        $frontendUrl = config('app.frontend_url', '');

        return redirect("{$frontendUrl}/auth/callback?token={$token}&user=" . urlencode(json_encode([
            'id'     => $user->id,
            'name'   => $user->name,
            'email'  => $user->email,
            'avatar' => $user->avatar,
        ])));
    }
}

<?php

namespace App\Services;

use App\Models\AutomationSettings;
use App\Models\JobListing;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContactDiscoveryService
{
    /**
     * Generic role prefixes we should never cold-email — not a hiring contact.
     */
    private const BLACKLISTED_PREFIXES = [
        'noreply', 'no-reply', 'support', 'info', 'hello', 'privacy',
        'legal', 'security', 'abuse', 'postmaster', 'webmaster',
        'admin', 'contact', 'team', 'press', 'media', 'billing',
        'unsubscribe', 'bounces', 'mailer-daemon',
    ];

    /**
     * Attempt to discover a hiring email for a given job.
     *
     * Resolution order (first non-null wins):
     *   1. Email extracted directly from the job description text
     *   2. Email already stored on the company record
     *   3. Hunter.io domain search  (key stored in user's AutomationSettings)
     *   4. Apollo.io people search  (key stored in user's AutomationSettings)
     *
     * @param  JobListing  $job
     * @param  int|null    $userId  Used to load the user's enrichment API keys.
     */
    public function discover(JobListing $job, ?int $userId = null): ?string
    {
        // 1. Job description — fastest, free, often contains careers@ or recruiter emails
        $email = $this->extractEmailFromText($job->description ?? '');
        if ($email) return $email;

        $company = $job->company;
        if (!$company) return null;

        // 2. Cached company email — avoid repeat API calls
        if ($company->contact_email) return $company->contact_email;

        $domain = $company->normalized_domain ?? null;
        if (!$domain) return null;

        // Load the user's enrichment keys (null-safe — skips enrichment if no keys set)
        $settings = $userId
            ? AutomationSettings::where('user_id', $userId)->first()
            : null;

        // 3. Hunter.io domain search
        $email = $this->hunterLookup($domain, $settings?->getHunterApiKey());
        if ($email) {
            $company->update(['contact_email' => $email]);
            return $email;
        }

        // 4. Apollo.io people search
        $email = $this->apolloLookup($company->name ?? '', $domain, $settings?->getApolloApiKey());
        if ($email) {
            $company->update(['contact_email' => $email]);
            return $email;
        }

        return null;
    }

    // ── Enrichment providers ──────────────────────────────────────────────────

    /**
     * Hunter.io /domain-search returns verified emails for a domain.
     * Docs: https://hunter.io/api-documentation/v2#domain-search
     */
    private function hunterLookup(string $domain, ?string $apiKey): ?string
    {
        if (empty($apiKey)) return null;

        try {
            $response = Http::timeout(10)->get('https://api.hunter.io/v2/domain-search', [
                'domain'  => $domain,
                'api_key' => $apiKey,
                'limit'   => 10,
                'type'    => 'personal', // prefer personal over generic role addresses
            ]);

            if ($response->failed()) {
                Log::debug('Hunter.io lookup failed', ['domain' => $domain, 'status' => $response->status()]);
                return null;
            }

            $emails = $response->json('data.emails') ?? [];

            $preferred = collect($emails)
                ->filter(fn($e) => ($e['confidence'] ?? 0) >= 70)
                ->sortByDesc('confidence')
                ->first();

            $email = $preferred['value'] ?? null;
            return $email && $this->isUsableEmail($email) ? strtolower($email) : null;

        } catch (\Exception $e) {
            Log::warning('Hunter.io exception', ['domain' => $domain, 'error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Apollo.io /mixed_people/search finds contacts by company domain.
     * Docs: https://apolloio.github.io/apollo-api-docs/#people-search
     */
    private function apolloLookup(string $companyName, string $domain, ?string $apiKey): ?string
    {
        if (empty($apiKey)) return null;

        try {
            $response = Http::timeout(10)
                ->withHeaders(['x-api-key' => $apiKey])
                ->post('https://api.apollo.io/v1/mixed_people/search', [
                    'q_organization_domains' => $domain,
                    'person_titles'          => [
                        'recruiter', 'talent acquisition', 'hiring manager',
                        'engineering manager', 'cto', 'vp engineering',
                    ],
                    'page'     => 1,
                    'per_page' => 5,
                ]);

            if ($response->failed()) {
                Log::debug('Apollo.io lookup failed', ['domain' => $domain, 'status' => $response->status()]);
                return null;
            }

            foreach ($response->json('people') ?? [] as $person) {
                $email = $person['email'] ?? null;
                if ($email && $this->isUsableEmail($email)) {
                    return strtolower($email);
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::warning('Apollo.io exception', ['domain' => $domain, 'error' => $e->getMessage()]);
            return null;
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function extractEmailFromText(string $text): ?string
    {
        if (empty($text)) return null;

        if (preg_match_all('/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i', $text, $matches)) {
            foreach ($matches[0] as $email) {
                if ($this->isUsableEmail($email)) return strtolower($email);
            }
        }

        return null;
    }

    private function isUsableEmail(string $email): bool
    {
        $email = strtolower(trim($email));

        foreach (self::BLACKLISTED_PREFIXES as $prefix) {
            if (str_starts_with($email, $prefix . '@') || str_contains($email, '+' . $prefix . '@')) {
                return false;
            }
        }

        if (str_contains($email, 'example.') || str_starts_with($email, 'test@') || str_ends_with($email, '@localhost')) {
            return false;
        }

        return true;
    }
}

<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lever — free public posting API, no key required.
 * https://api.lever.co/v0/postings/{company_slug}?mode=json
 *
 * Fetches all postings for specific company slugs and optionally
 * filters client-side by keywords.
 */
class LeverSource implements JobSourceInterface
{
    public function getName(): string { return 'lever'; }

    public function search(array $criteria): Collection
    {
        $companySlugs = $criteria['company_slugs'] ?? [];
        $keywords     = $criteria['keywords']      ?? [];

        if (empty($companySlugs)) {
            return collect();
        }

        $results = collect();

        foreach ($companySlugs as $slug) {
            $slug = trim($slug);
            if ($slug === '') continue;

            try {
                $response = Http::timeout(20)
                    ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                    ->get("https://api.lever.co/v0/postings/{$slug}", [
                        'mode' => 'json',
                    ]);

                if ($response->failed()) {
                    Log::warning('Lever fetch failed', [
                        'company_slug' => $slug,
                        'status'       => $response->status(),
                    ]);
                    continue;
                }

                $postings = $response->json() ?? [];

                // API may return an array directly or wrap in a data key
                if (isset($postings['data'])) {
                    $postings = $postings['data'];
                }

                $mapped = collect($postings)->map(fn($posting) => $this->normalize($posting, $slug));

                $results = $results->merge($mapped);

            } catch (\Exception $e) {
                Log::warning('Lever fetch exception', [
                    'company_slug' => $slug,
                    'error'        => $e->getMessage(),
                ]);
            }
        }

        // Client-side keyword filtering on title if keywords provided
        if (!empty($keywords)) {
            $results = $results->filter(function ($job) use ($keywords) {
                $haystack = strtolower($job['title'] . ' ' . $job['description']);
                foreach ($keywords as $kw) {
                    if (str_contains($haystack, strtolower(trim($kw)))) {
                        return true;
                    }
                }
                return false;
            });
        }

        return $results->values();
    }

    private function normalize(array $posting, string $slug): array
    {
        $id           = $posting['id'] ?? null;
        $title        = $posting['text'] ?? '';
        $categories   = $posting['categories'] ?? [];
        $locationText = $categories['location'] ?? '';
        $company      = $this->titleCaseSlug($slug);

        // tags = team category + tags array
        $tags = [];
        if (!empty($categories['team'])) {
            $tags[] = $categories['team'];
        }
        foreach (($posting['tags'] ?? []) as $tag) {
            if (!in_array($tag, $tags, true)) {
                $tags[] = $tag;
            }
        }

        // posted_at from Unix timestamp
        $postedAt = null;
        if (!empty($posting['createdAt'])) {
            try {
                $postedAt = date('Y-m-d H:i:s', (int) ($posting['createdAt'] / 1000));
            } catch (\Exception) {
                $postedAt = null;
            }
        }

        $applicationUrl = $posting['hostedUrl'] ?? null;

        return [
            'title'           => $title,
            'company'         => $company,
            'company_url'     => "https://jobs.lever.co/{$slug}",
            'location'        => $locationText,
            'is_remote'       => str_contains(strtolower($locationText), 'remote'),
            'description'     => strip_tags($posting['descriptionPlain'] ?? $posting['description'] ?? ''),
            'salary_min'      => null,
            'salary_max'      => null,
            'salary_currency' => null,
            'application_url' => $applicationUrl,
            'source_url'      => $applicationUrl,
            'external_id'     => $id !== null ? (string) $id : null,
            'tags'            => $tags,
            'posted_at'       => $postedAt,
            'source'          => 'lever',
            'workplace_type'  => $this->parseWorkplaceType($locationText),
            'experience_level'=> $this->parseExperienceLevel($title),
            'country'         => $this->parseCountry($locationText),
            'city'            => $this->parseCity($locationText),
        ];
    }

    /**
     * Convert a kebab-case slug to Title Case company name.
     * e.g. "acme-corp" → "Acme Corp"
     */
    private function titleCaseSlug(string $slug): string
    {
        return ucwords(str_replace(['-', '_'], ' ', $slug));
    }

    private function parseWorkplaceType(string $location): string
    {
        $lower = strtolower($location);
        if (str_contains($lower, 'remote')) return 'remote';
        if (str_contains($lower, 'hybrid')) return 'hybrid';
        return 'onsite';
    }

    private function parseExperienceLevel(string $title): string
    {
        $lower = strtolower($title);

        if (preg_match('/\b(intern)\b/', $lower))                                        return 'internship';
        if (preg_match('/\b(junior|entry.?level|graduate)\b/', $lower))                  return 'entry';
        if (preg_match('/\b(senior|lead|principal)\b/', $lower))                         return 'senior';
        if (preg_match('/\b(staff|vp|vice president|director|head|chief)\b/', $lower))   return 'lead';

        return 'unknown';
    }

    private function parseCountry(string $location): ?string
    {
        $parts = array_map('trim', explode(',', $location));
        return count($parts) >= 2 ? end($parts) : null;
    }

    private function parseCity(string $location): ?string
    {
        $parts = array_map('trim', explode(',', $location));
        return $parts[0] !== '' ? $parts[0] : null;
    }
}

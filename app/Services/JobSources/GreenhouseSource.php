<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Greenhouse — free public board API, no key required.
 * https://boards-api.greenhouse.io/v1/boards/{board_token}/jobs?content=true
 *
 * Unlike keyword-search sources, this connector fetches all jobs for
 * specific board tokens and optionally filters client-side by keywords.
 */
class GreenhouseSource implements JobSourceInterface
{
    public function getName(): string { return 'greenhouse'; }

    public function search(array $criteria): Collection
    {
        $boardTokens = $criteria['board_tokens'] ?? [];
        $keywords    = $criteria['keywords']    ?? [];

        if (empty($boardTokens)) {
            return collect();
        }

        $results = collect();

        foreach ($boardTokens as $token) {
            $token = trim($token);
            if ($token === '') continue;

            try {
                $response = Http::timeout(20)
                    ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                    ->get("https://boards-api.greenhouse.io/v1/boards/{$token}/jobs", [
                        'content' => 'true',
                    ]);

                if ($response->failed()) {
                    Log::warning('Greenhouse fetch failed', [
                        'board_token' => $token,
                        'status'      => $response->status(),
                    ]);
                    continue;
                }

                $jobs = $response->json('jobs', []);

                $mapped = collect($jobs)->map(fn($job) => $this->normalize($job, $token));

                $results = $results->merge($mapped);

            } catch (\Exception $e) {
                Log::warning('Greenhouse fetch exception', [
                    'board_token' => $token,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        // Client-side keyword filtering on title/description if keywords provided
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

    private function normalize(array $job, string $token): array
    {
        $id          = $job['id'] ?? null;
        $title       = $job['title'] ?? '';
        $locationText = $job['location']['name'] ?? '';

        // Departments → tags
        $tags = collect($job['departments'] ?? [])
            ->pluck('name')
            ->filter()
            ->values()
            ->all();

        $sourceUrl = $id
            ? "https://boards.greenhouse.io/{$token}/jobs/{$id}"
            : null;

        return [
            'title'           => $title,
            'company'         => $job['company_name'] ?? ucwords(str_replace(['-', '_'], ' ', $token)),
            'company_url'     => "https://boards.greenhouse.io/{$token}",
            'location'        => $locationText,
            'is_remote'       => str_contains(strtolower($locationText), 'remote'),
            'description'     => strip_tags($job['content'] ?? ''),
            'salary_min'      => null,
            'salary_max'      => null,
            'salary_currency' => null,
            'application_url' => $sourceUrl,
            'source_url'      => $sourceUrl,
            'external_id'     => $id !== null ? (string) $id : null,
            'tags'            => $tags,
            'posted_at'       => $job['updated_at'] ?? null,
            'source'          => 'greenhouse',
            'workplace_type'  => $this->parseWorkplaceType($locationText),
            'experience_level'=> $this->parseExperienceLevel($title),
            'country'         => $this->parseCountry($locationText),
            'city'            => $this->parseCity($locationText),
        ];
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
        // Best-effort: grab the last comma-separated segment
        $parts = array_map('trim', explode(',', $location));
        return count($parts) >= 2 ? end($parts) : null;
    }

    private function parseCity(string $location): ?string
    {
        $parts = array_map('trim', explode(',', $location));
        return $parts[0] !== '' ? $parts[0] : null;
    }
}

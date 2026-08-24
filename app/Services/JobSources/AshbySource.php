<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ashby — free public job board API, no key required.
 * https://api.ashbyhq.com/posting-api/job-board/{organization_id}
 *
 * Fetches all postings for specific organization IDs and optionally
 * filters client-side by keywords.
 */
class AshbySource implements JobSourceInterface
{
    public function getName(): string { return 'ashby'; }

    public function search(array $criteria): Collection
    {
        $organizationIds = $criteria['organization_ids'] ?? [];
        $keywords        = $criteria['keywords']        ?? [];

        if (empty($organizationIds)) {
            return collect();
        }

        $results = collect();

        foreach ($organizationIds as $orgId) {
            $orgId = trim($orgId);
            if ($orgId === '') continue;

            try {
                $response = Http::timeout(20)
                    ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                    ->get("https://api.ashbyhq.com/posting-api/job-board/{$orgId}");

                if ($response->failed()) {
                    Log::warning('Ashby fetch failed', [
                        'organization_id' => $orgId,
                        'status'          => $response->status(),
                    ]);
                    continue;
                }

                $jobs = $response->json('jobs', []);

                $mapped = collect($jobs)->map(fn($job) => $this->normalize($job, $orgId));

                $results = $results->merge($mapped);

            } catch (\Exception $e) {
                Log::warning('Ashby fetch exception', [
                    'organization_id' => $orgId,
                    'error'           => $e->getMessage(),
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

    private function normalize(array $job, string $orgId): array
    {
        $id           = $job['id'] ?? null;
        $title        = $job['title'] ?? '';
        $locationName = $job['locationName'] ?? '';
        $isRemote     = (bool) ($job['isRemote'] ?? false);
        $jobUrl       = $job['jobUrl'] ?? null;

        // Map employmentType string to standard values
        $employmentType = $this->mapEmploymentType($job['employmentType'] ?? '');

        // tags = department name
        $tags = [];
        if (!empty($job['department'])) {
            $tags[] = $job['department'];
        }

        return [
            'title'            => $title,
            'company'          => $job['organizationName'] ?? ucwords(str_replace(['-', '_'], ' ', $orgId)),
            'company_url'      => null,
            'location'         => $locationName,
            'is_remote'        => $isRemote || str_contains(strtolower($locationName), 'remote'),
            'description'      => strip_tags($job['descriptionHtml'] ?? ''),
            'salary_min'       => null,
            'salary_max'       => null,
            'salary_currency'  => null,
            'application_url'  => $jobUrl,
            'source_url'       => $jobUrl,
            'external_id'      => $id !== null ? (string) $id : null,
            'tags'             => $tags,
            'posted_at'        => $job['publishedDate'] ?? null,
            'source'           => 'ashby',
            'workplace_type'   => $this->parseWorkplaceType($isRemote, $locationName),
            'experience_level' => $this->parseExperienceLevel($title),
            'employment_type'  => $employmentType,
            'country'          => $this->parseCountry($locationName),
            'city'             => $this->parseCity($locationName),
        ];
    }

    private function mapEmploymentType(string $type): ?string
    {
        return match (strtolower(trim($type))) {
            'full-time', 'fulltime'   => 'full-time',
            'part-time', 'parttime'   => 'part-time',
            'contract', 'contractor'  => 'contract',
            default                   => null,
        };
    }

    private function parseWorkplaceType(bool $isRemote, string $location): string
    {
        if ($isRemote) return 'remote';
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

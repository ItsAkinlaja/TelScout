<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Jobicy (tag search) — keyword-specific remote jobs via Jobicy tag filter.
 * https://jobicy.com/api/v2/remote-jobs
 *
 * Runs alongside AfricaWorkSource using the tag param to return
 * keyword-specific results (e.g. react, python, laravel).
 * Free, no key needed.
 */
class IndeedNigeriaSource implements JobSourceInterface
{
    private string $apiUrl = 'https://jobicy.com/api/v2/remote-jobs';

    public function getName(): string { return 'jobicy_tagged'; }

    public function search(array $criteria): Collection
    {
        $keywords = $criteria['keywords'] ?? ['software engineer'];
        $daysOld  = $criteria['days_old'] ?? 30;
        $results  = collect();

        $tag    = $this->extractTag($keywords);
        $cutoff = now()->subDays($daysOld);

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                ->get($this->apiUrl, [
                    'count' => 50,
                    'tag'   => $tag,
                ]);

            if ($response->failed()) {
                Log::warning('Jobicy tagged fetch failed', ['status' => $response->status()]);
                return collect();
            }

            $jobs = $response->json('jobs', []);
            if (empty($jobs)) return collect();

            $results = collect($jobs)
                ->filter(function ($j) use ($cutoff) {
                    $date = $j['pubDate'] ?? null;
                    if (!$date) return true;
                    try {
                        return \Carbon\Carbon::parse($date)->gte($cutoff);
                    } catch (\Exception) {
                        return true;
                    }
                })
                ->map(fn($j) => [
                    'title'           => $j['jobTitle'] ?? '',
                    'company'         => $j['companyName'] ?? '',
                    'company_url'     => $j['companyUrl'] ?? null,
                    'location'        => $j['jobGeo'] ?? 'Remote',
                    'is_remote'       => true,
                    'description'     => strip_tags($j['jobDescription'] ?? $j['jobExcerpt'] ?? ''),
                    'salary_min'      => null,
                    'salary_max'      => null,
                    'salary_currency' => 'USD',
                    'application_url' => $j['url'] ?? null,
                    'source_url'      => $j['url'] ?? null,
                    'external_id'     => (string) ($j['id'] ?? md5(($j['jobTitle'] ?? '') . ($j['companyName'] ?? ''))),
                    'tags'            => array_filter([$j['jobType'] ?? null, $tag]),
                    'posted_at'       => isset($j['pubDate'])
                        ? date('Y-m-d H:i:s', strtotime($j['pubDate']))
                        : null,
                    'source'          => 'jobicy_tagged',
                ])
                ->filter(fn($j) => !empty($j['title']) && !empty($j['company']));

        } catch (\Exception $e) {
            Log::warning('Jobicy tagged fetch exception', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Extract the best single tag from keywords for Jobicy tag filter.
     * Jobicy tags are technology names like: react, python, laravel, node, etc.
     */
    private function extractTag(array $keywords): string
    {
        $techTags = ['react', 'vue', 'angular', 'python', 'laravel', 'php', 'node',
                     'javascript', 'typescript', 'ruby', 'go', 'rust', 'java', 'swift',
                     'kotlin', 'flutter', 'django', 'rails', 'nextjs', 'aws', 'devops'];

        foreach ($keywords as $kw) {
            $kw = strtolower(trim($kw));
            foreach ($techTags as $tag) {
                if (str_contains($kw, $tag)) return $tag;
            }
        }

        // Fallback to first keyword cleaned up
        $first = strtolower(trim($keywords[0] ?? 'developer'));
        return preg_replace('/[^a-z0-9]/', '', explode(' ', $first)[0]);
    }
}
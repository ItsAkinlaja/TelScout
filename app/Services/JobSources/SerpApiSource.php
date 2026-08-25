<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SerpAPI — Google Jobs search engine results.
 *
 * A single API key surfaces jobs from Jobberman, LinkedIn, Indeed Nigeria,
 * MyJobMag, and every other source Google Jobs indexes — without scraping
 * those sites directly.
 *
 * Free tier: 100 searches/month (no credit card required).
 * Register at https://serpapi.com and copy your API key.
 *
 * Set in .env:
 *   SERPAPI_KEY=your_serpapi_key
 *
 * Docs: https://serpapi.com/google-jobs-api
 */
class SerpApiSource implements JobSourceInterface
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.serpapi.key');
    }

    public function getName(): string { return 'serpapi'; }

    public function search(array $criteria): Collection
    {
        if (empty($this->apiKey)) {
            Log::info('SerpAPI skipped — set SERPAPI_KEY in .env. Free 100 searches/month at serpapi.com');
            return collect();
        }

        $keywords  = $criteria['keywords']  ?? ['software engineer'];
        $locations = $criteria['locations'] ?? [];
        $daysOld   = $criteria['days_old']  ?? 30;
        $results   = collect();

        // Build a natural-language query that Google Jobs understands well.
        $query = implode(' ', array_slice($keywords, 0, 2));

        // Append location to push results toward the right geography.
        // If remote-only, skip the location append so Google returns global remote jobs.
        if (!($criteria['remote_only'] ?? false) && !empty($locations)) {
            $firstLocation = $locations[0];
            if (!in_array(strtolower($firstLocation), ['remote', 'worldwide', 'anywhere'])) {
                $query .= ' ' . $firstLocation;
            }
        } elseif ($criteria['remote_only'] ?? false) {
            $query .= ' remote';
        }

        // Map days_old → Google's chips param (ltype=1 is the "past X days" filter).
        // SerpAPI exposes this as date_posted: today | 3days | week | month.
        $datePosted = match(true) {
            $daysOld <= 1  => 'today',
            $daysOld <= 3  => '3days',
            $daysOld <= 7  => 'week',
            default        => 'month',
        };

        // Derive a country/language hint from the first requested location so
        // Google Jobs surfaces local boards (e.g. Jobberman for Nigeria).
        $gl = $this->locationToGl($locations);

        try {
            $response = Http::timeout(20)
                ->get('https://serpapi.com/search', [
                    'engine'      => 'google_jobs',
                    'q'           => $query,
                    'api_key'     => $this->apiKey,
                    'date_posted' => $datePosted,
                    'gl'          => $gl,       // country code for localised results
                    'hl'          => 'en',      // interface language
                    'num'         => 10,        // max jobs per request
                ]);

            if ($response->failed()) {
                Log::warning('SerpAPI fetch failed', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return collect();
            }

            $jobs = $response->json('jobs_results', []);

            $results = collect($jobs)->map(function ($j) {
                // Build a clean location string from the structured data SerpAPI provides.
                $location = $j['location'] ?? '';

                // Detected extensions: salary, work_from_home, schedule_type, etc.
                $exts = $j['detected_extensions'] ?? [];

                $salaryMin = null;
                $salaryMax = null;
                $currency  = null;

                // SerpAPI sometimes includes salary as a string in highlights.
                if (!empty($j['salary'])) {
                    [$salaryMin, $salaryMax, $currency] = $this->parseSalary($j['salary']);
                }

                return [
                    'title'           => $j['title'] ?? '',
                    'company'         => $j['company_name'] ?? '',
                    'company_url'     => null,
                    'location'        => $location,
                    'is_remote'       => ($exts['work_from_home'] ?? false)
                                      || str_contains(strtolower($location), 'remote')
                                      || str_contains(strtolower($location), 'anywhere'),
                    'description'     => $this->buildDescription($j),
                    'salary_min'      => $salaryMin,
                    'salary_max'      => $salaryMax,
                    'salary_currency' => $currency,
                    'application_url' => $j['share_link'] ?? null,
                    'source_url'      => $j['share_link'] ?? null,
                    'external_id'     => $j['job_id'] ?? md5(($j['title'] ?? '') . ($j['company_name'] ?? '')),
                    'tags'            => $this->extractTags($j),
                    'posted_at'       => isset($exts['posted_at'])
                        ? $this->parsePostedAt($exts['posted_at'])
                        : null,
                    'source'          => 'serpapi',
                ];
            })->filter(fn($j) => !empty($j['title']) && !empty($j['company']));

        } catch (\Exception $e) {
            Log::warning('SerpAPI fetch exception', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    /**
     * Map location names → ISO 3166-1 alpha-2 country codes for Google's `gl` param.
     * This nudges Google Jobs to surface local boards (Jobberman for NG, etc.).
     */
    private function locationToGl(array $locations): string
    {
        $map = [
            'nigeria'        => 'ng',
            'lagos'          => 'ng',
            'abuja'          => 'ng',
            'port harcourt'  => 'ng',
            'ghana'          => 'gh',
            'kenya'          => 'ke',
            'south africa'   => 'za',
            'united states'  => 'us',
            'usa'            => 'us',
            'united kingdom' => 'gb',
            'uk'             => 'gb',
            'canada'         => 'ca',
            'australia'      => 'au',
            'germany'        => 'de',
            'france'         => 'fr',
            'india'          => 'in',
        ];

        foreach ($locations as $loc) {
            $l = strtolower(trim($loc));
            if (isset($map[$l])) return $map[$l];
            foreach ($map as $name => $code) {
                if (str_contains($l, $name)) return $code;
            }
        }

        return 'ng'; // Default to Nigeria since that's the primary use case
    }

    /**
     * Build a combined description from Google Jobs highlight snippets.
     */
    private function buildDescription(array $job): string
    {
        $desc = strip_tags($job['description'] ?? '');

        // Append key highlights if present (e.g. qualifications, responsibilities)
        $highlights = $job['job_highlights'] ?? [];
        foreach ($highlights as $h) {
            if (!empty($h['title']) && !empty($h['items'])) {
                $desc .= "\n\n" . $h['title'] . ":\n" . implode("\n", array_map(fn($i) => "• $i", $h['items']));
            }
        }

        return trim($desc);
    }

    /**
     * Extract searchable tags from the job's extensions and schedule type.
     */
    private function extractTags(array $job): array
    {
        $exts = $job['detected_extensions'] ?? [];
        $tags = [];

        if (!empty($exts['schedule_type'])) {
            $tags[] = strtolower($exts['schedule_type']); // "full-time", "contract", etc.
        }

        if ($exts['work_from_home'] ?? false) {
            $tags[] = 'remote';
        }

        return $tags;
    }

    /**
     * Try to parse a human-readable salary string into min/max/currency.
     * E.g. "₦300,000 – ₦500,000 a month" → [300000, 500000, 'NGN']
     */
    private function parseSalary(string $salary): array
    {
        // Detect currency symbol / code
        $currency = 'USD';
        if (str_contains($salary, '₦') || stripos($salary, 'NGN') !== false) {
            $currency = 'NGN';
        } elseif (str_contains($salary, '£') || stripos($salary, 'GBP') !== false) {
            $currency = 'GBP';
        } elseif (str_contains($salary, '€') || stripos($salary, 'EUR') !== false) {
            $currency = 'EUR';
        }

        // Extract numbers
        preg_match_all('/[\d,]+/', $salary, $matches);
        $numbers = array_map(fn($n) => (float) str_replace(',', '', $n), $matches[0] ?? []);
        $numbers = array_values(array_filter($numbers, fn($n) => $n > 0));

        $min = $numbers[0] ?? null;
        $max = $numbers[1] ?? $numbers[0] ?? null;

        // Annual conversion: SerpAPI might return monthly figures (detect "month" in string)
        if ($min && str_contains(strtolower($salary), 'month')) {
            $min = $min * 12;
            $max = $max ? $max * 12 : null;
        }

        return [$min, $max, $currency];
    }

    /**
     * Convert Google's relative "posted_at" strings into a Y-m-d timestamp.
     * Examples: "3 days ago", "1 week ago", "Posted today"
     */
    private function parsePostedAt(string $postedAt): ?string
    {
        $p = strtolower(trim($postedAt));

        if (str_contains($p, 'today') || str_contains($p, 'just posted') || str_contains($p, 'hour')) {
            return now()->format('Y-m-d H:i:s');
        }

        if (preg_match('/(\d+)\s+day/', $p, $m)) {
            return now()->subDays((int) $m[1])->format('Y-m-d H:i:s');
        }

        if (preg_match('/(\d+)\s+week/', $p, $m)) {
            return now()->subWeeks((int) $m[1])->format('Y-m-d H:i:s');
        }

        if (preg_match('/(\d+)\s+month/', $p, $m)) {
            return now()->subMonths((int) $m[1])->format('Y-m-d H:i:s');
        }

        return null;
    }
}

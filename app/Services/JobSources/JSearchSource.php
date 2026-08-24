<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * JSearch — powered by Indeed, Glassdoor, LinkedIn aggregation via RapidAPI.
 * Free tier: 200 requests/month. Covers worldwide postings.
 * https://rapidapi.com/letscrape-6bRBa3QguO5/api/jsearch
 *
 * Set in .env:
 *   JSEARCH_API_KEY=your_rapidapi_key
 */
class JSearchSource implements JobSourceInterface
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.jsearch.api_key');
    }

    public function getName(): string { return 'jsearch'; }

    public function search(array $criteria): Collection
    {
        if (empty($this->apiKey)) {
            return collect();
        }

        $keywords  = $criteria['keywords']  ?? ['software developer'];
        $locations = $criteria['locations'] ?? [];
        $daysOld   = $criteria['days_old']  ?? 30;
        $results   = collect();

        // Build query: combine first 2 keywords + location
        $query = implode(' ', array_slice($keywords, 0, 2));
        if (!empty($locations) && !in_array(strtolower($locations[0]), ['remote', 'worldwide', 'anywhere'])) {
            $query .= ' in ' . $locations[0];
        } elseif (!empty($criteria['remote_only'])) {
            $query .= ' remote';
        }

        // Map days_old to JSearch date_posted param
        $datePosted = match(true) {
            $daysOld <= 3  => 'today',
            $daysOld <= 7  => '3days',
            $daysOld <= 14 => 'week',
            default        => 'month',
        };

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-RapidAPI-Key'  => $this->apiKey,
                    'X-RapidAPI-Host' => 'jsearch.p.rapidapi.com',
                ])
                ->get('https://jsearch.p.rapidapi.com/search', [
                    'query'       => $query,
                    'page'        => '1',
                    'num_pages'   => '2',
                    'date_posted' => $datePosted,
                ]);

            if ($response->failed()) {
                Log::warning('JSearch fetch failed', ['status' => $response->status()]);
                return collect();
            }

            $jobs = $response->json('data', []);

            $results = collect($jobs)->map(fn($j) => [
                'title'           => $j['job_title'] ?? '',
                'company'         => $j['employer_name'] ?? '',
                'company_url'     => $j['employer_website'] ?? null,
                'location'        => trim(
                    ($j['job_city'] ? $j['job_city'] . ', ' : '') .
                    ($j['job_state'] ? $j['job_state'] . ', ' : '') .
                    ($j['job_country'] ?? '')
                , ', '),
                'is_remote'       => (bool) ($j['job_is_remote'] ?? false),
                'description'     => strip_tags($j['job_description'] ?? ''),
                'salary_min'      => $j['job_min_salary'] ?? null,
                'salary_max'      => $j['job_max_salary'] ?? null,
                'salary_currency' => $j['job_salary_currency'] ?? 'USD',
                'application_url' => $j['job_apply_link'] ?? null,
                'source_url'      => $j['job_apply_link'] ?? null,
                'external_id'     => $j['job_id'] ?? md5(($j['job_title'] ?? '') . ($j['employer_name'] ?? '')),
                'tags'            => array_values(array_filter([$j['job_required_experience']['required_experience_in_months'] ? 'experienced' : null])),
                'posted_at'       => isset($j['job_posted_at_datetime_utc'])
                    ? date('Y-m-d H:i:s', strtotime($j['job_posted_at_datetime_utc']))
                    : null,
                'source'          => 'jsearch',
            ])->filter(fn($j) => !empty($j['title']));

        } catch (\Exception $e) {
            Log::warning('JSearch fetch exception', ['error' => $e->getMessage()]);
        }

        return $results;
    }
}

<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Reed.co.uk — UK's largest job board. Free API (requires free registration).
 * https://www.reed.co.uk/developers/jobseeker
 * Strong UK & international coverage, salary data included.
 *
 * Set in .env:
 *   REED_API_KEY=your_reed_api_key
 */
class ReedSource implements JobSourceInterface
{
    private ?string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.reed.api_key');
    }

    public function getName(): string { return 'reed'; }

    public function search(array $criteria): Collection
    {
        if (empty($this->apiKey)) {
            return collect();
        }

        $keywords  = $criteria['keywords']  ?? ['software developer'];
        $locations = $criteria['locations'] ?? [];
        $daysOld   = $criteria['days_old']  ?? 30;
        $results   = collect();

        $query    = implode(' ', array_slice($keywords, 0, 2));
        $location = !empty($locations) ? $locations[0] : null;

        try {
            $params = [
                'keywords'         => $query,
                'resultsToTake'    => 100,
                'resultsToSkip'    => 0,
                'postedByRecruitmentAgency' => false,
            ];

            if ($location && !in_array(strtolower($location), ['remote', 'worldwide', 'anywhere'])) {
                $params['locationName'] = $location;
                $params['distanceFromLocation'] = 50;
            }

            if (!empty($criteria['remote_only'])) {
                $params['keywords'] = $query . ' remote';
            }

            $response = Http::timeout(15)
                ->withBasicAuth($this->apiKey, '')
                ->withHeaders(['Accept' => 'application/json'])
                ->get('https://www.reed.co.uk/api/1.0/search', $params);

            if ($response->failed()) {
                Log::warning('Reed fetch failed', ['status' => $response->status()]);
                return collect();
            }

            $jobs = $response->json('results', []);
            $cutoff = now()->subDays($daysOld);

            $results = collect($jobs)
                ->filter(function ($j) use ($cutoff) {
                    $date = $j['date'] ?? null;
                    if (!$date) return true;
                    try {
                        return \Carbon\Carbon::parse($date)->gte($cutoff);
                    } catch (\Exception) {
                        return true;
                    }
                })
                ->map(fn($j) => [
                    'title'           => $j['jobTitle'] ?? '',
                    'company'         => $j['employerName'] ?? '',
                    'company_url'     => null,
                    'location'        => $j['locationName'] ?? '',
                    'is_remote'       => str_contains(strtolower($j['jobTitle'] ?? ''), 'remote')
                                      || str_contains(strtolower($j['locationName'] ?? ''), 'remote'),
                    'description'     => strip_tags($j['jobDescription'] ?? ''),
                    'salary_min'      => $j['minimumSalary'] ?? null,
                    'salary_max'      => $j['maximumSalary'] ?? null,
                    'salary_currency' => 'GBP',
                    'application_url' => $j['jobUrl'] ?? null,
                    'source_url'      => $j['jobUrl'] ?? null,
                    'external_id'     => (string) ($j['jobId'] ?? md5($j['jobTitle'] . ($j['employerName'] ?? ''))),
                    'tags'            => [],
                    'posted_at'       => isset($j['date'])
                        ? date('Y-m-d H:i:s', strtotime($j['date']))
                        : null,
                    'source'          => 'reed',
                ])
                ->filter(fn($j) => !empty($j['title']));

        } catch (\Exception $e) {
            Log::warning('Reed fetch exception', ['error' => $e->getMessage()]);
        }

        return $results;
    }
}

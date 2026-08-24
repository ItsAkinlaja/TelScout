<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Arbeitnow — free public API, no key required.
 * https://arbeitnow.com/api/job-board-api
 * Direct company posts, Europe + remote, real application URLs.
 * Supports: keyword search, location, remote filter, tags.
 */
class ArbeitnowSource implements JobSourceInterface
{
    public function getName(): string { return 'arbeitnow'; }

    public function search(array $criteria): Collection
    {
        $keywords  = $criteria['keywords']  ?? ['software engineer'];
        $locations = $criteria['locations'] ?? [];
        $remoteOnly= $criteria['remote_only'] ?? false;
        $daysOld   = $criteria['days_old']  ?? 30;
        $cutoff    = now()->subDays($daysOld);

        $results = collect();

        // Arbeitnow paginates — fetch pages 1-3 for fresh results
        for ($page = 1; $page <= 3; $page++) {
            try {
                $params = [
                    'page' => $page,
                ];

                // Add first keyword as search term
                if (!empty($keywords)) {
                    $params['search'] = implode(' ', array_slice($keywords, 0, 2));
                }

                // Add location if provided
                if (!empty($locations) && !$remoteOnly) {
                    $params['location'] = $locations[0];
                }

                if ($remoteOnly) {
                    $params['remote'] = 'true';
                }

                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                    ->get('https://arbeitnow.com/api/job-board-api', $params);

                if ($response->failed()) break;

                $jobs = $response->json('data', []);
                if (empty($jobs)) break;

                $mapped = collect($jobs)
                    ->filter(function ($j) use ($cutoff) {
                        $created = $j['created_at'] ?? null;
                        if (!$created) return true;
                        try {
                            return \Carbon\Carbon::createFromTimestamp($created)->gte($cutoff);
                        } catch (\Exception) {
                            return true;
                        }
                    })
                    ->map(fn($j) => [
                        'title'           => $j['title'] ?? '',
                        'company'         => $j['company_name'] ?? '',
                        'company_url'     => null,
                        'location'        => $j['location'] ?? 'Remote',
                        'is_remote'       => (bool) ($j['remote'] ?? false),
                        'description'     => strip_tags($j['description'] ?? ''),
                        'salary_min'      => null,
                        'salary_max'      => null,
                        'salary_currency' => null,
                        'application_url' => $j['url'] ?? null,
                        'source_url'      => $j['url'] ?? null,
                        'external_id'     => $j['slug'] ?? md5($j['title'] . $j['company_name']),
                        'tags'            => $j['tags'] ?? [],
                        'posted_at'       => isset($j['created_at'])
                            ? date('Y-m-d H:i:s', $j['created_at'])
                            : null,
                        'source'          => 'arbeitnow',
                    ]);

                $results = $results->merge($mapped);

                // Stop if last page
                if (count($jobs) < 10) break;

            } catch (\Exception $e) {
                Log::warning('Arbeitnow fetch failed', ['page' => $page, 'error' => $e->getMessage()]);
                break;
            }
        }

        return $results->unique('external_id');
    }
}

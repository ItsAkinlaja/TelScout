<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The Muse — free API with API key for higher limits.
 * https://www.themuse.com/developers/api/v2
 *
 * US-focused with global companies. Category filtering returns near-zero results
 * so we fetch unfiltered and apply keyword matching post-fetch.
 *
 * Set in .env:
 *   THE_MUSE_API_KEY=your_key
 */
class TheMuseSource implements JobSourceInterface
{
    private ?string $apiKey;
    private string  $baseUrl = 'https://www.themuse.com/api/public/jobs';

    public function __construct()
    {
        $this->apiKey = config('services.the_muse.api_key');
    }

    public function getName(): string { return 'the_muse'; }

    public function search(array $criteria): Collection
    {
        if (empty($this->apiKey)) {
            return collect();
        }

        $keywords = $criteria['keywords'] ?? ['software engineer'];
        $daysOld  = $criteria['days_old'] ?? 30;
        $cutoff   = now()->subDays($daysOld);
        $results  = collect();

        // Fetch 2 pages of recent jobs — no category filter (returns near 0 with filters)
        for ($page = 1; $page <= 2; $page++) {
            try {
                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                    ->get($this->baseUrl, [
                        'api_key'    => $this->apiKey,
                        'page'       => $page,
                        'descending' => 'true',
                    ]);

                if ($response->failed()) break;

                $jobs = $response->json('results', []);
                if (empty($jobs)) break;

                $mapped = collect($jobs)
                    ->filter(function ($j) use ($cutoff, $keywords) {
                        // Date filter
                        $published = $j['publication_date'] ?? null;
                        if ($published) {
                            try {
                                if (!\Carbon\Carbon::parse($published)->gte($cutoff)) return false;
                            } catch (\Exception) {}
                        }
                        // Keyword relevance filter
                        $title = strtolower($j['name'] ?? '');
                        $cats  = strtolower(implode(' ', array_column($j['tags'] ?? [], 'name')));
                        foreach ($keywords as $kw) {
                            $kw = strtolower(trim($kw));
                            $parts = preg_split('/[\s\-_]+/', $kw);
                            if (str_contains($title, $kw)) return true;
                            foreach ($parts as $part) {
                                if (strlen($part) > 3 && (str_contains($title, $part) || str_contains($cats, $part))) return true;
                            }
                        }
                        return false;
                    })
                    ->map(function ($j) {
                        $location = collect($j['locations'] ?? [])->pluck('name')->implode(', ');
                        return [
                            'title'           => $j['name'] ?? '',
                            'company'         => $j['company']['name'] ?? '',
                            'company_url'     => $j['company']['refs']['landing_page'] ?? null,
                            'location'        => $location ?: 'US',
                            'is_remote'       => str_contains(strtolower($location), 'remote')
                                              || str_contains(strtolower($j['name'] ?? ''), 'remote'),
                            'description'     => strip_tags($j['contents'] ?? ''),
                            'salary_min'      => null,
                            'salary_max'      => null,
                            'salary_currency' => 'USD',
                            'application_url' => $j['refs']['landing_page'] ?? null,
                            'source_url'      => $j['refs']['landing_page'] ?? null,
                            'external_id'     => (string) ($j['id'] ?? md5($j['name'] ?? '')),
                            'tags'            => array_column($j['tags'] ?? [], 'name'),
                            'posted_at'       => $j['publication_date'] ?? null,
                            'source'          => 'the_muse',
                        ];
                    });

                $results = $results->merge($mapped);

            } catch (\Exception $e) {
                Log::warning('The Muse fetch failed', ['error' => $e->getMessage()]);
                break;
            }
        }

        return $results->unique('external_id');
    }
}
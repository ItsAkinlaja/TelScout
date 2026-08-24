<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The Muse — free API (requires free API key for higher limits).
 * https://www.themuse.com/developers/api/v2
 * US-focused but has international companies. Good company culture info.
 *
 * Set in .env:
 *   THE_MUSE_API_KEY=your_key   (optional — works without key at lower rate limits)
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
        $keywords  = $criteria['keywords']  ?? ['software engineer'];
        $locations = $criteria['locations'] ?? [];
        $daysOld   = $criteria['days_old']  ?? 30;
        $cutoff    = now()->subDays($daysOld);

        $results = collect();

        // The Muse uses category-based filtering
        $categories = $this->mapKeywordsToCategories($keywords);

        foreach (array_slice($categories, 0, 2) as $category) {
            for ($page = 1; $page <= 2; $page++) {
                try {
                    $params = [
                        'category' => $category,
                        'page'     => $page,
                        'descending' => 'true',
                    ];

                    if (!empty($locations)) {
                        $params['location'] = $locations[0];
                    }

                    if ($this->apiKey) {
                        $params['api_key'] = $this->apiKey;
                    }

                    $response = Http::timeout(15)
                        ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                        ->get($this->baseUrl, $params);

                    if ($response->failed()) break;

                    $jobs = $response->json('results', []);
                    if (empty($jobs)) break;

                    $mapped = collect($jobs)
                        ->filter(function ($j) use ($cutoff) {
                            $published = $j['publication_date'] ?? null;
                            if (!$published) return true;
                            try {
                                return \Carbon\Carbon::parse($published)->gte($cutoff);
                            } catch (\Exception) {
                                return true;
                            }
                        })
                        ->map(function ($j) {
                            $locations = collect($j['locations'] ?? [])
                                ->pluck('name')
                                ->implode(', ');

                            return [
                                'title'           => $j['name'] ?? '',
                                'company'         => $j['company']['name'] ?? '',
                                'company_url'     => $j['company']['refs']['landing_page'] ?? null,
                                'location'        => $locations ?: 'US',
                                'is_remote'       => str_contains(strtolower($locations), 'remote')
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
                    Log::warning('The Muse fetch failed', ['category' => $category, 'error' => $e->getMessage()]);
                    break;
                }
            }
        }

        return $results->unique('external_id');
    }

    private function mapKeywordsToCategories(array $keywords): array
    {
        $map = [
            'react'      => 'Engineering',
            'laravel'    => 'Engineering',
            'php'        => 'Engineering',
            'node'       => 'Engineering',
            'javascript' => 'Engineering',
            'typescript' => 'Engineering',
            'python'     => 'Engineering',
            'frontend'   => 'Engineering',
            'backend'    => 'Engineering',
            'fullstack'  => 'Engineering',
            'devops'     => 'IT',
            'design'     => 'Design & UX',
            'product'    => 'Product',
            'marketing'  => 'Marketing & PR',
            'data'       => 'Data Science',
        ];

        $categories = [];
        foreach ($keywords as $kw) {
            $kw = strtolower(trim($kw));
            foreach ($map as $key => $cat) {
                if (str_contains($kw, $key) && !in_array($cat, $categories)) {
                    $categories[] = $cat;
                }
            }
        }

        return $categories ?: ['Engineering'];
    }
}

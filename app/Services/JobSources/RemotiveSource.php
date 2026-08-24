<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Remotive — free public API, no key required.
 * https://remotive.com/api/remote-jobs
 * High-quality vetted remote jobs. Supports category + search filtering.
 */
class RemotiveSource implements JobSourceInterface
{
    public function getName(): string { return 'remotive'; }

    private const CATEGORIES = [
        'react'          => 'software-dev',
        'laravel'        => 'software-dev',
        'php'            => 'software-dev',
        'node'           => 'software-dev',
        'javascript'     => 'software-dev',
        'typescript'     => 'software-dev',
        'python'         => 'software-dev',
        'frontend'       => 'software-dev',
        'backend'        => 'software-dev',
        'full stack'     => 'software-dev',
        'fullstack'      => 'software-dev',
        'devops'         => 'devops-sysadmin',
        'design'         => 'design',
        'marketing'      => 'marketing',
        'product'        => 'product',
        'data'           => 'data',
    ];

    public function search(array $criteria): Collection
    {
        $keywords = $criteria['keywords'] ?? ['software developer'];
        $daysOld  = $criteria['days_old'] ?? 30;
        $cutoff   = now()->subDays($daysOld);

        $results = collect();

        foreach (array_slice($keywords, 0, 3) as $keyword) {
            try {
                $params = [
                    'search'   => $keyword,
                    'limit'    => 50,
                ];

                // Map keyword to category if possible
                $cat = $this->mapCategory($keyword);
                if ($cat) $params['category'] = $cat;

                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                    ->get('https://remotive.com/api/remote-jobs', $params);

                if ($response->failed()) continue;

                $jobs = $response->json('jobs', []);

                $mapped = collect($jobs)
                    ->filter(function ($j) use ($cutoff) {
                        $posted = $j['publication_date'] ?? null;
                        if (!$posted) return true;
                        return \Carbon\Carbon::parse($posted)->gte($cutoff);
                    })
                    ->map(fn($j) => [
                        'title'           => $j['title'] ?? '',
                        'company'         => $j['company_name'] ?? '',
                        'company_url'     => $j['company_logo'] ? null : null,
                        'location'        => $j['candidate_required_location'] ?? 'Remote',
                        'is_remote'       => true,
                        'description'     => strip_tags($j['description'] ?? ''),
                        'salary_min'      => null,
                        'salary_max'      => null,
                        'salary_currency' => null,
                        'application_url' => $j['url'] ?? null,
                        'source_url'      => $j['url'] ?? null,
                        'external_id'     => (string) ($j['id'] ?? ''),
                        'tags'            => $j['tags'] ?? [],
                        'posted_at'       => $j['publication_date'] ?? null,
                        'source'          => 'remotive',
                    ]);

                $results = $results->merge($mapped);

            } catch (\Exception $e) {
                Log::warning('Remotive fetch failed', ['keyword' => $keyword, 'error' => $e->getMessage()]);
            }
        }

        return $results->unique('external_id');
    }

    private function mapCategory(string $keyword): ?string
    {
        $kw = strtolower(trim($keyword));
        foreach (self::CATEGORIES as $key => $cat) {
            if (str_contains($kw, $key)) return $cat;
        }
        return 'software-dev'; // default for tech searches
    }
}

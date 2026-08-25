<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Jobicy — free global remote jobs API, no key required.
 * https://jobicy.com/api/v2/remote-jobs
 *
 * Returns remote jobs worldwide filtered by industry.
 * Free, no registration, no key needed.
 */
class AfricaWorkSource implements JobSourceInterface
{
    private string $apiUrl = 'https://jobicy.com/api/v2/remote-jobs';

    public function getName(): string { return 'jobicy'; }

    public function search(array $criteria): Collection
    {
        $keywords = $criteria['keywords'] ?? ['software engineer'];
        $daysOld  = $criteria['days_old'] ?? 30;
        $results  = collect();

        $industry = $this->mapToIndustry($keywords);
        $cutoff   = now()->subDays($daysOld);

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                ->get($this->apiUrl, [
                    'count'    => 50,
                    'industry' => $industry,
                ]);

            if ($response->failed()) {
                Log::warning('Jobicy fetch failed', ['status' => $response->status()]);
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
                    'tags'            => array_filter([$j['jobType'] ?? null, $j['jobIndustry'][0] ?? null]),
                    'posted_at'       => isset($j['pubDate'])
                        ? date('Y-m-d H:i:s', strtotime($j['pubDate']))
                        : null,
                    'source'          => 'jobicy',
                ])
                ->filter(fn($j) => !empty($j['title']) && !empty($j['company']));

        } catch (\Exception $e) {
            Log::warning('Jobicy fetch exception', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    private function mapToIndustry(array $keywords): string
    {
        $map = [
            'engineer'  => 'engineering',
            'developer' => 'engineering',
            'software'  => 'engineering',
            'frontend'  => 'engineering',
            'backend'   => 'engineering',
            'fullstack' => 'engineering',
            'react'     => 'engineering',
            'laravel'   => 'engineering',
            'php'       => 'engineering',
            'python'    => 'engineering',
            'data'      => 'data',
            'analyst'   => 'data',
            'ml'        => 'data',
            'ai'        => 'data',
            'design'    => 'design',
            'ux'        => 'design',
            'product'   => 'product',
            'marketing' => 'marketing',
            'devops'    => 'devops',
        ];

        foreach ($keywords as $kw) {
            $kw = strtolower(trim($kw));
            foreach ($map as $key => $industry) {
                if (str_contains($kw, $key)) return $industry;
            }
        }

        return 'engineering';
    }
}
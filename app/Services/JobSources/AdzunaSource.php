<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Adzuna — free API tier (requires free registration).
 * https://developer.adzuna.com
 * Worldwide coverage, date filter (max_days_old), salary range, location.
 *
 * Set in .env:
 *   ADZUNA_APP_ID=your_app_id
 *   ADZUNA_APP_KEY=your_app_key
 *   ADZUNA_COUNTRY=gb   (gb, us, au, ca, de, fr, in, nl, nz, pl, ru, sg, za)
 */
class AdzunaSource implements JobSourceInterface
{
    private ?string $appId;
    private ?string $appKey;
    private string  $country;
    private string  $baseUrl = 'https://api.adzuna.com/v1/api/jobs';

    public function __construct()
    {
        $this->appId   = config('services.adzuna.app_id');
        $this->appKey  = config('services.adzuna.app_key');
        $this->country = config('services.adzuna.country', 'gb');
    }

    public function getName(): string { return 'adzuna'; }

    public function search(array $criteria): Collection
    {
        if (empty($this->appId) || empty($this->appKey)) {
            Log::info('Adzuna skipped — no API credentials. Set ADZUNA_APP_ID and ADZUNA_APP_KEY.');
            return collect();
        }

        $keywords  = $criteria['keywords']  ?? ['software engineer'];
        $daysOld   = $criteria['days_old']  ?? 30;
        $minSalary = $criteria['min_salary'] ?? null;
        $results   = collect();

        // Location from criteria — map to Adzuna country if possible
        $locations = $criteria['locations'] ?? [];
        $country   = $this->detectCountry($locations) ?? $this->country;

        foreach (array_slice($keywords, 0, 3) as $keyword) {
            try {
                $params = [
                    'app_id'           => $this->appId,
                    'app_key'          => $this->appKey,
                    'results_per_page' => 50,
                    'what'             => $keyword,
                    'max_days_old'     => $daysOld,
                    'content-type'     => 'application/json',
                    'sort_by'          => 'date',
                    'sort_direction'   => 'down',
                ];

                // Finer location filtering within the country
                if (!empty($locations) && !($criteria['remote_only'] ?? false)) {
                    $params['where'] = $locations[0];
                }

                if ($minSalary) {
                    $params['salary_min'] = (int) $minSalary;
                }

                $response = Http::timeout(15)
                    ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                    ->get("{$this->baseUrl}/{$country}/search/1", $params);

                if ($response->failed()) continue;

                $jobs = $response->json('results', []);

                $mapped = collect($jobs)->map(fn($j) => [
                    'title'           => $j['title'] ?? '',
                    'company'         => $j['company']['display_name'] ?? '',
                    'company_url'     => null,
                    'location'        => $j['location']['display_name'] ?? '',
                    'is_remote'       => str_contains(strtolower($j['title'] ?? ''), 'remote')
                                      || str_contains(strtolower($j['description'] ?? ''), 'remote'),
                    'description'     => strip_tags($j['description'] ?? ''),
                    'salary_min'      => $j['salary_min'] ?? null,
                    'salary_max'      => $j['salary_max'] ?? null,
                    'salary_currency' => strtoupper($country === 'us' ? 'USD' : ($country === 'gb' ? 'GBP' : 'EUR')),
                    'application_url' => $j['redirect_url'] ?? null,
                    'source_url'      => $j['redirect_url'] ?? null,
                    'external_id'     => $j['id'] ?? md5($j['title'] . ($j['company']['display_name'] ?? '')),
                    'tags'            => explode(' ', strtolower($j['category']['tag'] ?? '')),
                    'posted_at'       => $j['created'] ?? null,
                    'source'          => 'adzuna',
                ]);

                $results = $results->merge($mapped);

            } catch (\Exception $e) {
                Log::warning('Adzuna fetch failed', ['keyword' => $keyword, 'error' => $e->getMessage()]);
            }
        }

        return $results->unique('external_id');
    }

    /**
     * Map location strings to Adzuna country codes.
     */
    private function detectCountry(array $locations): ?string
    {
        $map = [
            'nigeria'        => 'ng',
            'united kingdom' => 'gb',
            'uk'             => 'gb',
            'united states'  => 'us',
            'usa'            => 'us',
            'australia'      => 'au',
            'canada'         => 'ca',
            'germany'        => 'de',
            'france'         => 'fr',
            'india'          => 'in',
            'netherlands'    => 'nl',
            'new zealand'    => 'nz',
            'poland'         => 'pl',
            'russia'         => 'ru',
            'singapore'      => 'sg',
            'south africa'   => 'za',
            'brazil'         => 'br',
            'italy'          => 'it',
            'mexico'         => 'mx',
        ];

        foreach ($locations as $loc) {
            $loc = strtolower(trim($loc));
            if (isset($map[$loc])) return $map[$loc];

            // Check for partial matches
            foreach ($map as $name => $code) {
                if (str_contains($loc, $name)) return $code;
            }
        }

        return null;
    }
}

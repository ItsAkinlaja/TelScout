<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * RemoteOK — free public API, no key required.
 * https://remoteok.com/api
 * Returns remote-only jobs, tag-filtered.
 */
class RemoteOkSource implements JobSourceInterface
{
    public function getName(): string { return 'remoteok'; }

    public function search(array $criteria): Collection
    {
        $keywords = $criteria['keywords'] ?? ['react', 'laravel', 'node'];
        $daysOld  = $criteria['days_old'] ?? 30;
        $cutoff   = now()->subDays($daysOld)->timestamp;

        // RemoteOK uses comma-separated tags
        $tags = implode(',', array_map(
            fn($k) => strtolower(str_replace(' ', '-', trim($k))),
            array_slice($keywords, 0, 5)
        ));

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'TelScout/1.0 (+https://telscout.app)'])
                ->get("https://remoteok.com/api?tag={$tags}");

            if ($response->failed()) return collect();

            $data = $response->json() ?? [];
            if (isset($data[0]['legal'])) array_shift($data); // remove legal notice

            return collect($data)
                ->filter(fn($j) => isset($j['position']) && ($j['date'] ?? 0) >= $cutoff)
                ->map(fn($j) => [
                    'title'            => $j['position'] ?? '',
                    'company'          => $j['company'] ?? '',
                    'company_url'      => $j['company_url'] ?? null,
                    'location'         => 'Remote',
                    'is_remote'        => true,
                    'description'      => strip_tags($j['description'] ?? ''),
                    'salary_min'       => $j['salary_min'] ?? null,
                    'salary_max'       => $j['salary_max'] ?? null,
                    'salary_currency'  => 'USD',
                    'application_url'  => $j['apply_url'] ?? $j['url'] ?? null,
                    'source_url'       => $j['url'] ?? null,
                    'external_id'      => (string) ($j['id'] ?? ''),
                    'tags'             => $j['tags'] ?? [],
                    'posted_at'        => isset($j['date']) ? date('Y-m-d H:i:s', $j['date']) : null,
                    'source'           => 'remoteok',
                ]);

        } catch (\Exception $e) {
            Log::warning('RemoteOK fetch failed', ['error' => $e->getMessage()]);
            return collect();
        }
    }
}

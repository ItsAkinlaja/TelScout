<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OpenWebNinja — Real-time Google Jobs data.
 * https://api.openwebninja.com/realtime-jobs-data/google-jobs/search
 *
 * Complements SerpAPI with a second Google Jobs source for redundancy.
 * If SerpAPI hits its monthly cap, this kicks in automatically.
 *
 * Set in .env:
 *   OPENWEBNINJA_API_KEY=your_key
 *
 * Register at https://openwebninja.com
 */
class OpenWebNinjaSource implements JobSourceInterface
{
    private ?string $apiKey;
    private string  $baseUrl = 'https://api.openwebninja.com/realtime-jobs-data/google-jobs/search';

    public function __construct()
    {
        $this->apiKey = config('services.openwebninja.api_key');
    }

    public function getName(): string { return 'openwebninja'; }

    public function search(array $criteria): Collection
    {
        if (empty($this->apiKey)) {
            Log::info('OpenWebNinja skipped — set OPENWEBNINJA_API_KEY in .env');
            return collect();
        }

        $keywords  = $criteria['keywords']  ?? ['software engineer'];
        $locations = $criteria['locations'] ?? [];
        $daysOld   = $criteria['days_old']  ?? 30;
        $results   = collect();

        // Build natural-language query the same way Google Jobs expects it
        $query = implode(' ', array_slice($keywords, 0, 2));

        if (!($criteria['remote_only'] ?? false) && !empty($locations)) {
            $firstLoc = $locations[0];
            if (!in_array(strtolower($firstLoc), ['remote', 'worldwide', 'anywhere'])) {
                $query .= ' jobs in ' . $firstLoc;
            }
        } elseif ($criteria['remote_only'] ?? false) {
            $query .= ' remote jobs';
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'X-API-Key' => $this->apiKey,
                    'Accept'    => 'application/json',
                ])
                ->get($this->baseUrl, [
                    'query' => $query,
                ]);

            if ($response->failed()) {
                Log::warning('OpenWebNinja fetch failed', [
                    'status' => $response->status(),
                    'body'   => substr($response->body(), 0, 200),
                ]);
                return collect();
            }

            $data = $response->json() ?? [];

            // OpenWebNinja returns jobs under the 'data' key
            $jobs = $data['data'] ?? $data['jobs'] ?? $data['results'] ?? [];
            if (empty($jobs) && isset($data[0]['title'])) {
                $jobs = $data;
            }

            $results = collect($jobs)->map(function ($j) {
                $exts = $j['detected_extensions'] ?? $j['extensions'] ?? [];

                $salaryMin = null;
                $salaryMax = null;
                $currency  = null;

                if (!empty($j['job_salary_string'])) {
                    [$salaryMin, $salaryMax, $currency] = $this->parseSalary((string) $j['job_salary_string']);
                } elseif (!empty($j['salary'])) {
                    [$salaryMin, $salaryMax, $currency] = $this->parseSalary((string) $j['salary']);
                }

                // OpenWebNinja uses job_min_salary / job_max_salary directly
                if (!$salaryMin && !empty($j['job_min_salary'])) $salaryMin = $j['job_min_salary'];
                if (!$salaryMax && !empty($j['job_max_salary'])) $salaryMax = $j['job_max_salary'];

                $location = trim(
                    ($j['job_city']    ? $j['job_city']    . ', ' : '') .
                    ($j['job_state']   ? $j['job_state']   . ', ' : '') .
                    ($j['job_country'] ?? '')
                , ', ');
                if (empty($location)) $location = $j['job_location'] ?? '';

                $postedAt = null;
                if (!empty($j['job_posted_at_datetime_utc'])) {
                    $postedAt = date('Y-m-d H:i:s', strtotime($j['job_posted_at_datetime_utc']));
                } elseif (!empty($j['job_posted_at_timestamp'])) {
                    $postedAt = date('Y-m-d H:i:s', (int) $j['job_posted_at_timestamp']);
                }

                return [
                    'title'           => $j['job_title']      ?? $j['title']        ?? '',
                    'company'         => $j['employer_name']   ?? $j['job_publisher'] ?? $j['company_name'] ?? $j['company'] ?? '',
                    'company_url'     => $j['employer_website'] ?? null,
                    'location'        => $location,
                    'is_remote'       => (bool) ($j['job_is_remote'] ?? false)
                                      || str_contains(strtolower($location), 'remote'),
                    'description'     => strip_tags($j['job_description'] ?? $j['description'] ?? ''),
                    'salary_min'      => $salaryMin,
                    'salary_max'      => $salaryMax,
                    'salary_currency' => $currency ?? ($j['job_salary_currency'] ?? null),
                    'application_url' => $j['job_apply_link'] ?? $j['job_google_link'] ?? $j['share_link'] ?? null,
                    'source_url'      => $j['job_google_link'] ?? $j['job_apply_link'] ?? null,
                    'external_id'     => $j['job_id'] ?? $j['job_uid'] ?? $j['id'] ?? md5(($j['job_title'] ?? '') . ($j['employer_name'] ?? '')),
                    'tags'            => $this->extractTags($j),
                    'posted_at'       => $postedAt,
                    'source'          => 'openwebninja',
                ];
            })->filter(fn($j) => !empty($j['title']) && !empty($j['company']));

        } catch (\Exception $e) {
            Log::warning('OpenWebNinja fetch exception', ['error' => $e->getMessage()]);
        }

        return $results;
    }

    private function extractTags(array $job): array
    {
        $exts = $job['detected_extensions'] ?? $job['extensions'] ?? [];
        $tags = [];

        if (!empty($exts['schedule_type'])) $tags[] = strtolower($exts['schedule_type']);
        if ($exts['work_from_home'] ?? false)  $tags[] = 'remote';

        return $tags;
    }

    private function parseSalary(string $salary): array
    {
        $currency = 'USD';
        if (str_contains($salary, '₦') || stripos($salary, 'NGN') !== false) $currency = 'NGN';
        elseif (str_contains($salary, '£') || stripos($salary, 'GBP') !== false) $currency = 'GBP';
        elseif (str_contains($salary, '€') || stripos($salary, 'EUR') !== false) $currency = 'EUR';

        preg_match_all('/[\d,]+/', $salary, $matches);
        $numbers = array_map(fn($n) => (float) str_replace(',', '', $n), $matches[0] ?? []);
        $numbers = array_values(array_filter($numbers, fn($n) => $n > 0));

        $min = $numbers[0] ?? null;
        $max = $numbers[1] ?? $numbers[0] ?? null;

        if ($min && str_contains(strtolower($salary), 'month')) {
            $min = $min * 12;
            $max = $max ? $max * 12 : null;
        }

        return [$min, $max, $currency];
    }

    private function parsePostedAt(string $postedAt): ?string
    {
        $p = strtolower(trim($postedAt));
        if (str_contains($p, 'today') || str_contains($p, 'hour'))  return now()->format('Y-m-d H:i:s');
        if (preg_match('/(\d+)\s+day/', $p, $m))   return now()->subDays((int) $m[1])->format('Y-m-d H:i:s');
        if (preg_match('/(\d+)\s+week/', $p, $m))  return now()->subWeeks((int) $m[1])->format('Y-m-d H:i:s');
        if (preg_match('/(\d+)\s+month/', $p, $m)) return now()->subMonths((int) $m[1])->format('Y-m-d H:i:s');
        return null;
    }
}

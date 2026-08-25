<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;

/**
 * Orchestrates all job sources, merges results, and applies location filtering.
 *
 * Sources are injected via the service container (see AppServiceProvider).
 * This makes the class testable — swap in mock sources without touching production code.
 */
class JobSourceManager
{
    /** @var JobSourceInterface[] */
    private array $sources;

    /**
     * @param JobSourceInterface[] $sources Injected by the service container.
     */
    public function __construct(array $sources)
    {
        $this->sources = $sources;
    }

    public function search(array $criteria): Collection
    {
        $all = collect();

        foreach ($this->sources as $source) {
            try {
                $results = $source->search($criteria);
                $all = $all->merge($results);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error("JobSource [{$source->getName()}] failed", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $filtered = $all
            ->filter(fn($j) => !empty($j['title']) && !empty($j['company']))
            ->unique(fn($j) => $j['source_url'] ?? ($j['title'] . '|' . $j['company']))
            ->values();

        // Apply location filter if specific locations were requested
        $locations  = $criteria['locations']   ?? [];
        $remoteOnly = $criteria['remote_only'] ?? false;

        if ($remoteOnly) {
            $filtered = $filtered->filter(fn($j) => $this->isRemote($j));
        } elseif (!empty($locations) && !$this->hasWorldwideLocation($locations)) {
            // Include jobs matching location OR remote jobs (unless restricted to other country)
            $filtered = $filtered->filter(fn($j) =>
                $this->matchesLocation($j['location'] ?? '', $locations) ||
                ($this->isRemote($j) && !$this->isRestrictedToOtherCountry($j['location'] ?? '', $locations))
            );
        }

        return $filtered->values();
    }

    public function getSourceNames(): array
    {
        return array_map(fn($s) => $s->getName(), $this->sources);
    }

    /** @return JobSourceInterface[] */
    public function getSources(): array
    {
        return $this->sources;
    }

    /**
     * Check if a job is remote based on its is_remote flag or location string.
     */
    private function isRemote(array $job): bool
    {
        if (!empty($job['is_remote'])) return true;
        $loc = strtolower($job['location'] ?? '');
        return str_contains($loc, 'remote')
            || str_contains($loc, 'worldwide')
            || str_contains($loc, 'anywhere')
            || $loc === '';  // no location = treat as remote/global
    }

    /**
     * Check if the job location matches any of the requested locations.
     * Handles compound strings like "Lagos Nigeria", "Lagos, Nigeria".
     */
    private function matchesLocation(string $jobLocation, array $requestedLocations): bool
    {
        $jl = strtolower(trim($jobLocation));

        // Always include remote jobs — they're open to all locations
        if ($this->isRemote(['location' => $jobLocation, 'is_remote' => false])) {
            return true;
        }

        $aliases = [
            'uk'  => ['united kingdom', 'great britain', 'england', 'scotland', 'wales'],
            'usa' => ['united states', 'america', 'us'],
            'us'  => ['united states', 'america', 'usa'],
        ];

        foreach ($requestedLocations as $loc) {
            $loc = strtolower(trim($loc));
            if ($loc === '') continue;

            // Skip wildcards — handled upstream
            if (in_array($loc, ['worldwide', 'remote', 'anywhere', 'global', 'all'])) return true;

            // Split compound location into individual terms: "Lagos Nigeria" → ["lagos", "nigeria"]
            $terms = preg_split('/[\s,]+/', $loc);
            $terms = array_filter($terms, fn($t) => strlen($t) > 2);

            // Any single term match is sufficient (e.g. "Nigeria" matches "Lagos, Nigeria")
            foreach ($terms as $term) {
                if (str_contains($jl, $term)) return true;
            }

            // Direct whole-string match or reverse contains
            if (str_contains($jl, $loc) || str_contains($loc, $jl)) return true;

            // Alias match
            foreach ($aliases as $alias => $names) {
                if ($loc === $alias || in_array($loc, $names)) {
                    if (str_contains($jl, $alias) || collect($names)->contains(fn($n) => str_contains($jl, $n))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Checks if a "Remote" job is explicitly restricted to a region that
     * doesn't match our target locations (e.g., "Remote (UK only)").
     */
    private function isRestrictedToOtherCountry(string $jobLocation, array $requestedLocations): bool
    {
        $jl = strtolower(trim($jobLocation));

        // If it doesn't mention remote, it's not a remote job with restrictions we care about here
        if (!str_contains($jl, 'remote')) return false;

        // If it mentions our requested locations, it's not restricted *away* from us
        if ($this->matchesLocation($jobLocation, $requestedLocations)) return false;

        // If it contains a bracketed country or specific region name that isn't ours
        // Examples: "Remote (UK)", "Remote - US Only", "Remote (Europe)"
        $regions = ['uk', 'usa', 'us', 'europe', 'canada', 'germany', 'india'];
        foreach ($regions as $region) {
            if (str_contains($jl, $region)) return true;
        }

        return false;
    }

    /**
     * Returns true if locations list contains a worldwide/any indicator —
     * meaning the user wants jobs from everywhere, no location filtering needed.
     */
    private function hasWorldwideLocation(array $locations): bool
    {
        $wildcards = ['worldwide', 'remote', 'anywhere', 'global', 'all'];
        foreach ($locations as $loc) {
            if (in_array(strtolower(trim($loc)), $wildcards)) return true;
        }
        return false;
    }
}

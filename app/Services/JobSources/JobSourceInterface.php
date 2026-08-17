<?php

namespace App\Services\JobSources;

use Illuminate\Support\Collection;

interface JobSourceInterface
{
    /**
     * Search for job listings matching the given criteria.
     *
     * @param array $criteria {
     *   keywords: string[],
     *   locations: string[],
     *   remote_only: bool,
     *   days_old: int,       // max age in days (7, 14, 30)
     *   min_salary: float|null,
     *   per_page: int
     * }
     * @return Collection<array> Each item: {
     *   title, company, company_url, location, is_remote,
     *   description, salary_min, salary_max, salary_currency,
     *   application_url, source_url, external_id, tags[], posted_at
     * }
     */
    public function search(array $criteria): Collection;

    public function getName(): string;
}

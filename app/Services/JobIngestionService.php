<?php

namespace App\Services;

use App\Models\Company;
use App\Models\JobListing;
use App\Models\JobSkill;
use Illuminate\Support\Facades\Log;

class JobIngestionService
{
    /**
     * Upsert a normalized job array into the database.
     * Returns the JobListing (created or updated), or null if skipped.
     *
     * Normalized job array shape:
     * title, company, company_url, location, country, city, is_remote,
     * workplace_type, experience_level, employment_type, description,
     * salary_min, salary_max, salary_currency, application_url, source_url,
     * external_id, tags[], posted_at, source
     *
     * Deduplication priority:
     *   1. content_hash match (cross-source dedup — same job on multiple boards)
     *   2. source + external_id unique constraint
     *   3. source_url exact match
     */
    public function ingest(array $jobData): ?JobListing
    {
        // 1. Resolve/create the company
        $company = Company::findOrCreateByDomain([
            'name'    => $jobData['company'] ?? 'Unknown',
            'website' => $jobData['company_url'] ?? null,
        ]);

        // 2. Skip excluded companies
        if ($company->is_excluded) {
            return null;
        }

        // 3. Generate content hash for cross-source deduplication
        $contentHash = JobListing::generateContentHash(
            $company->name,
            $jobData['title'] ?? '',
            $jobData['location'] ?? ''
        );

        $now = now();

        // 4. Check for existing job by content_hash first (cross-source dedup)
        $existing = JobListing::where('content_hash', $contentHash)->first();

        if ($existing) {
            // Update last_seen_at and re-activate if stale
            $updates = ['last_seen_at' => $now];
            if ($existing->status === 'stale') {
                $updates['status'] = 'active';
            }
            $existing->update($updates);
            return $existing;
        }

        // 5. Check by source + external_id
        if (!empty($jobData['source']) && !empty($jobData['external_id'])) {
            $existing = JobListing::where('source', $jobData['source'])
                ->where('external_id', $jobData['external_id'])
                ->first();

            if ($existing) {
                $existing->update(['last_seen_at' => $now]);
                return $existing;
            }
        }

        // 6. Check by source_url
        if (!empty($jobData['source_url'])) {
            $existing = JobListing::where('source_url', $jobData['source_url'])->first();

            if ($existing) {
                $existing->update(['last_seen_at' => $now]);
                return $existing;
            }
        }

        // 7. Create new job listing
        $job = JobListing::create([
            'company_id'       => $company->id,
            'title'            => $jobData['title'],
            'description'      => $jobData['description'] ?? null,
            'location'         => $jobData['location'] ?? null,
            'country'          => $jobData['country'] ?? null,
            'city'             => $jobData['city'] ?? null,
            'workplace_type'   => $jobData['workplace_type'] ?? null,
            'experience_level' => $jobData['experience_level'] ?? null,
            'is_remote'        => (bool) ($jobData['is_remote'] ?? false),
            'employment_type'  => $jobData['employment_type'] ?? null,
            'salary_min'       => $jobData['salary_min'] ?? null,
            'salary_max'       => $jobData['salary_max'] ?? null,
            'salary_currency'  => $jobData['salary_currency'] ?? null,
            'application_url'  => $jobData['application_url'] ?? null,
            'source_url'       => $jobData['source_url'] ?? null,
            'external_id'      => $jobData['external_id'] ?? null,
            'source'           => $jobData['source'] ?? 'unknown',
            'status'           => 'active',
            'posted_at'        => $jobData['posted_at'] ?? $now,
            'content_hash'     => $contentHash,
            'first_seen_at'    => $now,
            'last_seen_at'     => $now,
        ]);

        // 8. Attach skills/tags
        foreach (array_unique($jobData['tags'] ?? []) as $tag) {
            $tag = trim((string) $tag);
            if (!empty($tag)) {
                $job->skills()->firstOrCreate(['skill' => strtolower($tag)]);
            }
        }

        return $job;
    }

    /**
     * Mark jobs from a source as stale if they were NOT seen in the latest fetch.
     * Only marks stale if last_seen_at is older than 24 hours (avoids race conditions).
     *
     * @param  string    $source          The source identifier (e.g. 'greenhouse')
     * @param  array     $seenExternalIds External IDs observed in the latest fetch
     * @param  int|null  $companyId       Optional company scope
     * @return int                        Number of records updated
     */
    public function markStaleJobs(string $source, array $seenExternalIds, ?int $companyId = null): int
    {
        $query = JobListing::where('source', $source)
            ->whereNotIn('external_id', $seenExternalIds)
            ->where('status', 'active')
            ->where('last_seen_at', '<', now()->subHours(24));

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->update(['status' => 'stale']);
    }
}

<?php

namespace App\Services;

use App\Models\JobListing;
use App\Models\Company;

class ContactDiscoveryService
{
    /**
     * Attempt to discover a hiring email for a given job.
     */
    public function discover(JobListing $job): ?string
    {
        // 1. Scan job description for email patterns
        $email = $this->extractEmailFromText($job->description ?? '');
        if ($email) return $email;

        // 2. Try domain-based heuristics if company domain is available
        $company = $job->company;
        if ($company && $company->normalized_domain) {
            // Check if we already have an email for this company
            if ($company->contact_email) return $company->contact_email;

            // We could try common patterns, but for "concise and great",
            // we'll stick to what we find in the data for now.
            // Placeholder for future enrichment logic (e.g. Hunter.io / Apollo)
        }

        return null;
    }

    /**
     * Regex to extract the first valid email found in a text.
     */
    private function extractEmailFromText(string $text): ?string
    {
        if (empty($text)) return null;

        // Look for common email formats
        $pattern = '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i';

        if (preg_match_all($pattern, $text, $matches)) {
            $blacklist = ['noreply@', 'no-reply@', 'support@', 'info@', 'hello@', 'privacy@'];

            foreach ($matches[0] as $email) {
                $email = strtolower($email);
                $isBlacklisted = false;

                foreach ($blacklist as $term) {
                    if (str_contains($email, $term)) {
                        $isBlacklisted = true;
                        break;
                    }
                }

                if (!$isBlacklisted) return $email;
            }
        }

        return null;
    }
}

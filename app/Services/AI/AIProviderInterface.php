<?php

namespace App\Services\AI;

interface AIProviderInterface
{
    /**
     * Analyze a job listing and return structured insights.
     */
    public function analyzeJob(array $jobData, array $profileData): array;

    /**
     * Generate a personalized outreach email.
     */
    public function generateEmail(array $jobData, array $companyData, array $profileData): array;
}

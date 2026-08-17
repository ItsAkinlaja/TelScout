<?php

namespace App\Services;

use App\Models\CandidateProfile;
use App\Models\JobListing;

class MatchScoringService
{
    // Default weights (must sum to 100)
    private const WEIGHTS = [
        'technology' => 30,
        'experience' => 20,
        'role_title'  => 20,
        'location'   => 10,
        'salary'     => 10,
        'industry'   => 5,
        'company'    => 5,
    ];

    private const CLASSIFICATIONS = [
        90 => 'excellent',
        80 => 'strong',
        70 => 'good',
        60 => 'possible',
        0  => 'low',
    ];

    public function score(CandidateProfile $profile, JobListing $job): array
    {
        $profile->loadMissing(['skills', 'experiences']);
        $job->loadMissing(['skills', 'company']);

        $candidateSkills = array_map('strtolower', $profile->skill_names);
        $jobSkills       = array_map('strtolower', $job->skill_names);

        // ── Technology match (30%) ───────────────────────────────────────────
        $techScore   = $this->scoreTechnology($candidateSkills, $jobSkills);
        $matchedSkills  = array_values(array_intersect($candidateSkills, $jobSkills));
        $missingSkills  = array_values(array_diff($jobSkills, $candidateSkills));

        // ── Experience match (20%) ───────────────────────────────────────────
        $expScore = $this->scoreExperience($profile, $job);

        // ── Role / title match (20%) ─────────────────────────────────────────
        $roleScore = $this->scoreRole($profile, $job);

        // ── Location / remote match (10%) ────────────────────────────────────
        $locationScore = $this->scoreLocation($profile, $job);

        // ── Salary match (10%) ───────────────────────────────────────────────
        $salaryScore = $this->scoreSalary($profile, $job);

        // ── Industry match (5%) ──────────────────────────────────────────────
        $industryScore = $this->scoreIndustry($profile, $job);

        // ── Company quality (5%) — simple heuristic ──────────────────────────
        $companyScore = $this->scoreCompany($job);

        $breakdown = [
            'technology' => round($techScore, 1),
            'experience' => round($expScore, 1),
            'role_title'  => round($roleScore, 1),
            'location'   => round($locationScore, 1),
            'salary'     => round($salaryScore, 1),
            'industry'   => round($industryScore, 1),
            'company'    => round($companyScore, 1),
        ];

        $total = ($techScore * self::WEIGHTS['technology']
                + $expScore * self::WEIGHTS['experience']
                + $roleScore * self::WEIGHTS['role_title']
                + $locationScore * self::WEIGHTS['location']
                + $salaryScore * self::WEIGHTS['salary']
                + $industryScore * self::WEIGHTS['industry']
                + $companyScore * self::WEIGHTS['company']) / 100;

        $total = min(100, max(0, round($total, 2)));

        return [
            'score'           => $total,
            'classification'  => $this->classify($total),
            'matched_skills'  => $matchedSkills,
            'missing_skills'  => $missingSkills,
            'reasoning'       => $this->buildReasoning($total, $matchedSkills, $missingSkills, $breakdown),
            'score_breakdown' => $breakdown,
        ];
    }

    private function scoreTechnology(array $candidateSkills, array $jobSkills): float
    {
        if (empty($jobSkills)) {
            return 70.0; // No specific requirements → good baseline
        }
        $matched = count(array_intersect($candidateSkills, $jobSkills));
        return min(100, ($matched / count($jobSkills)) * 100);
    }

    private function scoreExperience(CandidateProfile $profile, JobListing $job): float
    {
        $candidateYears = $profile->years_of_experience ?? 0;
        $desc = strtolower($job->description ?? '');

        // Extract required years from job description heuristically
        preg_match('/(\d+)\+?\s*years?\s*(of\s+)?experience/i', $desc, $m);
        $requiredYears = isset($m[1]) ? (int) $m[1] : 0;

        if ($requiredYears === 0) {
            return 75.0;
        }

        if ($candidateYears >= $requiredYears) {
            return 100.0;
        }

        $ratio = $candidateYears / $requiredYears;
        return min(100, $ratio * 100);
    }

    private function scoreRole(CandidateProfile $profile, JobListing $job): float
    {
        $jobTitle = strtolower($job->title ?? '');
        $preferredRoles = array_map('strtolower', $profile->preferred_roles ?? []);

        // Common keywords for full-stack / software engineers
        $candidateTitles = array_map('strtolower', array_filter([
            $profile->primary_title ?? '',
            ...$preferredRoles,
        ]));

        foreach ($candidateTitles as $ct) {
            if (empty($ct)) continue;
            $keywords = array_filter(explode(' ', $ct));
            $matched = 0;
            foreach ($keywords as $kw) {
                if (strlen($kw) > 3 && str_contains($jobTitle, $kw)) {
                    $matched++;
                }
            }
            if ($matched > 0 && count($keywords) > 0) {
                return min(100, ($matched / count($keywords)) * 100 + 20);
            }
        }

        // Partial matches for generic roles
        $genericMatches = ['engineer', 'developer', 'fullstack', 'full stack', 'full-stack', 'backend', 'frontend'];
        foreach ($genericMatches as $gm) {
            if (str_contains($jobTitle, $gm)) {
                return 70.0;
            }
        }

        return 30.0;
    }

    private function scoreLocation(CandidateProfile $profile, JobListing $job): float
    {
        // Remote jobs always work
        if ($job->is_remote) {
            return 100.0;
        }

        $preference = $profile->work_preference ?? 'any';
        if ($preference === 'remote') {
            return 20.0; // candidate wants remote, job is not remote
        }

        $candidateLoc  = strtolower($profile->location ?? '');
        $jobLoc        = strtolower($job->location ?? '');
        $preferredLocs = array_map('strtolower', $profile->preferred_locations ?? []);

        if (empty($jobLoc)) {
            return 60.0;
        }

        // Check preferred locations
        foreach ($preferredLocs as $pLoc) {
            if (!empty($pLoc) && (str_contains($jobLoc, $pLoc) || str_contains($pLoc, $jobLoc))) {
                return 100.0;
            }
        }

        // Candidate location vs job location
        if (!empty($candidateLoc) && (str_contains($jobLoc, $candidateLoc) || str_contains($candidateLoc, $jobLoc))) {
            return 90.0;
        }

        // "worldwide" or "global"
        if (str_contains($jobLoc, 'worldwide') || str_contains($jobLoc, 'global')) {
            return 90.0;
        }

        return 40.0;
    }

    private function scoreSalary(CandidateProfile $profile, JobListing $job): float
    {
        $minRequired = $profile->minimum_salary;
        if (!$minRequired) {
            return 80.0; // No minimum set → fine
        }

        if (!$job->salary_min && !$job->salary_max) {
            return 60.0; // No salary info → uncertain
        }

        $jobMax = $job->salary_max ?? $job->salary_min;
        if ($jobMax >= $minRequired) {
            return 100.0;
        }

        $ratio = $jobMax / $minRequired;
        return min(100, $ratio * 100);
    }

    private function scoreIndustry(CandidateProfile $profile, JobListing $job): float
    {
        $preferred = array_map('strtolower', $profile->preferred_industries ?? []);
        $excluded  = array_map('strtolower', $profile->excluded_industries ?? []);
        $jobIndustry = strtolower($job->company?->industry ?? '');

        if (!empty($jobIndustry)) {
            foreach ($excluded as $ex) {
                if (!empty($ex) && str_contains($jobIndustry, $ex)) {
                    return 0.0;
                }
            }
            foreach ($preferred as $pref) {
                if (!empty($pref) && str_contains($jobIndustry, $pref)) {
                    return 100.0;
                }
            }
        }

        return 70.0; // Neutral
    }

    private function scoreCompany(JobListing $job): float
    {
        $company = $job->company;
        if (!$company) {
            return 50.0;
        }

        $score = 50.0;
        if ($company->website) $score += 10;
        if ($company->careers_url) $score += 10;
        if ($company->description) $score += 10;
        if ($company->linkedin_url) $score += 10;
        if (!empty($company->tech_stack)) $score += 10;

        return min(100, $score);
    }

    private function classify(float $score): string
    {
        foreach (self::CLASSIFICATIONS as $threshold => $label) {
            if ($score >= $threshold) {
                return $label;
            }
        }
        return 'low';
    }

    private function buildReasoning(
        float $score,
        array $matched,
        array $missing,
        array $breakdown
    ): string {
        $parts = [];

        $classification = $this->classify($score);
        $parts[] = ucfirst($classification) . " match (score: {$score}%).";

        if (!empty($matched)) {
            $parts[] = 'Matched skills: ' . implode(', ', array_slice($matched, 0, 8)) . '.';
        }

        if (!empty($missing)) {
            $parts[] = 'Skills to develop: ' . implode(', ', array_slice($missing, 0, 5)) . '.';
        }

        $topDimension = array_search(max($breakdown), $breakdown);
        $parts[] = "Strongest factor: {$topDimension} ({$breakdown[$topDimension]}%).";

        return implode(' ', $parts);
    }
}

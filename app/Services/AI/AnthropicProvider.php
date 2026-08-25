<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Anthropic Claude provider (claude-3-5-haiku-20241022 and family).
 * Docs: https://docs.anthropic.com/en/api/messages
 */
class AnthropicProvider implements AIProviderInterface
{
    private const API_URL      = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION  = '2023-06-01';

    private string $apiKey;
    private string $model;
    private float  $temperature;
    private int    $maxTokens;

    public function __construct(?string $apiKey = null, ?string $model = null, float $temperature = 0.7, int $maxTokens = 1024)
    {
        $this->apiKey      = (string) ($apiKey ?? config('services.anthropic.api_key', ''));
        $this->model       = (string) ($model  ?? config('services.anthropic.model', 'claude-3-5-haiku-20241022'));
        $this->temperature = $temperature;
        $this->maxTokens   = $maxTokens;
    }

    public function analyzeJob(array $jobData, array $profileData): array
    {
        $prompt = $this->buildAnalysisPrompt($jobData, $profileData);

        $response = $this->message(
            system: $this->analysisSystemPrompt($profileData),
            user: $prompt,
        );

        return [
            'analysis' => $response,
            'provider' => 'anthropic',
            'model'    => $this->model,
        ];
    }

    public function generateEmail(array $jobData, array $companyData, array $profileData): array
    {
        $prompt   = $this->buildEmailPrompt($jobData, $companyData, $profileData);
        $response = $this->message(
            system: $this->emailSystemPrompt(),
            user: $prompt,
        );

        return [
            'subject'    => $this->extractSubject($response, $profileData),
            'body'       => $this->extractBody($response),
            'suggestions' => $this->extractSuggestions($response),
            'raw'        => $response,
            'provider'   => 'anthropic',
            'model'      => $this->model,
        ];
    }

    

    private function message(string $system, string $user): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('Anthropic API key is not configured. Go to Settings â†’ AI to add your key.');
        }

        $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => self::API_VERSION,
                'content-type'      => 'application/json',
            ])
            ->timeout(30)
            ->post(self::API_URL, [
                'model'       => $this->model,
                'max_tokens'  => $this->maxTokens,
                'temperature' => $this->temperature,
                'system'      => $system,
                'messages'    => [
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if ($response->failed()) {
            Log::error('Anthropic API error', [
                'status' => $response->status(),
                'error'  => $response->json('error.message'),
            ]);
            throw new RuntimeException('AI generation failed: ' . ($response->json('error.message') ?? 'Unknown error'));
        }

        return $response->json('content.0.text', '');
    }

    // â”€â”€ Prompt builders (mirrors OpenAIProvider) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function buildAnalysisPrompt(array $job, array $profile): string
    {
        $candidateName   = $profile['full_name']        ?? 'Candidate';
        $candidateTitle  = $profile['primary_title']    ?? 'Professional';
        $candidateSkills = $this->formatList($profile['skills'] ?? []);
        $candidateExp    = $this->formatExperience($profile['experiences'] ?? []);
        $candidateYears  = $profile['years_of_experience'] ?? 'Unknown';

        $jobTitle   = $job['title']       ?? 'Untitled';
        $jobCompany = $job['company_name'] ?? 'Unknown Company';
        $jobLoc     = $job['location']    ?? 'Unknown';
        $jobRemote  = $this->bool($job['is_remote'] ?? false);
        $jobDesc    = $this->truncate($job['description'] ?? '', 800);

        return <<<PROMPT
Analyze this job opportunity for the candidate:

CANDIDATE:
- Name: {$candidateName}
- Title: {$candidateTitle}
- Skills: {$candidateSkills}
- Experience: {$candidateExp}
- Years of experience: {$candidateYears}

JOB:
- Title: {$jobTitle}
- Company: {$jobCompany}
- Location: {$jobLoc} (Remote: {$jobRemote})
- Description: {$jobDesc}

Provide a brief (3-5 sentence) professional analysis of why this is or isn't a good fit. Focus on concrete skill alignment, growth potential, and any red flags. Do NOT invent information.
PROMPT;
    }

    private function buildEmailPrompt(array $job, array $company, array $profile): string
    {
        $contactName        = $job['contact_name']     ?? null;
        $greeting           = $contactName ? "Hi {$contactName}," : 'Hi,';
        $candidateName      = $profile['full_name']      ?? 'Candidate';
        $candidateTitle     = $profile['primary_title']  ?? 'Professional';
        $candidatePortfolio = $profile['portfolio_url']  ?? '';
        $candidateSkills    = $this->formatList(array_slice($profile['skills'] ?? [], 0, 8));
        $candidateExp       = $this->formatExperience($profile['experiences'] ?? []);
        $jobTitle           = $job['title']              ?? 'Untitled';
        $companyName        = $company['name']           ?? 'Unknown Company';
        $jobSkills          = $this->formatList($job['required_skills'] ?? []);
        $jobDesc            = $this->truncate($job['description'] ?? '', 400);

        return <<<PROMPT
Write a professional outreach email for this job application.

CANDIDATE:
- Name: {$candidateName}
- Title: {$candidateTitle}
- Portfolio: {$candidatePortfolio}
- Key skills: {$candidateSkills}
- Experience: {$candidateExp}

JOB:
- Title: {$jobTitle}
- Company: {$companyName}
- Tech mentioned: {$jobSkills}
- Description excerpt: {$jobDesc}

INSTRUCTIONS:
- Opening greeting: {$greeting}
- Keep it under 200 words
- Be professional and genuine
- Mention 1-2 specific technologies from the job that I actually know
- Do NOT fabricate projects, salary, or recruiter names
- End with: "Portfolio: {$candidatePortfolio}"
- Sign off: "best,\n{$candidateName}"
- Format: subject: [subject line]\n\n[email body]\n\n---CV TAILORING---\n[3-4 bullet points of resume tips]
PROMPT;
    }


    private function analysisSystemPrompt(array $profile): string
    {
        $title = $profile['primary_title'] ?? 'professional';
        return "You are an expert job market analyst helping a {$title} evaluate job opportunities. Be concise, honest and specific.";
    }
    private function emailSystemPrompt(): string
    {
        return 'You are writing genuine, concise professional outreach emails and resume tailoring tips for a job candidate. ' .
               'Never fabricate experience, projects, or company information. ' .
               'Be specific and honest. Never use hollow phrases. ' .
               'The email must be under 200 words. ' .
               'The CV tailoring section should provide 3-4 concrete, actionable tips specific to this job.';
    }

    // â”€â”€ Response parsers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function extractSubject(string $response, array $profile): string
    {
        if (preg_match('/^subject:\s*(.+)$/mi', $response, $m)) {
            return trim($m[1]);
        }
        return ($profile['primary_title'] ?? 'Professional') . ' â€” ' . ($profile['full_name'] ?? '');
    }

    private function extractBody(string $response): string
    {
        $body = preg_replace('/^subject:.*$/mi', '', $response);
        $body = explode('---CV TAILORING---', $body)[0];
        return trim($body);
    }

    private function extractSuggestions(string $response): ?string
    {
        $parts = explode('---CV TAILORING---', $response);
        return count($parts) > 1 ? trim($parts[1]) : null;
    }

    // â”€â”€ Utilities â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    private function formatList(array $items): string
    {
        return implode(', ', array_filter($items));
    }

    private function formatExperience(array $experiences): string
    {
        return implode(', ', array_map(
            fn($e) => ($e['title'] ?? '') . ' at ' . ($e['company'] ?? ''),
            array_slice($experiences, 0, 3)
        ));
    }

    private function truncate(string $text, int $limit): string
    {
        return strlen($text) > $limit ? substr($text, 0, $limit) . '...' : $text;
    }

    private function bool(mixed $v): string
    {
        return $v ? 'Yes' : 'No';
    }
}

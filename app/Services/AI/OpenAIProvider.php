<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpenAIProvider implements AIProviderInterface
{
    private string $apiKey;
    private string $model;
    private float  $temperature;
    private int    $maxTokens;
    private string $baseUrl = 'https://api.openai.com/v1';

    public function __construct(?string $apiKey = null, ?string $model = null, float $temperature = 0.7, int $maxTokens = 1000)
    {
        $this->apiKey      = (string) ($apiKey ?? config('services.ai.api_key', ''));
        $this->model       = (string) ($model  ?? config('services.ai.model', 'gpt-4o-mini'));
        $this->temperature = $temperature;
        $this->maxTokens   = $maxTokens;
    }

    public function analyzeJob(array $jobData, array $profileData): array
    {
        $prompt = $this->buildAnalysisPrompt($jobData, $profileData);

        $response = $this->chat([
            ['role' => 'system', 'content' => 'You are an expert job market analyst helping a software engineer evaluate job opportunities. Be concise, honest and specific.'],
            ['role' => 'user',   'content' => $prompt],
        ]);

        return [
            'analysis' => $response,
            'provider' => 'openai',
            'model'    => $this->model,
        ];
    }

    public function generateEmail(array $jobData, array $companyData, array $profileData): array
    {
        $prompt   = $this->buildEmailPrompt($jobData, $companyData, $profileData);
        $response = $this->chat([
            ['role' => 'system', 'content' => $this->emailSystemPrompt()],
            ['role' => 'user',   'content' => $prompt],
        ]);

        return [
            'subject'    => $this->extractSubject($response, $profileData),
            'body'       => $this->extractBody($response),
            'suggestions' => $this->extractSuggestions($response),
            'raw'        => $response,
            'provider'   => 'openai',
            'model'      => $this->model,
        ];
    }

    private function chat(array $messages): string
    {
        if (empty($this->apiKey)) {
            throw new RuntimeException('AI API key is not configured. Go to Settings → AI to add your OpenAI key.');
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post("{$this->baseUrl}/chat/completions", [
                'model'       => $this->model,
                'messages'    => $messages,
                'temperature' => $this->temperature,
                'max_tokens'  => $this->maxTokens,
            ]);

        if ($response->failed()) {
            Log::error('OpenAI API error', [
                'status' => $response->status(),
                'error'  => $response->json('error.message'),
            ]);
            throw new RuntimeException('AI generation failed: ' . ($response->json('error.message') ?? 'Unknown error'));
        }

        return $response->json('choices.0.message.content', '');
    }

    private function buildAnalysisPrompt(array $job, array $profile): string
    {
        $candidateName = $profile['full_name'] ?? 'Candidate';
        $candidateTitle = $profile['primary_title'] ?? 'Software Engineer';
        $candidateSkills = $this->formatList($profile['skills'] ?? []);
        $candidateExp = $this->formatExperience($profile['experiences'] ?? []);
        $candidateYears = $profile['years_of_experience'] ?? 'Unknown';

        $jobTitle = $job['title'] ?? 'Untitled';
        $jobCompany = $job['company_name'] ?? 'Unknown Company';
        $jobLoc = $job['location'] ?? 'Unknown';
        $jobRemote = $this->bool($job['is_remote'] ?? false);
        $jobDesc = $this->truncate($job['description'] ?? '', 800);

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
        $contactName = $job['contact_name'] ?? null;
        $greeting    = $contactName ? "Hi {$contactName}," : 'Hi,';

        $candidateName = $profile['full_name'] ?? 'Candidate';
        $candidateTitle = $profile['primary_title'] ?? 'Software Engineer';
        $candidatePortfolio = $profile['portfolio_url'] ?? '';
        $candidateSkills = $this->formatList(array_slice($profile['skills'] ?? [], 0, 8));
        $candidateExp = $this->formatExperience($profile['experiences'] ?? []);

        $jobTitle = $job['title'] ?? 'Untitled';
        $companyName = $company['name'] ?? 'Unknown Company';
        $jobSkills = $this->formatList($job['required_skills'] ?? []);
        $jobDesc = $this->truncate($job['description'] ?? '', 400);

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
- Reference my real experience (Vigilearn, Avario Digitals) only if relevant
- Do NOT fabricate projects, salary, or recruiter names
- End with: "Portfolio: {$candidatePortfolio}"
- sign off: "best,\n{$candidateName}"
- format: subject: [subject line]\n\n[email body]\n\n---CV TAILORING---\n[3-4 bullet points of resume tips]
PROMPT;
    }

    private function emailSystemPrompt(): string
    {
        return 'You are writing genuine, concise professional outreach emails and resume tailoring tips for a software engineer. ' .
               'Never fabricate experience, projects, or company information. ' .
               'Be specific and honest. Never use hollow phrases. ' .
               'The email must be under 200 words. ' .
               'The CV tailoring section should provide 3-4 concrete, actionable tips to update the candidate\'s resume for THIS specific job (e.g. "Highlight your experience with React Hooks", "Add AWS Lambda to your skills section").';
    }

    private function extractSubject(string $response, array $profile): string
    {
        if (preg_match('/^SUBJECT:\s*(.+)$/mi', $response, $m)) {
            return trim($m[1]);
        }
        return ($profile['primary_title'] ?? 'Software Engineer') . ' — ' . ($profile['full_name'] ?? '');
    }

    private function extractBody(string $response): string
    {
        // Remove Subject and Tailoring sections
        $body = preg_replace('/^SUBJECT:.*$/mi', '', $response);
        $body = explode('---CV TAILORING---', $body)[0];
        return trim($body);
    }

    private function extractSuggestions(string $response): ?string
    {
        $parts = explode('---CV TAILORING---', $response);
        return count($parts) > 1 ? trim($parts[1]) : null;
    }

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

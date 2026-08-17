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
        $this->apiKey      = $apiKey ?? config('services.ai.api_key', '');
        $this->model       = $model  ?? config('services.ai.model', 'gpt-4o-mini');
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
            'subject'  => $this->extractSubject($response, $profileData),
            'body'     => $this->extractBody($response),
            'raw'      => $response,
            'provider' => 'openai',
            'model'    => $this->model,
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
        return <<<PROMPT
Analyze this job opportunity for the candidate:

CANDIDATE:
- Name: {$profile['full_name']}
- Title: {$profile['primary_title']}
- Skills: {$this->formatList($profile['skills'] ?? [])}
- Experience: {$this->formatExperience($profile['experiences'] ?? [])}
- Years of experience: {$profile['years_of_experience']}

JOB:
- Title: {$job['title']}
- Company: {$job['company_name']}
- Location: {$job['location']} (Remote: {$this->bool($job['is_remote'])})
- Description: {$this->truncate($job['description'] ?? '', 800)}

Provide a brief (3-5 sentence) professional analysis of why this is or isn't a good fit. Focus on concrete skill alignment, growth potential, and any red flags. Do NOT invent information.
PROMPT;
    }

    private function buildEmailPrompt(array $job, array $company, array $profile): string
    {
        $contactName = $job['contact_name'] ?? null;
        $greeting    = $contactName ? "Hi {$contactName}," : 'Hi,';

        return <<<PROMPT
Write a professional outreach email for this job application.

CANDIDATE:
- Name: {$profile['full_name']}
- Title: {$profile['primary_title']}
- Portfolio: {$profile['portfolio_url']}
- Key skills: {$this->formatList(array_slice($profile['skills'] ?? [], 0, 8))}
- Experience: {$this->formatExperience($profile['experiences'] ?? [])}

JOB:
- Title: {$job['title']}
- Company: {$company['name']}
- Tech mentioned: {$this->formatList($job['required_skills'] ?? [])}
- Description excerpt: {$this->truncate($job['description'] ?? '', 400)}

INSTRUCTIONS:
- Opening greeting: {$greeting}
- Keep it under 200 words
- Be professional and genuine
- Mention 1-2 specific technologies from the job that I actually know
- Reference my real experience (Vigilearn, Avario Digitals) only if relevant
- Do NOT fabricate projects, salary, or recruiter names
- End with: "Portfolio: {$profile['portfolio_url']}"
- Sign off: "Best,\n{$profile['full_name']}"
- Format: SUBJECT: [subject line]\n\n[email body]
PROMPT;
    }

    private function emailSystemPrompt(): string
    {
        return 'You are writing genuine, concise professional outreach emails for a software engineer. ' .
               'Never fabricate experience, projects, or company information. ' .
               'Be specific and honest. Never use hollow phrases like "innovative company" or "passionate about technology". ' .
               'The email must be under 200 words and feel human-written, not template-like.';
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
        $body = preg_replace('/^SUBJECT:.*$/mi', '', $response);
        return trim($body);
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

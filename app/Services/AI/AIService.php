<?php

namespace App\Services\AI;

use App\Models\AutomationSettings;
use InvalidArgumentException;

class AIService
{
    private AIProviderInterface $provider;

    public function __construct(?int $userId = null)
    {
        $this->provider = $this->resolveProvider($userId);
    }

    public function analyzeJob(array $jobData, array $profileData): array
    {
        return $this->provider->analyzeJob($jobData, $profileData);
    }

    public function generateEmail(array $jobData, array $companyData, array $profileData): array
    {
        return $this->provider->generateEmail($jobData, $companyData, $profileData);
    }

    private function resolveProvider(?int $userId): AIProviderInterface
    {
        $settings = $userId
            ? AutomationSettings::where('user_id', $userId)->first()
            : null;

        $providerName = (string) ($settings?->ai_provider ?? config('services.ai.provider', 'openai'));
        $apiKey       = $settings?->getAiApiKey();
        $model        = (string) ($settings?->ai_model       ?? config('services.ai.model', 'gpt-4o-mini'));
        $temperature  = (float)  ($settings?->ai_temperature ?? config('services.ai.temperature', 0.7));
        $maxTokens    = (int)    ($settings?->ai_max_tokens  ?? config('services.ai.max_tokens', 1000));

        return match ($providerName) {
            'openai' => new OpenAIProvider($apiKey, $model, $temperature, $maxTokens),
            default  => throw new InvalidArgumentException("Unknown AI provider: {$providerName}"),
        };
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AutomationSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $settings = $request->user()
            ->automationSettings()
            ->firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json($this->safeSettings($settings));
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Outreach
            'daily_send_limit'        => 'sometimes|integer|min:1|max:100',
            'hourly_send_limit'       => 'sometimes|integer|min:1|max:50',
            'auto_send'               => 'sometimes|boolean',
            'require_approval'        => 'sometimes|boolean',
            'working_hours_start'     => 'sometimes|string|regex:/^\d{2}:\d{2}$/',
            'working_hours_end'       => 'sometimes|string|regex:/^\d{2}:\d{2}$/',
            'timezone'                => 'sometimes|string|timezone',
            'min_delay_seconds'       => 'sometimes|integer|min:5',
            'max_delay_seconds'       => 'sometimes|integer|min:5',
            'follow_up_interval_days' => 'sometimes|integer|min:1|max:30',
            'max_follow_ups'          => 'sometimes|integer|min:0|max:10',
            'minimum_match_score'     => 'sometimes|integer|min:0|max:100',
            'discovery_enabled'       => 'sometimes|boolean',
            'search_keywords'         => 'sometimes|nullable|array',
            'search_keywords.*'       => 'string',
            'search_locations'        => 'sometimes|nullable|array',
            'search_locations.*'      => 'string',
            'remote_only'             => 'sometimes|boolean',
            'minimum_salary'          => 'sometimes|nullable|numeric|min:0',

            // Google OAuth (plain text — will be encrypted before saving)
            'google_client_id'        => 'sometimes|nullable|string',
            'google_client_secret'    => 'sometimes|nullable|string',
            'google_redirect_uri'     => 'sometimes|nullable|url',

            // AI
            'ai_provider'             => 'sometimes|string|in:openai',
            'ai_api_key'              => 'sometimes|nullable|string',
            'ai_model'                => 'sometimes|nullable|string|max:100',
            'ai_temperature'          => 'sometimes|numeric|min:0|max:2',
            'ai_max_tokens'           => 'sometimes|integer|min:100|max:4000',
        ]);

        $settings = $request->user()
            ->automationSettings()
            ->firstOrCreate(['user_id' => $request->user()->id]);

        // Handle sensitive fields separately (encrypt before saving)
        if (isset($data['google_client_id']) && $data['google_client_id']) {
            $settings->setGoogleClientId($data['google_client_id']);
        }
        if (isset($data['google_client_secret']) && $data['google_client_secret']) {
            $settings->setGoogleClientSecret($data['google_client_secret']);
        }
        if (isset($data['ai_api_key']) && $data['ai_api_key']) {
            $settings->setAiApiKey($data['ai_api_key']);
        }

        // Save encrypted fields first
        $settings->save();

        // Update all non-sensitive fields
        $nonSensitive = array_diff_key($data, array_flip([
            'google_client_id', 'google_client_secret', 'ai_api_key',
        ]));

        $settings->update($nonSensitive);

        return response()->json($this->safeSettings($settings->fresh()));
    }

    /**
     * Return settings without encrypted values.
     * Indicate whether secrets are configured without exposing values.
     */
    private function safeSettings(AutomationSettings $settings): array
    {
        $arr = $settings->toArray(); // hidden fields already excluded

        // Add has_* flags so the UI can show "configured" without revealing values
        $arr['has_google_oauth']  = $settings->hasGoogleOAuth();
        $arr['has_ai_key']        = $settings->hasAiKey();
        $arr['google_redirect_uri'] = $settings->getEffectiveRedirectUri();

        return $arr;
    }
}
